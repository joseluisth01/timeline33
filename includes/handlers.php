<?php
/**
 * Handlerss para acciones de hitos y documentos
 * Archivo: includes/handlers.php
 * ACTUALIZADO: Manejo mejorado de sesiones para modo incógnito
 */

if (!defined('ABSPATH')) exit;

class Timeline_Handlers {
    
    private $milestones;
    private $documents;
    
    public function __construct() {
        $this->milestones = Timeline_Milestones::get_instance();
        $this->documents = Timeline_Documents::get_instance();
        
        // Registrar handlers de hitos
        add_action('admin_post_timeline_save_milestone', array($this, 'handle_save_milestone'));
        add_action('admin_post_timeline_delete_milestone', array($this, 'handle_delete_milestone'));
        
        // Registrar handlers de documentos
        // add_action('admin_post_timeline_upload_document', array($this, 'handle_upload_document'));
        // add_action('admin_post_timeline_delete_document', array($this, 'handle_delete_document'));
    }
    
    /**
     * Verificar si el usuario está logueado (compatible con modo incógnito)
     */
    private function verify_logged_in() {
        // Asegurar que la sesión está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Verificar que existe el usuario en sesión
        if (!isset($_SESSION['timeline_user_id']) || empty($_SESSION['timeline_user_id'])) {
            wp_redirect(home_url('/login-proyectos?error=session_expired'));
            exit;
        }
        
        return intval($_SESSION['timeline_user_id']);
    }
    
    /**
     * Obtener usuario actual
     */
    private function get_current_user($user_id) {
        global $wpdb;
        $table_users = $wpdb->prefix . 'timeline_users';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_users} WHERE id = %d",
            $user_id
        ));
    }
    
    /**
     * Guardar hito (crear o actualizar)
     */
    public function handle_save_milestone() {
        // Verificar login
        $user_id = $this->verify_logged_in();
        
        // Verificar nonce
        if (!isset($_POST['timeline_milestone_nonce']) || 
            !wp_verify_nonce($_POST['timeline_milestone_nonce'], 'timeline_milestone')) {
            wp_die('Error de seguridad - Nonce inválido');
        }
        
        global $wpdb;
        $user = $this->get_current_user($user_id);
        
        // Verificar permisos
        if (!$user || !in_array($user->role, array('super_admin', 'administrador'))) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $project_id = intval($_POST['project_id']);
        $milestone_id = isset($_POST['milestone_id']) && !empty($_POST['milestone_id']) 
            ? intval($_POST['milestone_id']) 
            : null;
        
        // Preparar datos
        $milestone_data = array(
            'project_id' => $project_id,
            'title' => sanitize_text_field($_POST['title']),
            'date' => sanitize_text_field($_POST['date']),
            'description' => sanitize_textarea_field($_POST['description']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        // Crear o actualizar hito
        if ($milestone_id) {
            // EDITAR - Actualizar hito existente
            $result = $this->milestones->update_milestone($milestone_id, $milestone_data, $user_id);
            
            if (!$result) {
                wp_redirect(home_url('/timeline-proyecto-admin/' . $project_id . '?error=update_failed'));
                exit;
            }
            
            $final_milestone_id = $milestone_id;
            
            // Procesar imágenes en modo edición
            $has_images = false;
            $db = Timeline_Database::get_instance();
            
            if (!empty($_POST['images_data'])) {
                $images_data = json_decode(stripslashes($_POST['images_data']), true);
                
                if (is_array($images_data) && count($images_data) > 0) {
                    // Eliminar todas las imágenes antiguas
                    $wpdb->delete(
                        $db->get_table_name('milestone_images'),
                        array('milestone_id' => $milestone_id)
                    );
                    
                    // Procesar nuevas imágenes
                    $order = 0;
                    foreach ($images_data as $image_item) {
                        if (isset($image_item['type'])) {
                            if ($image_item['type'] === 'existing' && isset($image_item['url'])) {
                                // Mantener imagen existente
                                $this->milestones->add_milestone_image($milestone_id, $image_item['url'], $order);
                                $has_images = true;
                            } elseif ($image_item['type'] === 'new' && isset($image_item['data'])) {
                                // Guardar nueva imagen
                                $image_url = $this->save_base64_image($image_item['data'], 'milestone_' . $milestone_id);
                                if ($image_url) {
                                    $this->milestones->add_milestone_image($milestone_id, $image_url, $order);
                                    $has_images = true;
                                }
                            }
                        }
                        $order++;
                    }
                }
            }
            
            // Si después de editar no hay imágenes, usar imagen por defecto
            if (!$has_images) {
                // Eliminar cualquier imagen antigua
                $wpdb->delete(
                    $db->get_table_name('milestone_images'),
                    array('milestone_id' => $milestone_id)
                );
                // Añadir imagen por defecto
                $default_image = 'https://www.bebuilt.es/wp-content/uploads/2023/08/cropped-favicon.png';
                $this->milestones->add_milestone_image($milestone_id, $default_image, 0);
            }
            
        } else {
            // CREAR - Nuevo hito
            $final_milestone_id = $this->milestones->create_milestone($milestone_data, $user_id);
            
            if (!$final_milestone_id) {
                wp_redirect(home_url('/timeline-proyecto-admin/' . $project_id . '?error=create_failed'));
                exit;
            }
            
            // Guardar imágenes para nuevo hito
            $has_images = false;
            
            if (!empty($_POST['images_data'])) {
                $images_data = json_decode(stripslashes($_POST['images_data']), true);
                
                if (is_array($images_data) && count($images_data) > 0) {
                    $order = 0;
                    foreach ($images_data as $image_item) {
                        if (isset($image_item['type']) && $image_item['type'] === 'new' && isset($image_item['data'])) {
                            $image_url = $this->save_base64_image($image_item['data'], 'milestone_' . $final_milestone_id);
                            
                            if ($image_url) {
                                $this->milestones->add_milestone_image($final_milestone_id, $image_url, $order);
                                $has_images = true;
                            }
                        }
                        $order++;
                    }
                }
            }
            
            // Si no se guardó ninguna imagen, usar la imagen por defecto
            if (!$has_images) {
                $default_image = 'https://www.bebuilt.es/wp-content/uploads/2023/08/cropped-favicon.png';
                $this->milestones->add_milestone_image($final_milestone_id, $default_image, 0);
            }
        }
        
        wp_redirect(home_url('/timeline-proyecto-admin/' . $project_id . '?success=saved'));
        exit;
    }
    
    /**
     * Eliminar hito
     */
    public function handle_delete_milestone() {
        // Verificar login
        $user_id = $this->verify_logged_in();
        
        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || 
            !wp_verify_nonce($_GET['_wpnonce'], 'delete_milestone')) {
            wp_die('Error de seguridad - Nonce inválido');
        }
        
        global $wpdb;
        $user = $this->get_current_user($user_id);
        
        // Verificar permisos
        if (!$user || !in_array($user->role, array('super_admin', 'administrador'))) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $milestone_id = intval($_GET['milestone_id']);
        $project_id = intval($_GET['project_id']);
        
        // Eliminar hito
        $result = $this->milestones->delete_milestone($milestone_id, $user_id);
        
        if ($result) {
            wp_redirect(home_url('/timeline-proyecto-admin/' . $project_id . '?success=deleted'));
        } else {
            wp_redirect(home_url('/timeline-proyecto-admin/' . $project_id . '?error=delete_failed'));
        }
        exit;
    }
    
    /**
     * Subir documento
     */
    public function handle_upload_document() {
        // Verificar login
        $user_id = $this->verify_logged_in();
        
        // Verificar nonce
        if (!isset($_POST['timeline_document_nonce']) || 
            !wp_verify_nonce($_POST['timeline_document_nonce'], 'timeline_document')) {
            wp_die('Error de seguridad - Nonce inválido');
        }
        
        global $wpdb;
        $user = $this->get_current_user($user_id);
        
        // Verificar permisos
        if (!$user || !in_array($user->role, array('super_admin', 'administrador'))) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $project_id = intval($_POST['project_id']);
        
        // Verificar que se haya subido un archivo
        if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(home_url('/timeline-documentos/' . $project_id . '?error=no_file'));
            exit;
        }
        
        $file = $_FILES['document_file'];
        $title = sanitize_text_field($_POST['document_title']);
        
        // Validar tamaño (10MB máximo)
        if ($file['size'] > 10 * 1024 * 1024) {
            wp_redirect(home_url('/timeline-documentos/' . $project_id . '?error=file_too_large'));
            exit;
        }
        
        // Subir archivo usando WordPress
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload_overrides = array(
            'test_form' => false,
            'mimes' => array(
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'txt' => 'text/plain',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            )
        );
        
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            $file_url = $movefile['url'];
            $file_type = pathinfo($movefile['file'], PATHINFO_EXTENSION);
            
            // Guardar en base de datos
            $result = $this->documents->add_document($project_id, $title, $file_url, $file_type, $user_id);
            
            if ($result) {
                wp_redirect(home_url('/timeline-documentos/' . $project_id . '?success=uploaded'));
            } else {
                wp_redirect(home_url('/timeline-documentos/' . $project_id . '?error=db_failed'));
            }
        } else {
            wp_redirect(home_url('/timeline-documentos/' . $project_id . '?error=upload_failed'));
        }
        exit;
    }
    
    /**
     * Eliminar documento
     */
    public function handle_delete_document() {
        // Verificar login
        $user_id = $this->verify_logged_in();
        
        // Verificar nonce
        if (!isset($_GET['_wpnonce']) || 
            !wp_verify_nonce($_GET['_wpnonce'], 'delete_document')) {
            wp_die('Error de seguridad - Nonce inválido');
        }
        
        global $wpdb;
        $user = $this->get_current_user($user_id);
        
        // Verificar permisos
        if (!$user || !in_array($user->role, array('super_admin', 'administrador'))) {
            wp_die('No tienes permisos para realizar esta acción.');
        }
        
        $document_id = intval($_GET['document_id']);
        $project_id = intval($_GET['project_id']);
        
        // Eliminar documento
        $result = $this->documents->delete_document($document_id, $user_id);
        
        if ($result) {
            wp_redirect(home_url('/timeline-documentos/' . $project_id . '?success=deleted'));
        } else {
            wp_redirect(home_url('/timeline-documentos/' . $project_id . '?error=delete_failed'));
        }
        exit;
    }
    
    /**
     * Guardar imagen base64 como archivo
     */
    private function save_base64_image($base64_string, $prefix = 'image') {
        // Extraer el tipo de imagen y los datos
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            
            // Validar tipo de imagen
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                error_log('Timeline Plugin: Tipo de imagen no válido - ' . $type);
                return '';
            }
            
            $base64_string = base64_decode($base64_string);
            
            if ($base64_string === false) {
                error_log('Timeline Plugin: Error al decodificar base64');
                return '';
            }
            
            // Crear directorio si no existe
            $upload_dir = wp_upload_dir();
            $timeline_dir = $upload_dir['basedir'] . '/timeline-milestones';
            
            if (!file_exists($timeline_dir)) {
                wp_mkdir_p($timeline_dir);
            }
            
            // Generar nombre único
            $filename = $prefix . '_' . uniqid() . '_' . time() . '.' . $type;
            $filepath = $timeline_dir . '/' . $filename;
            
            // Guardar archivo
            if (file_put_contents($filepath, $base64_string)) {
                // Retornar URL
                return $upload_dir['baseurl'] . '/timeline-milestones/' . $filename;
            } else {
                error_log('Timeline Plugin: Error al guardar imagen en ' . $filepath);
            }
        }
        
        return '';
    }
}

// Inicializar handlers
new Timeline_Handlers();