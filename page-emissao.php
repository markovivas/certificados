<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Processar solicitação de emissão de certificado
if (isset($_POST['gcwp_emitir_certificado']) && isset($_POST['gcwp_emissao_nonce']) && 
    wp_verify_nonce($_POST['gcwp_emissao_nonce'], 'gcwp_emitir_certificado')) {
    
    // Verificar permissões
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp'));
    }
    
    // Obter dados do formulário
    $participante_id = isset($_POST['participante_id']) ? intval($_POST['participante_id']) : 0;
    $modelo_id = isset($_POST['modelo_id']) ? sanitize_text_field($_POST['modelo_id']) : '';
    
    if ($participante_id > 0) {
        // Gerar certificado
        if (class_exists('GCWP_Certificate_Generator')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'gcwp_participantes';
            
            // Obter dados do participante
            $participante = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $participante_id
            ));
            
            if ($participante) {
                $generator = new GCWP_Certificate_Generator();
                $result = $generator->generate_certificate($participante, $modelo_id);
                
                if (!is_wp_error($result)) {
                    $success_message = sprintf(
                        __('Certificado gerado com sucesso para %s. <a href="%s" target="_blank">Baixar PDF</a>', 'gerador-certificados-wp'),
                        esc_html($participante->nome_completo),
                        esc_url($result['url'])
                    );
                } else {
                    $error_message = $result->get_error_message();
                }
            } else {
                $error_message = __('Participante não encontrado.', 'gerador-certificados-wp');
            }
        } else {
            $error_message = __('Gerador de certificados não disponível.', 'gerador-certificados-wp');
        }
    } else {
        $error_message = __('Selecione um participante válido.', 'gerador-certificados-wp');
    }
}

// Obter lista de participantes
global $wpdb;
$table_name = $wpdb->prefix . 'gcwp_participantes';
$participantes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY nome_completo ASC");

// Obter modelos disponíveis
$upload_dir = wp_upload_dir();
$modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
$modelos = is_dir($modelos_dir) ? array_filter(scandir($modelos_dir), function($item) use ($modelos_dir) {
    return is_dir($modelos_dir . '/' . $item) && !in_array($item, ['.', '..', 'frente', 'verso']);
}) : [];

// Obter modelo selecionado como padrão
$modelo_padrao = get_option('gcwp_modelo_selecionado');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo wp_kses_post($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="gcwp-emissao-container">
        <div class="gcwp-emissao-form">
            <form method="post" action="">
                <?php wp_nonce_field('gcwp_emitir_certificado', 'gcwp_emissao_nonce'); ?>
                
                <h2><?php esc_html_e('Emitir Certificado', 'gerador-certificados-wp'); ?></h2>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="participante_id"><?php esc_html_e('Selecione o Participante', 'gerador-certificados-wp'); ?></label>
                            </th>
                            <td>
                                <select name="participante_id" id="participante_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e('-- Selecione --', 'gerador-certificados-wp'); ?></option>
                                    <?php foreach ($participantes as $participante): ?>
                                        <option value="<?php echo esc_attr($participante->id); ?>">
                                            <?php echo esc_html($participante->nome_completo); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Selecione o participante para o qual deseja emitir o certificado.', 'gerador-certificados-wp'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="modelo_id"><?php esc_html_e('Modelo de Certificado', 'gerador-certificados-wp'); ?></label>
                            </th>
                            <td>
                                <select name="modelo_id" id="modelo_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e('-- Selecione --', 'gerador-certificados-wp'); ?></option>
                                    <?php foreach ($modelos as $modelo_slug): 
                                        $modelo_nome = ucwords(str_replace('-', ' ', $modelo_slug));
                                    ?>
                                        <option value="<?php echo esc_attr($modelo_slug); ?>" <?php selected($modelo_slug, $modelo_padrao); ?>>
                                            <?php echo esc_html($modelo_nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Selecione o modelo de certificado a ser utilizado.', 'gerador-certificados-wp'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="gcwp_emitir_certificado" class="button button-primary" value="<?php esc_attr_e('Gerar PDF', 'gerador-certificados-wp'); ?>">
                </p>
            </form>
        </div>
        
        <div class="gcwp-emissao-historico">
            <h2><?php esc_html_e('Certificados Emitidos Recentemente', 'gerador-certificados-wp'); ?></h2>
            
            <?php
            // Obter certificados emitidos recentemente
            $certificados_dir = $upload_dir['basedir'] . '/certificados/emitidos';
            $certificados = array();
            
            if (file_exists($certificados_dir)) {
                $pdf_files = glob($certificados_dir . '/*.pdf');
                
                if (!empty($pdf_files)) {
                    usort($pdf_files, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    
                    $pdf_files = array_slice($pdf_files, 0, 10); // Mostrar apenas os 10 mais recentes
                    
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr>';
                    echo '<th>' . esc_html__('Certificado', 'gerador-certificados-wp') . '</th>';
                    echo '<th>' . esc_html__('Data de Emissão', 'gerador-certificados-wp') . '</th>';
                    echo '<th>' . esc_html__('Ações', 'gerador-certificados-wp') . '</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($pdf_files as $file) {
                        $filename = basename($file);
                        $file_url = $upload_dir['baseurl'] . '/certificados/emitidos/' . $filename;
                        $file_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($file));
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($filename) . '</td>';
                        echo '<td>' . esc_html($file_date) . '</td>';
                        echo '<td>';
                        echo '<a href="' . esc_url($file_url) . '" target="_blank" class="button button-small">' . esc_html__('Visualizar', 'gerador-certificados-wp') . '</a> ';
                        echo '<a href="#" class="button button-small gcwp-reenviar-email" data-file="' . esc_attr($filename) . '">' . esc_html__('Reenviar E-mail', 'gerador-certificados-wp') . '</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>' . esc_html__('Nenhum certificado emitido recentemente.', 'gerador-certificados-wp') . '</p>';
                }
            } else {
                echo '<p>' . esc_html__('Diretório de certificados não encontrado.', 'gerador-certificados-wp') . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<style>
.gcwp-emissao-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-top: 20px;
}
.gcwp-emissao-form {
    flex: 1;
    min-width: 300px;
}
.gcwp-emissao-historico {
    flex: 1;
    min-width: 300px;
}
.gcwp-emissao-historico table {
    width: 100%;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Reenviar e-mail
    $('.gcwp-reenviar-email').on('click', function(e) {
        e.preventDefault();
        
        var file = $(this).data('file');
        
        if (confirm('<?php echo esc_js(__('Deseja reenviar este certificado por e-mail?', 'gerador-certificados-wp')); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gcwp_reenviar_certificado',
                    file: file,
                    nonce: '<?php echo wp_create_nonce('gcwp_reenviar_certificado'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php echo esc_js(__('E-mail enviado com sucesso!', 'gerador-certificados-wp')); ?>');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
});
</script>