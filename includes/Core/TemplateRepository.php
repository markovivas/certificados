<?php

namespace GCWP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TemplateRepository {

    const DEFAULT_FIELDS = [
        [
            'key' => 'nome_completo',
            'label' => 'Nome da pessoa',
            'page' => 'front',
            'x' => 0,
            'y' => 57,
            'width' => 297,
            'font_size' => 35,
            'color' => '#002e67',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'C',
            'uppercase' => 1,
            'sample' => 'NOME DA PESSOA',
        ],
        [
            'key' => 'curso',
            'label' => 'Nome do curso',
            'page' => 'front',
            'x' => 0,
            'y' => 90,
            'width' => 297,
            'font_size' => 35,
            'color' => '#002e67',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'C',
            'uppercase' => 1,
            'sample' => 'NOME DO CURSO',
        ],
        [
            'key' => 'data_inicio',
            'label' => 'Data de inicio',
            'page' => 'front',
            'x' => 100,
            'y' => 131,
            'width' => 40,
            'font_size' => 20,
            'color' => '#002e67',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'L',
            'uppercase' => 0,
            'sample' => '01/01/2026',
        ],
        [
            'key' => 'data_termino',
            'label' => 'Data de termino',
            'page' => 'front',
            'x' => 155,
            'y' => 131,
            'width' => 40,
            'font_size' => 20,
            'color' => '#002e67',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'L',
            'uppercase' => 0,
            'sample' => '31/01/2026',
        ],
        [
            'key' => 'carga_horaria',
            'label' => 'Carga horaria',
            'page' => 'front',
            'x' => 122,
            'y' => 143,
            'width' => 30,
            'font_size' => 25,
            'color' => '#002e67',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'L',
            'uppercase' => 0,
            'sample' => '40',
        ],
        [
            'key' => 'cidade_data',
            'label' => 'Cidade e data',
            'page' => 'front',
            'x' => 0,
            'y' => 160,
            'width' => 297,
            'font_size' => 16,
            'color' => '#000000',
            'font_family' => 'times',
            'font_weight' => '',
            'align' => 'C',
            'uppercase' => 0,
            'sample' => 'Sao Paulo, 31 de janeiro de 2026',
        ],
        [
            'key' => 'livro',
            'label' => 'Livro',
            'page' => 'back',
            'x' => 93,
            'y' => 188,
            'width' => 40,
            'font_size' => 12,
            'color' => '#000000',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'L',
            'uppercase' => 0,
            'sample' => '001',
        ],
        [
            'key' => 'pagina',
            'label' => 'Pagina',
            'page' => 'back',
            'x' => 188,
            'y' => 188,
            'width' => 30,
            'font_size' => 12,
            'color' => '#000000',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'L',
            'uppercase' => 0,
            'sample' => '189',
        ],
        [
            'key' => 'numero_certificado',
            'label' => 'Numero do certificado',
            'page' => 'back',
            'x' => 260,
            'y' => 188,
            'width' => 30,
            'font_size' => 12,
            'color' => '#000000',
            'font_family' => 'ArialCEMTBlack',
            'font_weight' => 'B',
            'align' => 'L',
            'uppercase' => 0,
            'sample' => '12718',
        ],
    ];

    public static function get_templates_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/certificados/modelos';
    }

    public static function list() {
        $templates_dir = self::get_templates_dir();
        $templates     = [];

        if ( ! is_dir( $templates_dir ) ) {
            return $templates;
        }

        $dirs = array_filter(
            scandir( $templates_dir ),
            static function ( $item ) use ( $templates_dir ) {
                return ! in_array( $item, [ '.', '..' ], true ) && is_dir( $templates_dir . '/' . $item );
            }
        );

        foreach ( $dirs as $slug ) {
            $template = self::get( $slug );
            if ( $template ) {
                $templates[ $slug ] = $template;
            }
        }

        uasort(
            $templates,
            static function ( $a, $b ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
        );

        return $templates;
    }

    public static function get( $slug ) {
        $slug = sanitize_title( $slug );
        if ( empty( $slug ) ) {
            return null;
        }

        $dir = self::get_templates_dir() . '/' . $slug;
        if ( ! is_dir( $dir ) ) {
            return null;
        }

        $config = self::read_config( $dir );
        $paths  = self::get_image_paths( $dir );

        return [
            'slug'       => $slug,
            'dir'        => $dir,
            'name'       => ! empty( $config['name'] ) ? $config['name'] : ucwords( str_replace( '-', ' ', $slug ) ),
            'fields'     => self::normalize_fields( isset( $config['fields'] ) ? $config['fields'] : [] ),
            'front_path' => $paths['front_path'],
            'back_path'  => $paths['back_path'],
            'front_url'  => $paths['front_url'],
            'back_url'   => $paths['back_url'],
        ];
    }

    public static function create( $name, $front_file, $back_file = null ) {
        $slug = sanitize_title( $name );
        if ( empty( $slug ) ) {
            return new \WP_Error( 'invalid_name', __( 'Informe um nome valido para o modelo.', 'gerador-certificados-wp' ) );
        }

        $dir = self::get_templates_dir() . '/' . $slug;
        if ( file_exists( $dir ) ) {
            return new \WP_Error( 'template_exists', __( 'Ja existe um modelo com este nome.', 'gerador-certificados-wp' ) );
        }

        wp_mkdir_p( $dir );

        $front_result = self::move_uploaded_file( $front_file, $dir, 'frente' );
        if ( is_wp_error( $front_result ) ) {
            return $front_result;
        }

        $back_result = null;
        if ( ! empty( $back_file['name'] ) ) {
            $back_result = self::move_uploaded_file( $back_file, $dir, 'verso' );
            if ( is_wp_error( $back_result ) ) {
                return $back_result;
            }
        }

        self::write_config(
            $dir,
            [
                'name'   => sanitize_text_field( $name ),
                'fields' => self::DEFAULT_FIELDS,
            ]
        );

        return self::get( $slug );
    }

    public static function update( $slug, array $data, array $files = [] ) {
        $template = self::get( $slug );
        if ( ! $template ) {
            return new \WP_Error( 'template_not_found', __( 'Modelo nao encontrado.', 'gerador-certificados-wp' ) );
        }

        $name   = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : $template['name'];
        $fields = isset( $data['fields'] ) ? self::normalize_fields( $data['fields'] ) : $template['fields'];

        $new_slug = sanitize_title( $name );
        if ( empty( $new_slug ) ) {
            return new \WP_Error( 'invalid_name', __( 'Informe um nome valido para o modelo.', 'gerador-certificados-wp' ) );
        }

        $target_dir = self::get_templates_dir() . '/' . $new_slug;
        if ( $new_slug !== $slug && file_exists( $target_dir ) ) {
            return new \WP_Error( 'template_exists', __( 'Ja existe outro modelo com este nome.', 'gerador-certificados-wp' ) );
        }

        if ( $new_slug !== $slug ) {
            rename( $template['dir'], $target_dir );
            $slug     = $new_slug;
            $template = self::get( $slug );
        }

        if ( ! empty( $files['modelo_frente']['name'] ) ) {
            $upload_result = self::move_uploaded_file( $files['modelo_frente'], $template['dir'], 'frente', true );
            if ( is_wp_error( $upload_result ) ) {
                return $upload_result;
            }
        }

        if ( ! empty( $files['modelo_verso']['name'] ) ) {
            $upload_result = self::move_uploaded_file( $files['modelo_verso'], $template['dir'], 'verso', true );
            if ( is_wp_error( $upload_result ) ) {
                return $upload_result;
            }
        }

        self::write_config(
            $template['dir'],
            [
                'name'   => $name,
                'fields' => $fields,
            ]
        );

        return self::get( $slug );
    }

    public static function rename( $slug, $new_name ) {
        return self::update(
            $slug,
            [
                'name' => $new_name,
            ]
        );
    }

    public static function delete( $slug ) {
        $template = self::get( $slug );
        if ( ! $template ) {
            return new \WP_Error( 'template_not_found', __( 'Modelo nao encontrado.', 'gerador-certificados-wp' ) );
        }

        Utils::delete_dir_recursive( $template['dir'] );
        return true;
    }

    public static function get_image_paths( $dir ) {
        $upload_dir  = wp_upload_dir();
        $front_files = glob( $dir . '/frente.*' );
        $back_files  = glob( $dir . '/verso.*' );

        $front_path = ! empty( $front_files ) ? $front_files[0] : '';
        $back_path  = ! empty( $back_files ) ? $back_files[0] : '';

        return [
            'front_path' => $front_path,
            'back_path'  => $back_path,
            'front_url'  => $front_path ? str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $front_path ) : '',
            'back_url'   => $back_path ? str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $back_path ) : '',
        ];
    }

    public static function get_available_fonts() {
        $fonts = [
            'helvetica' => 'Helvetica',
            'times'     => 'Times',
            'courier'   => 'Courier',
        ];

        $font_dir = GCWP_PLUGIN_DIR . 'fonts/';
        if ( is_dir( $font_dir ) ) {
            foreach ( glob( $font_dir . '*.ttf' ) as $font_file ) {
                $font_name           = basename( $font_file, '.ttf' );
                $fonts[ $font_name ] = $font_name;
            }
        }

        return $fonts;
    }

    public static function build_field_value_map( $participant ) {
        $participant = (array) $participant;

        $start_date = ! empty( $participant['data_inicio'] ) ? date_i18n( 'd/m/Y', strtotime( $participant['data_inicio'] ) ) : '';
        $end_date   = ! empty( $participant['data_termino'] ) ? date_i18n( 'd/m/Y', strtotime( $participant['data_termino'] ) ) : '';
        $issue_date = ! empty( $participant['data_emissao'] ) ? date_i18n( 'd \d\e F \d\e Y', strtotime( $participant['data_emissao'] ) ) : '';
        $city_date  = trim( implode( ', ', array_filter( [ $participant['cidade'] ?? '', $issue_date ] ) ) );

        return [
            'nome_completo'      => $participant['nome_completo'] ?? '',
            'nome'               => $participant['nome_completo'] ?? '',
            'curso'              => $participant['curso'] ?? '',
            'data_inicio'        => $start_date,
            'data_termino'       => $end_date,
            'cidade'             => $participant['cidade'] ?? '',
            'carga_horaria'      => isset( $participant['duracao_horas'] ) ? (string) $participant['duracao_horas'] : '',
            'duracao'            => isset( $participant['duracao_horas'] ) ? (string) $participant['duracao_horas'] : '',
            'data_emissao'       => $participant['data_emissao'] ?? '',
            'cidade_data'        => $city_date,
            'local_data'         => $city_date,
            'livro'              => $participant['numero_livro'] ?? '',
            'pagina'             => $participant['numero_pagina'] ?? '',
            'numero_certificado' => $participant['numero_certificado'] ?? '',
            'registro'           => $participant['numero_certificado'] ?? '',
        ];
    }

    private static function read_config( $dir ) {
        $config_file = $dir . '/model.json';
        if ( ! file_exists( $config_file ) ) {
            return [
                'name'   => ucwords( str_replace( '-', ' ', basename( $dir ) ) ),
                'fields' => self::DEFAULT_FIELDS,
            ];
        }

        $contents = file_get_contents( $config_file );
        $decoded  = json_decode( $contents, true );

        if ( ! is_array( $decoded ) ) {
            return [
                'name'   => ucwords( str_replace( '-', ' ', basename( $dir ) ) ),
                'fields' => self::DEFAULT_FIELDS,
            ];
        }

        return $decoded;
    }

    private static function write_config( $dir, array $config ) {
        file_put_contents(
            $dir . '/model.json',
            wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
        );
    }

    private static function move_uploaded_file( $file, $target_dir, $base_name, $replace = false ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $uploaded = wp_handle_upload(
            $file,
            [
                'test_form' => false,
            ]
        );

        if ( isset( $uploaded['error'] ) ) {
            return new \WP_Error( 'upload_error', $uploaded['error'] );
        }

        $ext         = strtolower( pathinfo( $uploaded['file'], PATHINFO_EXTENSION ) );
        $destination = $target_dir . '/' . $base_name . '.' . $ext;

        if ( $replace ) {
            foreach ( glob( $target_dir . '/' . $base_name . '.*' ) as $existing_file ) {
                if ( file_exists( $existing_file ) ) {
                    unlink( $existing_file );
                }
            }
        }

        rename( $uploaded['file'], $destination );

        return $destination;
    }

    private static function normalize_fields( array $fields ) {
        if ( empty( $fields ) ) {
            $fields = self::DEFAULT_FIELDS;
        }

        $normalized = [];

        foreach ( $fields as $field ) {
            $key = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
            if ( empty( $key ) ) {
                continue;
            }

            $normalized[] = [
                'key'         => $key,
                'label'       => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : $key,
                'page'        => ( isset( $field['page'] ) && 'back' === $field['page'] ) ? 'back' : 'front',
                'x'           => isset( $field['x'] ) ? (float) $field['x'] : 0,
                'y'           => isset( $field['y'] ) ? (float) $field['y'] : 0,
                'width'       => isset( $field['width'] ) ? max( 10, (float) $field['width'] ) : 80,
                'font_size'   => isset( $field['font_size'] ) ? max( 6, (float) $field['font_size'] ) : 18,
                'color'       => sanitize_hex_color( $field['color'] ?? '#000000' ) ?: '#000000',
                'font_family' => isset( $field['font_family'] ) ? sanitize_text_field( $field['font_family'] ) : 'helvetica',
                'font_weight' => ( isset( $field['font_weight'] ) && 'B' === $field['font_weight'] ) ? 'B' : '',
                'align'       => in_array( $field['align'] ?? 'L', [ 'L', 'C', 'R' ], true ) ? $field['align'] : 'L',
                'uppercase'   => empty( $field['uppercase'] ) ? 0 : 1,
                'sample'      => isset( $field['sample'] ) ? sanitize_text_field( $field['sample'] ) : strtoupper( str_replace( '_', ' ', $key ) ),
            ];
        }

        return array_values( $normalized );
    }
}
