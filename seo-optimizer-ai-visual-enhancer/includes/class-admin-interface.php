<?php

class SEOAI_Admin_Interface {
    
    public function enqueue_styles() {
        wp_enqueue_style('seoai-admin-style', SEOAI_PLUGIN_URL . 'admin/css/admin.css', array(), SEOAI_VERSION);
        
        // Charger le CSS des logs en direct uniquement sur la page concernée
        $screen = get_current_screen();
        if (isset($screen->id) && $screen->id === 'seo-ai-optimizer_page_seoai-live-logs') {
            wp_enqueue_style('seoai-live-logs-style', SEOAI_PLUGIN_URL . 'admin/css/live-logs.css', array(), SEOAI_VERSION);
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('seoai-admin-script', SEOAI_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), SEOAI_VERSION, true);
        
        wp_localize_script('seoai-admin-script', 'seoai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seoai_nonce')
        ));
        
        // Charger le JS des logs en direct uniquement sur la page concernée
        $screen = get_current_screen();
        if (isset($screen->id) && $screen->id === 'seo-ai-optimizer_page_seoai-live-logs') {
            wp_enqueue_script('seoai-live-logs-script', SEOAI_PLUGIN_URL . 'admin/js/live-logs.js', array('jquery'), SEOAI_VERSION, true);
            
            wp_localize_script('seoai-live-logs-script', 'seoai_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('seoai_live_logs_nonce')
            ));
        }
    }
    
    public function add_plugin_admin_menu() {
        add_menu_page(
            'SEO AI Optimizer',
            'SEO AI Optimizer',
            'manage_options',
            'seoai-optimizer',
            array($this, 'display_plugin_admin_page'),
            'dashicons-superhero-alt',
            30
        );
        
        add_submenu_page(
            'seoai-optimizer',
            'Paramètres',
            'Paramètres',
            'manage_options',
            'seoai-settings',
            array($this, 'display_settings_page')
        );
        
        add_submenu_page(
            'seoai-optimizer',
            'Logs',
            'Logs',
            'manage_options',
            'seoai-logs',
            array($this, 'display_logs_page')
        );
        
        add_submenu_page(
            'seoai-optimizer',
            'Logs en direct',
            'Logs en direct',
            'manage_options',
            'seoai-live-logs',
            array($this, 'display_live_logs_page')
        );
    }
    
    public function display_plugin_admin_page() {
        include_once SEOAI_PLUGIN_PATH . 'admin/partials/admin-display.php';
    }
    
    public function display_settings_page() {
        include_once SEOAI_PLUGIN_PATH . 'admin/partials/settings-display.php';
    }
    
    public function display_logs_page() {
        include_once SEOAI_PLUGIN_PATH . 'admin/partials/logs-display.php';
    }
    
    public function display_live_logs_page() {
        include_once SEOAI_PLUGIN_PATH . 'admin/partials/live-logs-display.php';
    }
    
    /**
     * Initialiser les hooks AJAX
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_seoai_get_live_logs', array($this, 'ajax_get_live_logs'));
    }
    
    /**
     * Récupérer les logs en direct via AJAX
     */
    public function ajax_get_live_logs() {
        // Vérifier le nonce pour la sécurité
        check_ajax_referer('seoai_live_logs_nonce', 'nonce');
        
        // S'assurer que l'utilisateur a les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissions insuffisantes'));
            return;
        }
        
        // Récupérer le timestamp de la dernière mise à jour
        $last_timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : 0;
        
        // Récupérer les logs depuis le fichier
        $log_file = WP_CONTENT_DIR . '/uploads/seoai-logs.log';
        $logs = array();
        
        if (file_exists($log_file)) {
            try {
                // Utiliser SplFileObject pour une lecture efficace des fichiers volumineux
                $file = new SplFileObject($log_file, 'r');
                $file->seek(PHP_INT_MAX); // Aller à la fin du fichier
                $total_lines = $file->key(); // Obtenir le nombre total de lignes
                
                // Déterminer combien de lignes lire (max 100 lignes à la fois)
                $lines_to_read = min(100, $total_lines);
                $start_line = max(0, $total_lines - $lines_to_read);
                
                // Lire les dernières lignes
                $file->seek($start_line);
                $lines = array();
                while (!$file->eof()) {
                    $line = $file->fgets();
                    if (trim($line) !== '') {
                        $lines[] = $line;
                    }
                }
                
                // Parser les lignes en entrées de log
                foreach ($lines as $line) {
                    // Format attendu: [2023-05-26 12:34:56] [LEVEL] Message
                    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[([^\]]+)\] (.+)/', $line, $matches)) {
                        $timestamp = strtotime($matches[1]);
                        
                        // Ne renvoyer que les logs plus récents que le dernier timestamp
                        if ($timestamp > $last_timestamp) {
                            $logs[] = array(
                                'timestamp' => $timestamp,
                                'level' => $matches[2],
                                'message' => $matches[3]
                            );
                        }
                    }
                }
                
                // Trier les logs par timestamp
                usort($logs, function($a, $b) {
                    return $a['timestamp'] <=> $b['timestamp'];
                });
                
                // Envoyer la réponse JSON
                wp_send_json_success(array(
                    'logs' => $logs,
                    'timestamp' => time()
                ));
                
            } catch (Exception $e) {
                wp_send_json_error(array('message' => 'Erreur lors de la lecture des logs: ' . $e->getMessage()));
            }
        } else {
            wp_send_json_error(array('message' => 'Fichier de logs non trouvé'));
        }
        
        wp_die();
    }
}
