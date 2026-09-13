<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TBW_Elementor
 * -------------
 * Registra el widget "Tasa BCV" para Elementor, solo si Elementor
 * está activo. No genera ningún error si no lo está.
 */
class TBW_Elementor {

    private static $instancia = null;

    public static function instancia() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {
        add_action('elementor/widgets/register', array($this, 'registrar_widget'));
        add_action('elementor/elements/categories_registered', array($this, 'registrar_categoria'));
    }

    public function registrar_categoria($elements_manager) {
        $elements_manager->add_category('tasa-bcv', array(
            'title' => __('Tasa BCV', 'tasa-bcv-widget'),
            'icon'  => 'fa fa-plug',
        ));
    }

    public function registrar_widget($widgets_manager) {
        // Elementor debe estar activo y su clase base disponible.
        if (!did_action('elementor/loaded')) {
            return;
        }

        require_once TBW_PLUGIN_DIR . 'includes/class-tbw-elementor-widget.php';
        $widgets_manager->register(new \TBW_Elementor_Widget());
    }
}
