<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo $participant ? __( 'Editar Participante', 'gerador-certificados-wp' ) : __( 'Adicionar Novo Participante', 'gerador-certificados-wp' ); ?></h1>
    <hr class="wp-header-end">

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="gcwp_save_participant"/>
        <input type="hidden" name="page" value="gcwp-participantes"/>
        <?php if ($participant): ?>
            <input type="hidden" name="participant_action" value="edit"/>
            <input type="hidden" name="participant" value="<?php echo esc_attr($participant['id']); ?>"/>
        <?php else: ?>
            <input type="hidden" name="participant_action" value="add"/>
        <?php endif; ?>
        
        <?php wp_nonce_field( 'gcwp_save_participant_nonce', 'gcwp_participant_nonce' ); ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="nome_completo"><?php _e( 'Nome Completo', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="text" id="nome_completo" name="nome_completo" value="<?php echo $participant ? esc_attr( $participant['nome_completo'] ) : ''; ?>" class="regular-text" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="email"><?php _e( 'E-mail', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="email" id="email" name="email" value="<?php echo $participant ? esc_attr( $participant['email'] ) : ''; ?>" class="regular-text"/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="curso"><?php _e( 'Curso', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="text" id="curso" name="curso" value="<?php echo $participant ? esc_attr( $participant['curso'] ) : ''; ?>" class="regular-text" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="data_inicio"><?php _e( 'Data de Início', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="date" id="data_inicio" name="data_inicio" value="<?php echo $participant ? esc_attr( $participant['data_inicio'] ) : ''; ?>" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="data_termino"><?php _e( 'Data de Término', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="date" id="data_termino" name="data_termino" value="<?php echo $participant ? esc_attr( $participant['data_termino'] ) : ''; ?>" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="duracao_horas"><?php _e( 'Duração (horas)', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="number" id="duracao_horas" name="duracao_horas" value="<?php echo $participant ? esc_attr( $participant['duracao_horas'] ) : ''; ?>" class="small-text" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="cidade"><?php _e( 'Cidade', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="text" id="cidade" name="cidade" value="<?php echo $participant ? esc_attr( $participant['cidade'] ) : ''; ?>" class="regular-text" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="data_emissao"><?php _e( 'Data de Emissão', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="date" id="data_emissao" name="data_emissao" value="<?php echo $participant ? esc_attr( $participant['data_emissao'] ) : ''; ?>" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="numero_livro"><?php _e( 'Número do Livro', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="text" id="numero_livro" name="numero_livro" value="<?php echo $participant ? esc_attr( $participant['numero_livro'] ) : ''; ?>" class="regular-text"/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="numero_pagina"><?php _e( 'Número da Página', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="text" id="numero_pagina" name="numero_pagina" value="<?php echo $participant ? esc_attr( $participant['numero_pagina'] ) : ''; ?>" class="regular-text"/></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="numero_certificado"><?php _e( 'Número do Certificado', 'gerador-certificados-wp' ); ?></label></th>
                <td><input type="text" id="numero_certificado" name="numero_certificado" value="<?php echo $participant ? esc_attr( $participant['numero_certificado'] ) : ''; ?>" class="regular-text"/></td>
            </tr>
        </table>
        
        <?php submit_button( $participant ? __( 'Atualizar Participante', 'gerador-certificados-wp' ) : __( 'Adicionar Participante', 'gerador-certificados-wp' ), 'primary', 'gcwp_save_participant' ); ?>
        <a href="<?php echo admin_url( 'admin.php?page=gcwp-participantes' ); ?>" class="button"><?php _e( 'Cancelar', 'gerador-certificados-wp' ); ?></a>
    </form>
</div>
