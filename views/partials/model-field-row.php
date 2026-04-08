<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<section class="gcwp-field-row" data-index="<?php echo esc_attr( $index ); ?>">
    <div class="gcwp-field-row-head">
        <strong class="gcwp-field-title"><?php echo esc_html( $field['label'] ?: $field['key'] ); ?></strong>
        <button type="button" class="button-link-delete gcwp-remove-field"><?php esc_html_e( 'Remover', 'gerador-certificados-wp' ); ?></button>
    </div>

    <div class="gcwp-field-grid">
        <label>
            <span><?php esc_html_e( 'Chave', 'gerador-certificados-wp' ); ?></span>
            <input type="text" name="fields[<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $field['key'] ); ?>" class="gcwp-sync-key" required>
        </label>
        <label>
            <span><?php esc_html_e( 'Rotulo', 'gerador-certificados-wp' ); ?></span>
            <input type="text" name="fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field['label'] ); ?>" class="gcwp-sync-label" required>
        </label>
        <label>
            <span><?php esc_html_e( 'Pagina', 'gerador-certificados-wp' ); ?></span>
            <select name="fields[<?php echo esc_attr( $index ); ?>][page]" class="gcwp-sync-page">
                <option value="front" <?php selected( $field['page'], 'front' ); ?>><?php esc_html_e( 'Frente', 'gerador-certificados-wp' ); ?></option>
                <option value="back" <?php selected( $field['page'], 'back' ); ?>><?php esc_html_e( 'Verso', 'gerador-certificados-wp' ); ?></option>
            </select>
        </label>
        <label>
            <span><?php esc_html_e( 'Texto de exemplo', 'gerador-certificados-wp' ); ?></span>
            <input type="text" name="fields[<?php echo esc_attr( $index ); ?>][sample]" value="<?php echo esc_attr( $field['sample'] ); ?>" class="gcwp-sync-sample">
        </label>
        <label>
            <span>X (mm)</span>
            <input type="number" step="0.1" name="fields[<?php echo esc_attr( $index ); ?>][x]" value="<?php echo esc_attr( $field['x'] ); ?>" class="gcwp-sync-x" required>
        </label>
        <label>
            <span>Y (mm)</span>
            <input type="number" step="0.1" name="fields[<?php echo esc_attr( $index ); ?>][y]" value="<?php echo esc_attr( $field['y'] ); ?>" class="gcwp-sync-y" required>
        </label>
        <label>
            <span><?php esc_html_e( 'Largura (mm)', 'gerador-certificados-wp' ); ?></span>
            <input type="number" step="0.1" name="fields[<?php echo esc_attr( $index ); ?>][width]" value="<?php echo esc_attr( $field['width'] ); ?>" class="gcwp-sync-width" required>
        </label>
        <label>
            <span><?php esc_html_e( 'Tamanho', 'gerador-certificados-wp' ); ?></span>
            <input type="number" step="0.1" name="fields[<?php echo esc_attr( $index ); ?>][font_size]" value="<?php echo esc_attr( $field['font_size'] ); ?>" class="gcwp-sync-font-size" required>
        </label>
        <label>
            <span><?php esc_html_e( 'Cor', 'gerador-certificados-wp' ); ?></span>
            <input type="color" name="fields[<?php echo esc_attr( $index ); ?>][color]" value="<?php echo esc_attr( $field['color'] ); ?>" class="gcwp-sync-color">
        </label>
        <label>
            <span><?php esc_html_e( 'Fonte', 'gerador-certificados-wp' ); ?></span>
            <select name="fields[<?php echo esc_attr( $index ); ?>][font_family]" class="gcwp-sync-font-family">
                <?php foreach ( $available_fonts as $font_key => $font_name ) : ?>
                    <option value="<?php echo esc_attr( $font_key ); ?>" <?php selected( $field['font_family'], $font_key ); ?>><?php echo esc_html( $font_name ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span><?php esc_html_e( 'Alinhamento', 'gerador-certificados-wp' ); ?></span>
            <select name="fields[<?php echo esc_attr( $index ); ?>][align]" class="gcwp-sync-align">
                <option value="L" <?php selected( $field['align'], 'L' ); ?>><?php esc_html_e( 'Esquerda', 'gerador-certificados-wp' ); ?></option>
                <option value="C" <?php selected( $field['align'], 'C' ); ?>><?php esc_html_e( 'Centro', 'gerador-certificados-wp' ); ?></option>
                <option value="R" <?php selected( $field['align'], 'R' ); ?>><?php esc_html_e( 'Direita', 'gerador-certificados-wp' ); ?></option>
            </select>
        </label>
        <label class="gcwp-inline-toggle">
            <input type="checkbox" name="fields[<?php echo esc_attr( $index ); ?>][font_weight]" value="B" <?php checked( $field['font_weight'], 'B' ); ?> class="gcwp-sync-bold">
            <span><?php esc_html_e( 'Negrito', 'gerador-certificados-wp' ); ?></span>
        </label>
        <label class="gcwp-inline-toggle">
            <input type="checkbox" name="fields[<?php echo esc_attr( $index ); ?>][uppercase]" value="1" <?php checked( ! empty( $field['uppercase'] ) ); ?> class="gcwp-sync-uppercase">
            <span><?php esc_html_e( 'Maiusculas', 'gerador-certificados-wp' ); ?></span>
        </label>
    </div>
</section>
