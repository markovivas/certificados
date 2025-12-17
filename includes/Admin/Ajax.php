<?php

namespace GCWP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ajax {

    /**
     * Callback for resending certificate email
     */
    public function reenviar_certificado() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gcwp_reenviar_certificado')) {
            wp_send_json_error(array('message' => __('Erro de segurança. Por favor, recarregue a página.', 'gerador-certificados-wp')));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp')));
        }
        
        $filename = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
        
        if (empty($filename)) {
            wp_send_json_error(array('message' => __('Arquivo não especificado.', 'gerador-certificados-wp')));
        }
        
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['basedir'] . '/certificados/emitidos/' . $filename;
        
        if (!file_exists($filepath)) {
            wp_send_json_error(array('message' => __('Arquivo não encontrado.', 'gerador-certificados-wp')));
        }
        
        $nome_participante = preg_replace('/^certificado_(.+)_\d+\.pdf$/', '$1', $filename);
        $nome_participante = str_replace('-', ' ', $nome_participante);
        $nome_participante = ucwords($nome_participante);
        
        $participante = \GCWP\Database\ParticipantsTable::find_by_name($nome_participante);
        
        if (!$participante) {
            wp_send_json_error(array('message' => __('Participante não encontrado.', 'gerador-certificados-wp')));
        }
        
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

    /**
     * Callback for managing templates (Select, Rename, Delete)
     */
    public function manage_template() {
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
                \GCWP\Core\Utils::delete_dir_recursive($modelo_path);

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

    /**
     * Get participant data for public interface
     */
    public function get_participant() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gcwp_public_actions')) {
            wp_send_json_error(__('Erro de segurança.', 'gerador-certificados-wp'));
        }

        if (!is_user_logged_in() || !current_user_can('subscriber')) {
            wp_send_json_error(__('Acesso negado.', 'gerador-certificados-wp'));
        }

        $participant_id = intval($_POST['participant_id']);
        $user_id = get_current_user_id();

        $participant = \GCWP\Database\ParticipantsTable::get($participant_id);
        if (!$participant || $participant['user_id'] != $user_id) {
            wp_send_json_error(__('Participante não encontrado.', 'gerador-certificados-wp'));
        }

        wp_send_json_success($participant);
    }

    /**
     * Delete participant for public interface
     */
    public function delete_participant() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gcwp_public_actions')) {
            wp_send_json_error(__('Erro de segurança.', 'gerador-certificados-wp'));
        }

        if (!is_user_logged_in() || !current_user_can('subscriber')) {
            wp_send_json_error(__('Acesso negado.', 'gerador-certificados-wp'));
        }

        $participant_id = intval($_POST['participant_id']);
        $user_id = get_current_user_id();

        $participant = \GCWP\Database\ParticipantsTable::get($participant_id);
        if (!$participant || $participant['user_id'] != $user_id) {
            wp_send_json_error(__('Participante não encontrado.', 'gerador-certificados-wp'));
        }

        \GCWP\Database\ParticipantsTable::delete($participant_id);
        wp_send_json_success(__('Participante excluído com sucesso.', 'gerador-certificados-wp'));
    }

    /**
     * Delete certificate
     */
    public function delete_certificate() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gcwp_template_actions')) {
            wp_send_json_error(array('message' => __('Erro de segurança. Por favor, recarregue a página.', 'gerador-certificados-wp')));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp')));
        }

        $filename = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';

        if (empty($filename)) {
            wp_send_json_error(array('message' => __('Arquivo não especificado.', 'gerador-certificados-wp')));
        }

        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['basedir'] . '/certificados/emitidos/' . $filename;

        if (!file_exists($filepath)) {
            wp_send_json_error(array('message' => __('Arquivo não encontrado.', 'gerador-certificados-wp')));
        }

        if (unlink($filepath)) {
            wp_send_json_success(array('message' => __('Certificado excluído com sucesso!', 'gerador-certificados-wp')));
        } else {
            wp_send_json_error(array('message' => __('Erro ao excluir o arquivo.', 'gerador-certificados-wp')));
        }
    }
}
