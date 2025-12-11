<?php
/**
 * Gestión de base de datos - Creación de tablas
 * Archivo: includes/database.php
 * ACTUALIZADO: Tabla de audit log mejorada con IP y User Agent
 */

if (!defined('ABSPATH')) exit;

class Timeline_Database {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de usuarios (ya existe)
        $table_users = $wpdb->prefix . 'timeline_users';
        
        // Tabla de proyectos
        $table_projects = $wpdb->prefix . 'timeline_projects';
        $sql_projects = "CREATE TABLE IF NOT EXISTS {$table_projects} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            start_date date NOT NULL,
            end_date date NOT NULL,
            actual_end_date date NULL,
            description text,
            featured_image varchar(255),
            status varchar(50) DEFAULT 'activo',
            project_status varchar(50) DEFAULT 'en_proceso',
            created_by mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Tabla de relación proyecto-cliente (muchos a muchos)
        $table_project_clients = $wpdb->prefix . 'timeline_project_clients';
        $sql_project_clients = "CREATE TABLE IF NOT EXISTS {$table_project_clients} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            client_id mediumint(9) NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY project_client (project_id, client_id),
            KEY project_id (project_id),
            KEY client_id (client_id)
        ) $charset_collate;";
        
        // Tabla de hitos
        $table_milestones = $wpdb->prefix . 'timeline_milestones';
        $sql_milestones = "CREATE TABLE IF NOT EXISTS {$table_milestones} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            title varchar(255) NOT NULL,
            date date NOT NULL,
            description text,
            status varchar(50) DEFAULT 'pendiente',
            created_by mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY created_by (created_by),
            KEY date (date)
        ) $charset_collate;";
        
        // Tabla de imágenes de hitos
        $table_milestone_images = $wpdb->prefix . 'timeline_milestone_images';
        $sql_milestone_images = "CREATE TABLE IF NOT EXISTS {$table_milestone_images} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            milestone_id mediumint(9) NOT NULL,
            image_url varchar(255) NOT NULL,
            image_order int DEFAULT 0,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY milestone_id (milestone_id)
        ) $charset_collate;";
        
        // Tabla de documentos del proyecto
        $table_documents = $wpdb->prefix . 'timeline_documents';
        $sql_documents = "CREATE TABLE IF NOT EXISTS {$table_documents} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            title varchar(255) NOT NULL,
            file_url varchar(255) NOT NULL,
            file_type varchar(50),
            uploaded_by mediumint(9) NOT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY uploaded_by (uploaded_by)
        ) $charset_collate;";
        
        // Tabla de logs de actividad - MEJORADA CON MÁS CAMPOS
        $table_activity_log = $wpdb->prefix . 'timeline_activity_log';
        $sql_activity_log = "CREATE TABLE IF NOT EXISTS {$table_activity_log} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            action varchar(100) NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id mediumint(9) NOT NULL,
            details text,
            metadata text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY entity (entity_type, entity_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_projects);
        dbDelta($sql_project_clients);
        dbDelta($sql_milestones);
        dbDelta($sql_milestone_images);
        dbDelta($sql_documents);
        dbDelta($sql_activity_log);
    }
    
    public function get_table_name($table) {
        global $wpdb;
        $tables = array(
            'users' => $wpdb->prefix . 'timeline_users',
            'projects' => $wpdb->prefix . 'timeline_projects',
            'project_clients' => $wpdb->prefix . 'timeline_project_clients',
            'milestones' => $wpdb->prefix . 'timeline_milestones',
            'milestone_images' => $wpdb->prefix . 'timeline_milestone_images',
            'documents' => $wpdb->prefix . 'timeline_documents',
            'activity_log' => $wpdb->prefix . 'timeline_activity_log'
        );
        
        return isset($tables[$table]) ? $tables[$table] : '';
    }
}