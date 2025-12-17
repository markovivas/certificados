<?php

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
        if ( ! is_user_logged_in() || ! current_user_can( 'subscriber' ) ) {
            return;
        }

        wp_enqueue_style( 'gcwp-public-style', GCWP_PLUGIN_URL . 'assets/css/public.css', [], GCWP_VERSION );
        wp_enqueue_script( 'gcwp-public-script', GCWP_PLUGIN_URL . 'assets/js/public.js', [ 'jquery' ], GCWP_VERSION, true );

        wp_localize_script( 'gcwp-public-script', 'gcwp_public_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'gcwp_public_actions' ),
        ] );
    }

    public static function shortcode_participantes( $atts ) {
        if ( ! is_user_logged_in() || ! current_user_can( 'subscriber' ) ) {
            return ''; // Não mostrar mensagem, apenas ocultar o conteúdo
        }

        ob_start();
        self::render_participantes_interface();
        return ob_get_clean();
    }

    public static function shortcode_emissao( $atts ) {
        if ( ! is_user_logged_in() || ! current_user_can( 'subscriber' ) ) {
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
            <h3><?php _e( 'Acesso Restrito', 'gerador-certificados-wp' ); ?></h3>
            <p><?php _e( 'Você precisa estar logado como assinante para acessar esta página.', 'gerador-certificados-wp' ); ?></p>
            <a href="<?php echo esc_url( $login_url ); ?>" class="button button-primary gcwp-login-btn">
                <span class="dashicons dashicons-admin-users"></span> <?php _e( 'Fazer Login', 'gerador-certificados-wp' ); ?>
            </a>
            <p class="gcwp-access-denied-note">
                <?php _e( 'Após o login, você será redirecionado de volta para esta página.', 'gerador-certificados-wp' ); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_participantes_interface() {
        $participants = \GCWP\Database\ParticipantsTable::get_participants( 1000 );

        ?>
        <div class="gcwp-public-wrap">
            <div class="gcwp-header">
                <h2><?php _e( 'Gerenciar Participantes', 'gerador-certificados-wp' ); ?></h2>
                <button id="gcwp-add-participant-btn" class="button button-primary gcwp-add-btn">
                    <span class="dashicons dashicons-plus"></span> <?php _e( 'Adicionar Participante', 'gerador-certificados-wp' ); ?>
                </button>
            </div>

            <div id="gcwp-participants-list" class="gcwp-participants-section">
                <?php if ( ! empty( $participants ) ) : ?>
                    <div class="gcwp-table-container">
                        <table class="wp-list-table widefat fixed striped gcwp-participants-table">
                            <thead>
                                <tr>
                                    <th><?php _e( 'Nome Completo', 'gerador-certificados-wp' ); ?></th>
                                    <th><?php _e( 'E-mail', 'gerador-certificados-wp' ); ?></th>
                                    <th><?php _e( 'Curso', 'gerador-certificados-wp' ); ?></th>
                                    <th><?php _e( 'Data de Emissão', 'gerador-certificados-wp' ); ?></th>
                                    <?php if ( current_user_can( 'manage_options' ) ) : ?>
                                        <th><?php _e( 'Ações', 'gerador-certificados-wp' ); ?></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $participants as $participant ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $participant['nome_completo'] ); ?></td>
                                        <td><?php echo esc_html( $participant['email'] ); ?></td>
                                        <td><?php echo esc_html( $participant['curso'] ); ?></td>
                                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $participant['data_emissao'] ) ) ); ?></td>
                                        <?php if ( current_user_can( 'manage_options' ) ) : ?>
                                            <td class="gcwp-actions">
                                                <button class="button button-secondary edit-participant" data-id="<?php echo esc_attr( $participant['id'] ); ?>" title="<?php _e( 'Editar', 'gerador-certificados-wp' ); ?>">
                                                    <span class="dashicons dashicons-edit"></span>
                                                </button>
                                                <button class="button button-secondary delete-participant" data-id="<?php echo esc_attr( $participant['id'] ); ?>" title="<?php _e( 'Excluir', 'gerador-certificados-wp' ); ?>">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="gcwp-no-participants">
                        <p><?php _e( 'Nenhum participante cadastrado ainda.', 'gerador-certificados-wp' ); ?></p>
                        <p><?php _e( 'Clique em "Adicionar Participante" para começar.', 'gerador-certificados-wp' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="gcwp-participant-form" class="gcwp-form-section" style="display: none;">
                <div class="gcwp-form-header">
                    <h3 id="gcwp-form-title"><?php _e( 'Adicionar Novo Participante', 'gerador-certificados-wp' ); ?></h3>
                    <button type="button" id="gcwp-close-form" class="button gcwp-close-btn" title="<?php _e( 'Fechar', 'gerador-certificados-wp' ); ?>">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <form id="gcwp-participant-form-data" class="gcwp-form">
                    <input type="hidden" name="participant_id" value="">

                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="nome_completo"><?php _e( 'Nome Completo', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="text" name="nome_completo" id="nome_completo" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="email"><?php _e( 'E-mail', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="email" name="email" id="email" required>
                        </div>
                    </div>

                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="curso"><?php _e( 'Curso', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="text" name="curso" id="curso" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="cidade"><?php _e( 'Cidade', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="text" name="cidade" id="cidade" required>
                        </div>
                    </div>

                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="data_inicio"><?php _e( 'Data de Início', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="date" name="data_inicio" id="data_inicio" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="data_termino"><?php _e( 'Data de Término', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="date" name="data_termino" id="data_termino" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="duracao_horas"><?php _e( 'Duração (Horas)', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="number" name="duracao_horas" id="duracao_horas" required min="1">
                        </div>
                    </div>

                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="data_emissao"><?php _e( 'Data de Emissão', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                            <input type="date" name="data_emissao" id="data_emissao" required>
                        </div>
                        <div class="gcwp-form-group">
                            <label for="numero_certificado"><?php _e( 'Número do Certificado', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="numero_certificado" id="numero_certificado">
                        </div>
                    </div>

                    <div class="gcwp-form-row">
                        <div class="gcwp-form-group">
                            <label for="numero_livro"><?php _e( 'Número do Livro', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="numero_livro" id="numero_livro">
                        </div>
                        <div class="gcwp-form-group">
                            <label for="numero_pagina"><?php _e( 'Número da Página', 'gerador-certificados-wp' ); ?></label>
                            <input type="text" name="numero_pagina" id="numero_pagina">
                        </div>
                    </div>

                    <div class="gcwp-form-actions">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Salvar Participante', 'gerador-certificados-wp' ); ?>">
                        <button type="button" id="gcwp-cancel-edit" class="button"><?php _e( 'Cancelar', 'gerador-certificados-wp' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    private static function render_emissao_interface() {
        $participants = \GCWP\Database\ParticipantsTable::get_participants( 1000 );

        // Fetch available models
        $upload_dir = wp_upload_dir();
        $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
        $modelos = [];
        if ( is_dir( $modelos_dir ) ) {
            $modelos_dirs = array_filter( scandir( $modelos_dir ), function( $item ) use ( $modelos_dir ) {
                return is_dir( $modelos_dir . '/' . $item ) && ! in_array( $item, [ '.', '..', 'frente', 'verso' ] );
            } );
            foreach ( $modelos_dirs as $modelo_slug ) {
                $modelo_nome = ucwords( str_replace( '-', ' ', $modelo_slug ) );
                $frente_files = glob( $modelos_dir . '/' . $modelo_slug . '/frente.*' );
                if ( ! empty( $frente_files ) ) {
                    $modelos[ $modelo_slug ] = $modelo_nome;
                }
            }
        }

        ?>
        <div class="gcwp-public-wrap">
            <div class="gcwp-header">
                <h2><?php _e( 'Emitir Certificados', 'gerador-certificados-wp' ); ?></h2>
            </div>

            <div class="gcwp-emissao-section">
                <div class="gcwp-emissao-form-container">
                    <h3><?php _e( 'Gerar Novo Certificado', 'gerador-certificados-wp' ); ?></h3>
                    <form id="gcwp-generate-certificate-form" class="gcwp-emissao-form">
                        <div class="gcwp-form-row">
                            <div class="gcwp-form-group">
                                <label for="participant_id"><?php _e( 'Selecione o Participante', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                                <select name="participant_id" id="participant_id" required>
                                    <option value=""><?php _e( 'Selecione um participante', 'gerador-certificados-wp' ); ?></option>
                                    <?php foreach ( $participants as $participant ) : ?>
                                        <option value="<?php echo esc_attr( $participant['id'] ); ?>"><?php echo esc_html( $participant['nome_completo'] ); ?> - <?php echo esc_html( $participant['curso'] ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="gcwp-form-group">
                                <label for="modelo_slug"><?php _e( 'Selecione o Modelo', 'gerador-certificados-wp' ); ?> <span class="required">*</span></label>
                                <select name="modelo_slug" id="modelo_slug" required>
                                    <option value=""><?php _e( 'Selecione um modelo', 'gerador-certificados-wp' ); ?></option>
                                    <?php foreach ( $modelos as $slug => $nome ) : ?>
                                        <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $nome ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="gcwp-form-actions">
                            <input type="submit" class="button button-primary gcwp-generate-btn" value="<?php _e( 'Gerar Certificado', 'gerador-certificados-wp' ); ?>">
                        </div>
                    </form>
                </div>

                <div id="gcwp-certificate-result" class="gcwp-result-section" style="display: none;">
                    <h3><?php _e( 'Resultado', 'gerador-certificados-wp' ); ?></h3>
                    <div id="gcwp-result-content"></div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function handle_save_participant() {
        check_ajax_referer( 'gcwp_public_actions', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'subscriber' ) ) {
            wp_die( __( 'Acesso negado.', 'gerador-certificados-wp' ) );
        }

        // $user_id = get_current_user_id();
        $data = [
            // 'user_id' => $user_id,
            'nome_completo' => sanitize_text_field( $_POST['nome_completo'] ),
            'email' => sanitize_email( $_POST['email'] ),
            'curso' => sanitize_text_field( $_POST['curso'] ),
            'data_inicio' => sanitize_text_field( $_POST['data_inicio'] ),
            'data_termino' => sanitize_text_field( $_POST['data_termino'] ),
            'duracao_horas' => intval( $_POST['duracao_horas'] ),
            'cidade' => sanitize_text_field( $_POST['cidade'] ),
            'data_emissao' => sanitize_text_field( $_POST['data_emissao'] ),
            'numero_livro' => sanitize_text_field( $_POST['numero_livro'] ),
            'numero_pagina' => sanitize_text_field( $_POST['numero_pagina'] ),
            'numero_certificado' => sanitize_text_field( $_POST['numero_certificado'] ),
        ];

        if ( ! empty( $_POST['participant_id'] ) ) {
            \GCWP\Database\ParticipantsTable::update( intval( $_POST['participant_id'] ), $data );
            wp_send_json_success( __( 'Participante atualizado com sucesso.', 'gerador-certificados-wp' ) );
        } else {
            \GCWP\Database\ParticipantsTable::insert( $data );
            wp_send_json_success( __( 'Participante adicionado com sucesso.', 'gerador-certificados-wp' ) );
        }
    }

    public static function handle_generate_certificate() {
        check_ajax_referer( 'gcwp_public_actions', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'subscriber' ) ) {
            wp_die( __( 'Acesso negado.', 'gerador-certificados-wp' ) );
        }

        $participant_id = intval( $_POST['participant_id'] );
        $modelo_slug = sanitize_text_field( $_POST['modelo_slug'] );
        // $user_id = get_current_user_id();

        $participant = \GCWP\Database\ParticipantsTable::get( $participant_id );
        // if ( ! $participant || $participant['user_id'] != $user_id ) {
        if ( ! $participant ) {
            wp_send_json_error( __( 'Participante não encontrado.', 'gerador-certificados-wp' ) );
        }

        if ( empty( $modelo_slug ) ) {
            wp_send_json_error( __( 'Modelo não selecionado.', 'gerador-certificados-wp' ) );
        }

        $generator = new \GCWP\Core\CertificateGenerator();
        $result = $generator->generate_certificate( (object) $participant, $modelo_slug );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        } else {
            wp_send_json_success( [
                'message' => __( 'Certificado gerado com sucesso.', 'gerador-certificados-wp' ),
                'url' => $result['url'],
            ] );
        }
    }
}