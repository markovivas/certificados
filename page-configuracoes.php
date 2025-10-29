<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Salvar configurações
if (isset($_POST['gcwp_save_config']) && check_admin_referer('gcwp_save_config', 'gcwp_config_nonce')) {
    // Posições
    update_option('gcwp_nome_pos_x', sanitize_text_field($_POST['nome_pos_x']));
    update_option('gcwp_nome_pos_y', sanitize_text_field($_POST['nome_pos_y']));
    update_option('gcwp_curso_pos_x', sanitize_text_field($_POST['curso_pos_x']));
    update_option('gcwp_curso_pos_y', sanitize_text_field($_POST['curso_pos_y']));
    update_option('gcwp_carga_pos_x', sanitize_text_field($_POST['carga_pos_x']));
    update_option('gcwp_carga_pos_y', sanitize_text_field($_POST['carga_pos_y']));
    update_option('gcwp_data_pos_x', sanitize_text_field($_POST['data_pos_x']));
    update_option('gcwp_data_pos_y', sanitize_text_field($_POST['data_pos_y']));
    
    // Estilos
    update_option('gcwp_nome_tamanho', sanitize_text_field($_POST['nome_tamanho']));
    update_option('gcwp_nome_cor', sanitize_text_field($_POST['nome_cor']));
    update_option('gcwp_nome_negrito', isset($_POST['nome_negrito']) ? '1' : '0');
    
    update_option('gcwp_texto_tamanho', sanitize_text_field($_POST['texto_tamanho']));
    update_option('gcwp_texto_cor', sanitize_text_field($_POST['texto_cor']));
    update_option('gcwp_texto_negrito', isset($_POST['texto_negrito']) ? '1' : '0');
    
    $success_message = __('Configurações salvas com sucesso!', 'gerador-certificados-wp');
}

// Obter valores salvos
$nome_pos_x = get_option('gcwp_nome_pos_x', '60');
$nome_pos_y = get_option('gcwp_nome_pos_y', '100');
$curso_pos_x = get_option('gcwp_curso_pos_x', '60');
$curso_pos_y = get_option('gcwp_curso_pos_y', '120');
$carga_pos_x = get_option('gcwp_carga_pos_x', '60');
$carga_pos_y = get_option('gcwp_carga_pos_y', '130');
$data_pos_x = get_option('gcwp_data_pos_x', '60');
$data_pos_y = get_option('gcwp_data_pos_y', '140');

$nome_tamanho = get_option('gcwp_nome_tamanho', '24');
$nome_cor = get_option('gcwp_nome_cor', '#000000');
$nome_negrito = get_option('gcwp_nome_negrito', '1');

$texto_tamanho = get_option('gcwp_texto_tamanho', '14');
$texto_cor = get_option('gcwp_texto_cor', '#000000');
$texto_negrito = get_option('gcwp_texto_negrito', '0');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('gcwp_save_config', 'gcwp_config_nonce'); ?>
        
        <h2><?php esc_html_e('Posição dos Textos', 'gerador-certificados-wp'); ?></h2>
        <p><?php esc_html_e('Defina as coordenadas X e Y (em milímetros) para posicionar os textos no certificado.', 'gerador-certificados-wp'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Nome do Participante', 'gerador-certificados-wp'); ?></th>
                <td>
                    X: <input type="number" name="nome_pos_x" value="<?php echo esc_attr($nome_pos_x); ?>" min="0" max="297" step="1" class="small-text">
                    Y: <input type="number" name="nome_pos_y" value="<?php echo esc_attr($nome_pos_y); ?>" min="0" max="210" step="1" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Curso', 'gerador-certificados-wp'); ?></th>
                <td>
                    X: <input type="number" name="curso_pos_x" value="<?php echo esc_attr($curso_pos_x); ?>" min="0" max="297" step="1" class="small-text">
                    Y: <input type="number" name="curso_pos_y" value="<?php echo esc_attr($curso_pos_y); ?>" min="0" max="210" step="1" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Carga Horária', 'gerador-certificados-wp'); ?></th>
                <td>
                    X: <input type="number" name="carga_pos_x" value="<?php echo esc_attr($carga_pos_x); ?>" min="0" max="297" step="1" class="small-text">
                    Y: <input type="number" name="carga_pos_y" value="<?php echo esc_attr($carga_pos_y); ?>" min="0" max="210" step="1" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Data', 'gerador-certificados-wp'); ?></th>
                <td>
                    X: <input type="number" name="data_pos_x" value="<?php echo esc_attr($data_pos_x); ?>" min="0" max="297" step="1" class="small-text">
                    Y: <input type="number" name="data_pos_y" value="<?php echo esc_attr($data_pos_y); ?>" min="0" max="210" step="1" class="small-text">
                </td>
            </tr>
        </table>
        
        <h2><?php esc_html_e('Estilo dos Textos', 'gerador-certificados-wp'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Nome do Participante', 'gerador-certificados-wp'); ?></th>
                <td>
                    <label><?php esc_html_e('Tamanho:', 'gerador-certificados-wp'); ?></label>
                    <input type="number" name="nome_tamanho" value="<?php echo esc_attr($nome_tamanho); ?>" min="8" max="72" class="small-text">
                    
                    <label><?php esc_html_e('Cor:', 'gerador-certificados-wp'); ?></label>
                    <input type="color" name="nome_cor" value="<?php echo esc_attr($nome_cor); ?>">
                    
                    <label>
                        <input type="checkbox" name="nome_negrito" value="1" <?php checked($nome_negrito, '1'); ?>>
                        <?php esc_html_e('Negrito', 'gerador-certificados-wp'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Outros Textos', 'gerador-certificados-wp'); ?></th>
                <td>
                    <label><?php esc_html_e('Tamanho:', 'gerador-certificados-wp'); ?></label>
                    <input type="number" name="texto_tamanho" value="<?php echo esc_attr($texto_tamanho); ?>" min="8" max="72" class="small-text">
                    
                    <label><?php esc_html_e('Cor:', 'gerador-certificados-wp'); ?></label>
                    <input type="color" name="texto_cor" value="<?php echo esc_attr($texto_cor); ?>">
                    
                    <label>
                        <input type="checkbox" name="texto_negrito" value="1" <?php checked($texto_negrito, '1'); ?>>
                        <?php esc_html_e('Negrito', 'gerador-certificados-wp'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="gcwp_save_config" class="button button-primary" value="<?php esc_attr_e('Salvar Configurações', 'gerador-certificados-wp'); ?>">
        </p>
    </form>
</div>