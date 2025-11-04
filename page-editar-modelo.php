<?php
/**
 * Página de edição de modelos de certificado
 */

// Verificar permissões
if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'gerador-certificados-wp'));
}

// Obter parâmetros
$tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$file = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : '';

if (empty($tipo) || empty($file)) {
    wp_die(__('Parâmetros inválidos.', 'gerador-certificados-wp'));
}

// Obter diretório de upload
$upload_dir = wp_upload_dir();
$modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
$file_path = $modelos_dir . '/' . $tipo . '/' . $file;
$file_url = $upload_dir['baseurl'] . '/certificados/modelos/' . $tipo . '/' . $file;

// Verificar se o arquivo existe
if (!file_exists($file_path)) {
    wp_die(__('Arquivo não encontrado.', 'gerador-certificados-wp'));
}

// Processar formulário de edição
if (isset($_POST['gcwp_edit_submit']) && isset($_POST['gcwp_edit_nonce']) && wp_verify_nonce($_POST['gcwp_edit_nonce'], 'gcwp_edit_template')) {
    // Verificar se foi enviado um novo arquivo
    if (!empty($_FILES['modelo_novo']['name'])) {
        // Configurar upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload_overrides = array('test_form' => false);
        
        // Fazer upload do arquivo
        $uploaded_file = wp_handle_upload($_FILES['modelo_novo'], $upload_overrides);
        
        if (!isset($uploaded_file['error'])) {
            // Upload bem-sucedido, mover para o diretório correto
            $new_file_path = $modelos_dir . '/' . $tipo . '/' . $file;
            
            // Remover arquivo antigo
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Mover novo arquivo
            if (rename($uploaded_file['file'], $new_file_path)) {
                // Atualizar URL se este modelo estiver selecionado
                $current_url = '';
                if ($tipo === 'frente') {
                    $current_url = get_option('gcwp_modelo_frente_url', '');
                } elseif ($tipo === 'verso') {
                    $current_url = get_option('gcwp_modelo_verso_url', '');
                }
                
                if (!empty($current_url) && strpos($current_url, $file) !== false) {
                    $new_url = $upload_dir['baseurl'] . '/certificados/modelos/' . $tipo . '/' . $file;
                    if ($tipo === 'frente') {
                        update_option('gcwp_modelo_frente_url', $new_url);
                    } elseif ($tipo === 'verso') {
                        update_option('gcwp_modelo_verso', $new_url);
                    }
                }
                
                // Redirecionar para a página de modelos
                wp_redirect(admin_url('admin.php?page=gcwp-modelos&updated=1'));
                exit;
            } else {
                $error_message = __('Não foi possível mover o arquivo para o diretório de destino.', 'gerador-certificados-wp');
            }
        } else {
            $error_message = $uploaded_file['error'];
        }
    } else {
        $error_message = __('Nenhum arquivo foi enviado.', 'gerador-certificados-wp');
    }
}

// Título da página
$page_title = sprintf(
    __('Editar Modelo de %s', 'gerador-certificados-wp'),
    $tipo === 'frente' ? __('Frente', 'gerador-certificados-wp') : __('Verso', 'gerador-certificados-wp')
);
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <?php if (isset($error_message)): ?>
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
            <a href="<?php echo esc_url(admin_url('admin.php?page=gcwp-modelos')); ?>" class="button"><?php esc_html_e('Cancelar', 'gerador-certificados-wp'); ?></a>
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