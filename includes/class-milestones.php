<?php
/**
 * Clase para gestión de hitos
 * Archivo: includes/class-milestones.php
 */

if (!defined('ABSPATH')) exit;

class Timeline_Milestones {
    
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
     * Crear un nuevo hito
     */
    public function create_milestone($data, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('milestones');
        
        $result = $wpdb->insert(
            $table,
            array(
                'project_id' => intval($data['project_id']),
                'title' => sanitize_text_field($data['title']),
                'date' => sanitize_text_field($data['date']),
                'description' => sanitize_textarea_field($data['description']),
                'status' => sanitize_text_field($data['status']),
                'created_by' => $user_id
            )
        );
        
        if ($result) {
            $milestone_id = $wpdb->insert_id;
            
            // Log de actividad
            $this->log_activity($user_id, 'create', 'milestone', $milestone_id, 
                'Hito creado: ' . $data['title']);
            
            // Enviar notificación a clientes del proyecto
            $this->notify_project_clients($data['project_id'], $milestone_id, 'new');
            
            return $milestone_id;
        }
        
        return false;
    }
    
    /**
     * Actualizar un hito
     */
    public function update_milestone($milestone_id, $data, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('milestones');
        
        $update_data = array();
        
        if (isset($data['title'])) {
            $update_data['title'] = sanitize_text_field($data['title']);
        }
        if (isset($data['date'])) {
            $update_data['date'] = sanitize_text_field($data['date']);
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $milestone_id)
        );
        
        if ($result !== false) {
            $milestone = $this->get_milestone($milestone_id);
            
            $this->log_activity($user_id, 'update', 'milestone', $milestone_id, 
                'Hito actualizado: ' . $milestone->title);
            
            // Enviar notificación de actualización
            $this->notify_project_clients($milestone->project_id, $milestone_id, 'update');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener un hito por ID
     */
    public function get_milestone($milestone_id) {
        global $wpdb;
        $table = $this->db->get_table_name('milestones');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $milestone_id
        ));
    }
    
    /**
     * Obtener hitos de un proyecto
     */
    public function get_project_milestones($project_id, $order = 'ASC') {
        global $wpdb;
        $table = $this->db->get_table_name('milestones');
        
        $order = ($order === 'DESC') ? 'DESC' : 'ASC';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE project_id = %d ORDER BY date {$order}",
            $project_id
        ));
    }
    
    /**
     * Añadir imagen a un hito
     */
    public function add_milestone_image($milestone_id, $image_url, $order = 0) {
        global $wpdb;
        $table = $this->db->get_table_name('milestone_images');
        
        return $wpdb->insert(
            $table,
            array(
                'milestone_id' => $milestone_id,
                'image_url' => sanitize_text_field($image_url),
                'image_order' => intval($order)
            )
        );
    }
    
    /**
     * Obtener imágenes de un hito
     */
    public function get_milestone_images($milestone_id) {
        global $wpdb;
        $table = $this->db->get_table_name('milestone_images');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE milestone_id = %d ORDER BY image_order ASC",
            $milestone_id
        ));
    }
    
    /**
     * Eliminar imagen de un hito
     */
    public function delete_milestone_image($image_id) {
        global $wpdb;
        $table = $this->db->get_table_name('milestone_images');
        
        return $wpdb->delete($table, array('id' => $image_id));
    }
    
    /**
     * Eliminar hito
     */
    public function delete_milestone($milestone_id, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('milestones');
        
        // Obtener info antes de eliminar
        $milestone = $this->get_milestone($milestone_id);
        
        $result = $wpdb->delete($table, array('id' => $milestone_id));
        
        if ($result) {
            // Eliminar imágenes asociadas
            $wpdb->delete($this->db->get_table_name('milestone_images'), 
                array('milestone_id' => $milestone_id));
            
            $this->log_activity($user_id, 'delete', 'milestone', $milestone_id, 
                'Hito eliminado: ' . $milestone->title);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener hito con sus imágenes
     */
    public function get_milestone_with_images($milestone_id) {
        $milestone = $this->get_milestone($milestone_id);
        
        if ($milestone) {
            $milestone->images = $this->get_milestone_images($milestone_id);
        }
        
        return $milestone;
    }
    
    /**
     * Obtener todos los hitos de un proyecto con imágenes
     */
    public function get_project_milestones_with_images($project_id) {
        $milestones = $this->get_project_milestones($project_id);
        
        foreach ($milestones as $milestone) {
            $milestone->images = $this->get_milestone_images($milestone->id);
        }
        
        return $milestones;
    }
    
    /**
     * Cambiar estado de un hito
     */
    public function change_milestone_status($milestone_id, $status, $user_id) {
        $valid_statuses = array('pendiente', 'en_proceso', 'finalizado');
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        return $this->update_milestone($milestone_id, array('status' => $status), $user_id);
    }
    
    /**
     * Notificar a clientes del proyecto sobre nuevo/actualizado hito
     */
    private function notify_project_clients($project_id, $milestone_id, $type = 'new') {
        global $wpdb;
        
        // Obtener proyecto y hito
        $projects_class = Timeline_Projects::get_instance();
        $project = $projects_class->get_project($project_id);
        $milestone = $this->get_milestone($milestone_id);
        
        if (!$project || !$milestone) {
            return false;
        }
        
        // Obtener clientes asignados al proyecto
        $clients = $projects_class->get_project_clients($project_id);
        
        if (empty($clients)) {
            return false;
        }
        
        // URL del proyecto
        $project_url = home_url('/timeline-proyecto/' . $project_id);
        
        // Preparar email
        if ($type === 'new') {
            $subject = 'Nuevo hito en tu proyecto: ' . $project->name;
            $action_text = 'Se ha añadido un nuevo hito';
        } else {
            $subject = 'Actualización en tu proyecto: ' . $project->name;
            $action_text = 'Se ha actualizado un hito';
        }
        
        foreach ($clients as $client) {
            $message = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #0a0a0a; color: #fff; padding: 30px; text-align: center; }
                    .content { background: #f8f9fa; padding: 30px; }
                    .milestone { background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #FDC425; }
                    .btn { display: inline-block; padding: 12px 30px; background: #FDC425; color: #000; text-decoration: none; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>BeBuilt</h1>
                    </div>
                    <div class='content'>
                        <h2>{$action_text}</h2>
                        <p>Hola <strong>{$client->username}</strong>,</p>
                        <p>{$action_text} en tu proyecto <strong>{$project->name}</strong>:</p>
                        
                        <div class='milestone'>
                            <h3>{$milestone->title}</h3>
                            <p><strong>Fecha:</strong> " . date('d/m/Y', strtotime($milestone->date)) . "</p>
                            <p><strong>Estado:</strong> " . $this->get_status_label($milestone->status) . "</p>
                        </div>
                        
                        <a href='{$project_url}' class='btn'>Ver proyecto completo</a>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($client->email, $subject, $message, $headers);
        }
        
        return true;
    }
    
    /**
     * Obtener etiqueta de estado
     */
    private function get_status_label($status) {
        $labels = array(
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En Proceso',
            'finalizado' => 'Finalizado'
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
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