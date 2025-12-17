<?php

namespace GCWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The main plugin class.
 */
class Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->plugin_name = 'gerador-certificados-wp';
        $this->version     = GCWP_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Load core classes
        require_once GCWP_PLUGIN_DIR . 'includes/Core/Utils.php';
        require_once GCWP_PLUGIN_DIR . 'includes/Core/CertificateGenerator.php';
        require_once GCWP_PLUGIN_DIR . 'includes/Database/ParticipantsTable.php';

        // Load Admin classes
        if ( is_admin() ) {
            require_once GCWP_PLUGIN_DIR . 'includes/Admin/Admin.php';
            require_once GCWP_PLUGIN_DIR . 'includes/Admin/Ajax.php';
            require_once GCWP_PLUGIN_DIR . 'includes/Admin/ParticipantsListTable.php';
        }

        // Load Public classes
        if ( file_exists( GCWP_PLUGIN_DIR . 'public/class-gcwp-public.php' ) ) {
            require_once GCWP_PLUGIN_DIR . 'public/class-gcwp-public.php';
        }
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        add_action( 'plugins_loaded', function() {
            load_plugin_textdomain(
                'gerador-certificados-wp',
                false,
                dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
            );
        } );
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        if ( is_admin() ) {
            $admin = new \GCWP\Admin\Admin( $this->get_plugin_name(), $this->get_version() );
            $ajax  = new \GCWP\Admin\Ajax();

            // Init Admin
            add_action( 'admin_menu', [ $admin, 'add_admin_menu' ] );
            add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_scripts' ] );
            add_action( 'admin_post_gcwp_reset_plugin', [ $admin, 'handle_reset_plugin' ] );
            add_action( 'admin_post_gcwp_save_participant', [ $admin, 'handle_save_participant' ] );
            
            // Ajax Hooks
            add_action( 'wp_ajax_gcwp_reenviar_certificado', [ $ajax, 'reenviar_certificado' ] );
            add_action( 'wp_ajax_gcwp_manage_template', [ $ajax, 'manage_template' ] );
            add_action( 'wp_ajax_gcwp_get_participant', [ $ajax, 'get_participant' ] );
            add_action( 'wp_ajax_gcwp_delete_participant', [ $ajax, 'delete_participant' ] );
            add_action( 'wp_ajax_gcwp_delete_certificate', [ $ajax, 'delete_certificate' ] );
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        if ( class_exists( 'GCWP_Public' ) ) {
            \GCWP_Public::init();
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Activation hook
     */
    public static function activate() {
        // Create custom directories
        $upload_dir = wp_upload_dir();
        $certificados_dir = $upload_dir['basedir'] . '/certificados';

        if ( ! is_dir( $certificados_dir ) ) {
            wp_mkdir_p( $certificados_dir );
        }
        wp_mkdir_p( $certificados_dir . '/modelos/frente' );
        wp_mkdir_p( $certificados_dir . '/modelos/verso' );
        wp_mkdir_p( $certificados_dir . '/emitidos' );

        // Create custom database table for participants
        require_once GCWP_PLUGIN_DIR . 'includes/Database/ParticipantsTable.php';
        \GCWP\Database\ParticipantsTable::create_table();
    }
}
