<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap gcp-wrap">
    <h1><span class="dashicons dashicons-awards"></span> <?php _e('Emissão de Certificados', 'gerador-certificados-wp'); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
            <p><?php echo $message; // Allow HTML for link ?></p>
        </div>
    <?php endif; ?>

    <div class="gcp-emissao-container">
        <div class="gcp-emissao-main">
            <div class="gcp-card">
                <h2><?php _e('Histórico de Emissões', 'gerador-certificados-wp'); ?></h2>
                <p><?php _e('Abaixo está a lista de certificados já gerados.', 'gerador-certificados-wp'); ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Aluno', 'gerador-certificados-wp'); ?></th>
                            <th><?php _e('Data de Emissão', 'gerador-certificados-wp'); ?></th>
                            <th><?php _e('Arquivo', 'gerador-certificados-wp'); ?></th>
                            <th><?php _e('Ações', 'gerador-certificados-wp'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $certificados_emitidos ) ) : ?>
                            <?php foreach ( $certificados_emitidos as $certificado ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $certificado['name'] ); ?></td>
                                    <td><?php echo esc_html( $certificado['date'] ); ?></td>
                                    <td><?php echo esc_html( $certificado['filename'] ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( $certificado['url'] ); ?>" class="button button-secondary" target="_blank">
                                            <span class="dashicons dashicons-download"></span> <?php _e('Baixar', 'gerador-certificados-wp'); ?>
                                        </a>
                                        <button class="button button-secondary reenviar-certificado" data-file="<?php echo esc_attr( $certificado['filename'] ); ?>">
                                            <span class="dashicons dashicons-email"></span> <?php _e('Reenviar E-mail', 'gerador-certificados-wp'); ?>
                                        </button>
                                        <button class="button button-link-delete excluir-certificado" data-file="<?php echo esc_attr( $certificado['filename'] ); ?>">
                                            <span class="dashicons dashicons-trash"></span> <?php _e('Excluir', 'gerador-certificados-wp'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4"><?php _e('Nenhum certificado emitido encontrado.', 'gerador-certificados-wp'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="gcp-emissao-sidebar">
            <div class="gcp-card">
                <h2><?php _e('Gerar Novo Certificado', 'gerador-certificados-wp'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'gcp_generate_pdf_nonce' ); ?>
                    <p><?php _e('Selecione o participante para gerar um novo certificado em PDF.', 'gerador-certificados-wp'); ?></p>
                    <select name="participant_id" class="widefat" style="margin-bottom: 15px;">
                        <option value=""><?php _e('Selecione um participante...', 'gerador-certificados-wp'); ?></option>
                        <?php if ( ! empty( $participants ) ) : ?>
                            <?php foreach ( $participants as $participant ) : ?>
                                <option value="<?php echo esc_attr( $participant['id'] ); ?>">
                                    <?php echo esc_html( $participant['nome_completo'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <select name="modelo_id" class="widefat" style="margin-bottom: 15px;">
                        <option value=""><?php _e('Selecione um modelo...', 'gerador-certificados-wp'); ?></option>
                        <?php if ( ! empty( $modelos ) ) : ?>
                            <?php foreach ( $modelos as $slug => $nome ) : ?>
                                <option value="<?php echo esc_attr( $slug ); ?>">
                                    <?php echo esc_html( $nome ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php submit_button(__('Gerar PDF', 'gerador-certificados-wp'), 'primary', 'gcp_generate_pdf', true); ?>
                    <p class="description"><?php _e('O PDF será gerado com base no modelo e dados cadastrados.', 'gerador-certificados-wp'); ?></p>
                </form>
            </div>
        </div>
    </div>
</div>
