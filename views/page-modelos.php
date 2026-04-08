<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap gcwp-admin-page">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
    <?php endif; ?>

    <p><?php esc_html_e( 'Cada modelo tem a imagem da frente, opcionalmente a do verso, e um conjunto proprio de variaveis posicionadas.', 'gerador-certificados-wp' ); ?></p>

    <div class="gcwp-models-layout">
        <div class="gcwp-card gcwp-models-list">
            <div class="gcwp-section-heading">
                <h2><?php esc_html_e( 'Modelos criados', 'gerador-certificados-wp' ); ?></h2>
            </div>

            <?php if ( ! empty( $templates ) ) : ?>
                <div class="gcwp-model-grid">
                    <?php foreach ( $templates as $slug => $template ) : ?>
                        <article class="gcwp-model-card <?php echo $selected_template === $slug ? 'is-selected' : ''; ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
                            <div class="gcwp-model-thumb">
                                <?php if ( ! empty( $template['front_url'] ) ) : ?>
                                    <img src="<?php echo esc_url( $template['front_url'] ); ?>" alt="<?php echo esc_attr( $template['name'] ); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="gcwp-model-body">
                                <h3><?php echo esc_html( $template['name'] ); ?></h3>
                                <p><?php echo esc_html( count( $template['fields'] ) ); ?> <?php esc_html_e( 'campos configurados', 'gerador-certificados-wp' ); ?></p>
                                <div class="gcwp-model-actions">
                                    <a href="#" class="button button-secondary select-modelo"><?php echo $selected_template === $slug ? esc_html__( 'Selecionado', 'gerador-certificados-wp' ) : esc_html__( 'Selecionar', 'gerador-certificados-wp' ); ?></a>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=gcwp-editar-modelo&modelo=' . $slug ) ); ?>" class="button button-primary"><?php esc_html_e( 'Editar modelo', 'gerador-certificados-wp' ); ?></a>
                                    <a href="#" class="button rename-modelo"><?php esc_html_e( 'Renomear', 'gerador-certificados-wp' ); ?></a>
                                    <a href="#" class="button-link-delete delete-modelo"><?php esc_html_e( 'Apagar', 'gerador-certificados-wp' ); ?></a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e( 'Nenhum modelo criado ainda.', 'gerador-certificados-wp' ); ?></p>
            <?php endif; ?>
        </div>

        <div class="gcwp-card gcwp-model-create">
            <div class="gcwp-section-heading">
                <h2><?php esc_html_e( 'Novo modelo', 'gerador-certificados-wp' ); ?></h2>
            </div>

            <form method="post" enctype="multipart/form-data" class="gcwp-stacked-form">
                <?php wp_nonce_field( 'gcwp_upload_template', 'gcwp_upload_nonce' ); ?>

                <label for="modelo_nome"><?php esc_html_e( 'Nome do modelo', 'gerador-certificados-wp' ); ?></label>
                <input type="text" name="modelo_nome" id="modelo_nome" class="regular-text" required>

                <label for="modelo_frente"><?php esc_html_e( 'Imagem da frente', 'gerador-certificados-wp' ); ?></label>
                <input type="file" name="modelo_frente" id="modelo_frente" accept="image/*" required>

                <label for="modelo_verso"><?php esc_html_e( 'Imagem do verso', 'gerador-certificados-wp' ); ?></label>
                <input type="file" name="modelo_verso" id="modelo_verso" accept="image/*">

                <p class="description"><?php esc_html_e( 'Depois de criar, voce abre o editor do modelo para cadastrar variaveis, posicionar textos, definir fonte, cor, tamanho e alinhamento.', 'gerador-certificados-wp' ); ?></p>

                <?php submit_button( __( 'Criar modelo', 'gerador-certificados-wp' ) ); ?>
            </form>
        </div>
    </div>
</div>
