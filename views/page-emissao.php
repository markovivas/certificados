<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap gcp-wrap">
    <h1><span class="dashicons dashicons-awards"></span> <?php esc_html_e( 'Emissao de Certificados', 'gerador-certificados-wp' ); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
    <?php endif; ?>

    <div class="gcp-emissao-container">
        <div class="gcp-emissao-main">
            <div class="gcp-card">
                <h2><?php esc_html_e( 'Historico de Emissoes', 'gerador-certificados-wp' ); ?></h2>
                <p><?php esc_html_e( 'Abaixo esta a lista de certificados ja gerados.', 'gerador-certificados-wp' ); ?></p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Aluno', 'gerador-certificados-wp' ); ?></th>
                            <th><?php esc_html_e( 'Data de Emissao', 'gerador-certificados-wp' ); ?></th>
                            <th><?php esc_html_e( 'Arquivo', 'gerador-certificados-wp' ); ?></th>
                            <th><?php esc_html_e( 'Acoes', 'gerador-certificados-wp' ); ?></th>
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
                                        <a href="<?php echo esc_url( $certificado['url'] ); ?>" class="button button-secondary" target="_blank"><?php esc_html_e( 'Baixar', 'gerador-certificados-wp' ); ?></a>
                                        <button class="button button-secondary reenviar-certificado" data-file="<?php echo esc_attr( $certificado['filename'] ); ?>"><?php esc_html_e( 'Reenviar E-mail', 'gerador-certificados-wp' ); ?></button>
                                        <button class="button button-link-delete excluir-certificado" data-file="<?php echo esc_attr( $certificado['filename'] ); ?>"><?php esc_html_e( 'Excluir', 'gerador-certificados-wp' ); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4"><?php esc_html_e( 'Nenhum certificado emitido encontrado.', 'gerador-certificados-wp' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="gcp-emissao-sidebar">
            <div class="gcp-card">
                <h2><?php esc_html_e( 'Gerar Novo Certificado', 'gerador-certificados-wp' ); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field( 'gcp_generate_pdf_nonce' ); ?>
                    <p><?php esc_html_e( 'Selecione o participante e o modelo para gerar o PDF usando as variaveis configuradas no modelo.', 'gerador-certificados-wp' ); ?></p>
                    <select name="participant_id" class="widefat" style="margin-bottom: 15px;">
                        <option value=""><?php esc_html_e( 'Selecione um participante...', 'gerador-certificados-wp' ); ?></option>
                        <?php foreach ( $participants as $participant ) : ?>
                            <option value="<?php echo esc_attr( $participant['id'] ); ?>"><?php echo esc_html( $participant['nome_completo'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="modelo_id" class="widefat" style="margin-bottom: 15px;">
                        <option value=""><?php esc_html_e( 'Selecione um modelo...', 'gerador-certificados-wp' ); ?></option>
                        <?php foreach ( $templates as $slug => $template ) : ?>
                            <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $template['name'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button( __( 'Gerar PDF', 'gerador-certificados-wp' ), 'primary', 'gcp_generate_pdf', true ); ?>
                    <p class="description"><?php esc_html_e( 'O PDF sera gerado com base no modelo e nos dados cadastrados.', 'gerador-certificados-wp' ); ?></p>
                </form>
            </div>
        </div>
    </div>
</div>
