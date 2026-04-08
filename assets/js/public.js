jQuery(function($) {
    $('#gcwp-add-participant-btn').on('click', function() {
        $('#gcwp-form-title').text('Adicionar participante');
        $('#gcwp-participant-form').show();
        $('#gcwp-participant-form-data')[0].reset();
        $('#gcwp-participant-form-data input[name="participant_id"]').val('');
    });

    $('#gcwp-close-form, #gcwp-cancel-edit').on('click', function() {
        $('#gcwp-participant-form').hide();
    });

    $(document).on('click', '.edit-participant', function() {
        $.post(gcwp_public_ajax.ajax_url, {
            action: 'gcwp_get_participant',
            participant_id: $(this).data('id'),
            nonce: gcwp_public_ajax.nonce
        }).done(function(response) {
            if (!response.success) {
                alert(response.data);
                return;
            }

            const data = response.data;
            $('#gcwp-form-title').text('Editar participante');
            $('#gcwp-participant-form').show();
            $('#gcwp-participant-form-data input[name="participant_id"]').val(data.id);
            $('#nome_completo').val(data.nome_completo);
            $('#email').val(data.email);
            $('#curso').val(data.curso);
            $('#cidade').val(data.cidade);
            $('#data_inicio').val(data.data_inicio);
            $('#data_termino').val(data.data_termino);
            $('#duracao_horas').val(data.duracao_horas);
            $('#data_emissao').val(data.data_emissao);
            $('#numero_livro').val(data.numero_livro);
            $('#numero_pagina').val(data.numero_pagina);
            $('#numero_certificado').val(data.numero_certificado);
        });
    });

    $(document).on('click', '.delete-participant', function() {
        if (!window.confirm('Tem certeza que deseja excluir este participante?')) {
            return;
        }

        $.post(gcwp_public_ajax.ajax_url, {
            action: 'gcwp_delete_participant',
            participant_id: $(this).data('id'),
            nonce: gcwp_public_ajax.nonce
        }).done(function(response) {
            if (response.success) {
                window.location.reload();
                return;
            }

            alert(response.data);
        });
    });

    $('#gcwp-participant-form-data').on('submit', function(e) {
        e.preventDefault();

        $.post(gcwp_public_ajax.ajax_url, $(this).serialize() + '&action=gcwp_public_save_participant&nonce=' + gcwp_public_ajax.nonce)
            .done(function(response) {
                if (response.success) {
                    window.location.reload();
                    return;
                }

                alert(response.data);
            });
    });

    $('#gcwp-generate-certificate-form').on('submit', function(e) {
        e.preventDefault();

        const button = $(this).find('.gcwp-generate-btn');
        button.prop('disabled', true).val('Gerando...');

        $.post(gcwp_public_ajax.ajax_url, $(this).serialize() + '&action=gcwp_public_generate_certificate&nonce=' + gcwp_public_ajax.nonce)
            .done(function(response) {
                const result = $('#gcwp-certificate-result');
                const content = $('#gcwp-result-content');

                if (response.success) {
                    content.html('<p class="gcwp-success">' + response.data.message + ' <a href="' + response.data.url + '" target="_blank">' + gcwp_public_ajax.strings.download + '</a></p>');
                } else {
                    content.html('<p class="gcwp-error">' + response.data + '</p>');
                }

                result.show();
                $('html, body').animate({ scrollTop: result.offset().top - 20 }, 400);
            })
            .fail(function() {
                $('#gcwp-certificate-result').show();
                $('#gcwp-result-content').html('<p class="gcwp-error">' + gcwp_public_ajax.strings.error + '</p>');
            })
            .always(function() {
                button.prop('disabled', false).val('Gerar certificado');
            });
    });
});
