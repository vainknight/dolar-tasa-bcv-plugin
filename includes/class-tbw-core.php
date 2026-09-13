<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TBW_Core
 * --------
 * Motor de datos: obtiene las tasas del BCV (USD y EUR) en segundo
 * plano cada 6 horas, las expone vía REST (sin caché) y provee el
 * shortcode + JS de respaldo. Reutilizado por el shortcode, el bloque
 * de Gutenberg y el widget de Elementor.
 */
class TBW_Core {

    private static $instancia = null;

    // Selectores HTML del BCV por cada moneda soportada.
    const SELECTORES = array(
        'usd' => 'dolar',
        'eur' => 'euro',
    );

    public static function instancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        add_filter('cron_schedules', array($this, 'agregar_intervalo_cron'));
        add_action('tbw_actualizar_tasa_evento', array($this, 'actualizar_tasas_en_segundo_plano'));
        add_action('rest_api_init', array($this, 'registrar_endpoint_rest'));
        add_shortcode('tasa_bcv_simple', array($this, 'shortcode_valor'));
        add_shortcode('tasa_bcv_fecha', array($this, 'shortcode_fecha'));
        add_action('wp_footer', array($this, 'imprimir_script_refresco'));
        add_action('init', array($this, 'endpoint_diagnostico'));
        add_action('init', array($this, 'asegurar_cron_programado'));
    }

    // ---------------------------------------------------------
    // Activación / desactivación
    // ---------------------------------------------------------
    public static function activar() {
        if (!wp_next_scheduled('tbw_actualizar_tasa_evento')) {
            wp_schedule_event(time(), 'tbw_seis_horas', 'tbw_actualizar_tasa_evento');
        }
    }

    public static function desactivar() {
        $timestamp = wp_next_scheduled('tbw_actualizar_tasa_evento');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'tbw_actualizar_tasa_evento');
        }
    }

    public function asegurar_cron_programado() {
        if (!wp_next_scheduled('tbw_actualizar_tasa_evento')) {
            wp_schedule_event(time(), 'tbw_seis_horas', 'tbw_actualizar_tasa_evento');
        }
    }

    public function agregar_intervalo_cron($schedules) {
        $schedules['tbw_seis_horas'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __('Cada 6 horas (Tasa BCV)', 'tasa-bcv-widget'),
        );
        return $schedules;
    }

    // ---------------------------------------------------------
    // Actualización de las tasas (USD y EUR en la misma petición)
    // ---------------------------------------------------------
    public function actualizar_tasas_en_segundo_plano() {
        $url_bcv = 'https://www.bcv.org.ve/';
        $args = array(
            'timeout'    => 25,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            // El certificado del BCV tiene una cadena de confianza defectuosa;
            // con sslverify => true la petición falla siempre. Es necesario
            // para este sitio específico.
            'sslverify'  => false,
        );

        $respuesta = wp_remote_get($url_bcv, $args);

        if (is_wp_error($respuesta)) {
            update_option('tbw_tasa_ultimo_error', $respuesta->get_error_message(), false);
            update_option('tbw_tasa_ultimo_intento_fallo', current_time('mysql'), false);
            return;
        }

        $codigo = wp_remote_retrieve_response_code($respuesta);
        if ($codigo !== 200) {
            update_option('tbw_tasa_ultimo_error', 'HTTP ' . $codigo, false);
            update_option('tbw_tasa_ultimo_intento_fallo', current_time('mysql'), false);
            return;
        }

        $html = wp_remote_retrieve_body($respuesta);

        $algun_valor_encontrado = false;
        $algun_valor_cambio     = false;

        foreach (self::SELECTORES as $moneda => $selector_id) {
            $valor_punto = $this->extraer_valor($html, $selector_id);

            if ($valor_punto === null) {
                // No sobreescribe el valor anterior de esta moneda si no
                // se encontró; solo se registra para diagnóstico.
                update_option('tbw_tasa_ultimo_error_' . $moneda, 'No se encontró el valor en el HTML (revisar selector #' . $selector_id . ')', false);
                continue;
            }

            $algun_valor_encontrado = true;
            delete_option('tbw_tasa_ultimo_error_' . $moneda);

            $tasa_final    = number_format((float) $valor_punto, 2, ',', '.');
            $tasa_anterior = get_option('tbw_tasa_valor_' . $moneda, null);

            update_option('tbw_tasa_valor_' . $moneda, $tasa_final, false);

            if ($tasa_anterior !== $tasa_final) {
                $algun_valor_cambio = true;
            }
        }

        if (!$algun_valor_encontrado) {
            update_option('tbw_tasa_ultimo_error', 'No se encontró ningún valor en el HTML (revisar selectores)', false);
            update_option('tbw_tasa_ultimo_intento_fallo', current_time('mysql'), false);
            return;
        }

        delete_option('tbw_tasa_ultimo_error');
        update_option('tbw_tasa_fecha', current_time('mysql'), false);

        if ($algun_valor_cambio) {
            if (function_exists('do_action')) {
                do_action('litespeed_purge_all'); // No hace nada si LiteSpeed no está activo.
            }
        }
    }

    private function extraer_valor($html, $selector_id) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $div = $dom->getElementById($selector_id);
        if ($div) {
            $texto = $div->textContent;
            if (preg_match('/([\d.]+,\d+)/', $texto, $m)) {
                $valor_limpio = str_replace('.', '', $m[1]);
                $valor_punto  = str_replace(',', '.', $valor_limpio);
                if (is_numeric($valor_punto)) {
                    return $valor_punto;
                }
            }
        }

        if (preg_match('/<div[^>]*id="' . preg_quote($selector_id, '/') . '".*?([\d.]+,\d+)/s', $html, $m)) {
            $valor_limpio = str_replace('.', '', $m[1]);
            $valor_punto  = str_replace(',', '.', $valor_limpio);
            if (is_numeric($valor_punto)) {
                return $valor_punto;
            }
        }

        return null;
    }

    // ---------------------------------------------------------
    // Diagnóstico: ?tbw_debug=1 estando logueado como admin
    // ---------------------------------------------------------
    public function endpoint_diagnostico() {
        if (isset($_GET['tbw_debug']) && current_user_can('manage_options')) {
            $this->actualizar_tasas_en_segundo_plano();

            $filas = '';
            foreach (self::SELECTORES as $moneda => $selector_id) {
                $filas .= sprintf(
                    '<p><strong>%s:</strong> %s (error: %s)</p>',
                    strtoupper($moneda),
                    esc_html(get_option('tbw_tasa_valor_' . $moneda, '(vacío)')),
                    esc_html(get_option('tbw_tasa_ultimo_error_' . $moneda, 'ninguno'))
                );
            }

            wp_die(
                '<h2>Diagnóstico Tasa BCV</h2>' .
                $filas .
                '<p><strong>Fecha última actualización:</strong> ' . esc_html(get_option('tbw_tasa_fecha', '(nunca)')) . '</p>' .
                '<p><strong>Error general:</strong> ' . esc_html(get_option('tbw_tasa_ultimo_error', '(ninguno)')) . '</p>' .
                '<p><strong>Fecha del último intento fallido:</strong> ' . esc_html(get_option('tbw_tasa_ultimo_intento_fallo', '(ninguno)')) . '</p>' .
                '<p><a href="' . esc_url(remove_query_arg('tbw_debug')) . '">&larr; Volver a la página</a></p>',
                'Diagnóstico Tasa BCV'
            );
        }
    }

    // ---------------------------------------------------------
    // Endpoint REST — nunca se cachea
    // ---------------------------------------------------------
    public function registrar_endpoint_rest() {
        register_rest_route('bcv/v1', '/tasa', array(
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => array($this, 'callback_rest'),
        ));
    }

    public function callback_rest() {
        $response = new WP_REST_Response(array(
            'usd'   => get_option('tbw_tasa_valor_usd', 'Cargando...'),
            'eur'   => get_option('tbw_tasa_valor_eur', 'Cargando...'),
            'fecha' => $this->obtener_fecha_actual(),
        ));

        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('X-LiteSpeed-Cache-Control', 'no-cache');

        return $response;
    }

    // ---------------------------------------------------------
    // Valores actuales (usados por shortcode, bloque y widget Elementor)
    // ---------------------------------------------------------
    public function obtener_valor_actual($moneda = 'usd') {
        $moneda = isset(self::SELECTORES[$moneda]) ? $moneda : 'usd';
        if (get_option('tbw_tasa_valor_' . $moneda, false) === false) {
            $this->actualizar_tasas_en_segundo_plano();
        }
        return get_option('tbw_tasa_valor_' . $moneda, 'Cargando...');
    }

    public function obtener_fecha_actual() {
        $fecha = get_option('tbw_tasa_fecha', '');
        return $fecha ? date_i18n('d/m/Y H:i', strtotime($fecha)) : '';
    }

    // ---------------------------------------------------------
    // Shortcodes (se conservan por compatibilidad hacia atrás; USD)
    // ---------------------------------------------------------
    public function shortcode_valor($atributos) {
        $atributos = shortcode_atts(array('moneda' => 'usd'), $atributos);
        return $this->render_html_valor(array('moneda' => $atributos['moneda']));
    }

    public function shortcode_fecha() {
        return $this->render_html_fecha();
    }

    /**
     * HTML reutilizable del "valor" — usado por el shortcode, el bloque
     * de Gutenberg y el widget de Elementor. 'moneda' puede ser 'usd',
     * 'eur', o 'ambas' (muestra las dos, cada una con su etiqueta).
     */
    public function render_html_valor($atributos = array()) {
        $defaults = array(
            'moneda'        => 'usd', // usd | eur | ambas
            'prefijo'       => '',
            'sufijo'        => '',
            'clase'         => '',
            'etiqueta_usd'  => 'USD: ',
            'etiqueta_eur'  => 'EUR: ',
        );
        $atributos = wp_parse_args($atributos, $defaults);
        $clase_extra = $atributos['clase'] ? ' ' . esc_attr($atributos['clase']) : '';

        if ($atributos['moneda'] === 'ambas') {
            $usd = sprintf(
                '<span class="bcv-tasa-linea bcv-tasa-linea-usd"><span class="bcv-tasa-etiqueta">%s</span><span class="bcv-tasa-valor%s" data-bcv-moneda="usd" data-bcv-live="1">%s%s%s</span></span>',
                esc_html($atributos['etiqueta_usd']),
                $clase_extra,
                esc_html($atributos['prefijo']),
                esc_html($this->obtener_valor_actual('usd')),
                esc_html($atributos['sufijo'])
            );
            $eur = sprintf(
                '<span class="bcv-tasa-linea bcv-tasa-linea-eur"><span class="bcv-tasa-etiqueta">%s</span><span class="bcv-tasa-valor%s" data-bcv-moneda="eur" data-bcv-live="1">%s%s%s</span></span>',
                esc_html($atributos['etiqueta_eur']),
                $clase_extra,
                esc_html($atributos['prefijo']),
                esc_html($this->obtener_valor_actual('eur')),
                esc_html($atributos['sufijo'])
            );
            return '<div class="bcv-tasa-grupo">' . $usd . $eur . '</div>';
        }

        $moneda = ($atributos['moneda'] === 'eur') ? 'eur' : 'usd';

        return sprintf(
            '<span class="bcv-tasa-valor%s" data-bcv-moneda="%s" data-bcv-live="1">%s%s%s</span>',
            $clase_extra,
            esc_attr($moneda),
            esc_html($atributos['prefijo']),
            esc_html($this->obtener_valor_actual($moneda)),
            esc_html($atributos['sufijo'])
        );
    }

    public function render_html_fecha($atributos = array()) {
        $clase_extra = !empty($atributos['clase']) ? ' ' . esc_attr($atributos['clase']) : '';
        return sprintf('<span class="bcv-tasa-fecha%s" data-bcv-live="1"></span>', $clase_extra);
    }

    // ---------------------------------------------------------
    // JS de refresco en vivo (evita el problema de caché de página)
    // ---------------------------------------------------------
    public function imprimir_script_refresco() {
        $endpoint = esc_url_raw(rest_url('bcv/v1/tasa'));
        ?>
        <script>
        (function () {
            if (!document.querySelector('.bcv-tasa-valor')) return;

            fetch('<?php echo $endpoint; ?>', { cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.querySelectorAll('.bcv-tasa-valor').forEach(function (el) {
                        var moneda = el.dataset.bcvMoneda === 'eur' ? 'eur' : 'usd';
                        var valor = data[moneda];
                        if (el.dataset.prefijo || el.dataset.sufijo) {
                            el.textContent = (el.dataset.prefijo || '') + valor + (el.dataset.sufijo || '');
                        } else {
                            el.textContent = valor;
                        }
                    });
                    document.querySelectorAll('.bcv-tasa-fecha').forEach(function (el) {
                        el.textContent = data.fecha;
                    });
                })
                .catch(function () {
                    // Si falla, se deja el valor de respaldo ya impreso en el HTML.
                });
        })();
        </script>
        <?php
    }
}
