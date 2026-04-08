<?php

namespace GCWP\Admin;

use GCWP\Core\TemplateRepository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ajax {

    public function reenviar_certificado() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'gcwp_template_actions' ) ) {
            wp_send_json_error( [ 'message' => __( 'Erro de seguranca. Recarregue a pagina.', 'gerador-certificados-wp' ) ] );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Voce nao tem permissao para realizar esta acao.', 'gerador-certificados-wp' ) ] );
        }

        $filename = isset( $_POST['file'] ) ? sanitize_file_name( wp_unslash( $_POST['file'] ) ) : '';
        if ( empty( $filename ) ) {
            wp_send_json_error( [ 'message' => __( 'Arquivo nao especificado.', 'gerador-certificados-wp' ) ] );
        }

        $upload_dir = wp_upload_dir();
        $filepath   = $upload_dir['basedir'] . '/certificados/emitidos/' . $filename;

        if ( ! file_exists( $filepath ) ) {
            wp_send_json_error( [ 'message' => __( 'Arquivo nao encontrado.', 'gerador-certificados-wp' ) ] );
        }

        $nome_participante = preg_replace( '/^certificado_(.+)_\d+\.pdf$/', '$1', $filename );
        $nome_participante = ucwords( str_replace( '-', ' ', $nome_participante ) );
        $participante      = \GCWP\Database\ParticipantsTable::find_by_name( $nome_participante );

        if ( ! $participante ) {
            wp_send_json_error( [ 'message' => __( 'Participante nao encontrado.', 'gerador-certificados-wp' ) ] );
        }

        $sent = wp_mail(
            $participante->email,
            sprintf( __( 'Seu certificado - %s', 'gerador-certificados-wp' ), get_bloginfo( 'name' ) ),
            sprintf(
                __( 'Ola %s,<br><br>Seu certificado esta anexado a este e-mail.<br><br>Atenciosamente,<br>%s', 'gerador-certificados-wp' ),
                esc_html( $participante->nome_completo ),
                get_bloginfo( 'name' )
            ),
            [ 'Content-Type: text/html; charset=UTF-8' ],
            [ $filepath ]
        );

        if ( $sent ) {
            wp_send_json_success( [ 'message' => __( 'E-mail enviado com sucesso.', 'gerador-certificados-wp' ) ] );
        }

        wp_send_json_error( [ 'message' => __( 'Erro ao enviar e-mail.', 'gerador-certificados-wp' ) ] );
    }

    public function manage_template() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'gcwp_template_actions' ) ) {
            wp_send_json_error( [ 'message' => __( 'Erro de seguranca. Recarregue a pagina.', 'gerador-certificados-wp' ) ] );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Voce nao tem permissao para realizar esta acao.', 'gerador-certificados-wp' ) ] );
        }

        $sub_action = isset( $_POST['sub_action'] ) ? sanitize_text_field( wp_unslash( $_POST['sub_action'] ) ) : '';
        $slug       = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

        if ( empty( $sub_action ) || empty( $slug ) ) {
            wp_send_json_error( [ 'message' => __( 'Dados incompletos.', 'gerador-certificados-wp' ) ] );
        }

        $template = TemplateRepository::get( $slug );
        if ( ! $template ) {
            wp_send_json_error( [ 'message' => __( 'Modelo nao encontrado.', 'gerador-certificados-wp' ) ] );
        }

        switch ( $sub_action ) {
            case 'select':
                update_option( 'gcwp_modelo_selecionado', $slug );
                wp_send_json_success( [ 'message' => __( 'Modelo selecionado com sucesso.', 'gerador-certificados-wp' ) ] );
                break;

            case 'rename':
                $new_name = isset( $_POST['new_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_name'] ) ) : '';
                if ( empty( $new_name ) ) {
                    wp_send_json_error( [ 'message' => __( 'O novo nome nao pode estar vazio.', 'gerador-certificados-wp' ) ] );
                }

                $result = TemplateRepository::rename( $slug, $new_name );
                if ( is_wp_error( $result ) ) {
                    wp_send_json_error( [ 'message' => $result->get_error_message() ] );
                }

                if ( get_option( 'gcwp_modelo_selecionado' ) === $slug && $result['slug'] !== $slug ) {
                    update_option( 'gcwp_modelo_selecionado', $result['slug'] );
                }

                wp_send_json_success(
                    [
                        'message'  => __( 'Modelo renomeado com sucesso.', 'gerador-certificados-wp' ),
                        'new_slug' => $result['slug'],
                    ]
                );
                break;

            case 'delete':
                $result = TemplateRepository::delete( $slug );
                if ( is_wp_error( $result ) ) {
                    wp_send_json_error( [ 'message' => $result->get_error_message() ] );
                }

                if ( get_option( 'gcwp_modelo_selecionado' ) === $slug ) {
                    delete_option( 'gcwp_modelo_selecionado' );
                }

                wp_send_json_success( [ 'message' => __( 'Modelo apagado com sucesso.', 'gerador-certificados-wp' ) ] );
                break;
        }

        wp_send_json_error( [ 'message' => __( 'Acao desconhecida.', 'gerador-certificados-wp' ) ] );
    }

    public function get_participant() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'gcwp_public_actions' ) ) {
            wp_send_json_error( __( 'Erro de seguranca.', 'gerador-certificados-wp' ) );
        }

        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
            wp_send_json_error( __( 'Acesso negado.', 'gerador-certificados-wp' ) );
        }

        $participant_id = intval( $_POST['participant_id'] ?? 0 );
        $participant    = \GCWP\Database\ParticipantsTable::get( $participant_id );

        if ( ! $participant ) {
            wp_send_json_error( __( 'Participante nao encontrado.', 'gerador-certificados-wp' ) );
        }

        wp_send_json_success( $participant );
    }

    public function delete_participant() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'gcwp_public_actions' ) ) {
            wp_send_json_error( __( 'Erro de seguranca.', 'gerador-certificados-wp' ) );
        }

        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
            wp_send_json_error( __( 'Acesso negado.', 'gerador-certificados-wp' ) );
        }

        $participant_id = intval( $_POST['participant_id'] ?? 0 );
        $participant    = \GCWP\Database\ParticipantsTable::get( $participant_id );

        if ( ! $participant ) {
            wp_send_json_error( __( 'Participante nao encontrado.', 'gerador-certificados-wp' ) );
        }

        \GCWP\Database\ParticipantsTable::delete( $participant_id );
        wp_send_json_success( __( 'Participante excluido com sucesso.', 'gerador-certificados-wp' ) );
    }

    public function delete_certificate() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'gcwp_template_actions' ) ) {
            wp_send_json_error( [ 'message' => __( 'Erro de seguranca. Recarregue a pagina.', 'gerador-certificados-wp' ) ] );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Voce nao tem permissao para realizar esta acao.', 'gerador-certificados-wp' ) ] );
        }

        $filename = isset( $_POST['file'] ) ? sanitize_file_name( wp_unslash( $_POST['file'] ) ) : '';
        if ( empty( $filename ) ) {
            wp_send_json_error( [ 'message' => __( 'Arquivo nao especificado.', 'gerador-certificados-wp' ) ] );
        }

        $upload_dir = wp_upload_dir();
        $filepath   = $upload_dir['basedir'] . '/certificados/emitidos/' . $filename;

        if ( ! file_exists( $filepath ) ) {
            wp_send_json_error( [ 'message' => __( 'Arquivo nao encontrado.', 'gerador-certificados-wp' ) ] );
        }

        if ( unlink( $filepath ) ) {
            wp_send_json_success( [ 'message' => __( 'Certificado excluido com sucesso.', 'gerador-certificados-wp' ) ] );
        }

        wp_send_json_error( [ 'message' => __( 'Erro ao excluir o arquivo.', 'gerador-certificados-wp' ) ] );
    }
}
