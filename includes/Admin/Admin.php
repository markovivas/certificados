<?php

namespace GCWP\Admin;

use GCWP\Core\CertificateGenerator;
use GCWP\Core\TemplateRepository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Gerador de Certificados', 'gerador-certificados-wp' ),
            __( 'Certificados', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-certificados',
            [ $this, 'render_modelos_page' ],
            'dashicons-awards',
            30
        );

        add_submenu_page(
            'gcwp-certificados',
            __( 'Modelos', 'gerador-certificados-wp' ),
            __( 'Modelos', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-certificados',
            [ $this, 'render_modelos_page' ]
        );

        add_submenu_page(
            'gcwp-certificados',
            __( 'Editar Modelo', 'gerador-certificados-wp' ),
            __( 'Editar Modelo', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-editar-modelo',
            [ $this, 'render_editar_modelo_page' ]
        );

        $participantes_page_hook = add_submenu_page(
            'gcwp-certificados',
            __( 'Participantes', 'gerador-certificados-wp' ),
            __( 'Participantes', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-participantes',
            [ $this, 'render_participantes_page' ]
        );

        add_action( "load-{$participantes_page_hook}", [ $this, 'load_participants_list_table' ] );

        add_submenu_page(
            'gcwp-certificados',
            __( 'Emissao', 'gerador-certificados-wp' ),
            __( 'Emissao', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-emissao',
            [ $this, 'render_emissao_page' ]
        );

        add_submenu_page(
            'gcwp-certificados',
            __( 'Configuracoes', 'gerador-certificados-wp' ),
            __( 'Configuracoes', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-configuracoes',
            [ $this, 'render_configuracoes_page' ]
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( false === strpos( $hook, 'gcwp' ) ) {
            return;
        }

        wp_enqueue_style( 'gcwp-admin-style', GCWP_PLUGIN_URL . 'assets/css/admin.css', [], $this->version );
        wp_enqueue_script( 'gcwp-admin-script', GCWP_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'jquery-ui-draggable' ], $this->version, true );

        wp_localize_script(
            'gcwp-admin-script',
            'gcwp_ajax',
            [
                'ajax_url'      => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'gcwp_template_actions' ),
                'confirm_reset' => __( 'Tem certeza absoluta? Esta acao nao pode ser desfeita.', 'gerador-certificados-wp' ),
                'fontOptions'   => $this->get_font_options_html(),
                'strings'       => [
                    'processing'      => __( 'Processando...', 'gerador-certificados-wp' ),
                    'select'          => __( 'Selecionar', 'gerador-certificados-wp' ),
                    'selected'        => __( 'Selecionado', 'gerador-certificados-wp' ),
                    'deleteConfirm'   => __( 'Tem certeza que deseja apagar este modelo?', 'gerador-certificados-wp' ),
                    'fieldDefault'    => __( 'Novo campo', 'gerador-certificados-wp' ),
                    'fieldSample'     => __( 'Exemplo do texto', 'gerador-certificados-wp' ),
                    'fieldNamePrompt' => __( 'Novo nome para o modelo:', 'gerador-certificados-wp' ),
                    'error'           => __( 'Erro ao processar a solicitacao.', 'gerador-certificados-wp' ),
                ],
            ]
        );
    }

    public function render_modelos_page() {
        $message      = '';
        $message_type = 'success';

        if ( isset( $_POST['gcwp_upload_nonce'] ) ) {
            $result = $this->handle_template_upload();
            if ( is_wp_error( $result ) ) {
                $message      = $result->get_error_message();
                $message_type = 'error';
            } elseif ( $result ) {
                $message = sprintf( __( 'Modelo "%s" criado com sucesso.', 'gerador-certificados-wp' ), $result['name'] );
            }
        }

        $templates         = TemplateRepository::list();
        $selected_template = get_option( 'gcwp_modelo_selecionado', '' );

        include_once GCWP_PLUGIN_DIR . 'views/page-modelos.php';
    }

    private function handle_template_upload() {
        if ( ! isset( $_POST['gcwp_upload_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gcwp_upload_nonce'] ), 'gcwp_upload_template' ) ) {
            return new \WP_Error( 'invalid_nonce', __( 'Acao invalida.', 'gerador-certificados-wp' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'permission', __( 'Voce nao tem permissao para realizar esta acao.', 'gerador-certificados-wp' ) );
        }

        $model_name = isset( $_POST['modelo_nome'] ) ? sanitize_text_field( wp_unslash( $_POST['modelo_nome'] ) ) : '';
        if ( empty( $model_name ) ) {
            return new \WP_Error( 'empty_name', __( 'O nome do modelo e obrigatorio.', 'gerador-certificados-wp' ) );
        }

        if ( empty( $_FILES['modelo_frente']['name'] ) ) {
            return new \WP_Error( 'empty_file', __( 'A imagem da frente e obrigatoria.', 'gerador-certificados-wp' ) );
        }

        return TemplateRepository::create(
            $model_name,
            $_FILES['modelo_frente'],
            $_FILES['modelo_verso'] ?? []
        );
    }

    public function render_editar_modelo_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Voce nao tem permissao para acessar esta pagina.', 'gerador-certificados-wp' ) );
        }

        $slug = isset( $_GET['modelo'] ) ? sanitize_title( wp_unslash( $_GET['modelo'] ) ) : '';
        if ( empty( $slug ) ) {
            wp_die( esc_html__( 'Modelo invalido.', 'gerador-certificados-wp' ) );
        }

        $template = TemplateRepository::get( $slug );
        if ( ! $template ) {
            wp_die( esc_html__( 'Modelo nao encontrado.', 'gerador-certificados-wp' ) );
        }

        $message      = '';
        $message_type = 'success';

        if ( isset( $_POST['gcwp_edit_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['gcwp_edit_nonce'] ), 'gcwp_edit_template' ) ) {
            $result = TemplateRepository::update(
                $slug,
                [
                    'name'   => isset( $_POST['modelo_nome'] ) ? sanitize_text_field( wp_unslash( $_POST['modelo_nome'] ) ) : $template['name'],
                    'fields' => $this->sanitize_fields_payload( $_POST['fields'] ?? [] ),
                ],
                $_FILES
            );

            if ( is_wp_error( $result ) ) {
                $message      = $result->get_error_message();
                $message_type = 'error';
            } else {
                if ( get_option( 'gcwp_modelo_selecionado' ) === $slug && $result['slug'] !== $slug ) {
                    update_option( 'gcwp_modelo_selecionado', $result['slug'] );
                }

                $template = $result;
                $slug     = $result['slug'];
                $message  = __( 'Modelo atualizado com sucesso.', 'gerador-certificados-wp' );
            }
        }

        $available_fonts = TemplateRepository::get_available_fonts();

        include_once GCWP_PLUGIN_DIR . 'views/page-editar-modelo.php';
    }

    public function render_participantes_page() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

        if ( 'add' === $action || 'edit' === $action ) {
            $participant = null;
            if ( 'edit' === $action && isset( $_GET['participant'] ) ) {
                $participant = \GCWP\Database\ParticipantsTable::get( absint( $_GET['participant'] ) );
            }
            include GCWP_PLUGIN_DIR . 'views/form-participant.php';
            return;
        }

        include GCWP_PLUGIN_DIR . 'views/page-participantes.php';
    }

    public function handle_save_participant() {
        if ( ! isset( $_POST['gcwp_participant_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gcwp_participant_nonce'] ), 'gcwp_save_participant_nonce' ) ) {
            wp_die( esc_html__( 'Acao invalida.', 'gerador-certificados-wp' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permissao negada.', 'gerador-certificados-wp' ) );
        }

        $data = [
            'nome_completo'      => sanitize_text_field( wp_unslash( $_POST['nome_completo'] ?? '' ) ),
            'email'              => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
            'curso'              => sanitize_text_field( wp_unslash( $_POST['curso'] ?? '' ) ),
            'data_inicio'        => sanitize_text_field( wp_unslash( $_POST['data_inicio'] ?? '' ) ),
            'data_termino'       => sanitize_text_field( wp_unslash( $_POST['data_termino'] ?? '' ) ),
            'duracao_horas'      => intval( $_POST['duracao_horas'] ?? 0 ),
            'cidade'             => sanitize_text_field( wp_unslash( $_POST['cidade'] ?? '' ) ),
            'data_emissao'       => sanitize_text_field( wp_unslash( $_POST['data_emissao'] ?? '' ) ),
            'numero_livro'       => sanitize_text_field( wp_unslash( $_POST['numero_livro'] ?? '' ) ),
            'numero_pagina'      => sanitize_text_field( wp_unslash( $_POST['numero_pagina'] ?? '' ) ),
            'numero_certificado' => sanitize_text_field( wp_unslash( $_POST['numero_certificado'] ?? '' ) ),
        ];

        if ( isset( $_POST['participant_action'] ) && 'edit' === $_POST['participant_action'] && isset( $_POST['participant'] ) ) {
            $result  = \GCWP\Database\ParticipantsTable::update( absint( $_POST['participant'] ), $data );
            $message = false !== $result ? 'success_update' : 'error_update';
        } else {
            $result  = \GCWP\Database\ParticipantsTable::insert( $data );
            $message = false !== $result ? 'success_add' : 'error_add';
        }

        wp_redirect( admin_url( 'admin.php?page=gcwp-participantes&message=' . $message ) );
        exit;
    }

    public function render_emissao_page() {
        $message      = '';
        $message_type = 'success';

        if ( isset( $_POST['gcp_generate_pdf'] ) ) {
            $result = $this->handle_generate_certificate();
            if ( is_wp_error( $result ) ) {
                $message      = $result->get_error_message();
                $message_type = 'error';
            } else {
                $message = sprintf( __( 'Certificado gerado com sucesso. <a href="%s" target="_blank">Clique aqui para baixar</a>.', 'gerador-certificados-wp' ), esc_url( $result['url'] ) );
            }
        }

        $participants = \GCWP\Database\ParticipantsTable::get_participants( 1000 );
        $templates    = TemplateRepository::list();

        $upload_dir            = wp_upload_dir();
        $emitidos_dir          = $upload_dir['basedir'] . '/certificados/emitidos';
        $emitidos_url          = $upload_dir['baseurl'] . '/certificados/emitidos';
        $certificados_emitidos = [];

        if ( is_dir( $emitidos_dir ) ) {
            $files = glob( $emitidos_dir . '/*.pdf' );
            if ( $files ) {
                usort(
                    $files,
                    static function ( $a, $b ) {
                        return filemtime( $b ) - filemtime( $a );
                    }
                );

                foreach ( $files as $file ) {
                    $filename = basename( $file );
                    $parts    = explode( '_', $filename );
                    $name     = isset( $parts[1] ) ? ucwords( str_replace( '-', ' ', $parts[1] ) ) : __( 'Desconhecido', 'gerador-certificados-wp' );

                    $certificados_emitidos[] = [
                        'filename' => $filename,
                        'url'      => $emitidos_url . '/' . $filename,
                        'date'     => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $file ) ),
                        'name'     => $name,
                    ];
                }
            }
        }

        include_once GCWP_PLUGIN_DIR . 'views/page-emissao.php';
    }

    private function handle_generate_certificate() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'gcp_generate_pdf_nonce' ) ) {
            return new \WP_Error( 'nonce_error', __( 'Acao invalida.', 'gerador-certificados-wp' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'permission_error', __( 'Permissao negada.', 'gerador-certificados-wp' ) );
        }

        $participant_id = isset( $_POST['participant_id'] ) ? absint( $_POST['participant_id'] ) : 0;
        $template_slug  = isset( $_POST['modelo_id'] ) ? sanitize_title( wp_unslash( $_POST['modelo_id'] ) ) : '';

        if ( ! $participant_id ) {
            return new \WP_Error( 'invalid_participant', __( 'Selecione um participante.', 'gerador-certificados-wp' ) );
        }

        if ( empty( $template_slug ) ) {
            return new \WP_Error( 'invalid_modelo', __( 'Selecione um modelo.', 'gerador-certificados-wp' ) );
        }

        $participant = \GCWP\Database\ParticipantsTable::get( $participant_id );
        if ( ! $participant ) {
            return new \WP_Error( 'participant_not_found', __( 'Participante nao encontrado.', 'gerador-certificados-wp' ) );
        }

        $values    = TemplateRepository::build_field_value_map( $participant );
        $generator = new CertificateGenerator();

        return $generator->generate_certificate( $values, $template_slug );
    }

    public function render_configuracoes_page() {
        $selected_template = get_option( 'gcwp_modelo_selecionado', '' );
        $template          = $selected_template ? TemplateRepository::get( $selected_template ) : null;
        include_once GCWP_PLUGIN_DIR . 'views/page-configuracoes.php';
    }

    public function load_participants_list_table() {
        if ( class_exists( '\GCWP\Admin\ParticipantsListTable' ) ) {
            global $gcwp_participants_list_table;
            $gcwp_participants_list_table         = new \GCWP\Admin\ParticipantsListTable();
            $gcwp_participants_list_table->screen = get_current_screen();
            $gcwp_participants_list_table->process_bulk_action();
        }

        add_screen_option(
            'per_page',
            [
                'label'   => __( 'Participantes por pagina', 'gerador-certificados-wp' ),
                'default' => 10,
                'option'  => 'participants_per_page',
            ]
        );
    }

    public function handle_reset_plugin() {
        if ( ! isset( $_POST['gcwp_reset_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gcwp_reset_nonce'] ), 'gcwp_reset_plugin_nonce' ) ) {
            wp_die( esc_html__( 'Acao invalida.', 'gerador-certificados-wp' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Voce nao tem permissao para realizar esta acao.', 'gerador-certificados-wp' ) );
        }
        if ( ! isset( $_POST['gcwp_reset_confirm'] ) || 'on' !== $_POST['gcwp_reset_confirm'] ) {
            wp_die( esc_html__( 'Voce precisa confirmar a acao de reset.', 'gerador-certificados-wp' ) );
        }

        global $wpdb;

        \GCWP\Database\ParticipantsTable::truncate();

        $upload_dir        = wp_upload_dir();
        $certificados_dir  = $upload_dir['basedir'] . '/certificados';
        $emitidos_dir      = $certificados_dir . '/emitidos';
        $modelos_dir       = $certificados_dir . '/modelos';

        if ( is_dir( $emitidos_dir ) ) {
            \GCWP\Core\Utils::delete_dir_recursive( $emitidos_dir );
        }
        if ( is_dir( $modelos_dir ) ) {
            \GCWP\Core\Utils::delete_dir_recursive( $modelos_dir );
        }

        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gcwp_%'" );

        wp_redirect( admin_url( 'admin.php?page=gcwp-configuracoes&reset=success' ) );
        exit;
    }

    private function sanitize_fields_payload( $fields ) {
        $normalized = [];

        if ( ! is_array( $fields ) ) {
            return $normalized;
        }

        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            $normalized[] = [
                'key'         => sanitize_key( $field['key'] ?? '' ),
                'label'       => sanitize_text_field( wp_unslash( $field['label'] ?? '' ) ),
                'page'        => 'back' === ( $field['page'] ?? 'front' ) ? 'back' : 'front',
                'x'           => (float) ( $field['x'] ?? 0 ),
                'y'           => (float) ( $field['y'] ?? 0 ),
                'width'       => (float) ( $field['width'] ?? 80 ),
                'font_size'   => (float) ( $field['font_size'] ?? 18 ),
                'color'       => sanitize_hex_color( $field['color'] ?? '#000000' ) ?: '#000000',
                'font_family' => sanitize_text_field( wp_unslash( $field['font_family'] ?? 'helvetica' ) ),
                'font_weight' => ! empty( $field['font_weight'] ) ? 'B' : '',
                'align'       => in_array( $field['align'] ?? 'L', [ 'L', 'C', 'R' ], true ) ? $field['align'] : 'L',
                'uppercase'   => empty( $field['uppercase'] ) ? 0 : 1,
                'sample'      => sanitize_text_field( wp_unslash( $field['sample'] ?? '' ) ),
            ];
        }

        return $normalized;
    }

    private function get_font_options_html() {
        $html = '';

        foreach ( TemplateRepository::get_available_fonts() as $font_key => $font_name ) {
            $html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr( $font_key ),
                esc_html( $font_name )
            );
        }

        return $html;
    }
}
