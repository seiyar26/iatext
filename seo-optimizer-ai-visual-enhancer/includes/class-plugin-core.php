<?php

/**
 * Classe principale du plugin pour la gestion des fonctionnalités de base
 *
 * @since 1.0.0
 */
class SEOAI_Plugin_Core {
    
    public static function activate() {
        // Vérifier la version de WordPress
        if (version_compare(get_bloginfo('version'), SEOAI_MIN_WP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(sprintf(
                'Ce plugin nécessite WordPress %s ou supérieur. Veuillez mettre à jour WordPress avant de réactiver ce plugin.',
                SEOAI_MIN_WP_VERSION
            ));
        }
        
        // Vérifier la version de PHP
        if (version_compare(PHP_VERSION, SEOAI_MIN_PHP_VERSION, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(sprintf(
                'Ce plugin nécessite PHP %s ou supérieur. Veuillez mettre à jour PHP avant de réactiver ce plugin.',
                SEOAI_MIN_PHP_VERSION
            ));
        }
        
        try {
            // Créer les tables si nécessaire
            self::create_tables();
            
            // Options par défaut
            $default_settings = array(
                'plugin_name' => 'SEO Optimizer & AI Visual Enhancer',
                'gemini_api_key' => '',
                'gemini_model' => 'gemini-2.0-flash',
                'gemini_temperature' => 0.7,
                'replicate_api_key' => '',
                'image_model' => 'stability-ai/stable-diffusion-xl-base-1.0',
                'image_dimensions' => '1024x768',
                'image_count' => 3,
                'auto_process' => false,
                'enable_backups' => true,
                'enable_caching' => true,
                'log_level' => 'INFO',
                'api_timeout' => 60,
                'log_retention' => 30,
                'image_positions' => array('after_first_paragraph', 'middle', 'conclusion'),
                'custom_prompt_template' => "Optimise cet article WordPress pour le SEO. Titre: {{title}}\n\nContenu: {{content}}\n\nGénère également 3 prompts pour créer des images pertinentes pour cet article."
            );
            
            // Ne pas écraser les paramètres existants
            if (!get_option('seoai_settings')) {
                add_option('seoai_settings', $default_settings);
            } else {
                // Mettre à jour les paramètres existants avec les nouvelles options par défaut
                $existing_settings = get_option('seoai_settings');
                $updated_settings = array_merge($default_settings, $existing_settings);
                update_option('seoai_settings', $updated_settings);
            }
            
            // Créer le dossier de backup
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/seoai-backups';
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
                
                // Créer un fichier index.php vide pour empêcher le listage des répertoires
                file_put_contents($backup_dir . '/index.php', '<?php // Silence is golden');
            }
            
            // Créer le dossier de cache
            if (!file_exists(SEOAI_CACHE_DIR)) {
                wp_mkdir_p(SEOAI_CACHE_DIR);
                
                // Créer un fichier index.php vide pour empêcher le listage des répertoires
                file_put_contents(SEOAI_CACHE_DIR . '/index.php', '<?php // Silence is golden');
                
                // Créer un fichier .htaccess pour protéger le répertoire
                file_put_contents(SEOAI_CACHE_DIR . '/.htaccess', 'Deny from all');
            }
            
            // Définir un transient pour afficher une notification après l'activation
            set_transient('seoai_activation_notice', true, 5);
            
            // Enregistrer la version de la base de données
            update_option('seoai_db_version', SEOAI_DB_VERSION);
            
            // Flush les règles de réécriture
            flush_rewrite_rules();
        } catch (Exception $e) {
            // En cas d'erreur lors de l'activation
            error_log('Erreur lors de l\'activation du plugin SEO Optimizer & AI Visual Enhancer: ' . $e->getMessage());
            wp_die('Une erreur est survenue lors de l\'activation du plugin: ' . $e->getMessage());
        }
    }
    
    public static function deactivate() {
        // Récupérer les paramètres
        $settings = get_option('seoai_settings', array());
        
        // Vérifier si on doit nettoyer les données temporaires
        $clean_temp = isset($settings['clean_temp_on_deactivate']) ? $settings['clean_temp_on_deactivate'] : true;
        
        if ($clean_temp) {
            // Nettoyer le cache
            if (file_exists(SEOAI_CACHE_DIR)) {
                $files = glob(SEOAI_CACHE_DIR . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== 'index.php' && basename($file) !== '.htaccess') {
                        @unlink($file);
                    }
                }
            }
        }
        
        // Supprimer les tâches planifiées
        wp_clear_scheduled_hook('seoai_process_post');
        wp_clear_scheduled_hook('seoai_cleanup_logs');
        
        // Journaliser la désactivation
        if (class_exists('SEOAI_Logger')) {
            $logger = SEOAI_Logger::get_instance();
            $logger->write_log('Plugin désactivé', 'SYSTEM');
        }
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        // Vérifier que $wpdb est disponible
        if (!isset($wpdb) || empty($wpdb)) {
            error_log('Erreur lors de la création des tables: $wpdb non disponible');
            return false;
        }
        
        try {
            $table_name = $wpdb->prefix . 'seoai_logs';
            
            // Vérifier si la table existe déjà
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                // La table existe déjà, pas besoin de la recréer
                return true;
            }
            
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id bigint(20) NOT NULL,
                action varchar(50) NOT NULL,
                status varchar(20) NOT NULL,
                message text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            // Vérifier que le fichier upgrade.php existe
            if (!file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
                error_log('Erreur lors de la création des tables: fichier upgrade.php non trouvé');
                return false;
            }
            
            // Inclure le fichier pour la fonction dbDelta
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Exécuter dbDelta
            $result = dbDelta($sql);
            
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de la création des tables: ' . $e->getMessage());
            return false;
        }
    }
}
