<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Título da página
$page_title = sprintf(
    __('Editar Modelo de %s', 'gerador-certificados-wp'),
    $tipo === 'frente' ? __('Frente', 'gerador-certificados-wp') : __('Verso', 'gerador-certificados-wp')
);
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <?php if (!empty($error_message)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="modelo-preview-large">
        <img src="<?php echo esc_url($file_url); ?>" alt="<?php echo esc_attr($file); ?>">
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        <?php wp_nonce_field('gcwp_edit_template', 'gcwp_edit_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="modelo_novo">
                            <?php esc_html_e('Substituir Modelo', 'gerador-certificados-wp'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="file" name="modelo_novo" id="modelo_novo" accept="image/*" required>
                        <p class="description">
                            <?php esc_html_e('Selecione uma nova imagem para substituir o modelo atual. O tamanho recomendado é A4 (2480x3508 pixels para 300 DPI).', 'gerador-certificados-wp'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="submit-buttons">
            <input type="submit" name="gcwp_edit_submit" class="button button-primary" value="<?php esc_attr_e('Salvar Alterações', 'gerador-certificados-wp'); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=gcwp-certificados')); ?>" class="button"><?php esc_html_e('Cancelar', 'gerador-certificados-wp'); ?></a>
        </div>
    </form>
</div>

<style>
.modelo-preview-large {
    max-width: 600px;
    margin: 20px 0;
    padding: 10px;
    border: 1px solid #ddd;
    background: #f9f9f9;
    text-align: center;
}
.modelo-preview-large img {
    max-width: 100%;
    height: auto;
}
.submit-buttons {
    margin-top: 20px;
}
.submit-buttons .button {
    margin-right: 10px;
}
</style>
