<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap gcwp-admin-page">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="gcwp-card">
        <h2><?php esc_html_e( 'Nova estrutura do plugin', 'gerador-certificados-wp' ); ?></h2>
        <p><?php esc_html_e( 'As configuracoes visuais agora ficam dentro de cada modelo. Para ajustar campos, abra o editor do modelo desejado.', 'gerador-certificados-wp' ); ?></p>
        <?php if ( $template ) : ?>
            <p>
                <?php esc_html_e( 'Modelo padrao atual:', 'gerador-certificados-wp' ); ?>
                <strong><?php echo esc_html( $template['name'] ); ?></strong>
            </p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gcwp-editar-modelo&modelo=' . $template['slug'] ) ); ?>" class="button button-primary"><?php esc_html_e( 'Editar modelo selecionado', 'gerador-certificados-wp' ); ?></a>
            </p>
        <?php else : ?>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gcwp-certificados' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Criar ou selecionar um modelo', 'gerador-certificados-wp' ); ?></a>
            </p>
        <?php endif; ?>
    </div>

    <div class="gcwp-card">
        <h2><?php esc_html_e( 'Resetar plugin', 'gerador-certificados-wp' ); ?></h2>
        <div class="notice notice-error inline">
            <p><?php esc_html_e( 'Esta acao apagará participantes, modelos, certificados emitidos e configuracoes do plugin.', 'gerador-certificados-wp' ); ?></p>
        </div>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gcwp-reset-form">
            <input type="hidden" name="action" value="gcwp_reset_plugin">
            <?php wp_nonce_field( 'gcwp_reset_plugin_nonce', 'gcwp_reset_nonce' ); ?>
            <p>
                <label for="gcwp_reset_confirm">
                    <input type="checkbox" name="gcwp_reset_confirm" id="gcwp_reset_confirm">
                    <?php esc_html_e( 'Sim, eu entendo que todos os dados serao apagados.', 'gerador-certificados-wp' ); ?>
                </label>
            </p>
            <p class="submit">
                <input type="submit" class="button button-danger" value="<?php esc_attr_e( 'Resetar plugin', 'gerador-certificados-wp' ); ?>" disabled>
            </p>
        </form>
    </div>
</div>
