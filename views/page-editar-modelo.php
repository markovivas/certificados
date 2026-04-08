<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sample_json = wp_json_encode( $template['fields'] );
?>

<div class="wrap gcwp-admin-page">
    <h1><?php echo esc_html( $template['name'] ); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="gcwp-model-editor">
        <?php wp_nonce_field( 'gcwp_edit_template', 'gcwp_edit_nonce' ); ?>

        <div class="gcwp-model-editor-main">
            <div class="gcwp-card">
                <div class="gcwp-section-heading">
                    <h2><?php esc_html_e( 'Dados do modelo', 'gerador-certificados-wp' ); ?></h2>
                </div>

                <div class="gcwp-two-columns">
                    <div class="gcwp-form-control">
                        <label for="modelo_nome"><?php esc_html_e( 'Nome do modelo', 'gerador-certificados-wp' ); ?></label>
                        <input type="text" id="modelo_nome" name="modelo_nome" value="<?php echo esc_attr( $template['name'] ); ?>" class="regular-text" required>
                    </div>
                    <div class="gcwp-form-control">
                        <label for="modelo_frente"><?php esc_html_e( 'Substituir frente', 'gerador-certificados-wp' ); ?></label>
                        <input type="file" id="modelo_frente" name="modelo_frente" accept="image/*">
                    </div>
                    <div class="gcwp-form-control">
                        <label for="modelo_verso"><?php esc_html_e( 'Substituir verso', 'gerador-certificados-wp' ); ?></label>
                        <input type="file" id="modelo_verso" name="modelo_verso" accept="image/*">
                    </div>
                </div>
            </div>

            <div class="gcwp-card">
                <div class="gcwp-section-heading">
                    <h2><?php esc_html_e( 'Campos variaveis', 'gerador-certificados-wp' ); ?></h2>
                    <button type="button" id="gcwp-add-field" class="button button-secondary"><?php esc_html_e( 'Adicionar campo', 'gerador-certificados-wp' ); ?></button>
                </div>

                <p class="description"><?php esc_html_e( 'Cada campo vira uma variavel no formulario publico e no PDF. Arraste os textos na prancheta para ajustar a posicao visualmente.', 'gerador-certificados-wp' ); ?></p>

                <div id="gcwp-field-list" class="gcwp-field-list" data-fields="<?php echo esc_attr( $sample_json ); ?>">
                    <?php foreach ( $template['fields'] as $index => $field ) : ?>
                        <?php include GCWP_PLUGIN_DIR . 'views/partials/model-field-row.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <aside class="gcwp-model-editor-side">
            <div class="gcwp-card">
                <div class="gcwp-section-heading">
                    <h2><?php esc_html_e( 'Preview da frente', 'gerador-certificados-wp' ); ?></h2>
                </div>
                <div class="gcwp-preview-board" id="gcwp-preview-front" data-page="front" style="background-image:url('<?php echo esc_url( $template['front_url'] ); ?>');"></div>
            </div>

            <div class="gcwp-card">
                <div class="gcwp-section-heading">
                    <h2><?php esc_html_e( 'Preview do verso', 'gerador-certificados-wp' ); ?></h2>
                </div>
                <div class="gcwp-preview-board" id="gcwp-preview-back" data-page="back" style="<?php echo ! empty( $template['back_url'] ) ? 'background-image:url(' . esc_url( $template['back_url'] ) . ');' : ''; ?>">
                    <?php if ( empty( $template['back_url'] ) ) : ?>
                        <div class="gcwp-preview-empty"><?php esc_html_e( 'Este modelo ainda nao tem verso.', 'gerador-certificados-wp' ); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="gcwp-card">
                <?php submit_button( __( 'Salvar modelo', 'gerador-certificados-wp' ), 'primary', 'gcwp_save_model', false ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=gcwp-certificados' ) ); ?>" class="button"><?php esc_html_e( 'Voltar', 'gerador-certificados-wp' ); ?></a>
            </div>
        </aside>
    </form>
</div>
