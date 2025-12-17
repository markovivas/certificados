<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ( ! empty( $success_message ) ): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $success_message ); ?></p>
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
                    <div id="draggable-nome" class="draggable-text" data-field="nome" data-target-x="nome_pos_x" data-target-y="nome_pos_y">Nome do Participante</div>
                    <div id="draggable-curso" class="draggable-text" data-field="curso" data-target-x="curso_pos_x" data-target-y="curso_pos_y">Nome do Curso</div>
                    <div id="draggable-data-inicio" class="draggable-text" data-field="data_inicio" data-target-x="data_inicio_pos_x" data-target-y="data_inicio_pos_y">Data de Início</div>
                    <div id="draggable-data-termino" class="draggable-text" data-field="data_termino" data-target-x="data_termino_pos_x" data-target-y="data_termino_pos_y">Data de Término</div>
                    <div id="draggable-duracao" class="draggable-text" data-field="duracao" data-target-x="duracao_pos_x" data-target-y="duracao_pos_y">Duração (horas)</div>
                    <div id="draggable-local-data" class="draggable-text" data-field="local_data" data-target-x="local_data_pos_x" data-target-y="local_data_pos_y">Local e Data</div>
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
                    <div id="draggable-livro" class="draggable-text" data-field="livro" data-target-x="livro_pos_x" data-target-y="livro_pos_y">Livro: 001</div>
                    <div id="draggable-pagina" class="draggable-text" data-field="pagina" data-target-x="pagina_pos_x" data-target-y="pagina_pos_y">Página: 189</div>
                    <div id="draggable-registro" class="draggable-text" data-field="registro" data-target-x="registro_pos_x" data-target-y="registro_pos_y">Registro: 12718</div>
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
                'data_termino' => 'Data de Término', 'duracao' => 'Duração (horas)', 'local_data' => 'Local e Data'
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
                        <input type="hidden" name="<?php echo $field; ?>_align" value="L">
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
