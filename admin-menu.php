<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add admin menu pages for the plugin.
 */
function gcwp_add_admin_menu() {
    add_menu_page(
        __( 'Gerador de Certificados', 'gerador-certificados-wp' ),
        __( 'Certificados', 'gerador-certificados-wp' ),
        'manage_options',
        'gcwp-certificados',
        'gcwp_render_modelos_page', // Callback function for the main page
        'dashicons-awards',
        30
    );

    add_submenu_page(
        'gcwp-certificados',
        __( 'Modelos de Certificado', 'gerador-certificados-wp' ),
        '🖼️ ' . __( 'Modelos', 'gerador-certificados-wp' ),
        'manage_options',
        'gcwp-certificados', // Same slug as parent to make it the default page
        'gcwp_render_modelos_page'
    );
    
    // Página de edição de modelos (oculta do menu)
    add_submenu_page(
        null, // não mostrar no menu
        __( 'Editar Modelo', 'gerador-certificados-wp' ),
        __( 'Editar Modelo', 'gerador-certificados-wp' ),
        'manage_options',
        'gcwp-editar-modelo',
        'gcwp_render_editar_modelo_page'
    );

    // Adiciona a página de participantes e captura o hook retornado
    $participantes_page_hook = add_submenu_page(
        'gcwp-certificados',
        __( 'Participantes', 'gerador-certificados-wp' ),
        '👩‍🎓 ' . __( 'Participantes', 'gerador-certificados-wp' ),
        'manage_options',
        'gcwp-participantes',
        'gcwp_render_participantes_page'
    );
    // Usa o hook para carregar a List Table apenas nessa página
    add_action( "load-{$participantes_page_hook}", 'gcwp_load_participants_list_table' );

    add_submenu_page(
        'gcwp-certificados',
        __( 'Emissão de Certificados', 'gerador-certificados-wp' ),
        '🧾 ' . __( 'Emissão', 'gerador-certificados-wp' ),
        'manage_options',
        'gcwp-emissao',
        'gcwp_render_emissao_page'
    );

    add_submenu_page(
        'gcwp-certificados',
        __( 'Configurações', 'gerador-certificados-wp' ),
        '⚙️ ' . __( 'Configurações', 'gerador-certificados-wp' ),
        'manage_options',
        'gcwp-configuracoes',
        'gcwp_render_configuracoes_page'
    );
}
add_action( 'admin_menu', 'gcwp_add_admin_menu' );

/**
 * Renders the "Modelos" page.
 */
function gcwp_render_modelos_page() {
    // A lógica de upload será movida para o admin_init
    include_once GCWP_PLUGIN_DIR . 'page-modelos.php';
}

/**
 * Renders the "Editar Modelo" page.
 */
function gcwp_render_editar_modelo_page() {
    include_once GCWP_PLUGIN_DIR . 'page-editar-modelo.php';
}

/**
 * Renders the "Participantes" page.
 */
function gcwp_render_participantes_page() {
    // A lógica de processamento foi movida para o admin_init
    include_once GCWP_PLUGIN_DIR . 'page-participantes.php';
}

/**
 * Renders the "Emissão" page.
 */
function gcwp_render_emissao_page() {
    include_once GCWP_PLUGIN_DIR . 'page-emissao.php';
}

/**
 * Renders the "Configurações" page.
 */
function gcwp_render_configuracoes_page() {
    include_once GCWP_PLUGIN_DIR . 'page-configuracoes.php';
}

/**
 * Instantiates and prepares the items for the Participants List Table.
 * This function is hooked to the specific admin page load action.
 */
function gcwp_load_participants_list_table() {
    if ( class_exists( 'GCWP_Participants_List_Table' ) ) {
        global $gcwp_participants_list_table;
        $gcwp_participants_list_table = new GCWP_Participants_List_Table();
        // A preparação dos itens (prepare_items) já é chamada dentro da classe,
        // mas podemos garantir que a lógica de ações (excluir) seja processada aqui.
        $gcwp_participants_list_table->process_bulk_action();
    }
}