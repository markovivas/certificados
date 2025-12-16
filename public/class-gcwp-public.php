<?php
/**
 * Public-facing functionality for Gerador de Certificados WP
 * Shortcodes and AJAX endpoints for subscribers (role: subscriber)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GCWP_Public {

    public static function init() {
        // Shortcodes
        add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );

        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

        // AJAX handlers (logged-in only)
        add_action( 'wp_ajax_gcwp_public_save_participant', array( __CLASS__, 'ajax_save_participant' ) );
        add_action( 'wp_ajax_gcwp_public_delete_participant', array( __CLASS__, 'ajax_delete_participant' ) );
        add_action( 'wp_ajax_gcwp_public_import_participants', array( __CLASS__, 'ajax_import_participants' ) );
        add_action( 'wp_ajax_gcwp_public_issue_certificate', array( __CLASS__, 'ajax_issue_certificate' ) );

        // Ensure participant table has user_id column to link participants to subscribers
        add_action( 'init', array( __CLASS__, 'maybe_add_user_id_column' ) );
    }

    public static function register_shortcodes() {
        add_shortcode( 'gerador_certificados_participantes', array( __CLASS__, 'shortcode_participantes' ) );
        add_shortcode( 'gerador_certificados_emissao', array( __CLASS__, 'shortcode_emissao' ) );
    }

    public static function enqueue_scripts() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        wp_enqueue_style( 'gcwp-public-style', GCWP_PLUGIN_URL . 'public/public.css', array(), GCWP_VERSION );
        wp_enqueue_script( 'gcwp-public-script', GCWP_PLUGIN_URL . 'public/public.js', array( 'jquery' ), GCWP_VERSION, true );
        wp_localize_script( 'gcwp-public-script', 'gcwp_public', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'gcwp_public_actions' ),
        ) );
    }

    /**
     * Ensure participants table has user_id column (idempotent)
     */
    public static function maybe_add_user_id_column() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gcwp_participantes';

        // Check if table exists
        $row = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table_name} LIKE %s", 'user_id' ) );
        if ( $row === null ) {
            // Add column
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN user_id bigint(20) DEFAULT 0 NOT NULL AFTER id" );
        }
    }

    private static function check_access() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'not_logged_in', __( 'Acesso negado. Faça login para continuar.', 'gerador-certificados-wp' ) );
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'subscriber', (array) $user->roles, true ) ) {
            return new WP_Error( 'forbidden', __( 'Acesso negado. Área exclusiva para assinantes.', 'gerador-certificados-wp' ) );
        }

        return $user;
    }

    /**
     * Shortcode: Gerenciamento de Participantes
     */
    public static function shortcode_participantes( $atts = array() ) {
        $access = self::check_access();
        if ( is_wp_error( $access ) ) {
            return '<div class="gcwp-alert gcwp-alert-error">' . esc_html( $access->get_error_message() ) . '</div>';
        }

        $user = $access;
        ob_start();
        ?>
        <div class="gcwp-public-wrap">
            <div class="gcwp-header">
                <h2><?php esc_html_e( 'Meus Participantes', 'gerador-certificados-wp' ); ?></h2>
                <div class="gcwp-actions-header">
                    <button class="button button-primary" id="gcwp-new-participant"><?php esc_html_e( 'Novo Participante', 'gerador-certificados-wp' ); ?></button>
                </div>
            </div>

            <div id="gcwp-message" role="status" aria-live="polite"></div>

            <div class="gcwp-grid">
                <div class="gcwp-panel gcwp-panel-form">
                    <form id="gcwp-participant-form" method="post">
                        <?php wp_nonce_field( 'gcwp_public_actions', 'gcwp_public_nonce' ); ?>
                        <input type="hidden" name="action" value="gcwp_public_save_participant" />
                        <input type="hidden" name="id" id="gcwp-participant-id" value="0" />

                        <div class="gcwp-form-row">
                            <label><?php esc_html_e( 'Nome completo', 'gerador-certificados-wp' ); ?>
                                <input type="text" name="nome_completo" id="nome_completo" required />
                            </label>
                        </div>

                        <div class="gcwp-form-row gcwp-form-three">
                            <label><?php esc_html_e( 'E-mail', 'gerador-certificados-wp' ); ?>
                                <input type="email" name="email" id="email" />
                            </label>
                            <label><?php esc_html_e( 'Curso', 'gerador-certificados-wp' ); ?>
                                <input type="text" name="curso" id="curso" />
                            </label>
                            <label><?php esc_html_e( 'Cidade', 'gerador-certificados-wp' ); ?>
                                <input type="text" name="cidade" id="cidade" />
                            </label>
                        </div>

                        <div class="gcwp-form-row gcwp-form-three">
                            <label><?php esc_html_e( 'Data Início', 'gerador-certificados-wp' ); ?>
                                <input type="date" name="data_inicio" id="data_inicio" />
                            </label>
                            <label><?php esc_html_e( 'Data Término', 'gerador-certificados-wp' ); ?>
                                <input type="date" name="data_termino" id="data_termino" />
                            </label>
                            <label><?php esc_html_e( 'Duração (horas)', 'gerador-certificados-wp' ); ?>
                                <input type="number" name="duracao_horas" id="duracao_horas" min="0" />
                            </label>
                        </div>

                        <div class="gcwp-form-row gcwp-form-three">
                            <label><?php esc_html_e( 'Data Emissão', 'gerador-certificados-wp' ); ?>
                                <input type="date" name="data_emissao" id="data_emissao" />
                            </label>
                            <label><?php esc_html_e( 'Número Livro', 'gerador-certificados-wp' ); ?>
                                <input type="text" name="numero_livro" id="numero_livro" />
                            </label>
                            <label><?php esc_html_e( 'Número Página', 'gerador-certificados-wp' ); ?>
                                <input type="text" name="numero_pagina" id="numero_pagina" />
                            </label>
                        </div>

                        <div class="gcwp-form-row">
                            <label><?php esc_html_e( 'Número Certificado', 'gerador-certificados-wp' ); ?>
                                <input type="text" name="numero_certificado" id="numero_certificado" />
                            </label>
                        </div>

                        <div class="gcwp-form-actions">
                            <button type="submit" class="button button-primary" id="gcwp-save-participant"><?php esc_html_e( 'Salvar', 'gerador-certificados-wp' ); ?></button>
                            <button type="button" class="button" id="gcwp-cancel-edit" style="display:none;"><?php esc_html_e( 'Cancelar', 'gerador-certificados-wp' ); ?></button>
                        </div>
                    </form>
                </div>

                <div class="gcwp-panel gcwp-panel-list">
                    <h3><?php esc_html_e( 'Lista de Participantes', 'gerador-certificados-wp' ); ?></h3>
                    <div id="gcwp-participants-list">
                        <?php echo self::render_participants_table( $user->ID ); // phpcs:ignore ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_participants_table( $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gcwp_participantes';

        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY nome_completo ASC", $user_id ), ARRAY_A );

        if ( empty( $rows ) ) {
            return '<p>' . esc_html__( 'Nenhum participante encontrado.', 'gerador-certificados-wp' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="gcwp-cards">
        <?php foreach ( $rows as $r ) : ?>
            <article class="gcwp-card" data-id="<?php echo esc_attr( $r['id'] ); ?>"
                     data-nome="<?php echo esc_attr( $r['nome_completo'] ); ?>"
                     data-email="<?php echo esc_attr( $r['email'] ); ?>"
                     data-curso="<?php echo esc_attr( $r['curso'] ); ?>"
                     data-data_inicio="<?php echo esc_attr( $r['data_inicio'] ); ?>"
                     data-data_termino="<?php echo esc_attr( $r['data_termino'] ); ?>"
                     data-duracao_horas="<?php echo esc_attr( $r['duracao_horas'] ); ?>"
                     data-cidade="<?php echo esc_attr( $r['cidade'] ); ?>"
                     data-data_emissao="<?php echo esc_attr( $r['data_emissao'] ); ?>"
                     data-numero_livro="<?php echo esc_attr( $r['numero_livro'] ); ?>"
                     data-numero_pagina="<?php echo esc_attr( $r['numero_pagina'] ); ?>"
                     data-numero_certificado="<?php echo esc_attr( $r['numero_certificado'] ); ?>">
                <h4 class="gcwp-card-title"><?php echo esc_html( $r['nome_completo'] ); ?></h4>
                <div class="gcwp-card-meta">
                    <span class="gcwp-card-course"><?php echo esc_html( $r['curso'] ); ?></span>
                    <span class="gcwp-card-email"><?php echo esc_html( $r['email'] ); ?></span>
                </div>
                <div class="gcwp-card-actions">
                    <button class="button gcwp-edit" data-id="<?php echo esc_attr( $r['id'] ); ?>"><?php esc_html_e( 'Editar', 'gerador-certificados-wp' ); ?></button>
                    <button class="button gcwp-delete" data-id="<?php echo esc_attr( $r['id'] ); ?>"><?php esc_html_e( 'Excluir', 'gerador-certificados-wp' ); ?></button>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Emissão de certificados
     */
    public static function shortcode_emissao( $atts = array() ) {
        $access = self::check_access();
        if ( is_wp_error( $access ) ) {
            return '<div class="gcwp-alert gcwp-alert-error">' . esc_html( $access->get_error_message() ) . '</div>';
        }

        $user = $access;
        global $wpdb;
        $table_name = $wpdb->prefix . 'gcwp_participantes';
        $participants = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY nome_completo ASC", $user->ID ), ARRAY_A );

        // List available models (read-only)
        $upload_dir = wp_upload_dir();
        $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
        $models = array();
        if ( is_dir( $modelos_dir ) ) {
            $items = scandir( $modelos_dir );
            foreach ( $items as $it ) {
                if ( $it === '.' || $it === '..' ) {
                    continue;
                }
                if ( is_dir( $modelos_dir . '/' . $it ) ) {
                    $models[] = $it;
                }
            }
        }

        ob_start();
        ?>
        <div class="gcwp-public-wrap">
            <h2><?php esc_html_e( 'Emissão de Certificados', 'gerador-certificados-wp' ); ?></h2>
            <div id="gcwp-emissao-message"></div>

            <form id="gcwp-emissao-form" method="post">
                <?php wp_nonce_field( 'gcwp_public_actions', 'gcwp_public_nonce' ); ?>
                <input type="hidden" name="action" value="gcwp_public_issue_certificate" />

                <p>
                    <label><?php esc_html_e( 'Participante', 'gerador-certificados-wp' ); ?><br />
                        <select name="participante_id" required>
                            <option value=""><?php esc_html_e( 'Selecione um participante', 'gerador-certificados-wp' ); ?></option>
                            <?php foreach ( $participants as $p ) : ?>
                                <option value="<?php echo esc_attr( $p['id'] ); ?>"><?php echo esc_html( $p['nome_completo'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </p>

                <p>
                    <label><?php esc_html_e( 'Modelo', 'gerador-certificados-wp' ); ?><br />
                        <select name="modelo_id" required>
                            <option value=""><?php esc_html_e( 'Selecione um modelo', 'gerador-certificados-wp' ); ?></option>
                            <?php foreach ( $models as $m ) : ?>
                                <option value="<?php echo esc_attr( $m ); ?>"><?php echo esc_html( $m ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </p>

                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Gerar certificado', 'gerador-certificados-wp' ); ?></button>
                </p>
            </form>

            <div id="gcwp-emissao-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ---------------- AJAX Handlers ---------------- */

    public static function ajax_save_participant() {
        // Check nonce and permissions
        if ( ! isset( $_POST['gcwp_public_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gcwp_public_nonce'] ) ), 'gcwp_public_actions' ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro de segurança (nonce).', 'gerador-certificados-wp' ) ) );
        }

        $access = self::check_access();
        if ( is_wp_error( $access ) ) {
            wp_send_json_error( array( 'message' => $access->get_error_message() ) );
        }
        $user = $access;

        global $wpdb;
        $table_name = $wpdb->prefix . 'gcwp_participantes';

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $data = array(
            'nome_completo'      => isset( $_POST['nome_completo'] ) ? sanitize_text_field( wp_unslash( $_POST['nome_completo'] ) ) : '',
            'email'              => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
            'curso'              => isset( $_POST['curso'] ) ? sanitize_text_field( wp_unslash( $_POST['curso'] ) ) : '',
            'data_inicio'        => isset( $_POST['data_inicio'] ) ? sanitize_text_field( wp_unslash( $_POST['data_inicio'] ) ) : '0000-00-00',
            'data_termino'       => isset( $_POST['data_termino'] ) ? sanitize_text_field( wp_unslash( $_POST['data_termino'] ) ) : '0000-00-00',
            'duracao_horas'      => isset( $_POST['duracao_horas'] ) ? intval( $_POST['duracao_horas'] ) : 0,
            'cidade'             => isset( $_POST['cidade'] ) ? sanitize_text_field( wp_unslash( $_POST['cidade'] ) ) : '',
            'data_emissao'       => isset( $_POST['data_emissao'] ) ? sanitize_text_field( wp_unslash( $_POST['data_emissao'] ) ) : '0000-00-00',
            'numero_livro'       => isset( $_POST['numero_livro'] ) ? sanitize_text_field( wp_unslash( $_POST['numero_livro'] ) ) : '',
            'numero_pagina'      => isset( $_POST['numero_pagina'] ) ? sanitize_text_field( wp_unslash( $_POST['numero_pagina'] ) ) : '',
            'numero_certificado' => isset( $_POST['numero_certificado'] ) ? sanitize_text_field( wp_unslash( $_POST['numero_certificado'] ) ) : '',
        );

        if ( $id > 0 ) {
            // Verify participant belongs to user
            $owner = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table_name} WHERE id = %d", $id ) );
            if ( intval( $owner ) !== intval( $user->ID ) ) {
                wp_send_json_error( array( 'message' => __( 'Permissão negada para editar este participante.', 'gerador-certificados-wp' ) ) );
            }

            $wpdb->update( $table_name, $data, array( 'id' => $id ), array_fill( 0, count( $data ), '%s' ), array( '%d' ) );
            wp_send_json_success( array( 'message' => __( 'Participante atualizado com sucesso.', 'gerador-certificados-wp' ) ) );
        } else {
            $data['user_id'] = $user->ID;
            $format = array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d' );
            $wpdb->insert( $table_name, $data );
            wp_send_json_success( array( 'message' => __( 'Participante criado com sucesso.', 'gerador-certificados-wp' ) ) );
        }
    }

    public static function ajax_delete_participant() {
        if ( ! isset( $_POST['gcwp_public_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gcwp_public_nonce'] ) ), 'gcwp_public_actions' ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro de segurança (nonce).', 'gerador-certificados-wp' ) ) );
        }

        $access = self::check_access();
        if ( is_wp_error( $access ) ) {
            wp_send_json_error( array( 'message' => $access->get_error_message() ) );
        }
        $user = $access;

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'ID inválido.', 'gerador-certificados-wp' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'gcwp_participantes';
        $owner = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table_name} WHERE id = %d", $id ) );
        if ( intval( $owner ) !== intval( $user->ID ) ) {
            wp_send_json_error( array( 'message' => __( 'Permissão negada para excluir este participante.', 'gerador-certificados-wp' ) ) );
        }

        $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
        wp_send_json_success( array( 'message' => __( 'Participante excluído.', 'gerador-certificados-wp' ) ) );
    }

    public static function ajax_import_participants() {
        if ( ! isset( $_POST['gcwp_public_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gcwp_public_nonce'] ) ), 'gcwp_public_actions' ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro de segurança (nonce).', 'gerador-certificados-wp' ) ) );
        }

        $access = self::check_access();
        if ( is_wp_error( $access ) ) {
            wp_send_json_error( array( 'message' => $access->get_error_message() ) );
        }
        $user = $access;

        if ( empty( $_FILES['csv_file'] ) || ! is_uploaded_file( $_FILES['csv_file']['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Arquivo CSV não enviado.', 'gerador-certificados-wp' ) ) );
        }

        $tmp = $_FILES['csv_file']['tmp_name'];
        $handle = fopen( $tmp, 'r' );
        if ( ! $handle ) {
            wp_send_json_error( array( 'message' => __( 'Não foi possível abrir o arquivo.', 'gerador-certificados-wp' ) ) );
        }

        $header = fgetcsv( $handle );
        if ( ! $header ) {
            fclose( $handle );
            wp_send_json_error( array( 'message' => __( 'CSV vazio ou inválido.', 'gerador-certificados-wp' ) ) );
        }

        $expected = array( 'nome_completo','email','curso','data_inicio','data_termino','duracao_horas','cidade','data_emissao','numero_livro','numero_pagina','numero_certificado' );
        $map = array();
        foreach ( $header as $i => $col ) {
            $col = strtolower( trim( $col ) );
            if ( in_array( $col, $expected, true ) ) {
                $map[ $i ] = $col;
            }
        }

        if ( empty( $map ) ) {
            fclose( $handle );
            wp_send_json_error( array( 'message' => __( 'Cabeçalho CSV não contém colunas esperadas.', 'gerador-certificados-wp' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'gcwp_participantes';
        $count = 0;
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $data = array();
            foreach ( $map as $idx => $col_name ) {
                $value = isset( $row[ $idx ] ) ? $row[ $idx ] : '';
                $data[ $col_name ] = sanitize_text_field( $value );
            }
            $data['user_id'] = $user->ID;
            // Ensure defaults
            foreach ( $expected as $k ) {
                if ( ! isset( $data[ $k ] ) ) {
                    $data[ $k ] = '';
                }
            }
            $wpdb->insert( $table_name, $data );
            $count++;
        }
        fclose( $handle );

        wp_send_json_success( array( 'message' => sprintf( _n( '%d participante importado.', '%d participantes importados.', $count, 'gerador-certificados-wp' ), $count ) ) );
    }

    public static function ajax_issue_certificate() {
        if ( ! isset( $_POST['gcwp_public_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gcwp_public_nonce'] ) ), 'gcwp_public_actions' ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro de segurança (nonce).', 'gerador-certificados-wp' ) ) );
        }

        $access = self::check_access();
        if ( is_wp_error( $access ) ) {
            wp_send_json_error( array( 'message' => $access->get_error_message() ) );
        }
        $user = $access;

        $participante_id = isset( $_POST['participante_id'] ) ? intval( $_POST['participante_id'] ) : 0;
        $modelo_id = isset( $_POST['modelo_id'] ) ? sanitize_text_field( wp_unslash( $_POST['modelo_id'] ) ) : '';

        if ( $participante_id <= 0 || empty( $modelo_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Parâmetros inválidos.', 'gerador-certificados-wp' ) ) );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'gcwp_participantes';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $participante_id ), ARRAY_A );
        if ( ! $row ) {
            wp_send_json_error( array( 'message' => __( 'Participante não encontrado.', 'gerador-certificados-wp' ) ) );
        }
        if ( intval( $row['user_id'] ) !== intval( $user->ID ) ) {
            wp_send_json_error( array( 'message' => __( 'Permissão negada para emitir certificado para este participante.', 'gerador-certificados-wp' ) ) );
        }

        // Load generator class (already required in main plugin file)
        if ( ! class_exists( 'GCWP_Certificate_Generator' ) ) {
            wp_send_json_error( array( 'message' => __( 'Gerador de certificados não disponível.', 'gerador-certificados-wp' ) ) );
        }

        $generator = new GCWP_Certificate_Generator();

        // Convert $row to object as expected by generator
        $participant_obj = (object) $row;

        $result = $generator->generate_certificate( $participant_obj, $modelo_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Expected $result to contain 'path' and 'url'
        wp_send_json_success( array( 'message' => __( 'Certificado gerado com sucesso.', 'gerador-certificados-wp' ), 'result' => $result ) );
    }

}
