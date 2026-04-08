<?php

use GCWP\Core\CertificateGenerator;
use GCWP\Core\TemplateRepository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GCWP_Public {

    public static function init() {
        add_shortcode( 'gerador_certificados_participantes', [ __CLASS__, 'shortcode_participantes' ] );
        add_shortcode( 'gerador_certificados_emissao', [ __CLASS__, 'shortcode_emissao' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_gcwp_public_save_participant', [ __CLASS__, 'handle_save_participant' ] );
        add_action( 'wp_ajax_gcwp_public_generate_certificate', [ __CLASS__, 'handle_generate_certificate' ] );
    }

    public static function enqueue_scripts() {
        wp_enqueue_style( 'gcwp-public-style', GCWP_PLUGIN_URL . 'assets/css/public.css', [], GCWP_VERSION );
        wp_enqueue_script( 'gcwp-public-script', GCWP_PLUGIN_URL . 'assets/js/public.js', [ 'jquery' ], GCWP_VERSION, true );

        wp_localize_script(
            'gcwp-public-script',
            'gcwp_public_ajax',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'gcwp_public_actions' ),
                'strings'  => [
                    'download' => __( 'Baixar certificado', 'gerador-certificados-wp' ),
                    'error'    => __( 'Erro ao processar a solicitacao.', 'gerador-certificados-wp' ),
                ],
            ]
        );
    }

    public static function shortcode_participantes() {
        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
            return '';
        }

        ob_start();
        self::render_participantes_interface();
        return ob_get_clean();
    }

    public static function shortcode_emissao() {
        if ( ! is_user_logged_in() ) {
            return self::render_access_denied();
        }

        ob_start();
        self::render_emissao_interface();
        return ob_get_clean();
    }

    private static function render_access_denied() {
        $login_url = wp_login_url( get_permalink() );
        ob_start();
        ?>
        <div class="gcwp-access-denied">
            <div class="gcwp-access-denied-icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <h3><?php esc_html_e( 'Acesso restrito', 'gerador-certificados-wp' ); ?></h3>
            <p><?php esc_html_e( 'Voce precisa estar logado para acessar esta pagina.', 'gerador-certificados-wp' ); ?></p>
            <a href="<?php echo esc_url( $login_url ); ?>" class="button button-primary gcwp-login-btn"><?php esc_html_e( 'Fazer login', 'gerador-certificados-wp' ); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_participantes_interface() {
        $participants = \GCWP\Database\ParticipantsTable::get_participants( 1000 );
        ?>
        <div class="gcwp-public-wrap">
            <div class="gcwp-header">
                <h2><?php esc_html_e( 'Gerenciar participantes', 'gerador-certificados-wp' ); ?></h2>
                <button id="gcwp-add-participant-btn" class="button button-primary gcwp-add-btn"><?php esc_html_e( 'Adicionar participante', 'gerador-certificados-wp' ); ?></button>
            </div>

            <div id="gcwp-participants-list" class="gcwp-participants-section">
                <?php if ( ! empty( $participants ) ) : ?>
                    <div class="gcwp-table-container">
                        <table class="wp-list-table widefat fixed striped gcwp-participants-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Nome completo', 'gerador-certificados-wp' ); ?></th>
                                    <th><?php esc_html_e( 'E-mail', 'gerador-certificados-wp' ); ?></th>
                                    <th><?php esc_html_e( 'Curso', 'gerador-certificados-wp' ); ?></th>
                                    <th><?php esc_html_e( 'Data de emissao', 'gerador-certificados-wp' ); ?></th>
                                    <th><?php esc_html_e( 'Acoes', 'gerador-certificados-wp' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $participants as $participant ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $participant['nome_completo'] ); ?></td>
                                        <td><?php echo esc_html( $participant['email'] ); ?></td>
                                        <td><?php echo esc_html( $participant['curso'] ); ?></td>
                                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $participant['data_emissao'] ) ) ); ?></td>
                                        <td class="gcwp-actions">
                                            <button class="button button-secondary edit-participant" data-id="<?php echo esc_attr( $participant['id'] ); ?>"><?php esc_html_e( 'Editar', 'gerador-certificados-wp' ); ?></button>
                                            <button class="button button-secondary delete-participant" data-id="<?php echo esc_attr( $participant['id'] ); ?>"><?php esc_html_e( 'Excluir', 'gerador-certificados-wp' ); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="gcwp-no-participants">
                        <p><?php esc_html_e( 'Nenhum participante cadastrado ainda.', 'gerador-certificados-wp' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="gcwp-participant-form" class="gcwp-form-section" style="display:none;">
                <div class="gcwp-form-header">
                    <h3 id="gcwp-form-title"><?php esc_html_e( 'Adicionar participante', 'gerador-certificados-wp' ); ?></h3>
                    <button type="button" id="gcwp-close-form" class="button gcwp-close-btn">X</button>
                </div>
                <form id="gcwp-participant-form-data" class="gcwp-form">
                    <input type="hidden" name="participant_id" value="">
                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="nome_completo"><?php esc_html_e( 'Nome completo', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="nome_completo" id="nome_completo" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="email"><?php esc_html_e( 'E-mail', 'gerador-certificados-wp' ); ?></label>
                            <input type="email" name="email" id="email" required>
                        </div>
                    </div>
                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="curso"><?php esc_html_e( 'Curso', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="curso" id="curso" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="cidade"><?php esc_html_e( 'Cidade', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="cidade" id="cidade" required>
                        </div>
                    </div>
                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="data_inicio"><?php esc_html_e( 'Data de inicio', 'gerador-certificados-wp' ); ?></label>
                            <input type="date" name="data_inicio" id="data_inicio" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="data_termino"><?php esc_html_e( 'Data de termino', 'gerador-certificados-wp' ); ?></label>
                            <input type="date" name="data_termino" id="data_termino" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="duracao_horas"><?php esc_html_e( 'Carga horaria', 'gerador-certificados-wp' ); ?></label>
                            <input type="number" name="duracao_horas" id="duracao_horas" required min="1">
                        </div>
                    </div>
                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="data_emissao"><?php esc_html_e( 'Data de emissao', 'gerador-certificados-wp' ); ?></label>
                            <input type="date" name="data_emissao" id="data_emissao" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="numero_certificado"><?php esc_html_e( 'Numero do certificado', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="numero_certificado" id="numero_certificado">
                        </div>
                    </div>
                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="numero_livro"><?php esc_html_e( 'Livro', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="numero_livro" id="numero_livro">
                        </div>
                        <div class="gcwp-form-group">
                            <label for="numero_pagina"><?php esc_html_e( 'Pagina', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="numero_pagina" id="numero_pagina">
                        </div>
                    </div>
                    <div class="gcwp-form-actions">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Salvar participante', 'gerador-certificados-wp' ); ?>">
                        <button type="button" id="gcwp-cancel-edit" class="button"><?php esc_html_e( 'Cancelar', 'gerador-certificados-wp' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    private static function render_emissao_interface() {
        $templates    = TemplateRepository::list();
        $participants = \GCWP\Database\ParticipantsTable::get_participants( 1000 );
        ?>
        <div class="gcwp-public-wrap">
            <div class="gcwp-header">
                <h2><?php esc_html_e( 'Gerar certificado', 'gerador-certificados-wp' ); ?></h2>
            </div>

            <?php if ( empty( $templates ) ) : ?>
                <div class="gcwp-no-participants">
                    <p><?php esc_html_e( 'Nenhum modelo foi configurado ainda.', 'gerador-certificados-wp' ); ?></p>
                </div>
            <?php else : ?>
                <div class="gcwp-emissao-section">
                    <div class="gcwp-emissao-form-container">
                        <form id="gcwp-generate-certificate-form" class="gcwp-emissao-form">
                            <div class="gcwp-form-row">
                                <div class="gcwp-form-group">
                                    <label for="modelo_slug"><?php esc_html_e( 'Modelo do certificado', 'gerador-certificados-wp' ); ?></label>
                                    <select name="modelo_slug" id="modelo_slug" required>
                                        <?php foreach ( $templates as $slug => $template ) : ?>
                                            <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $template['name'] ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="gcwp-form-group">
                                    <label for="participant_id"><?php esc_html_e( 'Nome da pessoa ja cadastrada', 'gerador-certificados-wp' ); ?></label>
                                    <select name="participant_id" id="participant_id" required>
                                        <option value=""><?php esc_html_e( 'Selecione uma pessoa', 'gerador-certificados-wp' ); ?></option>
                                        <?php foreach ( $participants as $participant ) : ?>
                                            <option value="<?php echo esc_attr( $participant['id'] ); ?>"><?php echo esc_html( $participant['nome_completo'] ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="gcwp-form-actions">
                                <input type="submit" class="button button-primary gcwp-generate-btn" value="<?php esc_attr_e( 'Gerar certificado', 'gerador-certificados-wp' ); ?>">
                            </div>
                        </form>
                    </div>

                    <div id="gcwp-certificate-result" class="gcwp-result-section" style="display:none;">
                        <h3><?php esc_html_e( 'Resultado', 'gerador-certificados-wp' ); ?></h3>
                        <div id="gcwp-result-content"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function handle_save_participant() {
        check_ajax_referer( 'gcwp_public_actions', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
            wp_send_json_error( __( 'Acesso negado.', 'gerador-certificados-wp' ) );
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

        if ( ! empty( $_POST['participant_id'] ) ) {
            \GCWP\Database\ParticipantsTable::update( intval( $_POST['participant_id'] ), $data );
            wp_send_json_success( __( 'Participante atualizado com sucesso.', 'gerador-certificados-wp' ) );
        }

        \GCWP\Database\ParticipantsTable::insert( $data );
        wp_send_json_success( __( 'Participante adicionado com sucesso.', 'gerador-certificados-wp' ) );
    }

    public static function handle_generate_certificate() {
        check_ajax_referer( 'gcwp_public_actions', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Acesso negado.', 'gerador-certificados-wp' ) );
        }

        $template_slug = isset( $_POST['modelo_slug'] ) ? sanitize_title( wp_unslash( $_POST['modelo_slug'] ) ) : '';
        if ( empty( $template_slug ) ) {
            wp_send_json_error( __( 'Modelo nao selecionado.', 'gerador-certificados-wp' ) );
        }

        $template = TemplateRepository::get( $template_slug );
        if ( ! $template ) {
            wp_send_json_error( __( 'Modelo nao encontrado.', 'gerador-certificados-wp' ) );
        }

        $participant_id = intval( $_POST['participant_id'] ?? 0 );
        if ( ! $participant_id ) {
            wp_send_json_error( __( 'Pessoa nao selecionada.', 'gerador-certificados-wp' ) );
        }

        $participant = \GCWP\Database\ParticipantsTable::get( $participant_id );
        if ( ! $participant ) {
            wp_send_json_error( __( 'Participante nao encontrado.', 'gerador-certificados-wp' ) );
        }

        $values = TemplateRepository::build_field_value_map( $participant );

        $generator = new CertificateGenerator();
        $result    = $generator->generate_certificate( $values, $template_slug );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success(
            [
                'message' => __( 'Certificado gerado com sucesso.', 'gerador-certificados-wp' ),
                'url'     => $result['url'],
            ]
        );
    }
}
