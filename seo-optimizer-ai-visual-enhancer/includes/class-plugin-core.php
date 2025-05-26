<?php

/**
 * Classe principale du plugin pour la gestion des fonctionnalités de base
 *
 * @since 1.0.0
 */
class SEOAI_Plugin_Core {
    
    public static function activate() {
        // Vérifier la version de WordPress
        if (version_compare(get_bloginfo('version'), '5.6', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Ce plugin nécessite WordPress 5.6 ou supérieur. Veuillez mettre à jour WordPress avant de réactiver ce plugin.');
        }
        
        // Vérifier la version de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('Ce plugin nécessite PHP 7.4 ou supérieur. Veuillez mettre à jour PHP avant de réactiver ce plugin.');
        }
        
        try {
            // Créer les tables si nécessaire
            self::create_tables();
            
            // Options par défaut
            $default_settings = array(
                'replicate_api_key' => '',
                'sambanova_api_key' => '',
                'auto_process' => false,
                'image_positions' => array('after_first_paragraph', 'middle', 'conclusion')
            );
            
            // Ne pas écraser les paramètres existants
            if (!get_option('seoai_settings')) {
                add_option('seoai_settings', $default_settings);
            }
            
            // Créer le dossier de backup
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/seoai-backups';
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }
            
            // Flush les règles de réécriture
            flush_rewrite_rules();
        } catch (Exception $e) {
            // En cas d'erreur lors de l'activation
            error_log('Erreur lors de l\'activation du plugin SEO Optimizer & AI Visual Enhancer: ' . $e->getMessage());
            wp_die('Une erreur est survenue lors de l\'activation du plugin: ' . $e->getMessage());
        }
    }
    
    public static function deactivate() {
        // Nettoyage si nécessaire
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
