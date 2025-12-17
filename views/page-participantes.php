<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Participantes', 'gerador-certificados-wp' ); ?></h1>
    <a href="<?php echo admin_url( 'admin.php?page=gcwp-participantes&action=add' ); ?>" class="page-title-action"><?php _e( 'Adicionar Novo', 'gerador-certificados-wp' ); ?></a>
    <hr class="wp-header-end">
    
    <?php
    if ( isset( $_GET['message'] ) ) {
        if ( $_GET['message'] === 'success_add' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Participante adicionado com sucesso!', 'gerador-certificados-wp' ) . '</p></div>';
        } elseif ( $_GET['message'] === 'success_update' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Participante atualizado com sucesso!', 'gerador-certificados-wp' ) . '</p></div>';
        } elseif ( $_GET['message'] === 'success_delete' ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Participante excluído com sucesso!', 'gerador-certificados-wp' ) . '</p></div>';
        } elseif ( $_GET['message'] === 'error_add' ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Erro ao adicionar participante. Verifique os dados e tente novamente.', 'gerador-certificados-wp' ) . '</p></div>';
        } elseif ( $_GET['message'] === 'error_update' ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Erro ao atualizar participante. Verifique os dados e tente novamente.', 'gerador-certificados-wp' ) . '</p></div>';
        }
    }
    ?>

    <form method="post">
        <?php
        global $gcwp_participants_list_table;
        if ( isset( $gcwp_participants_list_table ) ) {
            $participants_list_table = $gcwp_participants_list_table;
        } elseif ( class_exists( '\GCWP\Admin\ParticipantsListTable' ) ) {
            $participants_list_table = new \GCWP\Admin\ParticipantsListTable();
        } else {
            echo '<p>Erro: Classe ParticipantsListTable não encontrada.</p>';
            $participants_list_table = null;
        }
        if ( $participants_list_table ) {
            $participants_list_table->prepare_items();
            $participants_list_table->search_box( __( 'Pesquisar', 'gerador-certificados-wp' ), 'gcwp-participant-search' );
            $participants_list_table->display();
        }
        ?>
    </form>
</div>
