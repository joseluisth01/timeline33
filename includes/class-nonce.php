<?php
/**
 * Sistema de Nonces Personalizado para Sesiones PHP
 * Archivo: includes/class-nonce.php
 */

if (!defined('ABSPATH')) exit;

class Timeline_Nonce {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Crear un nonce personalizado
     * 
     * @param string $action Nombre de la acción
     * @return string El nonce generado
     */
    public function create($action = '') {
        // Asegurar que la sesión está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Obtener ID de usuario de la sesión
        $user_id = isset($_SESSION['timeline_user_id']) ? $_SESSION['timeline_user_id'] : 0;
        
        // Obtener timestamp (válido por 12 horas)
        $tick = ceil(time() / (12 * HOUR_IN_SECONDS));
        
        // Generar token único
        $token = $this->get_session_token();
        
        // Crear hash
        $hash = hash_hmac('sha256', "{$action}|{$user_id}|{$tick}|{$token}", $this->get_salt());
        
        return substr($hash, 0, 12);
    }
    
    /**
     * Verificar un nonce personalizado
     * 
     * @param string $nonce El nonce a verificar
     * @param string $action Nombre de la acción
     * @return bool True si es válido, false si no
     */
    public function verify($nonce, $action = '') {
        if (empty($nonce)) {
            return false;
        }
        
        // Asegurar que la sesión está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Obtener ID de usuario de la sesión
        $user_id = isset($_SESSION['timeline_user_id']) ? $_SESSION['timeline_user_id'] : 0;
        
        // Obtener token
        $token = $this->get_session_token();
        
        // Verificar con timestamp actual y anterior (ventana de 24 horas)
        $tick = ceil(time() / (12 * HOUR_IN_SECONDS));
        
        for ($i = 0; $i <= 1; $i++) {
            $expected = substr(
                hash_hmac('sha256', "{$action}|{$user_id}|" . ($tick - $i) . "|{$token}", $this->get_salt()),
                0,
                12
            );
            
            if (hash_equals($expected, $nonce)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtener o crear token de sesión
     * 
     * @return string Token de sesión
     */
    private function get_session_token() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['timeline_nonce_token'])) {
            $_SESSION['timeline_nonce_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['timeline_nonce_token'];
    }
    
    /**
     * Obtener salt único del sitio
     * 
     * @return string Salt
     */
    private function get_salt() {
        // Usar salt de WordPress si existe, si no crear uno
        if (defined('NONCE_SALT')) {
            return NONCE_SALT;
        }
        
        $option_name = 'timeline_nonce_salt';
        $salt = get_option($option_name);
        
        if (!$salt) {
            $salt = bin2hex(random_bytes(32));
            update_option($option_name, $salt, false);
        }
        
        return $salt;
    }
    
    /**
     * Campo oculto con nonce (similar a wp_nonce_field)
     * 
     * @param string $action Nombre de la acción
     * @param string $name Nombre del campo
     * @return void
     */
    public function field($action = '', $name = '_timeline_nonce') {
        $nonce = $this->create($action);
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($nonce) . '">';
    }
    
    /**
     * Obtener nonce para URL (similar a wp_create_nonce)
     * 
     * @param string $action Nombre de la acción
     * @return string El nonce generado
     */
    public function get($action = '') {
        return $this->create($action);
    }
}