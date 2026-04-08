(function($) {
    'use strict';

    const pageMm = { width: 297, height: 210 };

    function postTemplateAction(data, onSuccess) {
        $.ajax({
            url: gcwp_ajax.ajax_url,
            type: 'POST',
            data: Object.assign({
                action: 'gcwp_manage_template',
                nonce: gcwp_ajax.nonce
            }, data),
            success: function(response) {
                if (response.success) {
                    onSuccess(response);
                    return;
                }

                alert(response.data.message || gcwp_ajax.strings.error);
            },
            error: function() {
                alert(gcwp_ajax.strings.error || 'Erro');
            }
        });
    }

    $(document).on('click', '.select-modelo', function(e) {
        e.preventDefault();
        const card = $(this).closest('.gcwp-model-card');
        const button = $(this);

        button.prop('disabled', true).text(gcwp_ajax.strings.processing);

        postTemplateAction({
            sub_action: 'select',
            slug: card.data('slug')
        }, function(response) {
            $('.gcwp-model-card').removeClass('is-selected');
            $('.select-modelo').text(gcwp_ajax.strings.select).prop('disabled', false);
            card.addClass('is-selected');
            button.text(gcwp_ajax.strings.selected);
            alert(response.data.message);
        });
    });

    $(document).on('click', '.rename-modelo', function(e) {
        e.preventDefault();
        const card = $(this).closest('.gcwp-model-card');
        const newName = window.prompt(gcwp_ajax.strings.fieldNamePrompt);

        if (!newName) {
            return;
        }

        postTemplateAction({
            sub_action: 'rename',
            slug: card.data('slug'),
            new_name: newName
        }, function() {
            window.location.reload();
        });
    });

    $(document).on('click', '.delete-modelo', function(e) {
        e.preventDefault();

        if (!window.confirm(gcwp_ajax.strings.deleteConfirm)) {
            return;
        }

        const card = $(this).closest('.gcwp-model-card');

        postTemplateAction({
            sub_action: 'delete',
            slug: card.data('slug')
        }, function() {
            card.remove();
        });
    });

    $(document).on('click', '.reenviar-certificado', function(e) {
        e.preventDefault();
        const button = $(this);

        $.post(gcwp_ajax.ajax_url, {
            action: 'gcwp_reenviar_certificado',
            file: button.data('file'),
            nonce: gcwp_ajax.nonce
        }).done(function(response) {
            alert(response.data.message);
        }).fail(function() {
            alert(gcwp_ajax.strings.error || 'Erro');
        });
    });

    $(document).on('click', '.excluir-certificado', function(e) {
        e.preventDefault();
        const button = $(this);

        if (!window.confirm(gcwp_ajax.strings.deleteConfirm)) {
            return;
        }

        $.post(gcwp_ajax.ajax_url, {
            action: 'gcwp_delete_certificate',
            file: button.data('file'),
            nonce: gcwp_ajax.nonce
        }).done(function(response) {
            if (response.success) {
                window.location.reload();
                return;
            }

            alert(response.data.message);
        }).fail(function() {
            alert(gcwp_ajax.strings.error || 'Erro');
        });
    });

    function getPreviewBoard(page) {
        return page === 'back' ? $('#gcwp-preview-back') : $('#gcwp-preview-front');
    }

    function fieldTitle(row) {
        return row.find('.gcwp-sync-label').val() || row.find('.gcwp-sync-key').val() || gcwp_ajax.strings.fieldDefault;
    }

    function updateFieldRowTitle(row) {
        row.find('.gcwp-field-title').text(fieldTitle(row));
    }

    function updateOverlay(row) {
        const page = row.find('.gcwp-sync-page').val();
        const board = getPreviewBoard(page);
        const index = row.data('index');
        let overlay = $('.gcwp-overlay[data-index="' + index + '"]');

        $('.gcwp-overlay[data-index="' + index + '"]').appendTo(board);

        if (!overlay.length) {
            overlay = $('<div class="gcwp-overlay"></div>').attr('data-index', index);
            board.append(overlay);
        }

        const sample = row.find('.gcwp-sync-sample').val() || fieldTitle(row);
        const x = parseFloat(row.find('.gcwp-sync-x').val()) || 0;
        const y = parseFloat(row.find('.gcwp-sync-y').val()) || 0;
        const width = parseFloat(row.find('.gcwp-sync-width').val()) || 80;
        const fontSize = parseFloat(row.find('.gcwp-sync-font-size').val()) || 18;
        const color = row.find('.gcwp-sync-color').val() || '#000000';
        const align = row.find('.gcwp-sync-align').val() || 'L';
        const isBold = row.find('.gcwp-sync-bold').is(':checked');
        const uppercase = row.find('.gcwp-sync-uppercase').is(':checked');
        const text = uppercase ? sample.toUpperCase() : sample;

        overlay.text(text);
        overlay.css({
            left: ((x / pageMm.width) * board.width()) + 'px',
            top: ((y / pageMm.height) * board.height()) + 'px',
            width: ((width / pageMm.width) * board.width()) + 'px',
            color: color,
            fontSize: Math.max(11, (fontSize / 210) * board.height()) + 'px',
            fontWeight: isBold ? '700' : '400',
            textAlign: align === 'C' ? 'center' : (align === 'R' ? 'right' : 'left')
        });

        overlay.draggable({
            containment: 'parent',
            stop: function(event, ui) {
                row.find('.gcwp-sync-x').val(((ui.position.left / board.width()) * pageMm.width).toFixed(1));
                row.find('.gcwp-sync-y').val(((ui.position.top / board.height()) * pageMm.height).toFixed(1));
            }
        });
    }

    function refreshAllOverlays() {
        $('.gcwp-field-row').each(function() {
            updateFieldRowTitle($(this));
            updateOverlay($(this));
        });
    }

    function createFieldRow(index) {
        return $(`
            <section class="gcwp-field-row" data-index="${index}">
                <div class="gcwp-field-row-head">
                    <strong class="gcwp-field-title">${gcwp_ajax.strings.fieldDefault}</strong>
                    <button type="button" class="button-link-delete gcwp-remove-field">Remover</button>
                </div>
                <div class="gcwp-field-grid">
                    <label><span>Chave</span><input type="text" name="fields[${index}][key]" class="gcwp-sync-key" required></label>
                    <label><span>Rotulo</span><input type="text" name="fields[${index}][label]" class="gcwp-sync-label" value="${gcwp_ajax.strings.fieldDefault}" required></label>
                    <label><span>Pagina</span><select name="fields[${index}][page]" class="gcwp-sync-page"><option value="front">Frente</option><option value="back">Verso</option></select></label>
                    <label><span>Texto de exemplo</span><input type="text" name="fields[${index}][sample]" class="gcwp-sync-sample" value="${gcwp_ajax.strings.fieldSample}"></label>
                    <label><span>X (mm)</span><input type="number" step="0.1" name="fields[${index}][x]" class="gcwp-sync-x" value="20"></label>
                    <label><span>Y (mm)</span><input type="number" step="0.1" name="fields[${index}][y]" class="gcwp-sync-y" value="20"></label>
                    <label><span>Largura (mm)</span><input type="number" step="0.1" name="fields[${index}][width]" class="gcwp-sync-width" value="80"></label>
                    <label><span>Tamanho</span><input type="number" step="0.1" name="fields[${index}][font_size]" class="gcwp-sync-font-size" value="18"></label>
                    <label><span>Cor</span><input type="color" name="fields[${index}][color]" class="gcwp-sync-color" value="#000000"></label>
                    <label><span>Fonte</span><select name="fields[${index}][font_family]" class="gcwp-sync-font-family">${gcwp_ajax.fontOptions}</select></label>
                    <label><span>Alinhamento</span><select name="fields[${index}][align]" class="gcwp-sync-align"><option value="L">Esquerda</option><option value="C">Centro</option><option value="R">Direita</option></select></label>
                    <label class="gcwp-inline-toggle"><input type="checkbox" name="fields[${index}][font_weight]" value="B" class="gcwp-sync-bold"><span>Negrito</span></label>
                    <label class="gcwp-inline-toggle"><input type="checkbox" name="fields[${index}][uppercase]" value="1" class="gcwp-sync-uppercase"><span>Maiusculas</span></label>
                </div>
            </section>
        `);
    }

    const fieldList = $('#gcwp-field-list');

    if (fieldList.length) {
        refreshAllOverlays();

        $(document).on('input change', '.gcwp-field-row input, .gcwp-field-row select', function() {
            const row = $(this).closest('.gcwp-field-row');
            updateFieldRowTitle(row);
            updateOverlay(row);
        });

        $(document).on('click', '.gcwp-remove-field', function() {
            const row = $(this).closest('.gcwp-field-row');
            $('.gcwp-overlay[data-index="' + row.data('index') + '"]').remove();
            row.remove();
        });

        $('#gcwp-add-field').on('click', function() {
            const rows = fieldList.find('.gcwp-field-row');
            const nextIndex = rows.length ? Math.max.apply(null, rows.map(function() {
                return parseInt($(this).data('index'), 10);
            }).get()) + 1 : 0;

            const row = createFieldRow(nextIndex);
            fieldList.append(row);
            updateOverlay(row);
        });
    }

    const resetButton = $('#gcwp-reset-form input[type="submit"]');
    const confirmCheckbox = $('#gcwp_reset_confirm');
    if (confirmCheckbox.length) {
        confirmCheckbox.on('change', function() {
            resetButton.prop('disabled', !this.checked);
        });

        $('#gcwp-reset-form').on('submit', function(e) {
            if (!window.confirm(gcwp_ajax.confirm_reset)) {
                e.preventDefault();
            }
        });
    }
})(jQuery);
