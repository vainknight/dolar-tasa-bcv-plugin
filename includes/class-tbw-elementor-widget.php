<?php
if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class TBW_Elementor_Widget extends Widget_Base {

    public function get_name() {
        return 'tasa-bcv-widget';
    }

    public function get_title() {
        return __('Tasa BCV', 'tasa-bcv-widget');
    }

    public function get_icon() {
        return 'eicon-price-table';
    }

    public function get_categories() {
        return array('tasa-bcv', 'general');
    }

    public function get_keywords() {
        return array('bcv', 'dolar', 'tasa', 'cambio', 'venezuela');
    }

    protected function register_controls() {

        // ---------- Sección: Contenido ----------
        $this->start_controls_section(
            'seccion_contenido',
            array(
                'label' => __('Contenido', 'tasa-bcv-widget'),
            )
        );

        $this->add_control(
            'moneda',
            array(
                'label'   => __('Moneda a mostrar', 'tasa-bcv-widget'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'usd',
                'options' => array(
                    'usd'   => __('Dólar (USD)', 'tasa-bcv-widget'),
                    'eur'   => __('Euro (EUR)', 'tasa-bcv-widget'),
                    'ambas' => __('Ambas', 'tasa-bcv-widget'),
                ),
            )
        );

        $this->add_control(
            'etiqueta_usd',
            array(
                'label'     => __('Etiqueta USD', 'tasa-bcv-widget'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('USD: ', 'tasa-bcv-widget'),
                'condition' => array(
                    'moneda' => 'ambas',
                ),
            )
        );

        $this->add_control(
            'etiqueta_eur',
            array(
                'label'     => __('Etiqueta EUR', 'tasa-bcv-widget'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('EUR: ', 'tasa-bcv-widget'),
                'condition' => array(
                    'moneda' => 'ambas',
                ),
            )
        );

        $this->add_control(
            'prefijo',
            array(
                'label'       => __('Prefijo', 'tasa-bcv-widget'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => __('Ej: Bs. ', 'tasa-bcv-widget'),
            )
        );

        $this->add_control(
            'sufijo',
            array(
                'label'       => __('Sufijo', 'tasa-bcv-widget'),
                'type'        => Controls_Manager::TEXT,
                'default'     => ' Bs.',
                'placeholder' => __('Ej: Bs.', 'tasa-bcv-widget'),
            )
        );

        $this->add_control(
            'mostrar_fecha',
            array(
                'label'        => __('Mostrar fecha de actualización', 'tasa-bcv-widget'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __('Sí', 'tasa-bcv-widget'),
                'label_off'    => __('No', 'tasa-bcv-widget'),
                'return_value' => 'si',
                'default'      => 'si',
            )
        );

        $this->add_control(
            'etiqueta_fecha',
            array(
                'label'     => __('Etiqueta de la fecha', 'tasa-bcv-widget'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('Actualizado: ', 'tasa-bcv-widget'),
                'condition' => array(
                    'mostrar_fecha' => 'si',
                ),
            )
        );

        $this->add_control(
            'alineacion',
            array(
                'label'   => __('Alineación', 'tasa-bcv-widget'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Izquierda', 'tasa-bcv-widget'),
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => __('Centro', 'tasa-bcv-widget'),
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'right' => array(
                        'title' => __('Derecha', 'tasa-bcv-widget'),
                        'icon'  => 'eicon-text-align-right',
                    ),
                ),
                'default'   => 'left',
                'selectors' => array(
                    '{{WRAPPER}} .tbw-elementor-widget' => 'text-align: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // ---------- Sección: Estilo — Valor ----------
        $this->start_controls_section(
            'seccion_estilo_valor',
            array(
                'label' => __('Estilo del valor', 'tasa-bcv-widget'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'color_valor',
            array(
                'label'     => __('Color', 'tasa-bcv-widget'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => array(
                    '{{WRAPPER}} .bcv-tasa-valor' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'tipografia_valor',
                'selector' => '{{WRAPPER}} .bcv-tasa-valor',
            )
        );

        $this->end_controls_section();

        // ---------- Sección: Estilo — Fecha ----------
        $this->start_controls_section(
            'seccion_estilo_fecha',
            array(
                'label'     => __('Estilo de la fecha', 'tasa-bcv-widget'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'mostrar_fecha' => 'si',
                ),
            )
        );

        $this->add_control(
            'color_fecha',
            array(
                'label'     => __('Color', 'tasa-bcv-widget'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '',
                'selectors' => array(
                    '{{WRAPPER}} .tbw-fecha-envoltura' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'tipografia_fecha',
                'selector' => '{{WRAPPER}} .tbw-fecha-envoltura',
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $ajustes = $this->get_settings_for_display();
        $core    = TBW_Core::instancia();

        $valor_html = $core->render_html_valor(array(
            'moneda'       => $ajustes['moneda'],
            'prefijo'      => $ajustes['prefijo'],
            'sufijo'       => $ajustes['sufijo'],
            'etiqueta_usd' => $ajustes['etiqueta_usd'],
            'etiqueta_eur' => $ajustes['etiqueta_eur'],
        ));

        $fecha_html = '';
        if (!empty($ajustes['mostrar_fecha']) && $ajustes['mostrar_fecha'] === 'si') {
            $fecha_html = sprintf(
                '<div class="tbw-fecha-envoltura">%s%s</div>',
                esc_html($ajustes['etiqueta_fecha']),
                $core->render_html_fecha()
            );
        }

        printf(
            '<div class="tbw-elementor-widget">%s%s</div>',
            $valor_html,
            $fecha_html
        );
    }

    /**
     * Vista previa en el editor de Elementor (sin JS dinámico, solo
     * para que se vea algo mientras se edita). El front-end real
     * usa render() vía PHP + el script de refresco del plugin.
     */
    protected function content_template() {
        ?>
        <#
        var prefijo = settings.prefijo || '';
        var sufijo = settings.sufijo || '';
        #>
        <div class="tbw-elementor-widget">
            <span class="bcv-tasa-valor">{{{ prefijo }}}...{{{ sufijo }}}</span>
            <# if ( settings.mostrar_fecha === 'si' ) { #>
                <div class="tbw-fecha-envoltura">{{{ settings.etiqueta_fecha }}}<span class="bcv-tasa-fecha"></span></div>
            <# } #>
        </div>
        <?php
    }
}
