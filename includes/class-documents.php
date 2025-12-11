<?php
/**
 * Clase para gestión de documentos del proyecto
 * Archivo: includes/class-documents.php
 */

if (!defined('ABSPATH')) exit;

class Timeline_Documents {
    
    private static $instance = null;
    private $db;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->db = Timeline_Database::get_instance();
    }
    
    /**
     * Añadir documento a un proyecto
     */
    public function add_document($project_id, $title, $file_url, $file_type, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('documents');
        
        $result = $wpdb->insert(
            $table,
            array(
                'project_id' => intval($project_id),
                'title' => sanitize_text_field($title),
                'file_url' => sanitize_text_field($file_url),
                'file_type' => sanitize_text_field($file_type),
                'uploaded_by' => intval($user_id)
            )
        );
        
        if ($result) {
            $document_id = $wpdb->insert_id;
            
            // Log de actividad
            $this->log_activity($user_id, 'upload', 'document', $document_id, 
                'Documento subido: ' . $title);
            
            return $document_id;
        }
        
        return false;
    }
    
    /**
     * Obtener documentos de un proyecto
     */
    public function get_project_documents($project_id) {
        global $wpdb;
        $table = $this->db->get_table_name('documents');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE project_id = %d ORDER BY uploaded_at DESC",
            $project_id
        ));
    }
    
    /**
     * Eliminar documento
     */
    public function delete_document($document_id, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('documents');
        
        // Obtener info antes de eliminar
        $document = $this->get_document($document_id);
        
        if (!$document) {
            return false;
        }
        
        // Eliminar archivo físico si existe
        if (strpos($document->file_url, wp_upload_dir()['baseurl']) !== false) {
            $file_path = str_replace(
                wp_upload_dir()['baseurl'], 
                wp_upload_dir()['basedir'], 
                $document->file_url
            );
            
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
        
        $result = $wpdb->delete($table, array('id' => $document_id));
        
        if ($result) {
            $this->log_activity($user_id, 'delete', 'document', $document_id, 
                'Documento eliminado: ' . $document->title);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener un documento por ID
     */
    public function get_document($document_id) {
        global $wpdb;
        $table = $this->db->get_table_name('documents');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $document_id
        ));
    }
    
    /**
     * Contar documentos de un proyecto
     */
    public function count_project_documents($project_id) {
        global $wpdb;
        $table = $this->db->get_table_name('documents');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE project_id = %d",
            $project_id
        ));
    }
    
    /**
     * Log de actividad
     */
    private function log_activity($user_id, $action, $entity_type, $entity_id, $details) {
        global $wpdb;
        $table = $this->db->get_table_name('activity_log');
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'details' => $details
            )
        );
    }
}