<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TBW_Block
 * ---------
 * Registra el bloque "Tasa BCV" para el editor Gutenberg.
 * Se define directamente en PHP (register_block_type con render_callback),
 * sin necesidad de un paso de compilación (webpack/npm) para instalarlo.
 */
class TBW_Block {

    private static $instancia = null;

    public static function instancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        add_action('init', array($this, 'registrar_bloque'));
        add_action('enqueue_block_editor_assets', array($this, 'cargar_assets_editor'));
    }

    public function registrar_bloque() {
        register_block_type('tasa-bcv/tasa-widget', array(
            'api_version'     => 2,
            'title'           => __('Tasa BCV', 'tasa-bcv-widget'),
            'description'     => __('Muestra la tasa del dólar y/o euro BCV en vivo, siempre actualizada.', 'tasa-bcv-widget'),
            'category'        => 'widgets',
            'icon'            => 'money-alt',
            'render_callback' => array($this, 'render_bloque'),
            'attributes'      => array(
                'moneda'         => array('type' => 'string', 'default' => 'usd'), // usd | eur | ambas
                'prefijo'        => array('type' => 'string', 'default' => ''),
                'sufijo'         => array('type' => 'string', 'default' => ' Bs.'),
                'etiquetaUsd'    => array('type' => 'string', 'default' => 'USD: '),
                'etiquetaEur'    => array('type' => 'string', 'default' => 'EUR: '),
                'mostrarFecha'   => array('type' => 'boolean', 'default' => true),
                'etiquetaFecha'  => array('type' => 'string', 'default' => 'Actualizado: '),
                'alineacion'     => array('type' => 'string', 'default' => 'left'),
                'tamanoFuente'   => array('type' => 'number', 'default' => 24),
            ),
            'supports' => array(
                'html' => false,
            ),
        ));
    }

    public function cargar_assets_editor() {
        wp_register_script(
            'tbw-block-editor',
            TBW_PLUGIN_URL . 'blocks/tasa-bcv/block.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'),
            TBW_VERSION,
            true
        );
        wp_enqueue_script('tbw-block-editor');
    }

    /**
     * Renderiza el bloque tanto en el editor (vía ServerSideRender)
     * como en el front-end. Usa el mismo HTML que el shortcode y el
     * widget de Elementor, para consistencia total.
     */
    public function render_bloque($atributos) {
        $core = TBW_Core::instancia();

        $valor = $core->render_html_valor(array(
            'moneda'       => isset($atributos['moneda']) ? $atributos['moneda'] : 'usd',
            'prefijo'      => isset($atributos['prefijo']) ? $atributos['prefijo'] : '',
            'sufijo'       => isset($atributos['sufijo']) ? $atributos['sufijo'] : '',
            'etiqueta_usd' => isset($atributos['etiquetaUsd']) ? $atributos['etiquetaUsd'] : 'USD: ',
            'etiqueta_eur' => isset($atributos['etiquetaEur']) ? $atributos['etiquetaEur'] : 'EUR: ',
        ));

        $estilo = sprintf(
            'text-align:%s;font-size:%dpx;',
            esc_attr($atributos['alineacion'] ?? 'left'),
            intval($atributos['tamanoFuente'] ?? 24)
        );

        $fecha_html = '';
        if (!empty($atributos['mostrarFecha'])) {
            $etiqueta = esc_html($atributos['etiquetaFecha'] ?? 'Actualizado: ');
            $fecha_html = sprintf(
                '<div class="bcv-tasa-fecha-envoltura" style="font-size:13px;opacity:0.7;margin-top:4px;">%s%s</div>',
                $etiqueta,
                $core->render_html_fecha()
            );
        }

        return sprintf(
            '<div class="wp-block-tasa-bcv-tasa-widget" style="%s">%s%s</div>',
            $estilo,
            $valor,
            $fecha_html
        );
    }
}
