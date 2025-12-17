<?php

namespace GCWP\Admin;

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

    /**
     * Add admin menu pages for the plugin.
     */
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
            __( 'Modelos de Certificado', 'gerador-certificados-wp' ),
            '🖼️ ' . __( 'Modelos', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-certificados',
            [ $this, 'render_modelos_page' ]
        );
        
        add_submenu_page(
            null,
            __( 'Editar Modelo', 'gerador-certificados-wp' ),
            __( 'Editar Modelo', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-editar-modelo',
            [ $this, 'render_editar_modelo_page' ]
        );

        $participantes_page_hook = add_submenu_page(
            'gcwp-certificados',
            __( 'Participantes', 'gerador-certificados-wp' ),
            '👩‍🎓 ' . __( 'Participantes', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-participantes',
            [ $this, 'render_participantes_page' ]
        );
        
        add_action( "load-{$participantes_page_hook}", [ $this, 'load_participants_list_table' ] );

        add_submenu_page(
            'gcwp-certificados',
            __( 'Emissão de Certificados', 'gerador-certificados-wp' ),
            '🧾 ' . __( 'Emissão', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-emissao',
            [ $this, 'render_emissao_page' ]
        );

        add_submenu_page(
            'gcwp-certificados',
            __( 'Configurações', 'gerador-certificados-wp' ),
            '⚙️ ' . __( 'Configurações', 'gerador-certificados-wp' ),
            'manage_options',
            'gcwp-configuracoes',
            [ $this, 'render_configuracoes_page' ]
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'gcwp' ) === false ) {
            return;
        }

        wp_enqueue_style( 'gcwp-admin-style', GCWP_PLUGIN_URL . 'assets/css/admin.css', [], $this->version );

        $dependencies = [ 'jquery', 'jquery-ui-draggable' ];
        wp_enqueue_script( 'gcwp-admin-script', GCWP_PLUGIN_URL . 'assets/js/admin.js', $dependencies, $this->version, true );

        wp_localize_script('gcwp-admin-script', 'gcwp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gcwp_template_actions'),
            'confirm_reset' => __('TEM CERTEZA ABSOLUTA? Esta ação não pode ser desfeita.', 'gerador-certificados-wp')
        ]);
    }

    // Callbacks for pages
    public function render_modelos_page() {
        $message = '';
        $message_type = 'success';

        // Handle upload
        if ( isset( $_POST['gcwp_upload_nonce'] ) ) {
             $result = $this->handle_template_upload();
             if ( is_wp_error( $result ) ) {
                 $message = $result->get_error_message();
                 $message_type = 'error';
             } elseif ( is_string( $result ) ) {
                 $message = $result;
             }
        }
        
        include_once GCWP_PLUGIN_DIR . 'views/page-modelos.php';
    }

    private function handle_template_upload() {
        if ( ! isset( $_POST['gcwp_upload_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gcwp_upload_nonce'] ), 'gcwp_upload_template' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error('permission', __( 'Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp' ) );
        }

        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $modelo_nome = isset($_POST['modelo_nome']) ? sanitize_text_field($_POST['modelo_nome']) : '';
        if (empty($modelo_nome)) {
            return new \WP_Error('empty_name', __('O nome do modelo é obrigatório.', 'gerador-certificados-wp'));
        }

        if (empty($_FILES['modelo_frente']['name'])) {
            return new \WP_Error('empty_file', __('A imagem da frente do modelo é obrigatória.', 'gerador-certificados-wp'));
        }

        $upload_dir = wp_upload_dir();
        $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
        $modelo_slug = sanitize_title($modelo_nome);
        $modelo_path = $modelos_dir . '/' . $modelo_slug;

        if (file_exists($modelo_path)) {
            return new \WP_Error('exists', __('Já existe um modelo com este nome. Por favor, escolha outro.', 'gerador-certificados-wp'));
        }

        wp_mkdir_p($modelo_path);

        $upload_overrides = [ 'test_form' => false ];

        // Processar frente
        $frente_info = wp_handle_upload($_FILES['modelo_frente'], $upload_overrides);
        if ($frente_info && !isset($frente_info['error'])) {
            $ext = pathinfo($frente_info['file'], PATHINFO_EXTENSION);
            rename($frente_info['file'], $modelo_path . '/frente.' . $ext);
        } else {
            return new \WP_Error('upload_error', __('Erro ao enviar a imagem da frente: ', 'gerador-certificados-wp') . $frente_info['error']);
        }

        // Processar verso (opcional)
        $msg_suffix = '';
        if (!empty($_FILES['modelo_verso']['name'])) {
            $verso_info = wp_handle_upload($_FILES['modelo_verso'], $upload_overrides);
            if ($verso_info && !isset($verso_info['error'])) {
                $ext = pathinfo($verso_info['file'], PATHINFO_EXTENSION);
                rename($verso_info['file'], $modelo_path . '/verso.' . $ext);
            } else {
                $msg_suffix = ' ' . __('Porém, ocorreu um erro ao enviar a imagem do verso: ', 'gerador-certificados-wp') . $verso_info['error'];
            }
        }

        return sprintf(__('Modelo "%s" criado com sucesso!', 'gerador-certificados-wp'), $modelo_nome) . $msg_suffix;
    }

    public function render_editar_modelo_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'gerador-certificados-wp'));
        }

        $tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
        $file = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';

        if (empty($tipo) || empty($file)) {
            wp_die(__('Parâmetros inválidos.', 'gerador-certificados-wp'));
        }

        $upload_dir = wp_upload_dir();
        $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
        $file_path = $modelos_dir . '/' . $tipo . '/' . $file; // NOTE: This path construction seems wrong based on folder structure. It should be models_dir/model_slug/frente|verso.ext. But let's check the previous code.
        // The previous code had: $file_path = $modelos_dir . '/' . $tipo . '/' . $file;
        // Wait, looking at page-editar-modelo.php:
        // $tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : ''; // This seems to be the model slug actually?
        // $file = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : ''; // This is the filename like 'frente.jpg'
        // Ah, looking at page-modelos.php link generation (it was not there, it was a grid).
        // Let's assume the previous code logic was correct for now, but I suspect $tipo is actually the folder name (slug) and $file is the filename.
        // Let's re-read page-editar-modelo.php carefully.
        // $file_path = $modelos_dir . '/' . $tipo . '/' . $file;
        // Yes, $tipo is the subfolder (slug).
        
        $file_url = $upload_dir['baseurl'] . '/certificados/modelos/' . $tipo . '/' . $file;

        if (!file_exists($file_path)) {
            wp_die(__('Arquivo não encontrado.', 'gerador-certificados-wp'));
        }

        $error_message = '';
        
        // Handle edit
        if (isset($_POST['gcwp_edit_submit']) && isset($_POST['gcwp_edit_nonce']) && wp_verify_nonce($_POST['gcwp_edit_nonce'], 'gcwp_edit_template')) {
             if (!empty($_FILES['modelo_novo']['name'])) {
                if ( ! function_exists( 'wp_handle_upload' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                $upload_overrides = array('test_form' => false);
                $uploaded_file = wp_handle_upload($_FILES['modelo_novo'], $upload_overrides);
                
                if (!isset($uploaded_file['error'])) {
                    $new_file_path = $modelos_dir . '/' . $tipo . '/' . $file;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    if (rename($uploaded_file['file'], $new_file_path)) {
                        wp_redirect(admin_url('admin.php?page=gcwp-certificados&updated=1')); // Redirect to main page
                        exit;
                    } else {
                        $error_message = __('Não foi possível mover o arquivo para o diretório de destino.', 'gerador-certificados-wp');
                    }
                } else {
                    $error_message = $uploaded_file['error'];
                }
            } else {
                $error_message = __('Nenhum arquivo foi enviado.', 'gerador-certificados-wp');
            }
        }
        
        include_once GCWP_PLUGIN_DIR . 'views/page-editar-modelo.php';
    }

    public function render_participantes_page() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';

        if ( $action === 'add' || $action === 'edit' ) {
            $participant = null;
            if ( $action === 'edit' && isset( $_GET['participant'] ) ) {
                $participant = \GCWP\Database\ParticipantsTable::get( absint( $_GET['participant'] ) );
            }
            include GCWP_PLUGIN_DIR . 'views/form-participant.php';
        } else {
            include GCWP_PLUGIN_DIR . 'views/page-participantes.php';
        }
    }

    public function handle_save_participant() {
        if ( ! isset( $_POST['gcwp_participant_nonce'] ) || ! wp_verify_nonce( $_POST['gcwp_participant_nonce'], 'gcwp_save_participant_nonce' ) ) {
            wp_die( __( 'Ação inválida.', 'gerador-certificados-wp' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permissão negada.', 'gerador-certificados-wp' ) );
        }

        $data = [
            'nome_completo'      => sanitize_text_field( $_POST['nome_completo'] ),
            'email'              => sanitize_email( $_POST['email'] ),
            'curso'              => sanitize_text_field( $_POST['curso'] ),
            'data_inicio'        => sanitize_text_field( $_POST['data_inicio'] ),
            'data_termino'       => sanitize_text_field( $_POST['data_termino'] ),
            'duracao_horas'      => intval( $_POST['duracao_horas'] ),
            'cidade'             => sanitize_text_field( $_POST['cidade'] ),
            'data_emissao'       => sanitize_text_field( $_POST['data_emissao'] ),
            'numero_livro'       => sanitize_text_field( $_POST['numero_livro'] ),
            'numero_pagina'      => sanitize_text_field( $_POST['numero_pagina'] ),
            'numero_certificado' => sanitize_text_field( $_POST['numero_certificado'] ),
        ];

        if ( isset( $_POST['participant_action'] ) && $_POST['participant_action'] === 'edit' && isset( $_POST['participant'] ) ) {
            $id = absint( $_POST['participant'] );
            $result = \GCWP\Database\ParticipantsTable::update( $id, $data );
            $message = $result !== false ? 'success_update' : 'error_update';
        } else {
            $result = \GCWP\Database\ParticipantsTable::insert( $data );
            $message = $result !== false ? 'success_add' : 'error_add';
        }

        wp_redirect( admin_url( 'admin.php?page=gcwp-participantes&message=' . $message ) );
        exit;
    }

    public function render_emissao_page() {
        $message = '';
        $message_type = 'success';

        if ( isset( $_POST['gcp_generate_pdf'] ) ) {
             $result = $this->handle_generate_certificate();
             if ( is_wp_error( $result ) ) {
                 $message = $result->get_error_message();
                 $message_type = 'error';
             } else {
                 $message = sprintf( __( 'Certificado gerado com sucesso! <a href="%s" target="_blank">Clique aqui para baixar</a>.', 'gerador-certificados-wp' ), $result['url'] );
             }
        }

        // Fetch participants for the dropdown
        $participants = \GCWP\Database\ParticipantsTable::get_participants( 1000 ); // Limit 1000 for now

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

        // Fetch issued certificates
        $upload_dir = wp_upload_dir();
        $emitidos_dir = $upload_dir['basedir'] . '/certificados/emitidos';
        $emitidos_url = $upload_dir['baseurl'] . '/certificados/emitidos';
        $certificados_emitidos = [];

        if ( is_dir( $emitidos_dir ) ) {
            $files = glob( $emitidos_dir . '/*.pdf' );
            if ( $files ) {
                // Sort by date descending
                usort( $files, function( $a, $b ) {
                    return filemtime( $b ) - filemtime( $a );
                } );

                foreach ( $files as $file ) {
                    $filename = basename( $file );
                    // Try to extract participant name from filename: certificado_Nome_Do_Participante_timestamp.pdf
                    $parts = explode( '_', $filename );
                    $name_part = isset( $parts[1] ) ? $parts[1] : __( 'Desconhecido', 'gerador-certificados-wp' );
                    $name = str_replace( '-', ' ', $name_part );
                    
                    $certificados_emitidos[] = [
                        'filename' => $filename,
                        'url'      => $emitidos_url . '/' . $filename,
                        'date'     => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $file ) ),
                        'name'     => ucwords( $name )
                    ];
                }
            }
        }

        include_once GCWP_PLUGIN_DIR . 'views/page-emissao.php';
    }

    private function handle_generate_certificate() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'gcp_generate_pdf_nonce' ) ) {
            return new \WP_Error( 'nonce_error', __( 'Ação inválida (nonce).', 'gerador-certificados-wp' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return new \WP_Error( 'permission_error', __( 'Permissão negada.', 'gerador-certificados-wp' ) );
        }

        $participant_id = isset( $_POST['participant_id'] ) ? absint( $_POST['participant_id'] ) : 0;
        if ( ! $participant_id ) {
            return new \WP_Error( 'invalid_participant', __( 'Selecione um participante.', 'gerador-certificados-wp' ) );
        }

        $modelo_id = isset( $_POST['modelo_id'] ) ? sanitize_text_field( $_POST['modelo_id'] ) : '';
        if ( empty( $modelo_id ) ) {
            return new \WP_Error( 'invalid_modelo', __( 'Selecione um modelo.', 'gerador-certificados-wp' ) );
        }

        $participant_data = \GCWP\Database\ParticipantsTable::get( $participant_id );
        if ( ! $participant_data ) {
            return new \WP_Error( 'participant_not_found', __( 'Participante não encontrado.', 'gerador-certificados-wp' ) );
        }

        // Convert array to object because CertificateGenerator expects object
        $participant_obj = (object) $participant_data;

        $generator = new \GCWP\Core\CertificateGenerator();
        return $generator->generate_certificate( $participant_obj, $modelo_id );
    }

    public function render_configuracoes_page() {
        $success_message = '';
        
        if ( isset( $_POST['gcwp_save_config'] ) ) {
            if ( ! isset( $_POST['gcwp_config_nonce'] ) || ! wp_verify_nonce( $_POST['gcwp_config_nonce'], 'gcwp_save_config' ) ) {
                wp_die( __( 'Ação inválida.', 'gerador-certificados-wp' ) );
            }
            
            $fields = [
                'nome', 'curso', 'data_inicio', 'data_termino', 'duracao', 'local_data', // Frente
                'livro', 'pagina', 'registro' // Verso
            ];

            foreach ( $fields as $field ) {
                // Posição
                update_option( "gcwp_{$field}_pos_x", sanitize_text_field( $_POST["{$field}_pos_x"] ) );
                update_option( "gcwp_{$field}_pos_y", sanitize_text_field( $_POST["{$field}_pos_y"] ) );
                // Estilo
                update_option( "gcwp_{$field}_tamanho", sanitize_text_field( $_POST["{$field}_tamanho"] ) );
                update_option( "gcwp_{$field}_cor", sanitize_hex_color( $_POST["{$field}_cor"] ) );
                update_option( "gcwp_{$field}_fonte", sanitize_text_field( $_POST["{$field}_fonte"] ) );
                update_option( "gcwp_{$field}_align", sanitize_text_field( $_POST["{$field}_align"] ) );
                update_option( "gcwp_{$field}_negrito", isset( $_POST["{$field}_negrito"] ) ? '1' : '0' );
            }
            
            $success_message = __( 'Configurações salvas com sucesso!', 'gerador-certificados-wp' );
        }

        // Prepare data for view
        $fields_config = [
            'nome' => $this->get_field_settings('nome', ['pos_x' => 0, 'pos_y' => 57, 'tamanho' => 35, 'cor' => '#002e67', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'C']),
            'curso' => $this->get_field_settings('curso', ['pos_x' => 0, 'pos_y' => 90, 'tamanho' => 35, 'cor' => '#002e67', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'C']),
            'data_inicio' => $this->get_field_settings('data_inicio', ['pos_x' => 100, 'pos_y' => 131, 'tamanho' => 20, 'cor' => '#002e67', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'L']),
            'data_termino' => $this->get_field_settings('data_termino', ['pos_x' => 155, 'pos_y' => 131, 'tamanho' => 20, 'cor' => '#002e67', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'L']),
            'duracao' => $this->get_field_settings('duracao', ['pos_x' => 122, 'pos_y' => 143, 'tamanho' => 25, 'cor' => '#002e67', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'L']),
            'local_data' => $this->get_field_settings('local_data', ['pos_x' => 0, 'pos_y' => 160, 'tamanho' => 16, 'cor' => '#000000', 'negrito' => '0', 'fonte' => 'times', 'align' => 'C']),
            'livro' => $this->get_field_settings('livro', ['pos_x' => 93, 'pos_y' => 188, 'tamanho' => 12, 'cor' => '#000000', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'L']),
            'pagina' => $this->get_field_settings('pagina', ['pos_x' => 188, 'pos_y' => 188, 'tamanho' => 12, 'cor' => '#000000', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'L']),
            'registro' => $this->get_field_settings('registro', ['pos_x' => 260, 'pos_y' => 188, 'tamanho' => 12, 'cor' => '#000000', 'negrito' => '1', 'fonte' => 'ArialCEMTBlack', 'align' => 'L']),
        ];

        // Available fonts
        $font_dir = GCWP_PLUGIN_DIR . 'fonts/';
        $available_fonts = [];
        if (is_dir($font_dir)) {
            $font_files = glob($font_dir . '*.ttf');
            foreach ($font_files as $font_file) {
                $font_name = basename($font_file, '.ttf');
                $available_fonts[$font_name] = $font_name;
            }
        }

        // Preview URLs
        $modelo_selecionado_slug = get_option('gcwp_modelo_selecionado');
        $preview_frente_url = '';
        $preview_verso_url = '';
        if ($modelo_selecionado_slug) {
            $upload_dir = wp_upload_dir();
            $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
            $modelo_dir = $modelos_dir . '/' . $modelo_selecionado_slug;

            $frente_files = glob($modelo_dir . '/frente.*');
            $verso_files = glob($modelo_dir . '/verso.*');
            $preview_frente_url = !empty($frente_files) ? str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $frente_files[0]) : '';
            $preview_verso_url = !empty($verso_files) ? str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $verso_files[0]) : '';
        }

        include_once GCWP_PLUGIN_DIR . 'views/page-configuracoes.php';
    }

    private function get_field_settings($field, $defaults) {
        $settings = [];
        foreach ($defaults as $key => $default_value) {
            $option_name = "gcwp_{$field}_{$key}";
            $settings[$key] = get_option($option_name, $default_value);
        }
        return $settings;
    }

    public function load_participants_list_table() {
        if ( class_exists( '\GCWP\Admin\ParticipantsListTable' ) ) {
            // No global variable needed here if we instantiate it in the view, but the previous code used a global.
            // Let's keep the global for compatibility with the view which might use it.
            global $gcwp_participants_list_table;
            $gcwp_participants_list_table = new \GCWP\Admin\ParticipantsListTable();
            $gcwp_participants_list_table->screen = get_current_screen();
            $gcwp_participants_list_table->process_bulk_action();
        }
        add_screen_option( 'per_page', [
            'label' => __( 'Participantes por página', 'gerador-certificados-wp' ),
            'default' => 10,
            'option' => 'participants_per_page'
        ] );
    }

    /**
     * Handle the plugin reset action.
     */
    public function handle_reset_plugin() {
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

        \GCWP\Database\ParticipantsTable::truncate();

        $upload_dir = wp_upload_dir();
        $certificados_dir = $upload_dir['basedir'] . '/certificados';
        $emitidos_dir = $certificados_dir . '/emitidos';
        $modelos_dir = $certificados_dir . '/modelos';

        if (is_dir($emitidos_dir)) {
            \GCWP\Core\Utils::delete_dir_recursive($emitidos_dir);
        }
        if (is_dir($modelos_dir)) {
            \GCWP\Core\Utils::delete_dir_recursive($modelos_dir);
        }

        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gcwp_%'");

        wp_redirect(admin_url('admin.php?page=gcwp-configuracoes&reset=success'));
        exit;
    }
}
