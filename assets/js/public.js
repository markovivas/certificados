jQuery(document).ready(function($) {
    // Add participant button
    $('#gcwp-add-participant-btn').on('click', function() {
        $('#gcwp-form-title').text('Adicionar Novo Participante');
        $('#gcwp-participant-form').show();
        $('#gcwp-participant-form-data input[name="participant_id"]').val('');
        $('#gcwp-participant-form-data')[0].reset();
    });

    // Close form button
    $('#gcwp-close-form').on('click', function() {
        $('#gcwp-participant-form').hide();
    });

    // Edit participant
    $('.edit-participant').on('click', function() {
        var participantId = $(this).data('id');
        // Load participant data via AJAX
        $.ajax({
            url: gcwp_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gcwp_get_participant',
                participant_id: participantId,
                nonce: gcwp_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#gcwp-form-title').text('Editar Participante');
                    $('#gcwp-participant-form-data input[name="participant_id"]').val(data.id);
                    $('#nome_completo').val(data.nome_completo);
                    $('#email').val(data.email);
                    $('#curso').val(data.curso);
                    $('#data_inicio').val(data.data_inicio);
                    $('#data_termino').val(data.data_termino);
                    $('#duracao_horas').val(data.duracao_horas);
                    $('#cidade').val(data.cidade);
                    $('#data_emissao').val(data.data_emissao);
                    $('#numero_livro').val(data.numero_livro);
                    $('#numero_pagina').val(data.numero_pagina);
                    $('#numero_certificado').val(data.numero_certificado);
                    $('#gcwp-participant-form').show();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Delete participant
    $('.delete-participant').on('click', function() {
        if (!confirm('Tem certeza que deseja excluir este participante?')) {
            return;
        }
        var participantId = $(this).data('id');
        $.ajax({
            url: gcwp_public_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gcwp_delete_participant',
                participant_id: participantId,
                nonce: gcwp_public_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Cancel edit
    $('#gcwp-cancel-edit').on('click', function() {
        $('#gcwp-participant-form').hide();
    });

    // Save participant
    $('#gcwp-participant-form-data').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=gcwp_public_save_participant&nonce=' + gcwp_public_ajax.nonce;

        $.ajax({
            url: gcwp_public_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // Generate certificate
    $('#gcwp-generate-certificate-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=gcwp_public_generate_certificate&nonce=' + gcwp_public_ajax.nonce;

        // Disable button during processing
        var $button = $(this).find('.gcwp-generate-btn');
        $button.prop('disabled', true).text('Gerando...');

        $.ajax({
            url: gcwp_public_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                var $resultSection = $('#gcwp-certificate-result');
                var $resultContent = $('#gcwp-result-content');

                if (response.success) {
                    $resultContent.html('<p class="gcwp-success">' + response.data.message + ' <a href="' + response.data.url + '" target="_blank">Baixar Certificado</a></p>');
                } else {
                    $resultContent.html('<p class="gcwp-error">' + response.data + '</p>');
                }

                $resultSection.show();
                // Scroll to result
                $('html, body').animate({
                    scrollTop: $resultSection.offset().top - 20
                }, 500);
            },
            error: function() {
                $('#gcwp-result-content').html('<p class="gcwp-error">Erro ao processar a solicitação.</p>');
                $('#gcwp-certificate-result').show();
            },
            complete: function() {
                $button.prop('disabled', false).text('Gerar Certificado');
            }
        });
    });
});