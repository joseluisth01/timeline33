<?php
/**
 * Sistema de Auditoría - Registro de todas las acciones
 * Archivo: includes/class-audit-log.php
 */

if (!defined('ABSPATH')) exit;

class Timeline_Audit_Log {
    
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
     * Registrar acción en el log
     * 
     * @param int $user_id ID del usuario que realiza la acción
     * @param string $action Tipo de acción (create, update, delete, view, upload, download, login, etc.)
     * @param string $entity_type Tipo de entidad (project, milestone, document, user, etc.)
     * @param int $entity_id ID de la entidad afectada
     * @param string $details Detalles adicionales de la acción
     * @param array $metadata Datos adicionales en formato JSON (opcional)
     */
    public function log($user_id, $action, $entity_type, $entity_id, $details = '', $metadata = array()) {
        global $wpdb;
        $table = $this->db->get_table_name('activity_log');
        
        // Obtener información adicional del contexto
        $ip_address = $this->get_user_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Convertir metadata a JSON si hay datos
        $metadata_json = !empty($metadata) ? json_encode($metadata) : null;
        
        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => intval($user_id),
                'action' => sanitize_text_field($action),
                'entity_type' => sanitize_text_field($entity_type),
                'entity_id' => intval($entity_id),
                'details' => sanitize_text_field($details),
                'metadata' => $metadata_json,
                'ip_address' => sanitize_text_field($ip_address),
                'user_agent' => sanitize_text_field($user_agent)
            )
        );
        
        return $result !== false;
    }
    
    /**
     * Obtener logs con filtros
     */
    public function get_logs($filters = array()) {
        global $wpdb;
        $table_log = $this->db->get_table_name('activity_log');
        $table_users = $this->db->get_table_name('users');
        
        $where = array('1=1');
        $params = array();
        
        // Por defecto, excluir clientes (solo mostrar admins)
        $exclude_clients = isset($filters['exclude_clients']) ? $filters['exclude_clients'] : true;
        if ($exclude_clients) {
            $where[] = "u.role IN ('super_admin', 'administrador')";
        }
        
        // Filtro por usuario
        if (isset($filters['user_id'])) {
            $where[] = "l.user_id = %d";
            $params[] = intval($filters['user_id']);
        }
        
        // Filtro por acción
        if (isset($filters['action'])) {
            $where[] = "l.action = %s";
            $params[] = sanitize_text_field($filters['action']);
        }
        
        // Filtro por tipo de entidad
        if (isset($filters['entity_type'])) {
            $where[] = "l.entity_type = %s";
            $params[] = sanitize_text_field($filters['entity_type']);
        }
        
        // Filtro por entidad específica
        if (isset($filters['entity_id'])) {
            $where[] = "l.entity_id = %d";
            $params[] = intval($filters['entity_id']);
        }
        
        // Filtro por fecha desde
        if (isset($filters['date_from'])) {
            $where[] = "l.created_at >= %s";
            $params[] = sanitize_text_field($filters['date_from']);
        }
        
        // Filtro por fecha hasta
        if (isset($filters['date_to'])) {
            $where[] = "l.created_at <= %s";
            $params[] = sanitize_text_field($filters['date_to']);
        }
        
        // Límite de resultados
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 100;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
        
        $where_clause = implode(' AND ', $where);
        
        $query = "
            SELECT 
                l.*,
                u.username,
                u.email,
                u.role as user_role
            FROM {$table_log} l
            LEFT JOIN {$table_users} u ON l.user_id = u.id
            WHERE {$where_clause}
            ORDER BY l.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Obtener logs de una entidad específica
     */
    public function get_entity_logs($entity_type, $entity_id, $limit = 50) {
        return $this->get_logs(array(
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'limit' => $limit
        ));
    }
    
    /**
     * Obtener logs de un usuario
     */
    public function get_user_logs($user_id, $limit = 100) {
        return $this->get_logs(array(
            'user_id' => $user_id,
            'limit' => $limit
        ));
    }
    
    /**
     * Obtener logs de un proyecto (incluye todas las entidades relacionadas)
     */
    public function get_project_logs($project_id, $limit = 100) {
        global $wpdb;
        $table_log = $this->db->get_table_name('activity_log');
        $table_users = $this->db->get_table_name('users');
        $table_milestones = $this->db->get_table_name('milestones');
        
        // Obtener IDs de hitos del proyecto
        $milestone_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table_milestones} WHERE project_id = %d",
            $project_id
        ));
        
        $milestone_ids_str = !empty($milestone_ids) ? implode(',', array_map('intval', $milestone_ids)) : '0';
        
        $query = "
            SELECT 
                l.*,
                u.username,
                u.email,
                u.role as user_role
            FROM {$table_log} l
            LEFT JOIN {$table_users} u ON l.user_id = u.id
            WHERE 
                (l.entity_type = 'project' AND l.entity_id = %d)
                OR (l.entity_type = 'milestone' AND l.entity_id IN ($milestone_ids_str))
                OR (l.entity_type = 'document' AND l.entity_id IN (
                    SELECT id FROM " . $this->db->get_table_name('documents') . " WHERE project_id = %d
                ))
            ORDER BY l.created_at DESC
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $project_id, $project_id, $limit));
    }
    
    /**
     * Contar logs con filtros
     */
    public function count_logs($filters = array()) {
        global $wpdb;
        $table_log = $this->db->get_table_name('activity_log');
        $table_users = $this->db->get_table_name('users');
        
        $where = array('1=1');
        $params = array();
        
        // Por defecto, excluir clientes (solo contar admins)
        $exclude_clients = isset($filters['exclude_clients']) ? $filters['exclude_clients'] : true;
        if ($exclude_clients) {
            $where[] = "u.role IN ('super_admin', 'administrador')";
        }
        
        if (isset($filters['user_id'])) {
            $where[] = "l.user_id = %d";
            $params[] = intval($filters['user_id']);
        }
        
        if (isset($filters['action'])) {
            $where[] = "l.action = %s";
            $params[] = sanitize_text_field($filters['action']);
        }
        
        if (isset($filters['entity_type'])) {
            $where[] = "l.entity_type = %s";
            $params[] = sanitize_text_field($filters['entity_type']);
        }
        
        if (isset($filters['date_from'])) {
            $where[] = "l.created_at >= %s";
            $params[] = sanitize_text_field($filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $where[] = "l.created_at <= %s";
            $params[] = sanitize_text_field($filters['date_to']);
        }
        
        $where_clause = implode(' AND ', $where);
        $query = "
            SELECT COUNT(*) 
            FROM {$table_log} l
            LEFT JOIN {$table_users} u ON l.user_id = u.id
            WHERE {$where_clause}
        ";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_var($query);
    }
    
    /**
     * Obtener estadísticas de actividad
     */
    public function get_activity_stats($date_from = null, $date_to = null) {
        global $wpdb;
        $table_log = $this->db->get_table_name('activity_log');
        $table_users = $this->db->get_table_name('users');
        
        $where = array('1=1');
        $params = array();
        
        // Solo contar acciones de administradores
        $where[] = "u.role IN ('super_admin', 'administrador')";
        
        if ($date_from) {
            $where[] = "l.created_at >= %s";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $where[] = "l.created_at <= %s";
            $params[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Estadísticas por acción
        $query_actions = "
            SELECT l.action, COUNT(*) as count
            FROM {$table_log} l
            LEFT JOIN {$table_users} u ON l.user_id = u.id
            WHERE {$where_clause}
            GROUP BY l.action
            ORDER BY count DESC
        ";
        
        if (!empty($params)) {
            $query_actions = $wpdb->prepare($query_actions, $params);
        }
        
        $actions = $wpdb->get_results($query_actions);
        
        // Estadísticas por usuario (solo admins)
        $query_users = "
            SELECT l.user_id, COUNT(*) as count
            FROM {$table_log} l
            LEFT JOIN {$table_users} u ON l.user_id = u.id
            WHERE {$where_clause}
            GROUP BY l.user_id
            ORDER BY count DESC
            LIMIT 10
        ";
        
        if (!empty($params)) {
            $query_users = $wpdb->prepare($query_users, $params);
        }
        
        $users = $wpdb->get_results($query_users);
        
        // Total de acciones (solo admins)
        $query_total = "
            SELECT COUNT(*) 
            FROM {$table_log} l
            LEFT JOIN {$table_users} u ON l.user_id = u.id
            WHERE {$where_clause}
        ";
        if (!empty($params)) {
            $query_total = $wpdb->prepare($query_total, $params);
        }
        $total = $wpdb->get_var($query_total);
        
        return array(
            'total' => $total,
            'by_action' => $actions,
            'by_user' => $users
        );
    }
    
    /**
     * Limpiar logs antiguos
     * @param int $days Días de antigüedad para eliminar
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;
        $table = $this->db->get_table_name('activity_log');
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s",
            $date_limit
        ));
    }
    
    /**
     * Obtener IP del usuario
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
    }
    
    /**
     * Formatear acción para mostrar
     */
    public function format_action($action) {
        $actions = array(
            'create' => 'Creó',
            'update' => 'Actualizó',
            'delete' => 'Eliminó',
            'view' => 'Visualizó',
            'upload' => 'Subió',
            'download' => 'Descargó',
            'login' => 'Inició sesión',
            'logout' => 'Cerró sesión',
            'assign' => 'Asignó',
            'unassign' => 'Desasignó'
        );
        
        return isset($actions[$action]) ? $actions[$action] : ucfirst($action);
    }
    
    /**
     * Formatear tipo de entidad para mostrar
     */
    public function format_entity_type($entity_type) {
        $types = array(
            'project' => 'Proyecto',
            'milestone' => 'Hito',
            'document' => 'Documento',
            'user' => 'Usuario',
            'project_client' => 'Cliente del Proyecto'
        );
        
        return isset($types[$entity_type]) ? $types[$entity_type] : ucfirst($entity_type);
    }
}