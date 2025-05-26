<?php
/**
 * Classe de gestion des logs pour le plugin SEO Optimizer & AI Visual Enhancer
 */
class SEOAI_Logger {
    private static $instance = null;
    private $log_enabled = true;
    private $log_file;
    private $debug_mode = true;

    /**
     * Constructeur privé pour le singleton
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/seoai-logs.log';
        
        // Vérifier si le fichier existe et est accessible en écriture
        if (!file_exists($this->log_file)) {
            $this->write_log('Initialisation du fichier de logs SEO Optimizer & AI Visual Enhancer', 'INIT');
        }
    }

    /**
     * Obtenir l'instance unique de la classe
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Activer ou désactiver les logs
     */
    public function set_logging($enabled = true) {
        $this->log_enabled = $enabled;
        $this->write_log('Logging ' . ($enabled ? 'activé' : 'désactivé'), 'CONFIG');
    }

    /**
     * Activer ou désactiver le mode debug
     */
    public function set_debug_mode($enabled = true) {
        $this->debug_mode = $enabled;
        $this->write_log('Mode debug ' . ($enabled ? 'activé' : 'désactivé'), 'CONFIG');
    }

    /**
     * Écrire un message dans le fichier de log
     */
    public function write_log($message, $type = 'INFO') {
        if (!$this->log_enabled) {
            return;
        }

        // Ne pas logger les messages de debug si le mode debug est désactivé
        if ($type === 'DEBUG' && !$this->debug_mode) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        
        // Écrire dans le fichier de log
        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
        
        // Également écrire dans error_log pour les erreurs
        if ($type === 'ERROR') {
            error_log("SEOAI: {$message}");
        }
    }

    /**
     * Loguer une requête API
     */
    public function log_api_request($url, $data, $headers = []) {
        // Masquer les informations sensibles comme les clés API
        if (isset($headers['Authorization'])) {
            $headers['Authorization'] = 'Bearer ***API_KEY_MASKED***';
        }
        
        $message = "Requête API vers {$url}" . PHP_EOL;
        $message .= "Headers: " . json_encode($headers) . PHP_EOL;
        $message .= "Données: " . json_encode($data);
        
        $this->write_log($message, 'API_REQUEST');
    }

    /**
     * Loguer une réponse API
     */
    public function log_api_response($url, $response, $status_code = null) {
        if (is_wp_error($response)) {
            $message = "Erreur API pour {$url}: " . $response->get_error_message();
            $this->write_log($message, 'ERROR');
            return;
        }
        
        $message = "Réponse API de {$url}" . PHP_EOL;
        if ($status_code) {
            $message .= "Code HTTP: {$status_code}" . PHP_EOL;
        }
        
        // Tronquer la réponse si elle est trop longue
        $response_str = is_string($response) ? $response : json_encode($response);
        if (strlen($response_str) > 1000) {
            $response_str = substr($response_str, 0, 1000) . '... [tronqué]';
        }
        
        $message .= "Contenu: {$response_str}";
        $this->write_log($message, 'API_RESPONSE');
    }

    /**
     * Loguer un événement utilisateur
     */
    public function log_user_action($user_id, $action, $details = '') {
        $user = get_userdata($user_id);
        $username = $user ? $user->user_login : 'Utilisateur inconnu';
        
        $message = "Action utilisateur par {$username} (ID: {$user_id}): {$action}";
        if (!empty($details)) {
            $message .= " - Détails: {$details}";
        }
        
        $this->write_log($message, 'USER_ACTION');
    }

    /**
     * Loguer un événement de traitement de contenu
     */
    public function log_content_processing($post_id, $status, $details = '') {
        $post_title = get_the_title($post_id);
        
        $message = "Traitement de contenu pour l'article \"{$post_title}\" (ID: {$post_id}): {$status}";
        if (!empty($details)) {
            $message .= " - Détails: {$details}";
        }
        
        $this->write_log($message, 'CONTENT');
    }

    /**
     * Loguer une erreur
     */
    public function log_error($message, $context = '', $exception = null) {
        $error_message = "ERREUR";
        
        if (!empty($context)) {
            $error_message .= " dans {$context}";
        }
        
        $error_message .= ": {$message}";
        
        if ($exception instanceof Exception) {
            $error_message .= PHP_EOL . "Exception: " . $exception->getMessage();
            $error_message .= PHP_EOL . "Trace: " . $exception->getTraceAsString();
        }
        
        $this->write_log($error_message, 'ERROR');
    }
}
