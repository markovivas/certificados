<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
    <?php endif; ?>

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
                    
                    // Add edit links for front/back
                    $edit_link_frente = admin_url('admin.php?page=gcwp-editar-modelo&tipo=' . $modelo_slug . '&file=' . basename($frente_files[0]));
                    // echo '<a href="' . $edit_link_frente . '" class="button-link">Editar Frente</a>';
                    
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
    width: 250px;
    display: inline-block;
    vertical-align: top;
    margin-right: 20px;
    margin-bottom: 20px;
    background: #fff;
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
    gap: 5px;
    margin-top: 5px;
    flex-wrap: wrap;
}
.action-buttons .button-link-delete {
    align-self: center;
    color: #b32d2e;
}
.modelo-card.selected .select-modelo {
    background: #d6d6d6;
    border-color: #d6d6d6;
}
</style>
