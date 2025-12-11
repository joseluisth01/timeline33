<?php

/**
 * Plugiin Name: Timeline - Sistema de Gestión de Proyectos
 * Description: Sistema completo de gestión de proyectos con línea de tiempo, hitos y auditoría
 * Version: 2.1
 * Author: BeBuilttttt2
 */

if (!defined('ABSPATH')) exit;

// Definir constantes
define('TIMELINE_VERSION', '2.1');
define('TIMELINE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TIMELINE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Verificar y cargar clases
$required_files = array(
    'includes/database.php',
    'includes/class-projects.php',
    'includes/class-milestones.php',
    'includes/class-documents.php',
    'includes/class-audit-log.php',  // NUEVO: Sistema de auditoría
    'includes/handlers.php'
);

foreach ($required_files as $file) {
    $file_path = TIMELINE_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        add_action('admin_notices', function () use ($file) {
            echo '<div class="notice notice-error"><p><strong>Timeline Plugin Error:</strong> Falta el archivo: ' . esc_html($file) . '</p></div>';
        });
    }
}

class Timeline_Plugin
{
    private $table_users;
    private $db;
    private $projects;
    private $milestones;
    private $audit_log;  // NUEVO

    public function __construct()
    {
        global $wpdb;
        $this->table_users = $wpdb->prefix . 'timeline_users';

        // Inicializar clases
        if (class_exists('Timeline_Database')) {
            $this->db = Timeline_Database::get_instance();
        }
        if (class_exists('Timeline_Projects')) {
            $this->projects = Timeline_Projects::get_instance();
        }
        if (class_exists('Timeline_Milestones')) {
            $this->milestones = Timeline_Milestones::get_instance();
        }
        if (class_exists('Timeline_Audit_Log')) {
            $this->audit_log = Timeline_Audit_Log::get_instance();
        }

        // Hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('init', array($this, 'init'));
        add_action('template_redirect', array($this, 'handle_custom_pages'));

        // Login/logout
        add_action('admin_post_nopriv_timeline_login', array($this, 'handle_login'));
        add_action('admin_post_timeline_login', array($this, 'handle_login'));
        add_action('admin_post_timeline_logout', array($this, 'handle_logout'));

        // Usuarios
        add_action('admin_post_timeline_create_user', array($this, 'handle_create_user'));
        add_action('admin_post_timeline_change_password', array($this, 'handle_change_password'));

        // Proyectos
        add_action('admin_post_timeline_create_project', array($this, 'handle_create_project'));
        add_action('admin_post_timeline_update_project', array($this, 'handle_update_project'));
        add_action('admin_post_timeline_delete_project', array($this, 'handle_delete_project'));

        add_action('admin_post_timeline_delete_user', array($this, 'handle_delete_user'));
        add_action('admin_post_timeline_admin_change_password', array($this, 'handle_admin_change_password'));

        // URLs
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('init', array($this, 'add_rewrite_rules'));
    }

    public function activate()
    {
        global $wpdb;

        // Crear todas las tablas
        if ($this->db) {
            $this->db->create_tables();
        }

        // Crear tabla de usuarios
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_users} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            username varchar(100) NOT NULL,
            password varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            role varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY username (username)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Crear super admin si no existe
        $super_admin = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_users} WHERE username = %s",
            'administrador'
        ));

        if (!$super_admin) {
            $wpdb->insert(
                $this->table_users,
                array(
                    'username' => 'administrador',
                    'password' => password_hash('adminproyectos', PASSWORD_DEFAULT),
                    'email' => get_option('admin_email'),
                    'role' => 'super_admin'
                )
            );
        }

        flush_rewrite_rules();
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'timeline_page';
        $vars[] = 'timeline_id';
        return $vars;
    }

    public function add_rewrite_rules()
    {
        add_rewrite_rule('^login-proyectos/?$', 'index.php?timeline_page=login', 'top');
        add_rewrite_rule('^timeline-logout/?$', 'index.php?timeline_page=logout', 'top'); // NUEVA LÍNEA
        add_rewrite_rule('^timeline-dashboard/?$', 'index.php?timeline_page=dashboard', 'top');
        add_rewrite_rule('^timeline-usuarios/?$', 'index.php?timeline_page=users', 'top');
        add_rewrite_rule('^timeline-perfil/?$', 'index.php?timeline_page=profile', 'top');
        add_rewrite_rule('^timeline-proyectos/?$', 'index.php?timeline_page=projects', 'top');
        add_rewrite_rule('^timeline-mis-proyectos/?$', 'index.php?timeline_page=my_projects', 'top');
        add_rewrite_rule('^timeline-proyecto-nuevo/?$', 'index.php?timeline_page=project_new', 'top');
        add_rewrite_rule('^timeline-proyecto-editar/([0-9]+)/?$', 'index.php?timeline_page=project_edit&timeline_id=$matches[1]', 'top');
        add_rewrite_rule('^timeline-proyecto/([0-9]+)/?$', 'index.php?timeline_page=project_view&timeline_id=$matches[1]', 'top');
        add_rewrite_rule('^timeline-proyecto-admin/([0-9]+)/?$', 'index.php?timeline_page=project_admin&timeline_id=$matches[1]', 'top');
        add_rewrite_rule('^timeline-documentos/([0-9]+)/?$', 'index.php?timeline_page=project_documents&timeline_id=$matches[1]', 'top');
        add_rewrite_rule('^timeline-audit-log/?$', 'index.php?timeline_page=audit_log', 'top');  // NUEVO
    }

    public function init()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public function is_logged_in()
    {
        return isset($_SESSION['timeline_user_id']);
    }

    public function get_current_user()
    {
        if (!$this->is_logged_in()) {
            return null;
        }

        global $wpdb;
        $user_id = $_SESSION['timeline_user_id'];
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_users} WHERE id = %d",
            $user_id
        ));
    }

    public function can_manage_users($user)
    {
        if (!$user) return false;
        return in_array($user->role, array('super_admin', 'administrador'));
    }

    public function can_manage_projects($user)
    {
        if (!$user) return false;
        return in_array($user->role, array('super_admin', 'administrador'));
    }

    public function handle_custom_pages()
    {
        $page = get_query_var('timeline_page');
        if (!$page) return;

        switch ($page) {
            case 'login':
                if ($this->is_logged_in()) {
                    $current_user = $this->get_current_user();
                    if ($current_user->role === 'cliente') {
                        wp_redirect(home_url('/timeline-mis-proyectos'));
                    } else {
                        wp_redirect(home_url('/timeline-dashboard'));
                    }
                    exit;
                }
                $this->load_template('login');
                break;

            case 'logout':
                $this->handle_logout();
                break;

            case 'dashboard':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if ($current_user->role === 'cliente') {
                    wp_redirect(home_url('/timeline-mis-proyectos'));
                    exit;
                }
                $this->load_template('dashboard');
                break;

            case 'users':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if (!$this->can_manage_users($current_user)) {
                    wp_redirect(home_url('/timeline-dashboard'));
                    exit;
                }
                $this->load_template('users');
                break;

            case 'profile':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $this->load_template('profile');
                break;

            case 'projects':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if (!$this->can_manage_projects($current_user)) {
                    wp_redirect(home_url('/timeline-mis-proyectos'));
                    exit;
                }
                $this->load_template('projects');
                break;

            case 'my_projects':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $this->load_template('my-projects');
                break;

            case 'project_new':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if (!$this->can_manage_projects($current_user)) {
                    wp_redirect(home_url('/timeline-dashboard'));
                    exit;
                }
                $this->load_template('project-form');
                break;

            case 'project_edit':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if (!$this->can_manage_projects($current_user)) {
                    wp_redirect(home_url('/timeline-dashboard'));
                    exit;
                }
                $project_id = get_query_var('timeline_id');
                if (!$project_id) {
                    wp_redirect(home_url('/timeline-proyectos'));
                    exit;
                }
                $this->load_template('project-form');
                break;

            case 'project_view':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $project_id = get_query_var('timeline_id');
                if (!$project_id) {
                    wp_redirect(home_url('/timeline-dashboard'));
                    exit;
                }

                // REGISTRAR VISUALIZACIÓN DEL PROYECTO
                $current_user = $this->get_current_user();
                if ($this->audit_log) {
                    $this->audit_log->log(
                        $current_user->id,
                        'view',
                        'project',
                        $project_id,
                        'Proyecto visualizado'
                    );
                }

                $this->load_template('project-timeline');
                break;

            case 'project_admin':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if (!$this->can_manage_projects($current_user)) {
                    wp_redirect(home_url('/timeline-dashboard'));
                    exit;
                }
                $project_id = get_query_var('timeline_id');
                if (!$project_id) {
                    wp_redirect(home_url('/timeline-proyectos'));
                    exit;
                }
                $this->load_template_admin('project-timeline-admin');
                break;

            case 'project_documents':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if (!$this->can_manage_projects($current_user)) {
                    wp_redirect(home_url('/timeline-dashboard'));
                    exit;
                }
                $project_id = get_query_var('timeline_id');
                if (!$project_id) {
                    wp_redirect(home_url('/timeline-proyectos'));
                    exit;
                }
                $this->load_template_admin('project-documents');
                break;

            // NUEVO: Página de audit log
            case 'audit_log':
                if (!$this->is_logged_in()) {
                    wp_redirect(home_url('/login-proyectos'));
                    exit;
                }
                $current_user = $this->get_current_user();
                if (!$this->can_manage_users($current_user)) {
                    wp_redirect(home_url('/timeline-dashboard'));
                    exit;
                }
                $this->load_template_admin('audit-log');
                break;
        }
    }


    private function load_template($template)
    {
        $template_file = TIMELINE_PLUGIN_DIR . 'templates/' . $template . '.php';

        if (file_exists($template_file)) {
            $current_user = $this->get_current_user();
            $projects_class = $this->projects;
            $milestones_class = $this->milestones;

            $GLOBALS['timeline_plugin'] = $this;

            // Preparar variables según template
            switch ($template) {
                case 'users':
                    global $wpdb;
                    $users = $wpdb->get_results("SELECT * FROM {$this->table_users} ORDER BY created_at DESC");
                    break;

                case 'projects':
                    $projects = $this->projects ? $this->projects->get_all_projects() : array();
                    break;

                case 'my-projects':
                    $projects = $this->projects ? $this->projects->get_client_projects($current_user->id) : array();
                    break;

                case 'project-form':
                    $project_id = get_query_var('timeline_id');
                    $project = null;
                    $available_clients = array();
                    $assigned_clients = array();

                    if ($this->projects) {
                        $project = $project_id ? $this->projects->get_project($project_id) : null;
                        $available_clients = $this->projects->get_available_clients();
                        $assigned_clients = $project_id ? $this->projects->get_project_clients($project_id) : array();
                    }
                    break;
            }

            // NUEVO: Para páginas de cliente, usar get_header() antes del template
            if (in_array($template, array('my-projects', 'project-timeline'))) {
                // Indicar a WordPress que estamos en una página especial
                add_filter('body_class', function ($classes) {
                    $classes[] = 'timeline-client-page';
                    return $classes;
                });

                // Cargar header de WordPress (esto cargará todos los estilos del tema)
                get_header();

                // Cerrar la estructura del header si es necesario
                if (function_exists('get_theme_mod')) {
                    echo '<div class="timeline-content-wrapper">';
                }
            }

            include $template_file;
            exit;
        } else {
            wp_die('Template no encontrado: ' . $template);
        }
    }

    private function load_template_admin($template)
    {
        $template_file = TIMELINE_PLUGIN_DIR . 'admin/' . $template . '.php';

        if (file_exists($template_file)) {
            $current_user = $this->get_current_user();
            $projects_class = $this->projects;
            $milestones_class = $this->milestones;
            $GLOBALS['timeline_plugin'] = $this;

            include $template_file;
            exit;
        } else {
            wp_die('Template de admin no encontrado: ' . $template);
        }
    }

    public function handle_login()
    {
        if (
            !isset($_POST['timeline_login_nonce']) ||
            !wp_verify_nonce($_POST['timeline_login_nonce'], 'timeline_login')
        ) {
            wp_redirect(home_url('/login-proyectos?error=nonce'));
            exit;
        }

        global $wpdb;
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_users} WHERE username = %s",
            $username
        ));

        if ($user && password_verify($password, $user->password)) {
            $_SESSION['timeline_user_id'] = $user->id;
            $_SESSION['timeline_user_role'] = $user->role;
            $_SESSION['timeline_username'] = $user->username;

            // REGISTRAR LOGIN
            if ($this->audit_log) {
                $this->audit_log->log(
                    $user->id,
                    'login',
                    'user',
                    $user->id,
                    'Inicio de sesión exitoso'
                );
            }

            if ($user->role === 'cliente') {
                wp_redirect(home_url('/timeline-mis-proyectos'));
            } else {
                wp_redirect(home_url('/timeline-dashboard'));
            }
            exit;
        }

        wp_redirect(home_url('/login-proyectos?error=invalid'));
        exit;
    }

    public function handle_logout()
    {
        // REGISTRAR LOGOUT ANTES DE DESTRUIR SESIÓN
        if ($this->is_logged_in() && $this->audit_log) {
            $current_user = $this->get_current_user();
            $this->audit_log->log(
                $current_user->id,
                'logout',
                'user',
                $current_user->id,
                'Cierre de sesión'
            );
        }

        session_destroy();
        wp_redirect(home_url('/login-proyectos?logout=success'));
        exit;
    }

    public function handle_create_user()
    {
        if (!$this->is_logged_in()) {
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }

        $current_user = $this->get_current_user();

        if (!$this->can_manage_users($current_user)) {
            wp_die('No tienes permisos.');
        }

        if (
            !isset($_POST['timeline_create_user_nonce']) ||
            !wp_verify_nonce($_POST['timeline_create_user_nonce'], 'timeline_create_user')
        ) {
            wp_die('Error de seguridad');
        }

        global $wpdb;

        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $role = sanitize_text_field($_POST['role']);

        if ($current_user->role === 'administrador' && $role !== 'cliente') {
            wp_redirect(home_url('/timeline-usuarios?error=permission'));
            exit;
        }

        $password = wp_generate_password(12, false);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $result = $wpdb->insert(
            $this->table_users,
            array(
                'username' => $username,
                'password' => $password_hash,
                'email' => $email,
                'role' => $role
            )
        );

        if ($result) {
            $new_user_id = $wpdb->insert_id;

            // REGISTRAR CREACIÓN DE USUARIO
            if ($this->audit_log) {
                $this->audit_log->log(
                    $current_user->id,
                    'create',
                    'user',
                    $new_user_id,
                    "Usuario creado: {$username} ({$role})"
                );
            }

            $this->send_welcome_email($username, $email, $password);
            wp_redirect(home_url('/timeline-usuarios?success=created'));
        } else {
            wp_redirect(home_url('/timeline-usuarios?error=failed'));
        }
        exit;
    }

    /**
 * Eliminar usuario (Super Admin y Administradores)
 */
public function handle_delete_user()
{
    if (!$this->is_logged_in()) {
        wp_redirect(home_url('/login-proyectos'));
        exit;
    }

    $current_user = $this->get_current_user();

    // Solo super_admin y administrador pueden eliminar usuarios
    if (!in_array($current_user->role, ['super_admin', 'administrador'])) {
        wp_redirect(home_url('/timeline-usuarios?error=permission'));
        exit;
    }

    // Verificar nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'timeline_delete_user')) {
        wp_die('Error de seguridad');
    }

    global $wpdb;
    $user_id = intval($_GET['user_id']);

    // Obtener info del usuario a eliminar
    $user_to_delete = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$this->table_users} WHERE id = %d",
        $user_id
    ));

    if (!$user_to_delete) {
        wp_redirect(home_url('/timeline-usuarios?error=not_found'));
        exit;
    }

    // No permitir eliminar el propio usuario
    if ($user_id == $current_user->id) {
        wp_redirect(home_url('/timeline-usuarios?error=cannot_delete_self'));
        exit;
    }

    // No permitir eliminar al super admin
    if ($user_to_delete->role === 'super_admin') {
        wp_redirect(home_url('/timeline-usuarios?error=cannot_delete_super_admin'));
        exit;
    }

    // RESTRICCIÓN ADICIONAL: Administradores solo pueden eliminar clientes
    if ($current_user->role === 'administrador' && $user_to_delete->role !== 'cliente') {
        wp_redirect(home_url('/timeline-usuarios?error=permission'));
        exit;
    }

    // REGISTRAR EN AUDIT LOG ANTES DE ELIMINAR
    if ($this->audit_log) {
        $this->audit_log->log(
            $current_user->id,
            'delete',
            'user',
            $user_id,
            "Usuario eliminado: {$user_to_delete->username} ({$user_to_delete->role})"
        );
    }

    // Si es cliente, desasignar de todos los proyectos
    if ($user_to_delete->role === 'cliente' && $this->projects) {
        $table_project_clients = $this->db->get_table_name('project_clients');
        $wpdb->delete($table_project_clients, array('client_id' => $user_id));
    }

    // Eliminar usuario
    $result = $wpdb->delete(
        $this->table_users,
        array('id' => $user_id)
    );

    if ($result) {
        wp_redirect(home_url('/timeline-usuarios?success=deleted'));
    } else {
        wp_redirect(home_url('/timeline-usuarios?error=delete_failed'));
    }
    exit;
}

    /**
 * Cambiar contraseña de otro usuario (Super Admin y Administradores)
 */
public function handle_admin_change_password()
{
    if (!$this->is_logged_in()) {
        wp_redirect(home_url('/login-proyectos'));
        exit;
    }

    $current_user = $this->get_current_user();

    // Solo super_admin y administrador pueden cambiar contraseñas
    if (!in_array($current_user->role, ['super_admin', 'administrador'])) {
        wp_redirect(home_url('/timeline-usuarios?error=permission'));
        exit;
    }

    if (
        !isset($_POST['timeline_admin_change_password_nonce']) ||
        !wp_verify_nonce($_POST['timeline_admin_change_password_nonce'], 'timeline_admin_change_password')
    ) {
        wp_die('Error de seguridad');
    }

    global $wpdb;
    $target_user_id = intval($_POST['target_user_id']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if ($new_password !== $confirm_password) {
        wp_redirect(home_url('/timeline-usuarios?error=password_mismatch'));
        exit;
    }

    if (strlen($new_password) < 8) {
        wp_redirect(home_url('/timeline-usuarios?error=password_length'));
        exit;
    }

    // Obtener info del usuario
    $target_user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$this->table_users} WHERE id = %d",
        $target_user_id
    ));

    if (!$target_user) {
        wp_redirect(home_url('/timeline-usuarios?error=user_not_found'));
        exit;
    }

    // No permitir cambiar contraseña del super admin
    if ($target_user->role === 'super_admin') {
        wp_redirect(home_url('/timeline-usuarios?error=cannot_change_super_admin'));
        exit;
    }

    // RESTRICCIÓN ADICIONAL: Administradores solo pueden cambiar contraseñas de clientes
    if ($current_user->role === 'administrador' && $target_user->role !== 'cliente') {
        wp_redirect(home_url('/timeline-usuarios?error=permission'));
        exit;
    }

    // Actualizar contraseña
    $result = $wpdb->update(
        $this->table_users,
        array('password' => password_hash($new_password, PASSWORD_DEFAULT)),
        array('id' => $target_user_id)
    );

    if ($result !== false) {
        // REGISTRAR EN AUDIT LOG
        if ($this->audit_log) {
            $this->audit_log->log(
                $current_user->id,
                'update',
                'user',
                $target_user_id,
                "Contraseña cambiada para usuario: {$target_user->username}"
            );
        }

        // Enviar email al usuario con la nueva contraseña
        $this->send_password_changed_email($target_user->username, $target_user->email, $new_password);

        wp_redirect(home_url('/timeline-usuarios?success=password_changed'));
    } else {
        wp_redirect(home_url('/timeline-usuarios?error=password_change_failed'));
    }
    exit;
}

    /**
     * Enviar email cuando un admin cambia la contraseña de un usuario
     */
    private function send_password_changed_email($username, $email, $new_password)
    {
        $login_url = home_url('/login-proyectos');
        $subject = 'Tu contraseña ha sido actualizada - BeBuilt';

        $message = "
    <!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #2c3e50;'>Tu contraseña ha sido actualizada</h2>
            <p>Hola <strong>{$username}</strong>,</p>
            <p>Un administrador ha actualizado tu contraseña de acceso.</p>
            <div style='background-color: #f8f9fa; padding: 15px; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Usuario:</strong> {$username}</p>
                <p style='margin: 5px 0;'><strong>Nueva Contraseña:</strong> {$new_password}</p>
            </div>
            <p>Accede en: <a href='{$login_url}'>{$login_url}</a></p>
            <p style='color: #666; font-size: 13px; margin-top: 30px;'>
                Por seguridad, te recomendamos cambiar esta contraseña desde tu perfil una vez inicies sesión.
            </p>
            <p style='margin-top: 30px;'>Saludos,<br><strong>BeBuilt</strong></p>
        </div>
    </body>
    </html>
    ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);
    }

    public function handle_change_password()
    {
        if (!$this->is_logged_in()) {
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }

        if (
            !isset($_POST['timeline_change_password_nonce']) ||
            !wp_verify_nonce($_POST['timeline_change_password_nonce'], 'timeline_change_password')
        ) {
            wp_die('Error de seguridad');
        }

        global $wpdb;
        $current_user = $this->get_current_user();

        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $current_user->password)) {
            wp_redirect(home_url('/timeline-perfil?error=current_password'));
            exit;
        }

        if ($new_password !== $confirm_password) {
            wp_redirect(home_url('/timeline-perfil?error=match'));
            exit;
        }

        if (strlen($new_password) < 8) {
            wp_redirect(home_url('/timeline-perfil?error=length'));
            exit;
        }

        $result = $wpdb->update(
            $this->table_users,
            array('password' => password_hash($new_password, PASSWORD_DEFAULT)),
            array('id' => $current_user->id)
        );

        if ($result !== false) {
            // REGISTRAR CAMBIO DE CONTRASEÑA
            if ($this->audit_log) {
                $this->audit_log->log(
                    $current_user->id,
                    'update',
                    'user',
                    $current_user->id,
                    'Contraseña actualizada'
                );
            }

            wp_redirect(home_url('/timeline-perfil?success=password_changed'));
        } else {
            wp_redirect(home_url('/timeline-perfil?error=failed'));
        }
        exit;
    }


/**
 * Eliminar proyecto (Super Admin y Administradores)
 */
public function handle_delete_project()
{
    if (!$this->is_logged_in()) {
        wp_redirect(home_url('/login-proyectos'));
        exit;
    }

    $current_user = $this->get_current_user();

    // Solo super_admin y administrador pueden eliminar proyectos
    if (!$this->can_manage_projects($current_user)) {
        wp_redirect(home_url('/timeline-proyectos?error=permission'));
        exit;
    }

    // Verificar nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'timeline_delete_project')) {
        wp_die('Error de seguridad');
    }

    $project_id = intval($_GET['project_id']);

    if (!$project_id || !$this->projects) {
        wp_redirect(home_url('/timeline-proyectos?error=invalid_project'));
        exit;
    }

    // Obtener información del proyecto antes de eliminar
    $project = $this->projects->get_project($project_id);
    
    if (!$project) {
        wp_redirect(home_url('/timeline-proyectos?error=project_not_found'));
        exit;
    }

    global $wpdb;
    
    // ELIMINAR TODOS LOS DATOS RELACIONADOS
    
    // 1. Eliminar imágenes de hitos
    $milestone_images_table = $this->db->get_table_name('milestone_images');
    $milestones_table = $this->db->get_table_name('milestones');
    
    $wpdb->query($wpdb->prepare(
        "DELETE mi FROM {$milestone_images_table} mi
        INNER JOIN {$milestones_table} m ON mi.milestone_id = m.id
        WHERE m.project_id = %d",
        $project_id
    ));
    
    // 2. Eliminar hitos
    $wpdb->delete($milestones_table, array('project_id' => $project_id));
    
    // 3. Eliminar documentos
    $documents_table = $this->db->get_table_name('documents');
    
    // Obtener los documentos para eliminar archivos físicos
    $documents = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$documents_table} WHERE project_id = %d",
        $project_id
    ));
    
    // Eliminar archivos físicos de documentos
    foreach ($documents as $doc) {
        if (strpos($doc->file_url, wp_upload_dir()['baseurl']) !== false) {
            $file_path = str_replace(
                wp_upload_dir()['baseurl'], 
                wp_upload_dir()['basedir'], 
                $doc->file_url
            );
            
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
    }
    
    // Eliminar registros de documentos
    $wpdb->delete($documents_table, array('project_id' => $project_id));
    
    // 4. Eliminar asignaciones de clientes
    $project_clients_table = $this->db->get_table_name('project_clients');
    $wpdb->delete($project_clients_table, array('project_id' => $project_id));
    
    // 5. Eliminar la imagen destacada del proyecto si existe
    if (!empty($project->featured_image)) {
        if (strpos($project->featured_image, wp_upload_dir()['baseurl']) !== false) {
            $image_path = str_replace(
                wp_upload_dir()['baseurl'], 
                wp_upload_dir()['basedir'], 
                $project->featured_image
            );
            
            if (file_exists($image_path)) {
                @unlink($image_path);
            }
        }
    }
    
    // 6. Finalmente, eliminar el proyecto
    $projects_table = $this->db->get_table_name('projects');
    $result = $wpdb->delete($projects_table, array('id' => $project_id));
    
    if ($result) {
        // REGISTRAR EN AUDIT LOG
        if ($this->audit_log) {
            $this->audit_log->log(
                $current_user->id,
                'delete',
                'project',
                $project_id,
                "Proyecto eliminado: {$project->name} (incluye hitos, documentos y relaciones)"
            );
        }
        
        wp_redirect(home_url('/timeline-proyectos?success=deleted'));
    } else {
        wp_redirect(home_url('/timeline-proyectos?error=delete_failed'));
    }
    exit;
}


    public function handle_create_project()
    {
        if (!$this->is_logged_in() || !$this->projects) {
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }

        $current_user = $this->get_current_user();

        if (!$this->can_manage_projects($current_user)) {
            wp_die('No tienes permisos.');
        }

        if (
            !isset($_POST['timeline_project_nonce']) ||
            !wp_verify_nonce($_POST['timeline_project_nonce'], 'timeline_project_form')
        ) {
            wp_die('Error de seguridad');
        }

        $featured_image = '';
        if (!empty($_POST['featured_image'])) {
            $image_data = $_POST['featured_image'];
            if (strpos($image_data, 'data:image') === 0) {
                $featured_image = $this->save_base64_image($image_data, 'project');
            } else {
                $featured_image = $image_data;
            }
        }

        $project_data = array(
            'name' => $_POST['name'],
            'address' => $_POST['address'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'description' => $_POST['description'],
            'featured_image' => $featured_image,
            'project_status' => isset($_POST['project_status']) ? $_POST['project_status'] : 'en_proceso'
        );

        $project_id = $this->projects->create_project($project_data, $current_user->id);

        if ($project_id) {
            // Asignar clientes
            if (isset($_POST['clients']) && is_array($_POST['clients'])) {
                foreach ($_POST['clients'] as $client_id) {
                    $this->projects->assign_client_to_project($project_id, $client_id, $current_user->id);
                }
            }

            // NUEVO: Crear hitos semanales automáticamente
            $this->create_weekly_milestones($project_id, $_POST['start_date'], $_POST['end_date'], $current_user->id);

            wp_redirect(home_url('/timeline-proyectos?success=created'));
        } else {
            wp_redirect(home_url('/timeline-proyecto-nuevo?error=failed'));
        }
        exit;
    }


    /**
     * Crear hitos semanales automáticamente (cada viernes)
     */
    private function create_weekly_milestones($project_id, $start_date, $end_date, $user_id)
    {
        if (!$this->milestones) {
            return;
        }

        // Convertir fechas a objetos DateTime
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);

        // Ajustar la fecha de inicio al viernes más cercano
        $current = clone $start;

        // Si la fecha de inicio no es viernes, mover al siguiente viernes
        $day_of_week = (int)$current->format('N'); // 1=lunes, 5=viernes, 7=domingo
        if ($day_of_week != 5) {
            // Días hasta el próximo viernes
            $days_until_friday = (5 - $day_of_week + 7) % 7;
            if ($days_until_friday == 0) {
                $days_until_friday = 7; // Si es sábado o domingo, ir al siguiente viernes
            }
            $current->modify("+{$days_until_friday} days");
        }

        $counter = 0;

        // Generar hitos cada viernes hasta la fecha de fin
        while ($current <= $end) {
            $counter++;

            // Crear el hito
            $milestone_data = array(
                'project_id' => $project_id,
                'title' => "Revisión Semanal #{$counter}",
                'date' => $current->format('Y-m-d'),
                'description' => '',
                'status' => 'pendiente'
            );

            $milestone_id = $this->milestones->create_milestone($milestone_data, $user_id);

            // Añadir imagen por defecto al hito
            if ($milestone_id && $this->milestones) {
                $default_image = 'https://www.bebuilt.es/wp-content/uploads/2023/08/cropped-favicon.png';
                $this->milestones->add_milestone_image($milestone_id, $default_image, 0);
            }

            // Avanzar al siguiente viernes (7 días)
            $current->modify('+7 days');
        }

        // Log de actividad
        if ($this->audit_log && $counter > 0) {
            $this->audit_log->log(
                $user_id,
                'create',
                'project',
                $project_id,
                "Se crearon automáticamente {$counter} hitos semanales para el proyecto"
            );
        }
    }

    public function handle_update_project()
    {
        if (!$this->is_logged_in() || !$this->projects) {
            wp_redirect(home_url('/login-proyectos'));
            exit;
        }

        $current_user = $this->get_current_user();

        if (!$this->can_manage_projects($current_user)) {
            wp_die('No tienes permisos.');
        }

        if (
            !isset($_POST['timeline_project_nonce']) ||
            !wp_verify_nonce($_POST['timeline_project_nonce'], 'timeline_project_form')
        ) {
            wp_die('Error de seguridad');
        }

        $project_id = intval($_POST['project_id']);

        $featured_image = '';
        if (!empty($_POST['featured_image'])) {
            $image_data = $_POST['featured_image'];
            if (strpos($image_data, 'data:image') === 0) {
                $featured_image = $this->save_base64_image($image_data, 'project');
            } else {
                $featured_image = $image_data;
            }
        }

        $project_data = array(
            'name' => $_POST['name'],
            'address' => $_POST['address'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'description' => $_POST['description'],
            'featured_image' => $featured_image,
            'project_status' => isset($_POST['project_status']) ? $_POST['project_status'] : 'en_proceso'
        );

        $result = $this->projects->update_project($project_id, $project_data, $current_user->id);

        if ($result) {
            global $wpdb;
            $table = $this->db->get_table_name('project_clients');
            $wpdb->delete($table, array('project_id' => $project_id));

            if (isset($_POST['clients']) && is_array($_POST['clients'])) {
                foreach ($_POST['clients'] as $client_id) {
                    $this->projects->assign_client_to_project($project_id, $client_id, $current_user->id);
                }
            }

            wp_redirect(home_url('/timeline-proyectos?success=updated'));
        } else {
            wp_redirect(home_url('/timeline-proyecto-editar/' . $project_id . '?error=failed'));
        }
        exit;
    }

    private function save_base64_image($base64_string, $prefix = 'image')
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                return '';
            }

            $base64_string = base64_decode($base64_string);
            if ($base64_string === false) {
                return '';
            }

            $upload_dir = wp_upload_dir();
            $timeline_dir = $upload_dir['basedir'] . '/timeline-projects';

            if (!file_exists($timeline_dir)) {
                wp_mkdir_p($timeline_dir);
            }

            $filename = $prefix . '_' . uniqid() . '.' . $type;
            $filepath = $timeline_dir . '/' . $filename;

            if (file_put_contents($filepath, $base64_string)) {
                return $upload_dir['baseurl'] . '/timeline-projects/' . $filename;
            }
        }

        return '';
    }

    private function send_welcome_email($username, $email, $password)
    {
        $login_url = home_url('/login-proyectos');
        $subject = 'Bienvenido a tu área de proyectos - BeBuilt';

        $message = "
        <!DOCTYPE html>
        <html>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Bienvenido a tu área de proyectos</h2>
                <p>Hola <strong>{$username}</strong>,</p>
                <p>Se ha creado tu cuenta para acceder al seguimiento de tus proyectos.</p>
                <div style='background-color: #f8f9fa; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>Usuario:</strong> {$username}</p>
                    <p style='margin: 5px 0;'><strong>Contraseña:</strong> {$password}</p>
                </div>
                <p>Accede en: <a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px;'>Saludos,<br><strong>BeBuilt</strong></p>
            </div>
        </body>
        </html>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);
    }
}

// Inicializar plugin
$GLOBALS['timeline_plugin'] = new Timeline_Plugin();

// Hook de activación
register_activation_hook(__FILE__, function () {
    $plugin = new Timeline_Plugin();
    $plugin->activate();
    flush_rewrite_rules();
});
