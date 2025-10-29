<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $wpdb;
$table_name = $wpdb->prefix . 'gcwp_participantes';

// Lógica para salvar/atualizar o participante
if ( isset( $_POST['submit'] ) && check_admin_referer( 'gcwp_save_participant_nonce' ) ) {
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

    $id = isset( $_GET['participant'] ) ? absint( $_GET['participant'] ) : 0;

    if ( $id > 0 ) {
        // Update
        $wpdb->update( $table_name, $data, [ 'id' => $id ] );
        echo '<div class="notice notice-success is-dismissible"><p>Participante atualizado com sucesso!</p></div>';
    } else {
        // Insert
        $wpdb->insert( $table_name, $data );
        echo '<div class="notice notice-success is-dismissible"><p>Participante adicionado com sucesso!</p></div>';
    }
}

$participant = null;
if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' && isset( $_GET['participant'] ) ) {
    $id = absint( $_GET['participant'] );
    $participant = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );
}

$get_value = function( $field ) use ( $participant ) {
    return $participant[ $field ] ?? '';
};

?>
<h2><?php echo $participant ? 'Editar Participante' : 'Adicionar Novo Participante'; ?></h2>
<form method="post">
    <input type="hidden" name="page" value="gcwp-participantes"/>
    <?php wp_nonce_field( 'gcwp_save_participant_nonce' ); ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Nome Completo</th>
            <td><input type="text" name="nome_completo" value="<?php echo esc_attr( $get_value('nome_completo') ); ?>" class="regular-text" required/></td>
        </tr>
        <tr valign="top">
            <th scope="row">E-mail</th>
            <td><input type="email" name="email" value="<?php echo esc_attr( $get_value('email') ); ?>" class="regular-text"/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Curso</th>
            <td><input type="text" name="curso" value="<?php echo esc_attr( $get_value('curso') ); ?>" class="regular-text" required/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Data de Início</th>
            <td><input type="date" name="data_inicio" value="<?php echo esc_attr( $get_value('data_inicio') ); ?>" required/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Data de Término</th>
            <td><input type="date" name="data_termino" value="<?php echo esc_attr( $get_value('data_termino') ); ?>" required/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Duração (horas)</th>
            <td><input type="number" name="duracao_horas" value="<?php echo esc_attr( $get_value('duracao_horas') ); ?>" class="small-text" required/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Cidade</th>
            <td><input type="text" name="cidade" value="<?php echo esc_attr( $get_value('cidade') ); ?>" class="regular-text" required/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Data de Emissão</th>
            <td><input type="date" name="data_emissao" value="<?php echo esc_attr( $get_value('data_emissao') ); ?>" required/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Número do Livro</th>
            <td><input type="text" name="numero_livro" value="<?php echo esc_attr( $get_value('numero_livro') ); ?>" class="regular-text"/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Número da Página</th>
            <td><input type="text" name="numero_pagina" value="<?php echo esc_attr( $get_value('numero_pagina') ); ?>" class="regular-text"/></td>
        </tr>
        <tr valign="top">
            <th scope="row">Número do Certificado</th>
            <td><input type="text" name="numero_certificado" value="<?php echo esc_attr( $get_value('numero_certificado') ); ?>" class="regular-text"/></td>
        </tr>
    </table>
    <?php submit_button( $participant ? 'Atualizar Participante' : 'Adicionar Participante' ); ?>
</form>