(function($){
    'use strict';

    $(document).ready(function(){
        // Save participant
        $('#gcwp-participant-form').on('submit', function(e){
            e.preventDefault();
            var form = $(this);
            var data = new FormData(this);
            data.append('gcwp_public_nonce', $('input[name="gcwp_public_nonce"]', form).val());
            $.ajax({
                url: gcwp_public.ajax_url,
                method: 'POST',
                data: data,
                processData: false,
                contentType: false,
                success: function(resp){
                    if(resp.success){
                        $('#gcwp-message').html('<div class="gcwp-alert gcwp-alert-success">'+resp.data.message+'</div>');
                        // reload participants list
                        location.reload();
                    } else {
                        $('#gcwp-message').html('<div class="gcwp-alert gcwp-alert-error">'+(resp.data && resp.data.message ? resp.data.message : 'Erro')+'</div>');
                    }
                }
            });
        });

        // Edit button (cards)
        $(document).on('click', '.gcwp-edit', function(e){
            e.preventDefault();
            var card = $(this).closest('.gcwp-card');
            var id = card.data('id');
            $('#gcwp-participant-id').val(id);
            $('#nome_completo').val( card.data('nome') );
            $('#curso').val( card.data('curso') );
            $('#email').val( card.data('email') );
            $('#data_inicio').val( card.data('data_inicio') );
            $('#data_termino').val( card.data('data_termino') );
            $('#duracao_horas').val( card.data('duracao_horas') );
            $('#cidade').val( card.data('cidade') );
            $('#data_emissao').val( card.data('data_emissao') );
            $('#numero_livro').val( card.data('numero_livro') );
            $('#numero_pagina').val( card.data('numero_pagina') );
            $('#numero_certificado').val( card.data('numero_certificado') );
            $('#gcwp-cancel-edit').show();
            $('html,body').animate({ scrollTop: $('#gcwp-participant-form').offset().top - 20 }, 300);
        });

        $('#gcwp-cancel-edit').on('click', function(){
            $('#gcwp-participant-id').val('0');
            $('#gcwp-participant-form')[0].reset();
            $(this).hide();
        });

        // Delete
        $(document).on('click', '.gcwp-delete', function(e){
            e.preventDefault();
            if(!confirm('Deseja excluir este participante?')) return;
            var id = $(this).data('id');
            var data = {
                action: 'gcwp_public_delete_participant',
                id: id,
                gcwp_public_nonce: $('input[name="gcwp_public_nonce"]').val()
            };
            $.post(gcwp_public.ajax_url, data, function(resp){
                if(resp.success){
                    location.reload();
                } else {
                    alert(resp.data && resp.data.message ? resp.data.message : 'Erro');
                }
            });
        });

        // Import CSV
        $('#gcwp-import-form').on('submit', function(e){
            e.preventDefault();
            var form = $(this)[0];
            var data = new FormData(form);
            $.ajax({
                url: gcwp_public.ajax_url,
                method: 'POST',
                data: data,
                processData: false,
                contentType: false,
                success: function(resp){
                    if(resp.success){
                        $('#gcwp-message').html('<div class="gcwp-alert gcwp-alert-success">'+resp.data.message+'</div>');
                        location.reload();
                    } else {
                        $('#gcwp-message').html('<div class="gcwp-alert gcwp-alert-error">'+(resp.data && resp.data.message ? resp.data.message : 'Erro')+'</div>');
                    }
                }
            });
        });

        // Emissão form
        $('#gcwp-emissao-form').on('submit', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            $.post(gcwp_public.ajax_url, data, function(resp){
                if(resp.success){
                    var html = '<div class="gcwp-alert gcwp-alert-success">'+resp.data.message+'</div>';
                    if(resp.data.result && resp.data.result.url){
                        html += '<p><a href="'+resp.data.result.url+'" target="_blank">Ver/Download do certificado</a></p>';
                    }
                    $('#gcwp-emissao-result').html(html);
                } else {
                    $('#gcwp-emissao-message').html('<div class="gcwp-alert gcwp-alert-error">'+(resp.data && resp.data.message ? resp.data.message : 'Erro')+'</div>');
                }
            });
        });
    });
})(jQuery);
