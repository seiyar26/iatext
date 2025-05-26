<?php
/**
 * Plugin Name: SEO Optimizer & AI Visual Enhancer
 * Plugin URI: https://example.com
 * Description: Plugin WordPress pour optimiser le SEO et générer des images avec IA
 * Version: 1.0.0
 * Author: Votre Nom
 * License: GPL v2 or later
 * Text Domain: seo-optimizer-ai-visual-enhancer
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes
define('SEOAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SEOAI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SEOAI_VERSION', '1.0.0');

// Chargement des classes principales
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-plugin-core.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-content-processor.php';
require_once SEOAI_PLUGIN_PATH . 'includes/class-admin-interface.php';

// Fonction d'activation du plugin
function seoai_activate_plugin() {
    SEOAI_Plugin_Core::activate();
}

// Fonction de désactivation du plugin
function seoai_deactivate_plugin() {
    SEOAI_Plugin_Core::deactivate();
}

// Enregistrement des hooks d'activation/désactivation
register_activation_hook(__FILE__, 'seoai_activate_plugin');
register_deactivation_hook(__FILE__, 'seoai_deactivate_plugin');

// Classe principale du plugin
class SEOAIVisualEnhancer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once SEOAI_PLUGIN_PATH . 'includes/class-plugin-core.php';
        require_once SEOAI_PLUGIN_PATH . 'includes/class-api-handler.php';
        require_once SEOAI_PLUGIN_PATH . 'includes/class-content-processor.php';
        require_once SEOAI_PLUGIN_PATH . 'includes/class-admin-interface.php';
    }
    
    private function define_admin_hooks() {
        $admin = new SEOAI_Admin_Interface();
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        add_action('admin_menu', array($admin, 'add_plugin_admin_menu'));

        // Enregistrer les gestionnaires AJAX
        $admin->register_ajax_handlers();

        // Actions AJAX
        add_action('wp_ajax_seoai_process_content', array($this, 'ajax_process_content'));
        add_action('wp_ajax_seoai_get_posts', array($this, 'ajax_get_posts'));
        add_action('wp_ajax_seoai_save_settings', array($this, 'ajax_save_settings'));
    }
    
    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_styles'));
    }
    
    public function enqueue_public_styles() {
        wp_enqueue_style('seoai-public', SEOAI_PLUGIN_URL . 'public/css/public.css', array(), SEOAI_VERSION);
    }
    
    // Actions AJAX
    public function ajax_process_content() {
        try {
            // Vérifier le nonce
            check_ajax_referer('seoai_nonce', 'nonce');
            
            // Vérifier les autorisations
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => 'Accès refusé : permissions insuffisantes'));
                return;
            }
            
            // Log pour le débogage
            error_log('SEOAI: Début du traitement des posts');
            
            // Récupérer les IDs des posts
            if (!isset($_POST['post_ids']) || empty($_POST['post_ids'])) {
                wp_send_json_error(array('message' => 'Aucun ID de post fourni'));
                return;
            }
            
            $post_ids = sanitize_text_field($_POST['post_ids']);
            $processor = new SEOAI_Content_Processor();
            
            // Traiter les posts
            $result = $processor->process_posts($post_ids);
            
            // Envoyer la réponse
            wp_send_json_success($result);
        } catch (Exception $e) {
            error_log('SEOAI Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Erreur lors du traitement : ' . $e->getMessage()));
        }
    }
    
    public function ajax_get_posts() {
        check_ajax_referer('seoai_nonce', 'nonce');
        
        $post_type = sanitize_text_field($_POST['post_type']);
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        wp_send_json_success($posts);
    }
    
    public function ajax_save_settings() {
        // Vérifier le nonce
        if (!isset($_POST['seoai_settings_nonce']) || !wp_verify_nonce($_POST['seoai_settings_nonce'], 'seoai_settings')) {
            wp_send_json_error('Sécurité : nonce invalide');
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Accès refusé : permissions insuffisantes');
            return;
        }
        
        // Récupérer et nettoyer les valeurs
        $replicate_api_key = isset($_POST['replicate_api_key']) ? sanitize_text_field($_POST['replicate_api_key']) : '';
        $openrouter_api_key = isset($_POST['openrouter_api_key']) ? sanitize_text_field($_POST['openrouter_api_key']) : '';
        $openrouter_model = isset($_POST['openrouter_model']) ? sanitize_text_field($_POST['openrouter_model']) : 'openai/gpt-4o';
        $auto_process = isset($_POST['auto_process']) ? (bool) $_POST['auto_process'] : false;
        
        // Préparer les paramètres
        $settings = array(
            'replicate_api_key' => $replicate_api_key,
            'openrouter_api_key' => $openrouter_api_key,
            'openrouter_model' => $openrouter_model,
            'auto_process' => $auto_process
        );
        
        // Journaliser pour le débogage (optionnel)
        error_log('SEOAI: Sauvegarde des paramètres');
        
        // Sauvegarder les paramètres
        $update_result = update_option('seoai_settings', $settings);
        
        if ($update_result) {
            wp_send_json_success(array('message' => 'Paramètres sauvegardés avec succès'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la sauvegarde des paramètres ou aucun changement détecté'));
        }
    }
}

// Initialiser le plugin
SEOAIVisualEnhancer::get_instance();
