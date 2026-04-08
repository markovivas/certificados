<?php

namespace GCWP\Core;

use TCPDF_FONTS;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CertificateGenerator {

    public function generate_certificate( $data, $template_slug = '' ) {
        try {
            if ( ! class_exists( '\\setasign\\Fpdi\\Tcpdf\\Fpdi' ) ) {
                return new WP_Error( 'tcpdf_missing', __( 'A biblioteca TCPDF/FPDI nao esta instalada.', 'gerador-certificados-wp' ) );
            }

            $template_slug = ! empty( $template_slug ) ? sanitize_title( $template_slug ) : sanitize_title( get_option( 'gcwp_modelo_selecionado', '' ) );
            if ( empty( $template_slug ) ) {
                return new WP_Error( 'no_model_selected', __( 'Nenhum modelo foi selecionado.', 'gerador-certificados-wp' ) );
            }

            $template = TemplateRepository::get( $template_slug );
            if ( ! $template ) {
                return new WP_Error( 'template_not_found', __( 'Modelo nao encontrado.', 'gerador-certificados-wp' ) );
            }

            if ( empty( $template['front_path'] ) || ! file_exists( $template['front_path'] ) ) {
                return new WP_Error( 'missing_template', __( 'A imagem da frente do modelo nao foi encontrada.', 'gerador-certificados-wp' ) );
            }

            $upload_dir   = wp_upload_dir();
            $emitidos_dir = $upload_dir['basedir'] . '/certificados/emitidos';
            wp_mkdir_p( $emitidos_dir );

            $front_path = $this->process_image_for_tcpdf( $template['front_path'] );
            $back_path  = ! empty( $template['back_path'] ) ? $this->process_image_for_tcpdf( $template['back_path'] ) : '';
            $values     = is_object( $data ) || is_array( $data ) ? (array) $data : [];

            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi( 'L', 'mm', 'A4' );
            $pdf->setPrintHeader( false );
            $pdf->setPrintFooter( false );
            $pdf->SetMargins( 0, 0, 0 );
            $pdf->SetAutoPageBreak( false );

            $pdf->AddPage();
            $pdf->Image( $front_path, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0 );
            $this->render_fields( $pdf, $template['fields'], 'front', $values );

            if ( ! empty( $back_path ) && file_exists( $back_path ) ) {
                $has_back_fields = ! empty(
                    array_filter(
                        $template['fields'],
                        static function ( $field ) {
                            return isset( $field['page'] ) && 'back' === $field['page'];
                        }
                    )
                );

                if ( $has_back_fields || ! empty( $template['back_path'] ) ) {
                    $pdf->AddPage();
                    $pdf->Image( $back_path, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0 );
                    $this->render_fields( $pdf, $template['fields'], 'back', $values );
                }
            }

            $person_name = ! empty( $values['nome_completo'] ) ? $values['nome_completo'] : ( $values['nome'] ?? $template['name'] );
            $filename    = 'certificado_' . sanitize_title( $person_name ) . '_' . time() . '.pdf';
            $filepath    = $emitidos_dir . '/' . $filename;

            $pdf->Output( $filepath, 'F' );

            return [
                'path'     => $filepath,
                'url'      => $upload_dir['baseurl'] . '/certificados/emitidos/' . $filename,
                'filename' => $filename,
            ];
        } catch ( \Throwable $e ) {
            return new WP_Error( 'tcpdf_error', sprintf( __( 'Erro ao gerar o PDF: %s', 'gerador-certificados-wp' ), $e->getMessage() ) );
        }
    }

    private function render_fields( $pdf, array $fields, $page, array $values ) {
        foreach ( $fields as $field ) {
            if ( ( $field['page'] ?? 'front' ) !== $page ) {
                continue;
            }

            $value = isset( $values[ $field['key'] ] ) ? (string) $values[ $field['key'] ] : '';
            if ( '' === trim( $value ) ) {
                continue;
            }

            if ( ! empty( $field['uppercase'] ) ) {
                $value = mb_strtoupper( $value, 'UTF-8' );
            }

            $font_family = $this->resolve_font( $field['font_family'] );
            $pdf->SetFont( $font_family, $field['font_weight'], (float) $field['font_size'] );
            $pdf->SetTextColorArray( Utils::hex2rgb( $field['color'], true ) );
            $pdf->SetXY( (float) $field['x'], (float) $field['y'] );
            $pdf->Cell( (float) $field['width'], 0, $value, 0, 0, $field['align'] );
        }
    }

    private function resolve_font( $font_name ) {
        $font_name      = sanitize_text_field( $font_name );
        $font_file_path = GCWP_PLUGIN_DIR . 'fonts/' . $font_name . '.ttf';

        if ( file_exists( $font_file_path ) ) {
            $registered = TCPDF_FONTS::addTTFfont( $font_file_path, 'TrueTypeUnicode', '', 32 );
            if ( $registered ) {
                return $registered;
            }
        }

        if ( in_array( $font_name, [ 'times', 'helvetica', 'courier' ], true ) ) {
            return $font_name;
        }

        return 'helvetica';
    }

    private function process_image_for_tcpdf( $image_path ) {
        if ( ! function_exists( 'imagecreatefrompng' ) || ! function_exists( 'imagejpeg' ) ) {
            return $image_path;
        }

        $extension = strtolower( pathinfo( $image_path, PATHINFO_EXTENSION ) );
        if ( 'png' !== $extension ) {
            return $image_path;
        }

        $image_info = getimagesize( $image_path );
        if ( empty( $image_info['mime'] ) || 'image/png' !== $image_info['mime'] ) {
            return $image_path;
        }

        $image = @imagecreatefrompng( $image_path );
        if ( ! $image ) {
            return $image_path;
        }

        $has_alpha = false;
        $width     = imagesx( $image );
        $height    = imagesy( $image );

        for ( $x = 0; $x < $width; $x++ ) {
            for ( $y = 0; $y < $height; $y++ ) {
                $color = imagecolorat( $image, $x, $y );
                $alpha = ( $color & 0x7F000000 ) >> 24;
                if ( $alpha > 0 ) {
                    $has_alpha = true;
                    break 2;
                }
            }
        }

        if ( ! $has_alpha ) {
            imagedestroy( $image );
            return $image_path;
        }

        $new_image = imagecreatetruecolor( $width, $height );
        $white     = imagecolorallocate( $new_image, 255, 255, 255 );
        imagefill( $new_image, 0, 0, $white );
        imagealphablending( $image, true );
        imagecopy( $new_image, $image, 0, 0, 0, 0, $width, $height );

        $new_path = $image_path . '.processed.jpg';
        imagejpeg( $new_image, $new_path, 95 );

        imagedestroy( $image );
        imagedestroy( $new_image );

        return $new_path;
    }
}
