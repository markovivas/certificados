<?php

namespace GCWP\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ParticipantsTable {

    /**
     * Get table name
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'gcwp_participantes';
    }

    /**
     * Get participants with pagination and search
     */
    public static function get_participants($limit = 0, $offset = 0, $search = '', $orderby = 'id', $order = 'DESC') {
        global $wpdb;
        $table_name = self::get_table_name();

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return []; // Table does not exist
        }

        $sql = "SELECT * FROM {$table_name} WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (nome_completo LIKE %s OR curso LIKE %s OR numero_certificado LIKE %s)";
            $like = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Sanitize orderby
        $allowed_columns = ['id', 'nome_completo', 'curso', 'data_emissao'];
        if (!in_array($orderby, $allowed_columns)) {
            $orderby = 'id';
        }

        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY $orderby $order";

        if ($limit > 0) {
            $sql .= " LIMIT %d";
            $params[] = $limit;
        }

        if ($offset > 0) {
            $sql .= " OFFSET %d";
            $params[] = $offset;
        }

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        } else {
            $results = $wpdb->get_results($sql, ARRAY_A);
        }

        return $results ?: [];
    }

    /**
     * Create the custom table for participants.
     */
    public static function create_table() {
        global $wpdb;
        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0 NOT NULL,
            nome_completo tinytext NOT NULL,
            email varchar(100) DEFAULT '' NOT NULL,
            curso tinytext NOT NULL,
            data_inicio date DEFAULT '0000-00-00' NOT NULL,
            data_termino date DEFAULT '0000-00-00' NOT NULL,
            duracao_horas smallint(5) NOT NULL,
            cidade tinytext NOT NULL,
            data_emissao date DEFAULT '0000-00-00' NOT NULL,
            numero_livro varchar(55) DEFAULT '' NOT NULL,
            numero_pagina varchar(55) DEFAULT '' NOT NULL,
            numero_certificado varchar(55) DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Get participant by ID
     */
    public static function get($id) {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    }

    /**
     * Insert participant
     */
    public static function insert($data) {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->insert($table_name, $data);
    }

    /**
     * Update participant
     */
    public static function update($id, $data) {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->update($table_name, $data, ['id' => $id]);
    }

    /**
     * Get participants by user ID
     */
    public static function get_participants_by_user($user_id, $limit = 0, $offset = 0) {
        global $wpdb;
        $table_name = self::get_table_name();

        $sql = $wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY id DESC", $user_id);
        $params = [];

        if ($limit > 0) {
            $sql .= " LIMIT %d";
            $params[] = $limit;
        }

        if ($offset > 0) {
            $sql .= " OFFSET %d";
            $params[] = $offset;
        }

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
        } else {
            return $wpdb->get_results($sql, ARRAY_A);
        }
    }

    /**
     * Delete participant
     */
    public static function delete($id) {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->delete($table_name, ['id' => $id]);
    }

    /**
     * Find participant by name
     */
    public static function find_by_name($name) {
        global $wpdb;
        $table_name = self::get_table_name();
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE LOWER(nome_completo) LIKE %s LIMIT 1",
            '%' . strtolower($name) . '%'
        ));
    }

    /**
     * Truncate table
     */
    public static function truncate() {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query("TRUNCATE TABLE {$table_name}");
    }
}
