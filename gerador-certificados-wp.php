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
    wp_enqueue_script( 'gcwp-admin-script', GCWP_PLUGIN_URL . 'admin.js', [ 'jquery' ], GCWP_VERSION, true );

    // Pass data to JS, like nonces or ajaxurl
    wp_localize_script('gcwp-admin-script', 'gcwp_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gcwp_template_actions')
    ]);
}
add_action( 'admin_enqueue_scripts', 'gcwp_admin_enqueue_scripts' );

// Função AJAX para selecionar modelo
function gcwp_select_template_callback() {
    // Verificar nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gcwp_template_actions' ) ) {
        wp_send_json_error( array( 'message' => __( 'Erro de segurança. Por favor, recarregue a página.', 'gerador-certificados-wp' ) ) );
    }
    
    // Verificar permissões
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp' ) ) );
    }
    
    // Obter dados
    $tipo = isset( $_POST['tipo'] ) ? sanitize_text_field( $_POST['tipo'] ) : '';
    $file = isset( $_POST['file'] ) ? sanitize_text_field( $_POST['file'] ) : '';
    $url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
    
    if ( empty( $tipo ) || empty( $file ) || empty( $url ) ) {
        wp_send_json_error( array( 'message' => __( 'Dados incompletos.', 'gerador-certificados-wp' ) ) );
    }
    
    // Atualizar opção com o modelo selecionado
    if ( $tipo === 'frente' ) {
        update_option( 'gcwp_modelo_frente', $url );
    } elseif ( $tipo === 'verso' ) {
        update_option( 'gcwp_modelo_verso', $url );
    } else {
        wp_send_json_error( array( 'message' => __( 'Tipo de modelo inválido.', 'gerador-certificados-wp' ) ) );
    }
    
    wp_send_json_success( array( 'message' => __( 'Modelo selecionado com sucesso!', 'gerador-certificados-wp' ) ) );
}
add_action( 'wp_ajax_gcwp_select_template', 'gcwp_select_template_callback' );

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

// Função AJAX para apagar modelo
function gcwp_delete_template_callback() {
    // Verificar nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gcwp_template_actions' ) ) {
        wp_send_json_error( array( 'message' => __( 'Erro de segurança. Por favor, recarregue a página.', 'gerador-certificados-wp' ) ) );
    }
    
    // Verificar permissões
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp' ) ) );
    }
    
    // Obter dados
    $tipo = isset( $_POST['tipo'] ) ? sanitize_text_field( $_POST['tipo'] ) : '';
    $file = isset( $_POST['file'] ) ? sanitize_text_field( $_POST['file'] ) : '';
    
    if ( empty( $tipo ) || empty( $file ) ) {
        wp_send_json_error( array( 'message' => __( 'Dados incompletos.', 'gerador-certificados-wp' ) ) );
    }
    
    // Obter diretório de upload
    $upload_dir = wp_upload_dir();
    $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
    
    // Caminho completo do arquivo
    $file_path = $modelos_dir . '/' . $tipo . '/' . $file;
    
    // Verificar se o arquivo existe
    if ( ! file_exists( $file_path ) ) {
        wp_send_json_error( array( 'message' => __( 'Arquivo não encontrado.', 'gerador-certificados-wp' ) ) );
    }
    
    // Verificar se o modelo está sendo usado atualmente
    $current_url = '';
    if ( $tipo === 'frente' ) {
        $current_url = get_option( 'gcwp_modelo_frente', '' );
    } elseif ( $tipo === 'verso' ) {
        $current_url = get_option( 'gcwp_modelo_verso', '' );
    }
    
    // Se o modelo estiver sendo usado, remover a seleção
    if ( ! empty( $current_url ) && strpos( $current_url, $file ) !== false ) {
        if ( $tipo === 'frente' ) {
            delete_option( 'gcwp_modelo_frente' );
        } elseif ( $tipo === 'verso' ) {
            delete_option( 'gcwp_modelo_verso' );
        }
    }
    
    // Apagar o arquivo
    if ( ! unlink( $file_path ) ) {
        wp_send_json_error( array( 'message' => __( 'Não foi possível apagar o arquivo.', 'gerador-certificados-wp' ) ) );
    }
    
    wp_send_json_success( array( 'message' => __( 'Modelo apagado com sucesso!', 'gerador-certificados-wp' ) ) );
}
add_action( 'wp_ajax_gcwp_delete_template', 'gcwp_delete_template_callback' );

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