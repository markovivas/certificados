<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// A tabela agora é preparada no admin_init e armazenada em uma global
global $gcwp_participants_list_table;

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=gcwp-participantes&action=add' ); ?>" class="page-title-action">Adicionar Novo</a>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['action'] ) && ( $_GET['action'] === 'add' || $_GET['action'] === 'edit' ) ) :
        // Lógica para formulário de Adicionar/Editar
        include_once GCWP_PLUGIN_DIR . 'form-participant.php';
    else : ?>
        <p><?php esc_html_e( 'Gerencie os participantes dos cursos aqui. Você pode adicionar, editar, excluir ou importar em massa via CSV.', 'gerador-certificados-wp' ); ?></p>

        <form method="post">
            <input type="hidden" name="page" value="gcwp_participants_list_table">
            <?php
            $gcwp_participants_list_table->search_box( 'Pesquisar participantes', 'participant' );
            $gcwp_participants_list_table->display();
            ?>
        </form>
    <?php endif; ?>
</div>
<style>
    /* Pequenos ajustes para o formulário */
    .form-table th {
        width: 150px;
    }
    .form-table input[type="date"] {
        width: 25em;
    }
</style>