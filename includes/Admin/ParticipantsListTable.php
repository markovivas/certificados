<?php

namespace GCWP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ParticipantsListTable extends \WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Participante', 'gerador-certificados-wp' ),
            'plural'   => __( 'Participantes', 'gerador-certificados-wp' ),
            'ajax'     => false,
        ] );
    }

    public static function get_participants( $per_page = 10, $page_number = 1, $search = '' ) {
        $orderby = ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'id';
        $order = ! empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'DESC';
        $offset = ( $page_number - 1 ) * $per_page;

        return \GCWP\Database\ParticipantsTable::get_participants($per_page, $offset, $search, $orderby, $order);
    }

    public static function delete_participant( $id ) {
        global $wpdb;
        $table_name = \GCWP\Database\ParticipantsTable::get_table_name();
        $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] );
    }

    public static function record_count( $search = '' ) {
        global $wpdb;
        $table_name = \GCWP\Database\ParticipantsTable::get_table_name();
        $sql = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1";
        $params = [];
        if ( ! empty( $search ) ) {
            $sql .= " AND (nome_completo LIKE %s OR curso LIKE %s OR numero_certificado LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            return $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
        }
        return $wpdb->get_var( $sql );
    }

    public function no_items() {
        _e( 'Nenhum participante encontrado.', 'gerador-certificados-wp' );
    }

    function column_nome_completo( $item ) {
        $delete_nonce = wp_create_nonce( 'gcwp_delete_participant' );
        $title        = '<strong>' . $item['nome_completo'] . '</strong>';

        $actions = [
            'edit'   => sprintf( '<a href="?page=%s&action=%s&participant=%s">Editar</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ) ),
            'delete' => sprintf( '<a href="?page=%s&action=%s&participant=%s&_wpnonce=%s">Excluir</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
        ];

        return $title . $this->row_actions( $actions );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'curso':
            case 'duracao_horas':
            case 'numero_certificado':
                return $item[ $column_name ];
            case 'data_emissao':
                return date_i18n( get_option( 'date_format' ), strtotime( $item[ $column_name ] ) );
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    function get_columns() {
        $columns = [
            'cb'                 => '<input type="checkbox" />',
            'nome_completo'      => __( 'Nome Completo', 'gerador-certificados-wp' ),
            'curso'              => __( 'Curso', 'gerador-certificados-wp' ),
            'duracao_horas'      => __( 'Duração (Horas)', 'gerador-certificados-wp' ),
            'data_emissao'       => __( 'Data de Emissão', 'gerador-certificados-wp' ),
            'numero_certificado' => __( 'Nº do Certificado', 'gerador-certificados-wp' ),
        ];

        return $columns;
    }

    public function get_sortable_columns() {
        $sortable_columns = [
            'nome_completo' => [ 'nome_completo', true ],
            'curso'         => [ 'curso', false ],
            'data_emissao'  => [ 'data_emissao', false ],
        ];

        return $sortable_columns;
    }

    public function get_column_info() {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $primary = 'nome_completo'; // Define a primary column
        return array( $columns, $hidden, $sortable, $primary );
    }

    public function get_hidden_columns() {
        $hidden = get_hidden_columns( $this->screen->id );
        return is_array( $hidden ) ? $hidden : array();
    }

    function get_bulk_actions() {
        $actions = [
            'bulk-delete' => 'Excluir'
        ];
        return $actions;
    }

    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $search_term = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
        $per_page     = $this->get_items_per_page( 'participants_per_page', 10 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count( $search_term );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ] );

        $this->items = self::get_participants( $per_page, $current_page, $search_term );
    }

    public function process_bulk_action() {
        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'gcwp_delete_participant' ) ) {
                die( 'Ação inválida.' );
            } else {
                self::delete_participant( absint( $_GET['participant'] ) );
                // esc_url_raw() is used to prevent converting ampersands to &amp;
                wp_redirect( esc_url_raw( add_query_arg( '', '', admin_url( 'admin.php?page=gcwp-participantes' ) ) ) );
                exit;
            }
        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
             || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {
            $delete_ids = esc_sql( $_POST['bulk-delete'] );

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                self::delete_participant( $id );
            }

            wp_redirect( esc_url_raw( add_query_arg( '', '', admin_url( 'admin.php?page=gcwp-participantes' ) ) ) );
            exit;
        }
    }
    
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }
}
