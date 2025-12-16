<?php
/**
 * Sistema de Nonces Personalizado MEJORADO para Sesiones PHP
 * Archivo: includes/class-nonce.php
 * ARREGLADO: Ya no depende de token de sesión volátil
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
        
        // Obtener timestamp (válido por 24 horas - ventana más amplia)
        $tick = ceil(time() / (24 * HOUR_IN_SECONDS));
        
        // ✅ CAMBIO CLAVE: Ya no usar token de sesión volátil
        // En su lugar, usar una combinación de user_id + action + tick + salt
        $unique_string = $user_id . '|' . $action . '|' . $tick;
        
        // Crear hash
        $hash = hash_hmac('sha256', $unique_string, $this->get_salt());
        
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
            error_log('Timeline Nonce: Nonce vacío');
            return false;
        }
        
        // Asegurar que la sesión está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Obtener ID de usuario de la sesión
        $user_id = isset($_SESSION['timeline_user_id']) ? $_SESSION['timeline_user_id'] : 0;
        
        // DEBUG: Log para ver qué está pasando
        error_log("Timeline Nonce Verify - User ID: {$user_id}, Action: {$action}, Nonce: {$nonce}");
        
        // Verificar con timestamp actual y los 2 anteriores (ventana de 72 horas)
        $tick = ceil(time() / (24 * HOUR_IN_SECONDS));
        
        for ($i = 0; $i <= 2; $i++) {
            $unique_string = $user_id . '|' . $action . '|' . ($tick - $i);
            $expected = substr(
                hash_hmac('sha256', $unique_string, $this->get_salt()),
                0,
                12
            );
            
            // DEBUG
            error_log("Timeline Nonce: Comparando - Esperado: {$expected}, Recibido: {$nonce}, Tick: " . ($tick - $i));
            
            if (hash_equals($expected, $nonce)) {
                error_log('Timeline Nonce: ✓ VERIFICACIÓN EXITOSA');
                return true;
            }
        }
        
        error_log('Timeline Nonce: ✗ VERIFICACIÓN FALLIDA');
        return false;
    }
    
    /**
     * Obtener salt único del sitio
     * 
     * @return string Salt
     */
    private function get_salt() {
        // Usar múltiples fuentes para crear un salt único y estable
        $sources = array();
        
        // 1. Salt de WordPress si existe
        if (defined('NONCE_SALT')) {
            $sources[] = NONCE_SALT;
        }
        
        // 2. Auth Key de WordPress
        if (defined('AUTH_KEY')) {
            $sources[] = AUTH_KEY;
        }
        
        // 3. Salt almacenado en opciones (crear si no existe)
        $option_name = 'timeline_nonce_salt';
        $stored_salt = get_option($option_name);
        
        if (!$stored_salt) {
            $stored_salt = bin2hex(random_bytes(32));
            update_option($option_name, $stored_salt, false);
        }
        $sources[] = $stored_salt;
        
        // 4. URL del sitio (para hacer el salt único por instalación)
        $sources[] = home_url();
        
        // Combinar todas las fuentes
        return hash('sha256', implode('|', $sources));
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
        
        // DEBUG: Log del nonce generado
        error_log("Timeline Nonce Field - Action: {$action}, Nonce generado: {$nonce}");
        
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