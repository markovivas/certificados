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

/**
 * Plugin activation hook.
 * Creates custom directories for certificates.
 */
function gcwp_activate() {
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
    gcwp_create_participants_table();
}
register_activation_hook( __FILE__, 'gcwp_activate' );

/**
 * Load admin menu and other admin-related functionalities.
 */
if ( is_admin() ) {
    // Includes
    require_once GCWP_PLUGIN_DIR . 'certificate-generator.php';
    require_once GCWP_PLUGIN_DIR . 'class-participants-list-table.php';
    require_once GCWP_PLUGIN_DIR . 'admin-menu.php';
}

/**
 * Enqueue admin scripts and styles.
 */
function gcwp_admin_enqueue_scripts( $hook ) {
    // Only load on our plugin pages
    if ( strpos( $hook, 'certificados' ) === false ) {
        return;
    }

    wp_enqueue_style( 'gcwp-admin-style', GCWP_PLUGIN_URL . 'admin.css', [], GCWP_VERSION );

    // Adiciona o script principal e o draggable do jQuery UI
    $dependencies = [ 'jquery', 'jquery-ui-draggable' ];
    if ($hook === 'certificados_page_gcwp-configuracoes') {
        wp_enqueue_script( 'gcwp-admin-script', GCWP_PLUGIN_URL . 'admin.js', $dependencies, GCWP_VERSION, true );
    }

    // Pass data to JS, like nonces or ajaxurl
    wp_localize_script('gcwp-admin-script', 'gcwp_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gcwp_template_actions')
    ]);
}
add_action( 'admin_enqueue_scripts', 'gcwp_admin_enqueue_scripts' );

/**
 * Callback para reenviar certificado por e-mail
 */
function gcwp_reenviar_certificado_callback() {
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gcwp_reenviar_certificado')) {
        wp_send_json_error(array('message' => __('Erro de segurança. Por favor, recarregue a página.', 'gerador-certificados-wp')));
    }
    
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp')));
    }
    
    // Obter arquivo
    $filename = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
    
    if (empty($filename)) {
        wp_send_json_error(array('message' => __('Arquivo não especificado.', 'gerador-certificados-wp')));
    }
    
    // Verificar se o arquivo existe
    $upload_dir = wp_upload_dir();
    $filepath = $upload_dir['basedir'] . '/certificados/emitidos/' . $filename;
    
    if (!file_exists($filepath)) {
        wp_send_json_error(array('message' => __('Arquivo não encontrado.', 'gerador-certificados-wp')));
    }
    
    // Extrair nome do participante do nome do arquivo
    $nome_participante = preg_replace('/^certificado_(.+)_\d+\.pdf$/', '$1', $filename);
    $nome_participante = str_replace('-', ' ', $nome_participante);
    $nome_participante = ucwords($nome_participante);
    
    // Buscar e-mail do participante
    global $wpdb;
    $table_name = $wpdb->prefix . 'gcwp_participantes';
    $participante = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE LOWER(nome_completo) LIKE %s LIMIT 1",
        '%' . strtolower($nome_participante) . '%'
    ));
    
    if (!$participante) {
        wp_send_json_error(array('message' => __('Participante não encontrado.', 'gerador-certificados-wp')));
    }
    
    // Enviar e-mail
    $to = $participante->email;
    $subject = sprintf(__('Seu Certificado - %s', 'gerador-certificados-wp'), get_bloginfo('name'));
    
    $message = sprintf(
        __('Olá %s,<br><br>Seu certificado está anexado a este e-mail.<br><br>Atenciosamente,<br>%s', 'gerador-certificados-wp'),
        esc_html($participante->nome_completo),
        get_bloginfo('name')
    );
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $attachments = array($filepath);
    
    $sent = wp_mail($to, $subject, $message, $headers, $attachments);
    
    if ($sent) {
        wp_send_json_success(array('message' => __('E-mail enviado com sucesso!', 'gerador-certificados-wp')));
    } else {
        wp_send_json_error(array('message' => __('Erro ao enviar e-mail. Por favor, tente novamente.', 'gerador-certificados-wp')));
    }
}
add_action('wp_ajax_gcwp_reenviar_certificado', 'gcwp_reenviar_certificado_callback');

// Função AJAX para gerenciar modelos (Selecionar, Renomear, Apagar)
function gcwp_manage_template_callback() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gcwp_template_actions' ) ) {
        wp_send_json_error( array( 'message' => __( 'Erro de segurança. Por favor, recarregue a página.', 'gerador-certificados-wp' ) ) );
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp' ) ) );
    }

    $sub_action = isset($_POST['sub_action']) ? sanitize_text_field($_POST['sub_action']) : '';
    $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';

    if (empty($sub_action) || empty($slug)) {
        wp_send_json_error( array( 'message' => __( 'Dados incompletos.', 'gerador-certificados-wp' ) ) );
    }

    $upload_dir = wp_upload_dir();
    $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
    $modelo_path = $modelos_dir . '/' . $slug;

    if (!file_exists($modelo_path)) {
        wp_send_json_error( array( 'message' => __( 'O diretório do modelo não foi encontrado.', 'gerador-certificados-wp' ) ) );
    }

    switch ($sub_action) {
        case 'select':
            update_option('gcwp_modelo_selecionado', $slug);
            wp_send_json_success( array( 'message' => __( 'Modelo selecionado com sucesso!', 'gerador-certificados-wp' ) ) );
            break;

        case 'rename':
            $new_name = isset($_POST['new_name']) ? sanitize_text_field($_POST['new_name']) : '';
            if (empty($new_name)) {
                wp_send_json_error( array( 'message' => __( 'O novo nome não pode estar vazio.', 'gerador-certificados-wp' ) ) );
            }

            $new_slug = sanitize_title($new_name);
            $new_path = $modelos_dir . '/' . $new_slug;

            if (file_exists($new_path)) {
                wp_send_json_error( array( 'message' => __( 'Já existe um modelo com este nome.', 'gerador-certificados-wp' ) ) );
            }

            if (rename($modelo_path, $new_path)) {
                // Se o modelo renomeado era o selecionado, atualiza a opção
                if (get_option('gcwp_modelo_selecionado') === $slug) {
                    update_option('gcwp_modelo_selecionado', $new_slug);
                }
                wp_send_json_success( array( 
                    'message' => __( 'Modelo renomeado com sucesso!', 'gerador-certificados-wp' ),
                    'new_slug' => $new_slug
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Falha ao renomear o diretório do modelo.', 'gerador-certificados-wp' ) ) );
            }
            break;

        case 'delete':
            // Função para apagar diretório recursivamente
            function gcwp_delete_dir($dirPath) {
                if (!is_dir($dirPath)) {
                    return;
                }
                if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                    $dirPath .= '/';
                }
                $files = glob($dirPath . '*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_dir($file)) {
                        gcwp_delete_dir($file);
                    } else {
                        unlink($file);
                    }
                }
                rmdir($dirPath);
            }

            gcwp_delete_dir($modelo_path);

            // Se o modelo apagado era o selecionado, limpa a opção
            if (get_option('gcwp_modelo_selecionado') === $slug) {
                delete_option('gcwp_modelo_selecionado');
            }

            wp_send_json_success( array( 'message' => __( 'Modelo apagado com sucesso!', 'gerador-certificados-wp' ) ) );
            break;

        default:
            wp_send_json_error( array( 'message' => __( 'Ação desconhecida.', 'gerador-certificados-wp' ) ) );
            break;
    }
}
add_action( 'wp_ajax_gcwp_manage_template', 'gcwp_manage_template_callback' );

/**
 * Handle the plugin reset action.
 */
function gcwp_handle_reset_plugin() {
    // 1. Verify nonce and permissions
    if (!isset($_POST['gcwp_reset_nonce']) || !wp_verify_nonce($_POST['gcwp_reset_nonce'], 'gcwp_reset_plugin_nonce')) {
        wp_die(__('Ação inválida (nonce).', 'gerador-certificados-wp'));
    }
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp'));
    }
    if (!isset($_POST['gcwp_reset_confirm']) || $_POST['gcwp_reset_confirm'] !== 'on') {
        wp_die(__('Você precisa confirmar a ação de reset.', 'gerador-certificados-wp'));
    }

    global $wpdb;

    // 2. Delete participants table data
    $table_name = $wpdb->prefix . 'gcwp_participantes';
    $wpdb->query("TRUNCATE TABLE {$table_name}");

    // 3. Delete files and directories
    $upload_dir = wp_upload_dir();
    $certificados_dir = $upload_dir['basedir'] . '/certificados';
    $emitidos_dir = $certificados_dir . '/emitidos';
    $modelos_dir = $certificados_dir . '/modelos';

    // Helper function to delete directories
    function gcwp_delete_dir_recursive($dirPath) {
        if (!is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                gcwp_delete_dir_recursive($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    // Delete all issued certificates and models
    if (is_dir($emitidos_dir)) {
        gcwp_delete_dir_recursive($emitidos_dir);
    }
    if (is_dir($modelos_dir)) {
        gcwp_delete_dir_recursive($modelos_dir);
    }

    // 4. Delete plugin options
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gcwp_%'");

    // 5. Redirect back with a success message
    wp_redirect(admin_url('admin.php?page=gcwp-configuracoes&reset=success'));
    exit;
}
add_action('admin_post_gcwp_reset_plugin', 'gcwp_handle_reset_plugin');


/**
 * Creates the custom table for participants.
 */
function gcwp_create_participants_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'gcwp_participantes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nome_completo tinytext NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        curso tinytext NOT NULL,
        data_inicio date DEFAULT '0000-00-00' NOT NULL,
        data_termino date DEFAULT '0000-00-00' NOT NULL,
        duracao_horas smallint(5) NOT NULL,
        cidade tinytext NOT NULL,
        data_emissao date DEFAULT '0000-00-00' NOT NULL,
        numero_livro varchar(55) DEFAULT '' NOT NULL,
        numero_pagina varchar(55) DEFAULT '' NOT NULL,
        numero_certificado varchar(55) DEFAULT '' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}