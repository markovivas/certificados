<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Salvar configurações
if (isset($_POST['gcwp_save_config']) && check_admin_referer('gcwp_save_config', 'gcwp_config_nonce')) {
    $fields = [
        'nome', 'curso', 'data_inicio', 'data_termino', 'duracao', 'cidade', 'data_emissao', // Frente
        'livro', 'pagina', 'registro' // Verso
    ];

    foreach ($fields as $field) {
        // Posição
        update_option("gcwp_{$field}_pos_x", sanitize_text_field($_POST["{$field}_pos_x"]));
        update_option("gcwp_{$field}_pos_y", sanitize_text_field($_POST["{$field}_pos_y"]));
        // Estilo
        update_option("gcwp_{$field}_tamanho", sanitize_text_field($_POST["{$field}_tamanho"]));
        update_option("gcwp_{$field}_cor", sanitize_hex_color($_POST["{$field}_cor"]));
        update_option("gcwp_{$field}_fonte", sanitize_text_field($_POST["{$field}_fonte"]));
        update_option("gcwp_{$field}_align", sanitize_text_field($_POST["{$field}_align"]));
        update_option("gcwp_{$field}_negrito", isset($_POST["{$field}_negrito"]) ? '1' : '0');
    }
    
    $success_message = __('Configurações salvas com sucesso!', 'gerador-certificados-wp');
}

// Função para obter valores e estilos
function gcwp_get_field_settings($field, $defaults) {
    $settings = [];
    foreach ($defaults as $key => $default_value) {
        $option_name = "gcwp_{$field}_{$key}";
        $settings[$key] = get_option($option_name, $default_value);
    }
    return $settings;
}

$fields_config = [
    'nome' => gcwp_get_field_settings('nome', ['pos_x' => 0, 'pos_y' => 80, 'tamanho' => 24, 'cor' => '#000000', 'negrito' => '1', 'fonte' => 'helvetica', 'align' => 'C']),
    'curso' => gcwp_get_field_settings('curso', ['pos_x' => 0, 'pos_y' => 100, 'tamanho' => 16, 'cor' => '#000000', 'negrito' => '1', 'fonte' => 'helvetica', 'align' => 'C']),
    'data_inicio' => gcwp_get_field_settings('data_inicio', ['pos_x' => 60, 'pos_y' => 120, 'tamanho' => 12, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'R']),
    'data_termino' => gcwp_get_field_settings('data_termino', ['pos_x' => 100, 'pos_y' => 120, 'tamanho' => 12, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'L']),
    'duracao' => gcwp_get_field_settings('duracao', ['pos_x' => 0, 'pos_y' => 135, 'tamanho' => 12, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'C']),
    'cidade' => gcwp_get_field_settings('cidade', ['pos_x' => 60, 'pos_y' => 150, 'tamanho' => 12, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'R']),
    'data_emissao' => gcwp_get_field_settings('data_emissao', ['pos_x' => 100, 'pos_y' => 150, 'tamanho' => 12, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'L']),
    'livro' => gcwp_get_field_settings('livro', ['pos_x' => 30, 'pos_y' => 50, 'tamanho' => 11, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'R']),
    'pagina' => gcwp_get_field_settings('pagina', ['pos_x' => 30, 'pos_y' => 65, 'tamanho' => 11, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'R']),
    'registro' => gcwp_get_field_settings('registro', ['pos_x' => 30, 'pos_y' => 80, 'tamanho' => 11, 'cor' => '#333333', 'negrito' => '0', 'fonte' => 'helvetica', 'align' => 'R']),
];

// Obter fontes disponíveis
$font_dir = GCWP_PLUGIN_DIR . 'fonts/';
$available_fonts = [];
if (is_dir($font_dir)) {
    $font_files = glob($font_dir . '*.ttf');
    foreach ($font_files as $font_file) {
        $font_name = basename($font_file, '.ttf');
        $available_fonts[$font_name] = $font_name;
    }
}

// Obter modelo selecionado para preview
$modelo_selecionado_slug = get_option('gcwp_modelo_selecionado');
$preview_frente_url = '';
$preview_verso_url = '';
if ($modelo_selecionado_slug) {
    $upload_dir = wp_upload_dir();
    $modelos_dir = $upload_dir['basedir'] . '/certificados/modelos';
    $modelo_dir = $modelos_dir . '/' . $modelo_selecionado_slug;

    $frente_files = glob($modelo_dir . '/frente.*');
    $verso_files = glob($modelo_dir . '/verso.*');
    $preview_frente_url = !empty($frente_files) ? str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $frente_files[0]) : '';
    $preview_verso_url = !empty($verso_files) ? str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $verso_files[0]) : '';
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Preview Gráfico -->
    <h2><?php esc_html_e('Ajuste Gráfico de Posição', 'gerador-certificados-wp'); ?></h2>
    <p><?php esc_html_e('Arraste os campos de texto para a posição desejada. As coordenadas X e Y abaixo serão atualizadas automaticamente.', 'gerador-certificados-wp'); ?></p>

    <div class="gcwp-preview-container">
        <!-- Frente -->
        <div class="gcwp-preview-wrapper">
            <h4><?php esc_html_e('Frente do Certificado', 'gerador-certificados-wp'); ?></h4>
            <?php if ($preview_frente_url): ?>
                <div id="preview-frente" class="gcwp-preview-area" style="background-image: url('<?php echo esc_url($preview_frente_url); ?>');">
                    <div id="draggable-nome" class="draggable-text" data-target-x="nome_pos_x" data-target-y="nome_pos_y">Nome do Participante</div>
                    <div id="draggable-curso" class="draggable-text" data-target-x="curso_pos_x" data-target-y="curso_pos_y">Nome do Curso</div>
                    <div id="draggable-data-inicio" class="draggable-text" data-target-x="data_inicio_pos_x" data-target-y="data_inicio_pos_y">Data de Início</div>
                    <div id="draggable-data-termino" class="draggable-text" data-target-x="data_termino_pos_x" data-target-y="data_termino_pos_y">Data de Término</div>
                    <div id="draggable-duracao" class="draggable-text" data-target-x="duracao_pos_x" data-target-y="duracao_pos_y">Duração (horas)</div>
                    <div id="draggable-cidade" class="draggable-text" data-target-x="cidade_pos_x" data-target-y="cidade_pos_y">Cidade</div>
                    <div id="draggable-data-emissao" class="draggable-text" data-target-x="data_emissao_pos_x" data-target-y="data_emissao_pos_y">Data de Emissão</div>
                </div>
            <?php else: ?>
                <div class="notice notice-warning"><p><?php esc_html_e('Nenhum modelo padrão selecionado ou imagem da frente não encontrada. Selecione um modelo na página "Modelos" para ativar a pré-visualização.', 'gerador-certificados-wp'); ?></p></div>
            <?php endif; ?>
        </div>
        <!-- Verso -->
        <div class="gcwp-preview-wrapper">
            <h4><?php esc_html_e('Verso do Certificado', 'gerador-certificados-wp'); ?></h4>
            <?php if ($preview_verso_url): ?>
                <div id="preview-verso" class="gcwp-preview-area" style="background-image: url('<?php echo esc_url($preview_verso_url); ?>');">
                    <div id="draggable-livro" class="draggable-text" data-target-x="livro_pos_x" data-target-y="livro_pos_y">Livro: 001</div>
                    <div id="draggable-pagina" class="draggable-text" data-target-x="pagina_pos_x" data-target-y="pagina_pos_y">Página: 189</div>
                    <div id="draggable-registro" class="draggable-text" data-target-x="registro_pos_x" data-target-y="registro_pos_y">Registro: 12718</div>
                </div>
            <?php elseif ($preview_frente_url): ?>
                <div class="notice notice-info"><p><?php esc_html_e('O modelo selecionado não possui uma imagem de verso.', 'gerador-certificados-wp'); ?></p></div>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('gcwp_save_config', 'gcwp_config_nonce'); ?>
        
        <h2><?php esc_html_e('Posição dos Textos (Frente)', 'gerador-certificados-wp'); ?></h2>
        <p><?php esc_html_e('Defina as coordenadas X e Y (em milímetros) para posicionar os textos no certificado.', 'gerador-certificados-wp'); ?></p>
        
        <table class="form-table">
            <?php
            $front_labels = [
                'nome' => 'Nome do Participante', 'curso' => 'Curso', 'data_inicio' => 'Data de Início',
                'data_termino' => 'Data de Término', 'duracao' => 'Duração (horas)', 'cidade' => 'Cidade', 'data_emissao' => 'Data de Emissão'
            ];
            foreach ($front_labels as $field => $label):
                $s = $fields_config[$field];
            ?>
            <tr class="gcwp-field-row">
                <th scope="row"><?php echo esc_html($label); ?></th>
                <td>
                    <span class="gcwp-setting-group">
                        <label>X:</label> <input type="number" name="<?php echo $field; ?>_pos_x" value="<?php echo esc_attr($s['pos_x']); ?>" class="small-text">
                        <label>Y:</label> <input type="number" name="<?php echo $field; ?>_pos_y" value="<?php echo esc_attr($s['pos_y']); ?>" class="small-text">
                    </span>
                    <span class="gcwp-setting-group">
                        <label><?php esc_html_e('Fonte:', 'gerador-certificados-wp'); ?></label>
                        <select name="<?php echo $field; ?>_fonte">
                            <optgroup label="<?php esc_attr_e('Padrão PDF', 'gerador-certificados-wp'); ?>">
                                <option value="helvetica" <?php selected($s['fonte'], 'helvetica'); ?>>Helvetica</option>
                                <option value="times" <?php selected($s['fonte'], 'times'); ?>>Times</option>
                                <option value="courier" <?php selected($s['fonte'], 'courier'); ?>>Courier</option>
                            </optgroup>
                            <?php if (!empty($available_fonts)): ?>
                            <optgroup label="<?php esc_attr_e('Fontes Personalizadas', 'gerador-certificados-wp'); ?>">
                                <?php foreach ($available_fonts as $font_key => $font_name): ?>
                                <option value="<?php echo esc_attr($font_key); ?>" <?php selected($s['fonte'], $font_key); ?>><?php echo esc_html($font_name); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                        <label><?php esc_html_e('Tam:', 'gerador-certificados-wp'); ?></label> <input type="number" name="<?php echo $field; ?>_tamanho" value="<?php echo esc_attr($s['tamanho']); ?>" class="small-text">
                        <label><?php esc_html_e('Cor:', 'gerador-certificados-wp'); ?></label> <input type="color" name="<?php echo $field; ?>_cor" value="<?php echo esc_attr($s['cor']); ?>">
                        <label><input type="checkbox" name="<?php echo $field; ?>_negrito" value="1" <?php checked($s['negrito'], '1'); ?>> <?php esc_html_e('N', 'gerador-certificados-wp'); ?></label>
                        <label><?php esc_html_e('Alinhar:', 'gerador-certificados-wp'); ?></label>
                        <select name="<?php echo $field; ?>_align">
                            <option value="L" <?php selected($s['align'], 'L'); ?>><?php esc_html_e('Esquerda', 'gerador-certificados-wp'); ?></option>
                            <option value="C" <?php selected($s['align'], 'C'); ?>><?php esc_html_e('Centro', 'gerador-certificados-wp'); ?></option>
                            <option value="R" <?php selected($s['align'], 'R'); ?>><?php esc_html_e('Direita', 'gerador-certificados-wp'); ?></option>
                        </select>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2><?php esc_html_e('Posição dos Textos (Verso)', 'gerador-certificados-wp'); ?></h2>
        <table class="form-table">
            <?php
            $back_labels = ['livro' => 'Número do Livro', 'pagina' => 'Número da Página', 'registro' => 'Número do Registro/Certificado'];
            foreach ($back_labels as $field => $label):
                $s = $fields_config[$field];
            ?>
            <tr class="gcwp-field-row">
                <th scope="row"><?php echo esc_html($label); ?></th>
                <td>
                    <span class="gcwp-setting-group">
                        <label>X:</label> <input type="number" name="<?php echo $field; ?>_pos_x" value="<?php echo esc_attr($s['pos_x']); ?>" class="small-text">
                        <label>Y:</label> <input type="number" name="<?php echo $field; ?>_pos_y" value="<?php echo esc_attr($s['pos_y']); ?>" class="small-text">
                    </span>
                    <span class="gcwp-setting-group">
                        <label><?php esc_html_e('Fonte:', 'gerador-certificados-wp'); ?></label>
                        <select name="<?php echo $field; ?>_fonte">
                            <optgroup label="<?php esc_attr_e('Padrão PDF', 'gerador-certificados-wp'); ?>">
                                <option value="helvetica" <?php selected($s['fonte'], 'helvetica'); ?>>Helvetica</option>
                                <option value="times" <?php selected($s['fonte'], 'times'); ?>>Times</option>
                                <option value="courier" <?php selected($s['fonte'], 'courier'); ?>>Courier</option>
                            </optgroup>
                             <?php if (!empty($available_fonts)): ?>
                            <optgroup label="<?php esc_attr_e('Fontes Personalizadas', 'gerador-certificados-wp'); ?>">
                                <?php foreach ($available_fonts as $font_key => $font_name): ?>
                                <option value="<?php echo esc_attr($font_key); ?>" <?php selected($s['fonte'], $font_key); ?>><?php echo esc_html($font_name); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                        <label><?php esc_html_e('Tam:', 'gerador-certificados-wp'); ?></label> <input type="number" name="<?php echo $field; ?>_tamanho" value="<?php echo esc_attr($s['tamanho']); ?>" class="small-text">
                        <label><?php esc_html_e('Cor:', 'gerador-certificados-wp'); ?></label> <input type="color" name="<?php echo $field; ?>_cor" value="<?php echo esc_attr($s['cor']); ?>">
                        <label><input type="checkbox" name="<?php echo $field; ?>_negrito" value="1" <?php checked($s['negrito'], '1'); ?>> <?php esc_html_e('N', 'gerador-certificados-wp'); ?></label>
                        <label><?php esc_html_e('Alinhar:', 'gerador-certificados-wp'); ?></label>
                        <select name="<?php echo $field; ?>_align">
                            <option value="L" <?php selected($s['align'], 'L'); ?>><?php esc_html_e('Esquerda', 'gerador-certificados-wp'); ?></option>
                            <option value="C" <?php selected($s['align'], 'C'); ?>><?php esc_html_e('Centro', 'gerador-certificados-wp'); ?></option>
                            <option value="R" <?php selected($s['align'], 'R'); ?>><?php esc_html_e('Direita', 'gerador-certificados-wp'); ?></option>
                        </select>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <p class="submit">
            <input type="submit" name="gcwp_save_config" class="button button-primary" value="<?php esc_attr_e('Salvar Configurações', 'gerador-certificados-wp'); ?>">
        </p>
    </form>

    <hr>

    <h2><?php esc_html_e('Resetar Plugin', 'gerador-certificados-wp'); ?></h2>
    <div class="notice notice-error inline">
        <p>
            <strong><?php esc_html_e('Atenção:', 'gerador-certificados-wp'); ?></strong>
            <?php esc_html_e('Esta ação é irreversível. Ela apagará permanentemente todos os participantes, modelos de certificado, certificados emitidos e configurações do plugin.', 'gerador-certificados-wp'); ?>
        </p>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="gcwp-reset-form">
        <input type="hidden" name="action" value="gcwp_reset_plugin">
        <?php wp_nonce_field('gcwp_reset_plugin_nonce', 'gcwp_reset_nonce'); ?>
        
        <p>
            <label for="gcwp_reset_confirm">
                <input type="checkbox" name="gcwp_reset_confirm" id="gcwp_reset_confirm">
                <?php esc_html_e('Sim, eu entendo que desejo apagar todos os dados.', 'gerador-certificados-wp'); ?>
            </label>
        </p>

        <p class="submit">
            <input type="submit" name="gcwp_reset_submit" class="button button-danger" value="<?php esc_attr_e('Resetar Plugin', 'gerador-certificados-wp'); ?>" disabled>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    const resetButton = $('#gcwp-reset-form input[type="submit"]');
    const confirmCheckbox = $('#gcwp_reset_confirm');

    confirmCheckbox.on('change', function() {
        resetButton.prop('disabled', !this.checked);
    });

    $('#gcwp-reset-form').on('submit', function(e) {
        const isConfirmed = confirm('<?php echo esc_js(__('TEM CERTEZA ABSOLUTA? Esta ação não pode ser desfeita.', 'gerador-certificados-wp')); ?>');
        if (!isConfirmed) {
            e.preventDefault();
        }
    });
});
</script>

<style>
.gcwp-preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}
.gcwp-preview-wrapper {
    flex: 1;
    min-width: 400px;
}
.gcwp-preview-area {
    position: relative;
    width: 560px; /* Proporcional a A4 (297/210 * 400) */
    height: 396px;
    border: 1px solid #ccc;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.draggable-text {
    position: absolute;
    cursor: move;
    padding: 5px;
    background-color: rgba(255, 255, 0, 0.7);
    border: 1px dashed #333;
    font-size: 12px;
    white-space: nowrap;
    border-radius: 3px;
}
.gcwp-field-row td {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
}
.gcwp-setting-group {
    display: inline-flex;
    gap: 5px;
    align-items: center;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Função para inicializar os draggables
    function initDraggable(containerSelector) {
        const container = $(containerSelector);
        if (!container.length) return;

        const containerWidth = container.width();
        const containerHeight = container.height();
        const mmWidth = 297; // A4 Landscape width
        const mmHeight = 210; // A4 Landscape height

        // Posicionar elementos inicialmente
        container.find('.draggable-text').each(function() {
            const el = $(this);
            const field = el.data('field');
            const xInput = $(`input[name="${field}_pos_x"]`);
            const yInput = $(`input[name="${field}_pos_y"]`);

            const posX_mm = parseFloat(xInput.val()) || 0;
            const posY_mm = parseFloat(yInput.val()) || 0;

            const posX_px = (posX_mm / mmWidth) * containerWidth;
            const posY_px = (posY_mm / mmHeight) * containerHeight;

            el.css({ left: posX_px + 'px', top: posY_px + 'px' });
        });

        // Ativar o 'draggable'
        container.find('.draggable-text').draggable({
            containment: 'parent',
            stop: function(event, ui) {
                const el = $(this);
                const field = el.data('field');

                const posX_px = ui.position.left;
                const posY_px = ui.position.top;

                const posX_mm = Math.round((posX_px / containerWidth) * mmWidth);
                const posY_mm = Math.round((posY_px / containerHeight) * mmHeight);

                $(`input[name="${field}_pos_x"]`).val(posX_mm);
                $(`input[name="${field}_pos_y"]`).val(posY_mm);
            }
        });
    }

    initDraggable('#preview-frente');
    initDraggable('#preview-verso');
});
</script>