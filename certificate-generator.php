<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class GCWP_Certificate_Generator {

    /**
     * Generates a PDF certificate for a given participant.
     *
     * @param object $participant The participant's data.
     * @param string $modelo_id The template ID to use.
     * @return array|WP_Error Array with path and URL to the generated PDF or a WP_Error on failure.
     */
    public function generate_certificate( $participant, $modelo_id = '' ) {
        try {
            // Verificar se a biblioteca TCPDF/FPDI está disponível
            if (!class_exists('\\setasign\\Fpdi\\Tcpdf\\Fpdi')) {
                return new WP_Error( 'tcpdf_missing', __( 'A biblioteca TCPDF/FPDI não está instalada. Por favor, execute "composer update" na pasta do plugin.', 'gerador-certificados-wp' ) );
            }
            
            // Obter diretórios de upload
            $upload_dir = wp_upload_dir();
            $certificados_dir = $upload_dir['basedir'] . '/certificados';
            $modelos_dir = $certificados_dir . '/modelos';
            $emitidos_dir = $certificados_dir . '/emitidos';
            
            // Criar diretórios se não existirem
            foreach ([$certificados_dir, $modelos_dir, $emitidos_dir, $modelos_dir . '/frente', $modelos_dir . '/verso'] as $dir) {
                if (!file_exists($dir)) {
                    wp_mkdir_p($dir);
                }
            }
            
            // Obter caminhos dos modelos
            // Prioriza o modelo passado como parâmetro, senão, usa o modelo padrão selecionado.
            $modelo_selecionado_slug = !empty($modelo_id) ? $modelo_id : get_option('gcwp_modelo_selecionado', '');

            if (empty($modelo_selecionado_slug)) {
                return new WP_Error('no_model_selected', __('Nenhum modelo de certificado foi selecionado. Por favor, selecione um na página de Modelos.', 'gerador-certificados-wp'));
            }

            $modelo_dir = $modelos_dir . '/' . $modelo_selecionado_slug;
            
            $template_front = !empty(glob($modelo_dir . '/frente.*')) ? glob($modelo_dir . '/frente.*')[0] : '';
            $template_back = !empty(glob($modelo_dir . '/verso.*')) ? glob($modelo_dir . '/verso.*')[0] : '';

            if (empty($template_front) || !file_exists($template_front)) {
                return new WP_Error('missing_template', __('O modelo de certificado (frente) não foi encontrado.', 'gerador-certificados-wp'));
            }

            // 2. Initialize TCPDF in landscape mode
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('L', 'mm', 'A4');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false);

            // 3. Add Front Page
            $pdf->AddPage();
            // Use a imagem como fundo de página inteira (A4 Landscape: 297x210 mm)
            $pdf->Image($template_front, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

            // Função auxiliar para escrever texto com estilo individual
            $write_text = function($field, $text) use ($pdf) {
                $pos_x = get_option("gcwp_{$field}_pos_x");
                $pos_y = get_option("gcwp_{$field}_pos_y");
                $tamanho = get_option("gcwp_{$field}_tamanho");
                $cor = get_option("gcwp_{$field}_cor", '#000000');
                $fonte_nome = get_option("gcwp_{$field}_fonte");
                $negrito = get_option("gcwp_{$field}_negrito");
                $align = get_option("gcwp_{$field}_align", 'L'); // 'L' como padrão

                // Adiciona a fonte personalizada se for um arquivo TTF
                $font_file_path = GCWP_PLUGIN_DIR . 'fonts/' . $fonte_nome . '.ttf';
                if (file_exists($font_file_path)) {
                    // O método addTTFfont retorna o nome da fonte para ser usado em SetFont
                    $fonte_nome = TCPDF_FONTS::addTTFfont($font_file_path, 'TrueTypeUnicode', '', 32);
                }

                $estilo = $negrito == '1' ? 'B' : '';
                $pdf->SetFont($fonte_nome, $estilo, $tamanho);
                $pdf->SetTextColorArray($this->hex2rgb($cor, true));

                // Define a posição Y
                $pdf->SetY($pos_y);

                // Para alinhamento 'L', a coordenada X é usada.
                // Para 'C' e 'R', a coordenada X é ignorada e o alinhamento é relativo à página.
                $current_x = ($align === 'L') ? $pos_x : 0;
                $pdf->SetX($current_x);

                // A largura da célula (0) faz com que ela se estenda até a margem direita, permitindo o alinhamento.
                $pdf->Cell(0, 0, $text, 0, 0, $align);
            };

            // Formatar datas
            $data_inicio_f = date_i18n('d/m/Y', strtotime($participant->data_inicio));
            $data_termino_f = date_i18n('d/m/Y', strtotime($participant->data_termino));
            $data_emissao_f = date_i18n('d \d\e F \d\e Y', strtotime($participant->data_emissao)); // Ex: 04 de novembro de 2025
            $local_data_f = sprintf('%s, %s', $participant->cidade, $data_emissao_f);

            // Escrever campos da frente
            $write_text('nome', mb_strtoupper($participant->nome_completo, 'UTF-8'));
            $write_text('curso', mb_strtoupper($participant->curso, 'UTF-8'));
            $write_text('data_inicio', $data_inicio_f);
            $write_text('data_termino', $data_termino_f);
            $write_text('duracao', $participant->duracao_horas);
            $write_text('local_data', $local_data_f);
            
            // Adicionar verso se existir
            if (!empty($template_back) && file_exists($template_back)) {
                $pdf->AddPage();
                // Use a imagem como fundo de página inteira
                $pdf->Image($template_back, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

                // Escrever campos do verso
                $write_text('livro', $participant->numero_livro);
                $write_text('pagina', $participant->numero_pagina);
                $write_text('registro', $participant->numero_certificado);
            }
            
            // 4. Gerar nome de arquivo único
            $filename = 'certificado_' . sanitize_title($participant->nome_completo) . '_' . time() . '.pdf';
            $filepath = $emitidos_dir . '/' . $filename;
            
            // 5. Salvar PDF
            $pdf->Output($filepath, 'F');
            
            // 7. Retornar informações do arquivo gerado
             return array(
                 'path' => $filepath,
                 'url' => $upload_dir['baseurl'] . '/certificados/emitidos/' . $filename,
                 'filename' => $filename
             );
            } catch (\Throwable $e) {
            // Captura qualquer tipo de erro (Exception, ParseError, etc.)
            $error_message = sprintf(
                __('Ocorreu um erro ao gerar o PDF: %s no arquivo %s na linha %s.', 'gerador-certificados-wp'),
                $e->getMessage(), $e->getFile(), $e->getLine()
            );
            return new WP_Error('tcpdf_error', $error_message);
        }
    }

    /**
     * Envia o certificado por e-mail para o participante.
     *
     * @param object $participant Dados do participante.
     * @param string $filepath Caminho do arquivo PDF.
     * @return bool True se o e-mail foi enviado com sucesso, false caso contrário.
     */
    /**
     * Converte cor hexadecimal para RGB
     *
     * @param string $hex Cor em formato hexadecimal (ex: #000000)
     * @param bool $as_array Retorna um array para TCPDF se true
     * @return array Array com valores R, G e B
     */
    private function hex2rgb($hex, $as_array = false) {
        $hex = str_replace('#', '', $hex);
        
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        
        $rgb = array('r' => $r, 'g' => $g, 'b' => $b);

        if ($as_array) {
            return [$r, $g, $b];
        }
        return $rgb;
    }
}