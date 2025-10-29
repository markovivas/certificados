<?php
use Mpdf\Mpdf;
use Mpdf\MpdfException;

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
            // Verificar se a biblioteca mPDF está disponível
            if (!class_exists('\\Mpdf\\Mpdf')) {
                return new WP_Error( 'mpdf_missing', __( 'A biblioteca mPDF não está instalada. Por favor, instale o Composer e execute "composer require mpdf/mpdf" na pasta do plugin.', 'gerador-certificados-wp' ) );
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
            $template_front_url = get_option('gcwp_modelo_frente', '');
            $template_back_url = get_option('gcwp_modelo_verso', '');

            $template_front = !empty($template_front_url) ? str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $template_front_url) : '';
            $template_back = !empty($template_back_url) ? str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $template_back_url) : '';

            if (empty($template_front) || !file_exists($template_front)) {
                return new WP_Error('missing_template', __('O modelo de certificado (frente) não foi encontrado.', 'gerador-certificados-wp'));
            }

            // 2. Initialize mPDF in landscape mode
            $mpdf = new Mpdf([
                'mode'   => 'utf-8',
                'format' => 'A4-L', // A4 Landscape
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);

            // 3. Add Front Page
            $mpdf->AddPage();
            $mpdf->SetDocTemplate($template_front, true);
            
            // Obter configurações de posição e estilo
            $nome_pos_x = get_option('gcwp_nome_pos_x', '60');
            $nome_pos_y = get_option('gcwp_nome_pos_y', '100');
            $curso_pos_x = get_option('gcwp_curso_pos_x', '60');
            $curso_pos_y = get_option('gcwp_curso_pos_y', '120');
            $carga_pos_x = get_option('gcwp_carga_pos_x', '60');
            $carga_pos_y = get_option('gcwp_carga_pos_y', '130');
            $data_pos_x = get_option('gcwp_data_pos_x', '60');
            $data_pos_y = get_option('gcwp_data_pos_y', '140');
            
            $nome_tamanho = get_option('gcwp_nome_tamanho', '24');
            $nome_cor = get_option('gcwp_nome_cor', '#000000');
            $nome_negrito = get_option('gcwp_nome_negrito', '1');
            
            $texto_tamanho = get_option('gcwp_texto_tamanho', '14');
            $texto_cor = get_option('gcwp_texto_cor', '#000000');
            $texto_negrito = get_option('gcwp_texto_negrito', '0');
            
            // Converter cor hex para RGB
            $nome_rgb = $this->hex2rgb($nome_cor);
            $texto_rgb = $this->hex2rgb($texto_cor);
            
            // Adicionar dados do participante - Nome
            $nome_estilo = $nome_negrito == '1' ? 'B' : '';
            $mpdf->SetFont('helvetica', $nome_estilo, $nome_tamanho);
            $mpdf->SetTextColor($nome_rgb['r'], $nome_rgb['g'], $nome_rgb['b']);
            
            // Posicionar e adicionar o nome do participante
            $mpdf->SetXY($nome_pos_x, $nome_pos_y);
            $mpdf->Write(0, $participant->nome_completo);
            
            // Configurar estilo para outros textos
            $texto_estilo = $texto_negrito == '1' ? 'B' : '';
            $mpdf->SetFont('helvetica', $texto_estilo, $texto_tamanho);
            $mpdf->SetTextColor($texto_rgb['r'], $texto_rgb['g'], $texto_rgb['b']);
            
            // Adicionar informações adicionais
            $mpdf->SetXY($curso_pos_x, $curso_pos_y);
            $mpdf->Write(0, sprintf(__('Curso: %s', 'gerador-certificados-wp'), $participant->curso));
            
            $mpdf->SetXY($carga_pos_x, $carga_pos_y);
            $mpdf->Write(0, sprintf(__('Carga Horária: %s horas', 'gerador-certificados-wp'), $participant->duracao_horas));
            
            $mpdf->SetXY($data_pos_x, $data_pos_y);
            $mpdf->Write(0, sprintf(__('Data: %s', 'gerador-certificados-wp'), date_i18n(get_option('date_format'))));
            
            // Adicionar verso se existir
            if (!empty($template_back) && file_exists($template_back)) {
                $mpdf->AddPage();
                $mpdf->SetDocTemplate($template_back, true);
            }
            
            // 4. Gerar nome de arquivo único
            $filename = 'certificado_' . sanitize_title($participant->nome_completo) . '_' . time() . '.pdf';
            $filepath = $emitidos_dir . '/' . $filename;
            
            // 5. Salvar PDF
            $mpdf->Output($filepath, 'F');
            
            // 7. Retornar informações do arquivo gerado
             return array(
                 'path' => $filepath,
                 'url' => $upload_dir['baseurl'] . '/certificados/emitidos/' . $filename,
                 'filename' => $filename
             );
            } catch (MpdfException $e) {
            return new WP_Error('mpdf_error', $e->getMessage());
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
     * @return array Array com valores R, G e B
     */
    private function hex2rgb($hex) {
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
        
        return array('r' => $r, 'g' => $g, 'b' => $b);
    }
}