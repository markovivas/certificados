<?php
/**
 * Plugin Name:       Gerador de Certificados WP
 * Plugin URI:        https://example.com/
 * Description:       Gere certificados em PDF (frente e verso) diretamente no painel do WordPress.
 * Version:           1.0.0
 * Author:            Seu Nome
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gerador-certificados-wp
 * Domain Path:       /languages
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'GCWP_VERSION', '1.0.0' );
define( 'GCWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GCWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Require Composer autoloader
if ( file_exists( GCWP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once GCWP_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>' .
             esc_html__( 'Biblioteca mPDF não encontrada. Rode composer install/require na pasta do plugin (certificados).', 'gerador-certificados-wp' ) .
             '</p></div>';
    } );
}

// Require Plugin Class
if ( file_exists( GCWP_PLUGIN_DIR . 'includes/Core/Plugin.php' ) ) {
    require_once GCWP_PLUGIN_DIR . 'includes/Core/Plugin.php';
}

/**
 * Activation hook
 */
register_activation_hook( __FILE__, [ 'GCWP\\Core\\Plugin', 'activate' ] );

/**
 * Initialize Plugin
 */
function run_gcwp() {
    if ( class_exists( 'GCWP\\Core\\Plugin' ) ) {
        $plugin = new GCWP\Core\Plugin();
    }
}
run_gcwp();
