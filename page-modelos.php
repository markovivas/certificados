<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Processar o upload de modelos se o formulário foi enviado
if (isset($_POST['gcwp_upload_nonce'])) {
    gcwp_handle_template_upload();
}

/**
 * Handle file uploads for certificate templates.
 */
if ( ! function_exists( 'gcwp_handle_template_upload' ) ) {
    function gcwp_handle_template_upload() {
        if ( ! isset( $_POST['gcwp_upload_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gcwp_upload_nonce'] ), 'gcwp_upload_template' ) ) {
            return;
        }
    
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para realizar esta ação.', 'gerador-certificados-wp' ) );
        }
    
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
    
        $upload_overrides = [ 'test_form' => false ];
    
        $process_upload = function( string $file_key, string $option_url, string $option_path, string $sub_dir ) use ( $upload_overrides ) {
            if ( isset( $_FILES[ $file_key ] ) && ! empty( $_FILES[ $file_key ]['name'] ) ) {
                // Garantir que o diretório de destino exista
                $upload_dir = wp_upload_dir();
                $target_dir = $upload_dir['basedir'] . '/certificados/modelos/' . $sub_dir;
                
                if (!file_exists($target_dir)) {
                    wp_mkdir_p($target_dir);
                }
                
                $moved_file = wp_handle_upload( $_FILES[ $file_key ], $upload_overrides );
    
                if ( $moved_file && ! isset( $moved_file['error'] ) ) {
                    // Mover para o diretório correto
                    $file_name = basename($moved_file['file']);
                    $new_file = $target_dir . '/' . $file_name;
                    
                    if (rename($moved_file['file'], $new_file)) {
                        $new_url = $upload_dir['baseurl'] . '/certificados/modelos/' . $sub_dir . '/' . $file_name;
                        update_option( $option_url, $new_url );
                        update_option( $option_path, $new_file );
                        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Modelo %s enviado com sucesso!', 'gerador-certificados-wp' ), "<strong>$sub_dir</strong>" ) . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Erro ao mover o arquivo para o diretório final %s', 'gerador-certificados-wp' ), "<strong>$sub_dir</strong>" ) . '</p></div>';
                    }
                } else {
                    $error = isset($moved_file['error']) ? $moved_file['error'] : __('Erro desconhecido', 'gerador-certificados-wp');
                    echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Erro ao enviar o modelo %s: %s', 'gerador-certificados-wp' ), "<strong>$sub_dir</strong>", esc_html( $error ) ) . '</p></div>';
                }
            }
        };
    
        $process_upload( 'modelo_frente', 'gcwp_modelo_frente_url', 'gcwp_modelo_frente_path', 'frente' );
        $process_upload( 'modelo_verso', 'gcwp_modelo_verso_url', 'gcwp_modelo_verso_path', 'verso' );
    }
}

$modelo_frente_url = get_option('gcwp_modelo_frente_url');
$modelo_verso_url = get_option('gcwp_modelo_verso_url');

?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p>
        <?php esc_html_e( 'Faça o upload das imagens de fundo (frente e verso) para os seus certificados. O tamanho recomendado é A4 (2480x3508 pixels para 300 DPI).', 'gerador-certificados-wp' ); ?>
    </p>

    <!-- Abas para navegação -->
    <h2 class="nav-tab-wrapper">
        <a href="#upload-modelos" class="nav-tab nav-tab-active"><?php esc_html_e('Upload de Modelos', 'gerador-certificados-wp'); ?></a>
        <a href="#gerenciar-modelos" class="nav-tab"><?php esc_html_e('Gerenciar Modelos', 'gerador-certificados-wp'); ?></a>
    </h2>

    <!-- Seção de Upload -->
    <div id="upload-modelos" class="tab-content" style="display: block;">
        <form method="POST" enctype="multipart/form-data">
            <?php wp_nonce_field( 'gcwp_upload_template', 'gcwp_upload_nonce' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="modelo_frente"><?php esc_html_e( 'Modelo Frente (A4)', 'gerador-certificados-wp' ); ?></label></th>
                        <td>
                            <input type="file" name="modelo_frente" id="modelo_frente" accept="image/*">
                            <?php if ( $modelo_frente_url ) : ?>
                                <div class="preview"><img src="<?php echo esc_url($modelo_frente_url); ?>" style="max-width: 200px; margin-top: 10px;" /></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="modelo_verso"><?php esc_html_e( 'Modelo Verso (A4)', 'gerador-certificados-wp' ); ?></label></th>
                        <td>
                            <input type="file" name="modelo_verso" id="modelo_verso" accept="image/*">
                            <?php if ( $modelo_verso_url ) : ?>
                                <div class="preview"><img src="<?php echo esc_url($modelo_verso_url); ?>" style="max-width: 200px; margin-top: 10px;" /></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( 'Salvar Modelos', 'gerador-certificados-wp' ) ); ?>
        </form>
    </div>

    <!-- Seção de Gerenciamento de Modelos -->
    <div id="gerenciar-modelos" class="tab-content" style="display: none;">
        <h3><?php esc_html_e('Modelos Disponíveis', 'gerador-certificados-wp'); ?></h3>
        
        <div class="modelo-grid">
            <?php
            // Obter modelos salvos
            $upload_dir = wp_upload_dir();
            $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
            
            // Verificar modelos de frente
            echo '<div class="modelo-section">';
            echo '<h4>' . esc_html__('Modelos de Frente', 'gerador-certificados-wp') . '</h4>';
            echo '<div class="modelo-cards">';
            
            if (file_exists($modelos_dir . '/frente')) {
                $frente_files = glob($modelos_dir . '/frente/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                
                if (!empty($frente_files)) {
                    foreach ($frente_files as $file) {
                        $filename = basename($file);
                        $file_url = $upload_dir['baseurl'] . '/certificados/modelos/frente/' . $filename;
                        $is_selected = ($modelo_frente_url && strpos($modelo_frente_url, $filename) !== false);
                        
                        echo '<div class="modelo-card ' . ($is_selected ? 'selected' : '') . '">';
                        echo '<div class="modelo-preview"><img src="' . esc_url($file_url) . '" alt="' . esc_attr($filename) . '"></div>';
                        echo '<div class="modelo-actions">';
                        echo '<span class="modelo-name">' . esc_html($filename) . '</span>';
                        echo '<div class="action-buttons">';
                        echo '<a href="#" class="select-modelo" data-tipo="frente" data-file="' . esc_attr($file) . '" data-url="' . esc_attr($file_url) . '">' . 
                             ($is_selected ? esc_html__('Selecionado', 'gerador-certificados-wp') : esc_html__('Selecionar', 'gerador-certificados-wp')) . '</a>';
                        echo '<a href="#" class="edit-modelo" data-tipo="frente" data-file="' . esc_attr($filename) . '">' . esc_html__('Editar', 'gerador-certificados-wp') . '</a>';
                        echo '<a href="#" class="delete-modelo" data-tipo="frente" data-file="' . esc_attr($filename) . '">' . esc_html__('Apagar', 'gerador-certificados-wp') . '</a>';
                        echo '</div></div></div>';
                    }
                } else {
                    echo '<p>' . esc_html__('Nenhum modelo de frente encontrado.', 'gerador-certificados-wp') . '</p>';
                }
            } else {
                echo '<p>' . esc_html__('Diretório de modelos não encontrado.', 'gerador-certificados-wp') . '</p>';
            }
            
            echo '</div></div>';
            
            // Verificar modelos de verso
            echo '<div class="modelo-section">';
            echo '<h4>' . esc_html__('Modelos de Verso', 'gerador-certificados-wp') . '</h4>';
            echo '<div class="modelo-cards">';
            
            if (file_exists($modelos_dir . '/verso')) {
                $verso_files = glob($modelos_dir . '/verso/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                
                if (!empty($verso_files)) {
                    foreach ($verso_files as $file) {
                        $filename = basename($file);
                        $file_url = $upload_dir['baseurl'] . '/certificados/modelos/verso/' . $filename;
                        $is_selected = ($modelo_verso_url && strpos($modelo_verso_url, $filename) !== false);
                        
                        echo '<div class="modelo-card ' . ($is_selected ? 'selected' : '') . '">';
                        echo '<div class="modelo-preview"><img src="' . esc_url($file_url) . '" alt="' . esc_attr($filename) . '"></div>';
                        echo '<div class="modelo-actions">';
                        echo '<span class="modelo-name">' . esc_html($filename) . '</span>';
                        echo '<div class="action-buttons">';
                        echo '<a href="#" class="select-modelo" data-tipo="verso" data-file="' . esc_attr($file) . '" data-url="' . esc_attr($file_url) . '">' . 
                             ($is_selected ? esc_html__('Selecionado', 'gerador-certificados-wp') : esc_html__('Selecionar', 'gerador-certificados-wp')) . '</a>';
                        echo '<a href="#" class="edit-modelo" data-tipo="verso" data-file="' . esc_attr($filename) . '">' . esc_html__('Editar', 'gerador-certificados-wp') . '</a>';
                        echo '<a href="#" class="delete-modelo" data-tipo="verso" data-file="' . esc_attr($filename) . '">' . esc_html__('Apagar', 'gerador-certificados-wp') . '</a>';
                        echo '</div></div></div>';
                    }
                } else {
                    echo '<p>' . esc_html__('Nenhum modelo de verso encontrado.', 'gerador-certificados-wp') . '</p>';
                }
            } else {
                echo '<p>' . esc_html__('Diretório de modelos não encontrado.', 'gerador-certificados-wp') . '</p>';
            }
            
            echo '</div></div>';
            ?>
        </div>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}
.tab-content {
    margin-top: 20px;
}
.modelo-grid {
    display: flex;
    flex-direction: column;
    gap: 30px;
}
.modelo-section h4 {
    margin-bottom: 15px;
}
.modelo-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}
.modelo-card {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
    transition: all 0.3s ease;
}
.modelo-card.selected {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
}
.modelo-preview {
    height: 200px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    margin-bottom: 10px;
}
.modelo-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.modelo-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.modelo-name {
    font-weight: bold;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 5px;
}
.action-buttons a {
    text-decoration: none;
    font-size: 12px;
}
.select-modelo {
    color: #2271b1;
}
.edit-modelo {
    color: #3c434a;
}
.delete-modelo {
    color: #b32d2e;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Navegação por abas
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Atualizar abas ativas
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mostrar conteúdo da aba
        var target = $(this).attr('href');
        $('.tab-content').hide();
        $(target).show();
    });
    
    // Selecionar modelo
    $('.select-modelo').on('click', function(e) {
        e.preventDefault();
        
        var tipo = $(this).data('tipo');
        var file = $(this).data('file');
        var url = $(this).data('url');
        
        // Enviar solicitação AJAX para selecionar o modelo
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gcwp_select_template',
                tipo: tipo,
                file: file,
                url: url,
                nonce: '<?php echo wp_create_nonce('gcwp_template_actions'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Atualizar UI
                    $('.modelo-card').removeClass('selected');
                    $(e.target).closest('.modelo-card').addClass('selected');
                    $('.select-modelo').text('<?php echo esc_js(__('Selecionar', 'gerador-certificados-wp')); ?>');
                    $(e.target).text('<?php echo esc_js(__('Selecionado', 'gerador-certificados-wp')); ?>');
                    
                    // Mostrar mensagem de sucesso
                    alert('<?php echo esc_js(__('Modelo selecionado com sucesso!', 'gerador-certificados-wp')); ?>');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Apagar modelo
    $('.delete-modelo').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('<?php echo esc_js(__('Tem certeza que deseja apagar este modelo?', 'gerador-certificados-wp')); ?>')) {
            var tipo = $(this).data('tipo');
            var file = $(this).data('file');
            
            // Enviar solicitação AJAX para apagar o modelo
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gcwp_delete_template',
                    tipo: tipo,
                    file: file,
                    nonce: '<?php echo wp_create_nonce('gcwp_template_actions'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Remover o card do modelo
                        $(e.target).closest('.modelo-card').remove();
                        
                        // Mostrar mensagem de sucesso
                        alert('<?php echo esc_js(__('Modelo apagado com sucesso!', 'gerador-certificados-wp')); ?>');
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
    
    // Editar modelo (redirecionar para página de edição)
    $('.edit-modelo').on('click', function(e) {
        e.preventDefault();
        
        var tipo = $(this).data('tipo');
        var file = $(this).data('file');
        
        // Redirecionar para página de edição
        window.location.href = '<?php echo admin_url('admin.php?page=gcwp-editar-modelo'); ?>&tipo=' + tipo + '&file=' + file;
    });
});
</script>