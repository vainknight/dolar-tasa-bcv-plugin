<?php
/**
 * Plugin Name: Tasa BCV Widget
 * Description: Muestra la tasa del dólar BCV (Venezuela) en vivo, resistente al caché de página. Disponible como shortcode, bloque de Gutenberg y widget de Elementor.
 * Version: 1.0.0
 * Author: Fran Velazco
 * Author URI:  https://www.linkedin.com/in/fran-velazco/
 * Text Domain: tasa-bcv-widget
 */

if (!defined('ABSPATH')) {
    exit; // Acceso directo no permitido.
}

define('TBW_PLUGIN_FILE', __FILE__);
define('TBW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TBW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TBW_VERSION', '1.0.0');

// -----------------------------------------------------------------
// Núcleo: cron, extracción del HTML del BCV, endpoint REST, shortcode
// y script de actualización en vivo. Es el mismo motor que ya
// tenías funcionando; aquí solo vive dentro del plugin.
// -----------------------------------------------------------------
require_once TBW_PLUGIN_DIR . 'includes/class-tbw-core.php';

// Bloque de Gutenberg.
require_once TBW_PLUGIN_DIR . 'includes/class-tbw-block.php';

// Widget de Elementor (se registra solo si Elementor está activo).
require_once TBW_PLUGIN_DIR . 'includes/class-tbw-elementor.php';

// Activación / desactivación: programar y limpiar el cron.
register_activation_hook(__FILE__, array('TBW_Core', 'activar'));
register_deactivation_hook(__FILE__, array('TBW_Core', 'desactivar'));

// Inicializar todo.
add_action('plugins_loaded', function () {
    TBW_Core::instancia();
    TBW_Block::instancia();
    TBW_Elementor::instancia();
});
