<?php
/**
 * Clase para gestión de proyectos
 * Archivo: includes/class-projects.php
 */

if (!defined('ABSPATH')) exit;

class Timeline_Projects {
    
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
     * Crear un nuevo proyecto
     */
    public function create_project($data, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('projects');
        
        $result = $wpdb->insert(
            $table,
            array(
                'name' => sanitize_text_field($data['name']),
                'address' => sanitize_textarea_field($data['address']),
                'start_date' => sanitize_text_field($data['start_date']),
                'end_date' => sanitize_text_field($data['end_date']),
                'description' => sanitize_textarea_field($data['description']),
                'featured_image' => sanitize_text_field($data['featured_image']),
                'project_status' => isset($data['project_status']) ? sanitize_text_field($data['project_status']) : 'en_proceso',
                'created_by' => $user_id
            )
        );
        
        if ($result) {
            $project_id = $wpdb->insert_id;
            
            // Log de actividad
            $this->log_activity($user_id, 'create', 'project', $project_id, 'Proyecto creado: ' . $data['name']);
            
            return $project_id;
        }
        
        return false;
    }
    
    /**
     * Actualizar un proyecto
     */
    public function update_project($project_id, $data, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('projects');
        
        // DEBUG: Ver qué datos llegan
        error_log('=== UPDATE PROJECT DEBUG ===');
        error_log('Project ID: ' . $project_id);
        error_log('Data recibida: ' . print_r($data, true));
        error_log('Tabla: ' . $table);
        
        $update_data = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['address'])) {
            $update_data['address'] = sanitize_textarea_field($data['address']);
        }
        if (isset($data['start_date'])) {
            $update_data['start_date'] = sanitize_text_field($data['start_date']);
        }
        if (isset($data['end_date'])) {
            $update_data['end_date'] = sanitize_text_field($data['end_date']);
        }
        if (isset($data['actual_end_date'])) {
            $update_data['actual_end_date'] = sanitize_text_field($data['actual_end_date']);
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['featured_image'])) {
            $update_data['featured_image'] = sanitize_text_field($data['featured_image']);
        }
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['project_status'])) {
            $update_data['project_status'] = sanitize_text_field($data['project_status']);
            error_log('✓ project_status detectado: ' . $data['project_status']);
        } else {
            error_log('✗ project_status NO está en $data');
        }
        
        // DEBUG: Ver qué se va a actualizar
        error_log('Update data preparada: ' . print_r($update_data, true));
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $project_id)
        );
        
        // DEBUG: Ver resultado de la actualización
        error_log('Resultado wpdb->update: ' . var_export($result, true));
        error_log('wpdb->last_error: ' . $wpdb->last_error);
        error_log('wpdb->last_query: ' . $wpdb->last_query);
        error_log('=== FIN UPDATE PROJECT DEBUG ===');
        
        if ($result !== false) {
            $milestone = $this->get_project($project_id);
            
            $this->log_activity($user_id, 'update', 'project', $project_id, 
                'Proyecto actualizado');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener un proyecto por ID
     */
    public function get_project($project_id) {
        global $wpdb;
        $table = $this->db->get_table_name('projects');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $project_id
        ));
    }

    /**
 * Notificar a clientes sobre nuevo proyecto asignado
 */
public function notify_clients_new_project($project_id, $client_ids = array()) {
    global $wpdb;
    
    $project = $this->get_project($project_id);
    
    if (!$project || empty($client_ids)) {
        return false;
    }
    
    // URL del proyecto
    $project_url = home_url('/timeline-proyecto/' . $project_id);
    
    // Obtener información de cada cliente
    $table_users = $this->db->get_table_name('users');
    
    foreach ($client_ids as $client_id) {
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_users} WHERE id = %d",
            intval($client_id)
        ));
        
        if (!$client) {
            continue;
        }
        
        $subject = '¡Nuevo proyecto asignado! - BeBuilt';
        
        $start_date = new DateTime($project->start_date);
        $end_date = new DateTime($project->end_date);
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 0;
                }
                .header { 
                    background: #0a0a0a; 
                    color: #fff; 
                    padding: 40px 30px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 300;
                    letter-spacing: 3px;
                }
                .content { 
                    background: #ffffff; 
                    padding: 40px 30px; 
                }
                .content h2 {
                    color: #2c3e50;
                    font-size: 24px;
                    margin-bottom: 20px;
                    font-weight: 400;
                }
                .project-card { 
                    background: #f8f9fa; 
                    padding: 25px; 
                    margin: 25px 0; 
                    border-left: 4px solid #FDC425; 
                }
                .project-card h3 {
                    margin: 0 0 15px 0;
                    color: #2c3e50;
                    font-size: 20px;
                    font-weight: 600;
                }
                .project-info {
                    margin: 10px 0;
                    color: #555;
                    font-size: 14px;
                }
                .project-info strong {
                    color: #2c3e50;
                    font-weight: 600;
                }
                .btn { 
                    display: inline-block; 
                    padding: 14px 35px; 
                    background: #FDC425; 
                    color: #000; 
                    text-decoration: none; 
                    margin-top: 25px;
                    font-weight: 600;
                    border-radius: 5px;
                    text-transform: uppercase;
                    font-size: 12px;
                    letter-spacing: 1px;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px 30px;
                    text-align: center;
                    color: #666;
                    font-size: 13px;
                }
                .divider {
                    height: 1px;
                    background: #e0e0e0;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BEBUILT</h1>
                </div>
                <div class='content'>
                    <h2>¡Tienes un nuevo proyecto! 🎉</h2>
                    <p>Hola <strong>{$client->username}</strong>,</p>
                    <p>Nos complace informarte que se ha creado un nuevo proyecto y ha sido asignado a tu cuenta.</p>
                    
                    <div class='project-card'>
                        <h3>{$project->name}</h3>
                        <div class='project-info'>
                            <strong>📍 Ubicación:</strong> " . esc_html($project->address) . "
                        </div>
                        <div class='project-info'>
                            <strong>📅 Inicio:</strong> " . $start_date->format('d/m/Y') . "
                        </div>
                        <div class='project-info'>
                            <strong>🏁 Fin previsto:</strong> " . $end_date->format('d/m/Y') . "
                        </div>
                    </div>
                    
                    " . (!empty($project->description) ? "
                    <div class='divider'></div>
                    <p style='color: #555; line-height: 1.8;'>" . nl2br(esc_html($project->description)) . "</p>
                    " : "") . "
                    
                    <div class='divider'></div>
                    
                    <p style='margin-top: 25px;'>Desde tu área de proyectos podrás:</p>
                    <ul style='color: #555; line-height: 1.8;'>
                        <li>Ver el timeline con todos los hitos del proyecto</li>
                        <li>Consultar el estado de cada fase</li>
                        <li>Descargar documentos relacionados</li>
                        <li>Seguir el progreso en tiempo real</li>
                    </ul>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='{$project_url}' class='btn'>Ver mi proyecto</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>Este es un correo automático. Si tienes alguna duda, contacta con tu gestor de proyecto.</p>
                    <p style='margin-top: 10px;'><strong>BeBuilt</strong> - Gestión de Proyectos</p>
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
     * Obtener todos los proyectos
     */
    public function get_all_projects($filters = array()) {
        global $wpdb;
        $table = $this->db->get_table_name('projects');
        
        $where = array('1=1');
        
        if (isset($filters['status'])) {
            $where[] = $wpdb->prepare('status = %s', $filters['status']);
        }
        
        if (isset($filters['created_by'])) {
            $where[] = $wpdb->prepare('created_by = %d', $filters['created_by']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        return $wpdb->get_results(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC"
        );
    }
    
    /**
     * Obtener proyectos de un cliente
     */
    public function get_client_projects($client_id) {
        global $wpdb;
        $table_projects = $this->db->get_table_name('projects');
        $table_project_clients = $this->db->get_table_name('project_clients');
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT p.* 
            FROM {$table_projects} p
            INNER JOIN {$table_project_clients} pc ON p.id = pc.project_id
            WHERE pc.client_id = %d
            ORDER BY 
                CASE p.project_status
                    WHEN 'en_proceso' THEN 1
                    WHEN 'pendiente' THEN 2
                    WHEN 'finalizado' THEN 3
                    ELSE 4
                END,
                p.created_at DESC",
            $client_id
        ));
        
        // DEBUG: Descomentar para ver qué se está obteniendo
        // foreach ($results as $proj) {
        //     error_log('Proyecto: ' . $proj->name . ' - project_status en resultado: ' . (isset($proj->project_status) ? $proj->project_status : 'NO EXISTE'));
        // }
        
        return $results;
    }
    
    /**
     * Asignar cliente a proyecto
     */
    public function assign_client_to_project($project_id, $client_id, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('project_clients');
        
        $result = $wpdb->insert(
            $table,
            array(
                'project_id' => $project_id,
                'client_id' => $client_id
            )
        );
        
        if ($result) {
            $this->log_activity($user_id, 'assign', 'project_client', $project_id, 
                "Cliente ID {$client_id} asignado al proyecto");
            return true;
        }
        
        return false;
    }
    
    /**
     * Desasignar cliente de proyecto
     */
    public function unassign_client_from_project($project_id, $client_id, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('project_clients');
        
        $result = $wpdb->delete(
            $table,
            array(
                'project_id' => $project_id,
                'client_id' => $client_id
            )
        );
        
        if ($result) {
            $this->log_activity($user_id, 'unassign', 'project_client', $project_id, 
                "Cliente ID {$client_id} desasignado del proyecto");
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener clientes asignados a un proyecto
     */
    public function get_project_clients($project_id) {
        global $wpdb;
        $table_users = $this->db->get_table_name('users');
        $table_project_clients = $this->db->get_table_name('project_clients');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.* 
            FROM {$table_users} u
            INNER JOIN {$table_project_clients} pc ON u.id = pc.client_id
            WHERE pc.project_id = %d",
            $project_id
        ));
    }
    
    /**
     * Obtener todos los clientes disponibles
     */
    public function get_available_clients() {
        global $wpdb;
        $table_users = $this->db->get_table_name('users');
        
        return $wpdb->get_results(
            "SELECT id, username, email FROM {$table_users} WHERE role = 'cliente' ORDER BY username ASC"
        );
    }
    
    /**
     * Eliminar proyecto
     */
    public function delete_project($project_id, $user_id) {
        global $wpdb;
        $table = $this->db->get_table_name('projects');
        
        // Obtener info del proyecto antes de eliminar
        $project = $this->get_project($project_id);
        
        $result = $wpdb->delete($table, array('id' => $project_id));
        
        if ($result) {
            // Eliminar relaciones
            $wpdb->delete($this->db->get_table_name('project_clients'), array('project_id' => $project_id));
            
            $this->log_activity($user_id, 'delete', 'project', $project_id, 
                'Proyecto eliminado: ' . $project->name);
            return true;
        }
        
        return false;
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
    
    /**
     * Obtener estadísticas de proyectos
     */
    public function get_project_stats($user_id = null, $role = null) {
        global $wpdb;
        $table = $this->db->get_table_name('projects');
        
        $stats = array(
            'total' => 0,
            'activos' => 0,
            'completados' => 0,
            'en_retraso' => 0
        );
        
        if ($role === 'cliente') {
            $table_project_clients = $this->db->get_table_name('project_clients');
            $projects = $wpdb->get_results($wpdb->prepare(
                "SELECT p.* 
                FROM {$table} p
                INNER JOIN {$table_project_clients} pc ON p.id = pc.project_id
                WHERE pc.client_id = %d",
                $user_id
            ));
        } else {
            $projects = $wpdb->get_results("SELECT * FROM {$table}");
        }
        
        $stats['total'] = count($projects);
        
        foreach ($projects as $project) {
            if ($project->status === 'completado') {
                $stats['completados']++;
            } elseif ($project->status === 'activo') {
                $stats['activos']++;
                
                // Verificar si está en retraso
                if (strtotime($project->end_date) < time() && !$project->actual_end_date) {
                    $stats['en_retraso']++;
                }
            }
        }
        
        return $stats;
    }
}