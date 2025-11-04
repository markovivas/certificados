<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle file uploads for certificate templates.
 */
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

    $modelo_nome = isset($_POST['modelo_nome']) ? sanitize_text_field($_POST['modelo_nome']) : '';
    if (empty($modelo_nome)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('O nome do modelo é obrigatório.', 'gerador-certificados-wp') . '</p></div>';
        return;
    }

    if (empty($_FILES['modelo_frente']['name'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('A imagem da frente do modelo é obrigatória.', 'gerador-certificados-wp') . '</p></div>';
        return;
    }

    $upload_dir = wp_upload_dir();
    $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
    $modelo_slug = sanitize_title($modelo_nome);
    $modelo_path = $modelos_dir . '/' . $modelo_slug;

    if (file_exists($modelo_path)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Já existe um modelo com este nome. Por favor, escolha outro.', 'gerador-certificados-wp') . '</p></div>';
        return;
    }

    wp_mkdir_p($modelo_path);

    $upload_overrides = [ 'test_form' => false ];

    // Processar frente
    $frente_info = wp_handle_upload($_FILES['modelo_frente'], $upload_overrides);
    if ($frente_info && !isset($frente_info['error'])) {
        $ext = pathinfo($frente_info['file'], PATHINFO_EXTENSION);
        rename($frente_info['file'], $modelo_path . '/frente.' . $ext);
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Erro ao enviar a imagem da frente: ', 'gerador-certificados-wp') . esc_html($frente_info['error']) . '</p></div>';
        return;
    }

    // Processar verso (opcional)
    if (!empty($_FILES['modelo_verso']['name'])) {
        $verso_info = wp_handle_upload($_FILES['modelo_verso'], $upload_overrides);
        if ($verso_info && !isset($verso_info['error'])) {
            $ext = pathinfo($verso_info['file'], PATHINFO_EXTENSION);
            rename($verso_info['file'], $modelo_path . '/verso.' . $ext);
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('A imagem da frente foi salva, mas ocorreu um erro ao enviar a imagem do verso: ', 'gerador-certificados-wp') . esc_html($verso_info['error']) . '</p></div>';
        }
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Modelo "%s" criado com sucesso!', 'gerador-certificados-wp'), esc_html($modelo_nome)) . '</p></div>';
}

// Processar o upload de modelos se o formulário foi enviado
if ( isset( $_POST['gcwp_upload_nonce'] ) ) {
    gcwp_handle_template_upload();
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p>
        <?php esc_html_e( 'Gerencie os modelos de certificado. Um modelo consiste em uma imagem de frente e, opcionalmente, uma de verso.', 'gerador-certificados-wp' ); ?>
    </p>

    <!-- Abas para navegação -->
    <h2 class="nav-tab-wrapper">
        <a href="#gerenciar-modelos" class="nav-tab nav-tab-active"><?php esc_html_e('Gerenciar Modelos', 'gerador-certificados-wp'); ?></a>
        <a href="#upload-modelos" class="nav-tab"><?php esc_html_e('Adicionar Novo Modelo', 'gerador-certificados-wp'); ?></a>
    </h2>

    <!-- Seção de Upload -->
    <div id="upload-modelos" class="tab-content" style="display: none;">
        <form method="POST" enctype="multipart/form-data">
            <?php wp_nonce_field( 'gcwp_upload_template', 'gcwp_upload_nonce' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="modelo_nome"><?php esc_html_e( 'Nome do Modelo', 'gerador-certificados-wp' ); ?></label></th>
                        <td>
                            <input type="text" name="modelo_nome" id="modelo_nome" class="regular-text" required>
                            <p class="description"><?php esc_html_e('Ex: "Certificado Padrão 2025"', 'gerador-certificados-wp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="modelo_frente"><?php esc_html_e( 'Imagem da Frente', 'gerador-certificados-wp' ); ?></label></th>
                        <td>
                            <input type="file" name="modelo_frente" id="modelo_frente" accept="image/*" required>
                            <p class="description"><?php esc_html_e('Obrigatório. Tamanho recomendado: A4 paisagem (ex: 1280x720 pixels).', 'gerador-certificados-wp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="modelo_verso"><?php esc_html_e( 'Imagem do Verso', 'gerador-certificados-wp' ); ?></label></th>
                        <td>
                            <input type="file" name="modelo_verso" id="modelo_verso" accept="image/*">
                            <p class="description"><?php esc_html_e('Opcional. Usado para informações adicionais no verso.', 'gerador-certificados-wp'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( 'Criar Modelo', 'gerador-certificados-wp' ) ); ?>
        </form>
    </div>

    <!-- Seção de Gerenciamento de Modelos -->
    <div id="gerenciar-modelos" class="tab-content" style="display: block;">
        <h3><?php esc_html_e('Modelos Disponíveis', 'gerador-certificados-wp'); ?></h3>
        
        <div class="modelo-grid">
            <?php
            $upload_dir = wp_upload_dir();
            $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
            $modelo_selecionado = get_option('gcwp_modelo_selecionado');
            $modelos = is_dir($modelos_dir) ? array_filter(scandir($modelos_dir), function($item) use ($modelos_dir) {
                return is_dir($modelos_dir . '/' . $item) && !in_array($item, ['.', '..', 'frente', 'verso']);
            }) : [];

            if (!empty($modelos)) {
                foreach ($modelos as $modelo_slug) {
                    $modelo_nome = ucwords(str_replace('-', ' ', $modelo_slug));
                    $frente_files = glob($modelos_dir . '/' . $modelo_slug . '/frente.*');
                    $verso_files = glob($modelos_dir . '/' . $modelo_slug . '/verso.*');
                    
                    $frente_url = !empty($frente_files) ? str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $frente_files[0]) : '';
                    $verso_url = !empty($verso_files) ? str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $verso_files[0]) : '';
                    
                    $is_selected = ($modelo_selecionado === $modelo_slug);

                    echo '<div class="modelo-card ' . ($is_selected ? 'selected' : '') . '" data-slug="' . esc_attr($modelo_slug) . '">';
                    echo '<div class="modelo-preview"><img src="' . esc_url($frente_url) . '" alt="' . esc_attr($modelo_nome) . '"></div>';
                    echo '<div class="modelo-actions">';
                    echo '<span class="modelo-name">' . esc_html($modelo_nome) . '</span>';
                    echo '<div class="action-buttons">';
                    echo '<a href="#" class="select-modelo button button-small">' . ($is_selected ? esc_html__('Selecionado', 'gerador-certificados-wp') : esc_html__('Selecionar', 'gerador-certificados-wp')) . '</a>';
                    echo '<a href="#" class="rename-modelo button-secondary button-small">' . esc_html__('Renomear', 'gerador-certificados-wp') . '</a>';
                    echo '<a href="#" class="delete-modelo button-link-delete">' . esc_html__('Apagar', 'gerador-certificados-wp') . '</a>';
                    echo '</div></div></div>';
                }
            } else {
                echo '<p>' . esc_html__('Nenhum modelo encontrado. Adicione um novo modelo na aba "Adicionar Novo Modelo".', 'gerador-certificados-wp') . '</p>';
            }
            ?>
        </div>
    </div>
</div>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
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
    height: 150px;
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
.action-buttons .button-link-delete {
    align-self: center;
}
.modelo-card.selected .select-modelo {
    background: #d6d6d6;
    border-color: #d6d6d6;
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
        
        var card = $(this).closest('.modelo-card');
        var slug = card.data('slug');
        
        // Enviar solicitação AJAX para selecionar o modelo
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gcwp_manage_template',
                sub_action: 'select',
                slug: slug,
                nonce: '<?php echo wp_create_nonce('gcwp_template_actions'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Atualizar UI
                    $('.modelo-card').removeClass('selected');
                    $(e.target).closest('.modelo-card').addClass('selected');
                    $('.select-modelo').text('<?php echo esc_js(__('Selecionar', 'gerador-certificados-wp')); ?>').prop('disabled', false);
                    $(e.target).text('<?php echo esc_js(__('Selecionado', 'gerador-certificados-wp')); ?>');
                    
                    // Mostrar mensagem de sucesso
                    // alert(response.data.message);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Apagar modelo
    $('.delete-modelo').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('<?php echo esc_js(__('Tem certeza que deseja apagar este modelo? Esta ação não pode ser desfeita e apagará a frente e o verso.', 'gerador-certificados-wp')); ?>')) {
            var card = $(this).closest('.modelo-card');
            var slug = card.data('slug');
            
            // Enviar solicitação AJAX para apagar o modelo
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gcwp_manage_template',
                    sub_action: 'delete',
                    slug: slug,
                    nonce: '<?php echo wp_create_nonce('gcwp_template_actions'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Remover o card do modelo
                        $(e.target).closest('.modelo-card').remove();
                        
                        // Mostrar mensagem de sucesso
                        // alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
    
    // Renomear modelo
    $('.rename-modelo').on('click', function(e) {
        e.preventDefault();
        var card = $(this).closest('.modelo-card');
        var slug = card.data('slug');
        var currentName = card.find('.modelo-name').text();
        
        var newName = prompt('<?php echo esc_js(__('Digite o novo nome para o modelo:', 'gerador-certificados-wp')); ?>', currentName);
        
        if (newName && newName !== currentName) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gcwp_manage_template',
                    sub_action: 'rename',
                    slug: slug,
                    new_name: newName,
                    nonce: '<?php echo wp_create_nonce('gcwp_template_actions'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Atualizar UI
                        card.find('.modelo-name').text(newName);
                        card.data('slug', response.data.new_slug);
                        // alert(response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });
});
</script>