(function($) {
    'use strict';

    // Navegação por abas
    $('.nav-tab').on('click', function(e) {
        if ($(this).attr('href').startsWith('#')) {
            e.preventDefault();
            
            // Atualizar abas ativas
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Mostrar conteúdo da aba
            var target = $(this).attr('href');
            $('.tab-content').hide();
            $(target).show();
        }
    });

    // Selecionar modelo
    $('.select-modelo').on('click', function(e) {
        e.preventDefault();
        var card = $(this).closest('.modelo-card');
        var slug = card.data('slug');
        var btn = $(this);
        
        btn.prop('disabled', true).text('Processando...');
        
        $.ajax({
            url: gcwp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gcwp_manage_template',
                sub_action: 'select',
                slug: slug,
                nonce: gcwp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.modelo-card').removeClass('selected');
                    $('.select-modelo').text('Selecionar').prop('disabled', false);
                    card.addClass('selected');
                    btn.text('Selecionado');
                    alert(response.data.message);
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false).text('Selecionar');
                }
            },
            error: function() {
                alert('Erro ao processar a solicitação.');
                btn.prop('disabled', false).text('Selecionar');
            }
        });
    });

    // Renomear modelo
    $('.rename-modelo').on('click', function(e) {
        e.preventDefault();
        var card = $(this).closest('.modelo-card');
        var slug = card.data('slug');
        var newName = prompt('Novo nome para o modelo:');
        
        if (newName && newName.trim() !== '') {
            $.ajax({
                url: gcwp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gcwp_manage_template',
                    sub_action: 'rename',
                    slug: slug,
                    new_name: newName,
                    nonce: gcwp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação.');
                }
            });
        }
    });

    // Apagar modelo
    $('.delete-modelo').on('click', function(e) {
        e.preventDefault();
        if (confirm('Tem certeza que deseja apagar este modelo? Esta ação não pode ser desfeita.')) {
            var card = $(this).closest('.modelo-card');
            var slug = card.data('slug');
            
            $.ajax({
                url: gcwp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gcwp_manage_template',
                    sub_action: 'delete',
                    slug: slug,
                    nonce: gcwp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        card.fadeOut(function() {
                            $(this).remove();
                            if ($('.modelo-card').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação.');
                }
            });
        }
    });

    // Reenviar certificado
    $('.reenviar-certificado').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var file = btn.data('file');
        
        if (confirm('Deseja reenviar o certificado para o e-mail do participante?')) {
            btn.prop('disabled', true).text('Enviando...');
            
            $.ajax({
                url: gcwp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gcwp_reenviar_certificado',
                    file: file,
                    nonce: gcwp_ajax.nonce
                },
                success: function(response) {
                    alert(response.data.message);
                    btn.prop('disabled', false).text('Reenviar E-mail');
                },
                error: function() {
                    alert('Erro ao enviar e-mail.');
                    btn.prop('disabled', false).text('Reenviar E-mail');
                }
            });
        }
    });

    // Excluir certificado
    $('.excluir-certificado').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var file = btn.data('file');
        
        if (confirm('Tem certeza que deseja excluir este certificado? Esta ação não pode ser desfeita.')) {
            btn.prop('disabled', true).text('Excluindo...');
            
            $.ajax({
                url: gcwp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gcwp_delete_certificate',
                    file: file,
                    nonce: gcwp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Excluir');
                    }
                },
                error: function() {
                    alert('Erro ao excluir certificado.');
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Excluir');
                }
            });
        }
    });
    // Reset Plugin Confirmation
    const resetButton = $('#gcwp-reset-form input[type="submit"]');
    const confirmCheckbox = $('#gcwp_reset_confirm');

    if (confirmCheckbox.length) {
        confirmCheckbox.on('change', function() {
            resetButton.prop('disabled', !this.checked);
        });

        $('#gcwp-reset-form').on('submit', function(e) {
            const isConfirmed = confirm(gcwp_ajax.confirm_reset);
            if (!isConfirmed) {
                e.preventDefault();
            }
        });
    }

    // Draggable Functionality
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

})(jQuery);