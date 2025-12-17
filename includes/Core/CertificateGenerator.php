<?php

namespace GCWP\Core;

use WP_Error;
use TCPDF_FONTS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CertificateGenerator {

    /**
     * Generates a PDF certificate for a given participant.
     *
     * @param object $participant The participant's data.
     * @param string $modelo_id The template ID to use.
     * @return array|WP_Error Array with path and URL to the generated PDF or a WP_Error on failure.
     */
    public function generate_certificate( $participant, $modelo_id = '' ) {
        try {
            // Check for TCPDF/FPDI
            if (!class_exists('\\setasign\\Fpdi\\Tcpdf\\Fpdi')) {
                return new WP_Error( 'tcpdf_missing', __( 'A biblioteca TCPDF/FPDI não está instalada. Por favor, execute "composer update" na pasta do plugin.', 'gerador-certificados-wp' ) );
            }
            
            // Setup directories
            $upload_dir = wp_upload_dir();
            $certificados_dir = $upload_dir['basedir'] . '/certificados';
            $modelos_dir = $certificados_dir . '/modelos';
            $emitidos_dir = $certificados_dir . '/emitidos';
            
            $this->ensure_directories([$certificados_dir, $modelos_dir, $emitidos_dir, $modelos_dir . '/frente', $modelos_dir . '/verso']);
            
            // Get template paths
            $modelo_selecionado_slug = !empty($modelo_id) ? $modelo_id : get_option('gcwp_modelo_selecionado', '');

            if (empty($modelo_selecionado_slug)) {
                return new WP_Error('no_model_selected', __('Nenhum modelo de certificado foi selecionado.', 'gerador-certificados-wp'));
            }

            $modelo_dir = $modelos_dir . '/' . $modelo_selecionado_slug;
            
            $template_front = !empty(glob($modelo_dir . '/frente.*')) ? glob($modelo_dir . '/frente.*')[0] : '';
            $template_back = !empty(glob($modelo_dir . '/verso.*')) ? glob($modelo_dir . '/verso.*')[0] : '';

            if (empty($template_front) || !file_exists($template_front)) {
                return new WP_Error('missing_template', __('O modelo de certificado (frente) não foi encontrado.', 'gerador-certificados-wp'));
            }

            // Process images to handle alpha channel
            $template_front = $this->process_image_for_tcpdf($template_front);
            if (!empty($template_back) && file_exists($template_back)) {
                $template_back = $this->process_image_for_tcpdf($template_back);
            }

            // Initialize TCPDF
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi('L', 'mm', 'A4');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false);

            // Add Front Page
            $pdf->AddPage();
            $pdf->Image($template_front, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

            // Format dates
            $data_inicio_f = date_i18n('d/m/Y', strtotime($participant->data_inicio));
            $data_termino_f = date_i18n('d/m/Y', strtotime($participant->data_termino));
            $data_emissao_f = date_i18n('d \d\e F \d\e Y', strtotime($participant->data_emissao));
            $local_data_f = sprintf('%s, %s', $participant->cidade, $data_emissao_f);

            // Write fields
            $this->write_text($pdf, 'nome', mb_strtoupper($participant->nome_completo, 'UTF-8'));
            $this->write_text($pdf, 'curso', mb_strtoupper($participant->curso, 'UTF-8'));
            $this->write_text($pdf, 'data_inicio', $data_inicio_f);
            $this->write_text($pdf, 'data_termino', $data_termino_f);
            $this->write_text($pdf, 'duracao', $participant->duracao_horas);
            $this->write_text($pdf, 'local_data', $local_data_f);
            
            // Add Back Page if exists
            if (!empty($template_back) && file_exists($template_back)) {
                $pdf->AddPage();
                $pdf->Image($template_back, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

                $this->write_text($pdf, 'livro', $participant->numero_livro);
                $this->write_text($pdf, 'pagina', $participant->numero_pagina);
                $this->write_text($pdf, 'registro', $participant->numero_certificado);
            }
            
            // Generate filename and save
            $filename = 'certificado_' . sanitize_title($participant->nome_completo) . '_' . time() . '.pdf';
            $filepath = $emitidos_dir . '/' . $filename;
            
            $pdf->Output($filepath, 'F');
            
            return array(
                 'path' => $filepath,
                 'url' => $upload_dir['baseurl'] . '/certificados/emitidos/' . $filename,
                 'filename' => $filename
            );

        } catch (\Throwable $e) {
            $error_message = sprintf(
                __('Ocorreu um erro ao gerar o PDF: %s', 'gerador-certificados-wp'),
                $e->getMessage()
            );
            return new WP_Error('tcpdf_error', $error_message);
        }
    }

    /**
     * Ensure directories exist
     */
    private function ensure_directories($dirs) {
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    /**
     * Write text to PDF with styles from options
     */
    private function write_text($pdf, $field, $text) {
        // Default settings based on field
        $defaults = [
            'nome' => ['pos_x' => 0, 'pos_y' => 100, 'tamanho' => 24, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '1', 'align' => 'C'],
            'curso' => ['pos_x' => 0, 'pos_y' => 130, 'tamanho' => 18, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'C'],
            'data_inicio' => ['pos_x' => 50, 'pos_y' => 160, 'tamanho' => 12, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'L'],
            'data_termino' => ['pos_x' => 150, 'pos_y' => 160, 'tamanho' => 12, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'L'],
            'duracao' => ['pos_x' => 100, 'pos_y' => 180, 'tamanho' => 14, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'C'],
            'local_data' => ['pos_x' => 0, 'pos_y' => 200, 'tamanho' => 12, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'C'],
            'livro' => ['pos_x' => 50, 'pos_y' => 220, 'tamanho' => 10, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'L'],
            'pagina' => ['pos_x' => 150, 'pos_y' => 220, 'tamanho' => 10, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'L'],
            'registro' => ['pos_x' => 250, 'pos_y' => 220, 'tamanho' => 10, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'L'],
        ];

        $default = isset($defaults[$field]) ? $defaults[$field] : ['pos_x' => 10, 'pos_y' => 10, 'tamanho' => 12, 'cor' => '#000000', 'fonte' => 'helvetica', 'negrito' => '0', 'align' => 'L'];

        $pos_x = get_option("gcwp_{$field}_pos_x", $default['pos_x']);
        $pos_y = get_option("gcwp_{$field}_pos_y", $default['pos_y']);
        $tamanho = get_option("gcwp_{$field}_tamanho", $default['tamanho']);
        $cor = get_option("gcwp_{$field}_cor", $default['cor']);
        $fonte_nome = get_option("gcwp_{$field}_fonte", $default['fonte']);
        $negrito = get_option("gcwp_{$field}_negrito", $default['negrito']);
        $align = get_option("gcwp_{$field}_align", $default['align']);

        // Custom font
        $font_file_path = GCWP_PLUGIN_DIR . 'fonts/' . $fonte_nome . '.ttf';
        if (file_exists($font_file_path)) {
            $fonte_nome = TCPDF_FONTS::addTTFfont($font_file_path, 'TrueTypeUnicode', '', 32);
        } elseif (!in_array($fonte_nome, ['times', 'helvetica', 'courier'])) {
            // Fallback to default if custom font not found and not a core font
            $fonte_nome = 'helvetica';
        }

        // Custom font
        $font_file_path = GCWP_PLUGIN_DIR . 'fonts/' . $fonte_nome . '.ttf';
        if (file_exists($font_file_path)) {
            $fonte_nome = TCPDF_FONTS::addTTFfont($font_file_path, 'TrueTypeUnicode', '', 32);
        } elseif (!in_array($fonte_nome, ['times', 'helvetica', 'courier'])) {
            // Fallback to default if custom font not found and not a core font
            $fonte_nome = 'helvetica';
        }

        $estilo = $negrito == '1' ? 'B' : '';
        $pdf->SetFont($fonte_nome, $estilo, $tamanho);
        $pdf->SetTextColorArray(Utils::hex2rgb($cor, true));

        $pdf->SetY($pos_y);

        $current_x = ($align === 'L') ? $pos_x : 0;
        $pdf->SetX($current_x);

        $pdf->Cell(0, 0, $text, 0, 0, $align);
    }

    /**
     * Process image to handle alpha channel for TCPDF compatibility
     */
    private function process_image_for_tcpdf($image_path) {
        if (!function_exists('imagecreatefrompng') || !function_exists('imagejpeg')) {
            // GD not available, return original
            return $image_path;
        }

        $extension = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
        if ($extension !== 'png') {
            return $image_path; // Only process PNGs
        }

        // Check if image has alpha channel
        $image_info = getimagesize($image_path);
        if ($image_info['mime'] !== 'image/png') {
            return $image_path;
        }

        // Load PNG
        $image = @imagecreatefrompng($image_path);
        if (!$image) {
            return $image_path;
        }

        // Check if has alpha
        $has_alpha = false;
        $width = imagesx($image);
        $height = imagesy($image);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                $alpha = ($color & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    $has_alpha = true;
                    break 2;
                }
            }
        }

        if (!$has_alpha) {
            return $image_path; // No alpha, use original
        }

        // Create new image without alpha
        $new_image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $white);

        // Copy with alpha blending
        imagealphablending($image, true);
        imagecopy($new_image, $image, 0, 0, 0, 0, $width, $height);

        // Save as JPG
        $new_path = $image_path . '.processed.jpg';
        imagejpeg($new_image, $new_path, 95);

        imagedestroy($image);
        imagedestroy($new_image);

        return $new_path;
    }
}
