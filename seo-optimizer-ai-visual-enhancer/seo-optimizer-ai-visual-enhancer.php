<?php
/**
 * Plugin Name: SEO Optimizer & AI Visual Enhancer
 * Plugin URI: https://example.com/seo-optimizer-ai-visual-enhancer
 * Description: Optimisez automatiquement votre contenu WordPress pour le SEO et générez des images pertinentes avec l'IA
 * Version: 1.1.0
 * Author: SEO AI Team
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seo-optimizer-ai-visual-enhancer
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * 
 * Ce plugin utilise l'IA pour optimiser le contenu WordPress pour le référencement
 * et générer des images pertinentes pour améliorer l'engagement des utilisateurs.
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes
define('SEOAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SEOAI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SEOAI_VERSION', '1.1.0');
define('SEOAI_DB_VERSION', '1.0.1');
define('SEOAI_CACHE_DIR', WP_CONTENT_DIR . '/cache/seoai');
define('SEOAI_MIN_WP_VERSION', '5.6');
define('SEOAI_MIN_PHP_VERSION', '7.4');

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
    private $logger;
    private $settings;
    
    /**
     * Obtenir l'instance unique du plugin (pattern Singleton)
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour le Singleton
     */
    private function __construct() {
        // Initialiser le plugin après le chargement de WordPress
        add_action('plugins_loaded', array($this, 'init'));
        
        // Ajouter un lien vers les paramètres dans la liste des plugins
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        
        // Ajouter des liens supplémentaires dans la rangée des plugins
        add_filter('plugin_row_meta', array($this, 'add_plugin_meta_links'), 10, 2);
    }
    
    /**
     * Initialisation du plugin
     */
    public function init() {
        // Vérifier les exigences minimales
        if (!$this->check_requirements()) {
            return;
        }
        
        // Charger les dépendances
        $this->load_dependencies();
        
        // Initialiser le logger
        $this->logger = SEOAI_Logger::get_instance();
        
        // Charger les paramètres
        $this->settings = get_option('seoai_settings', array());
        
        // Définir les hooks d'administration
        $this->define_admin_hooks();
        
        // Définir les hooks publics
        $this->define_public_hooks();
        
        // Définir les hooks pour le traitement automatique
        $this->define_auto_processing_hooks();
        
        // Charger les traductions
        load_plugin_textdomain('seo-optimizer-ai-visual-enhancer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialiser le répertoire de cache si nécessaire
        $this->initialize_cache_directory();
        
        // Log d'initialisation
        $this->logger->write_log('Plugin initialisé avec succès', 'INIT');
    }
    
    /**
     * Vérifier que les exigences minimales sont satisfaites
     */
    private function check_requirements() {
        $requirements_met = true;
        
        // Vérifier la version de WordPress
        if (version_compare(get_bloginfo('version'), SEOAI_MIN_WP_VERSION, '<')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo sprintf(
                    __('SEO Optimizer & AI Visual Enhancer nécessite WordPress %s ou supérieur. Veuillez mettre à jour WordPress.', 'seo-optimizer-ai-visual-enhancer'),
                    SEOAI_MIN_WP_VERSION
                );
                echo '</p></div>';
            });
            $requirements_met = false;
        }
        
        // Vérifier la version de PHP
        if (version_compare(PHP_VERSION, SEOAI_MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                echo sprintf(
                    __('SEO Optimizer & AI Visual Enhancer nécessite PHP %s ou supérieur. Veuillez contacter votre hébergeur pour mettre à jour PHP.', 'seo-optimizer-ai-visual-enhancer'),
                    SEOAI_MIN_PHP_VERSION
                );
                echo '</p></div>';
            });
            $requirements_met = false;
        }
        
        return $requirements_met;
    }
    
    /**
     * Charger les dépendances du plugin
     */
    private function load_dependencies() {
        require_once SEOAI_PLUGIN_PATH . 'includes/class-plugin-core.php';
        require_once SEOAI_PLUGIN_PATH . 'includes/class-api-handler.php';
        require_once SEOAI_PLUGIN_PATH . 'includes/class-content-processor.php';
        require_once SEOAI_PLUGIN_PATH . 'includes/class-admin-interface.php';
    }
    
    /**
     * Initialiser le répertoire de cache
     */
    private function initialize_cache_directory() {
        if (!file_exists(SEOAI_CACHE_DIR)) {
            wp_mkdir_p(SEOAI_CACHE_DIR);
            
            // Créer un fichier index.php vide pour empêcher le listage des répertoires
            $index_file = SEOAI_CACHE_DIR . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
            }
            
            // Créer un fichier .htaccess pour protéger le répertoire
            $htaccess_file = SEOAI_CACHE_DIR . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                file_put_contents($htaccess_file, 'Deny from all');
            }
        }
    }
    
    /**
     * Définir les hooks d'administration
     */
    private function define_admin_hooks() {
        $admin = new SEOAI_Admin_Interface();
        
        // Enregistrer les styles et scripts
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        
        // Ajouter les menus d'administration
        add_action('admin_menu', array($admin, 'add_plugin_admin_menu'));

        // Enregistrer les gestionnaires AJAX
        $admin->register_ajax_handlers();

        // Actions AJAX
        add_action('wp_ajax_seoai_process_content', array($this, 'ajax_process_content'));
        add_action('wp_ajax_seoai_get_posts', array($this, 'ajax_get_posts'));
        add_action('wp_ajax_seoai_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_seoai_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_seoai_get_logs', array($this, 'ajax_get_logs'));
        
        // Ajouter une notification après l'activation
        if (get_transient('seoai_activation_notice')) {
            add_action('admin_notices', array($this, 'display_activation_notice'));
            delete_transient('seoai_activation_notice');
        }
    }
    
    /**
     * Définir les hooks publics
     */
    private function define_public_hooks() {
        // Enregistrer les styles publics
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_styles'));
        
        // Ajouter des classes CSS aux images générées par l'IA
        add_filter('post_thumbnail_html', array($this, 'add_ai_image_classes'), 10, 5);
        add_filter('the_content', array($this, 'enhance_ai_images_in_content'), 20);
    }
    
    /**
     * Définir les hooks pour le traitement automatique
     */
    private function define_auto_processing_hooks() {
        // Vérifier si le traitement automatique est activé
        if (isset($this->settings['auto_process']) && $this->settings['auto_process']) {
            // Traiter automatiquement les nouveaux articles lors de la publication
            add_action('transition_post_status', array($this, 'auto_process_on_publish'), 10, 3);
        }
    }
    
    /**
     * Ajouter un lien vers les paramètres dans la liste des plugins
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=seoai-settings') . '">' . __('Paramètres', 'seo-optimizer-ai-visual-enhancer') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Ajouter des liens supplémentaires dans la rangée des plugins
     */
    public function add_plugin_meta_links($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="https://example.com/documentation" target="_blank">' . __('Documentation', 'seo-optimizer-ai-visual-enhancer') . '</a>';
            $links[] = '<a href="https://example.com/support" target="_blank">' . __('Support', 'seo-optimizer-ai-visual-enhancer') . '</a>';
        }
        return $links;
    }
    
    /**
     * Afficher une notification après l'activation du plugin
     */
    public function display_activation_notice() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Merci d\'avoir installé SEO Optimizer & AI Visual Enhancer ! <a href="admin.php?page=seoai-settings">Configurez vos paramètres</a> pour commencer à optimiser votre contenu.', 'seo-optimizer-ai-visual-enhancer'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Enregistrer les styles publics
     */
    public function enqueue_public_styles() {
        wp_enqueue_style('seoai-public', SEOAI_PLUGIN_URL . 'public/css/public.css', array(), SEOAI_VERSION);
    }
    
    /**
     * Ajouter des classes CSS aux images générées par l'IA
     */
    public function add_ai_image_classes($html, $post_id, $post_thumbnail_id, $size, $attr) {
        // Vérifier si l'image a été générée par l'IA
        $is_ai_generated = get_post_meta($post_thumbnail_id, '_seoai_generated', true);
        
        if ($is_ai_generated) {
            // Ajouter la classe CSS
            $html = str_replace('class="', 'class="seoai-generated-image ', $html);
            
            // Si aucune classe n'existe, en ajouter une
            if (strpos($html, 'class="') === false) {
                $html = str_replace('<img', '<img class="seoai-generated-image"', $html);
            }
        }
        
        return $html;
    }
    
    /**
     * Améliorer les images générées par l'IA dans le contenu
     */
    public function enhance_ai_images_in_content($content) {
        // Ajouter des attributs de chargement paresseux et des classes aux images générées par l'IA
        $content = preg_replace(
            '/<figure class="seoai-generated-image">(.*?)<img/s',
            '<figure class="seoai-generated-image">$1<img loading="lazy" decoding="async"',
            $content
        );
        
        return $content;
    }
    
    /**
     * Traiter automatiquement les articles lors de la publication
     */
    public function auto_process_on_publish($new_status, $old_status, $post) {
        // Vérifier si c'est une nouvelle publication
        if ($new_status === 'publish' && $old_status !== 'publish') {
            // Vérifier si c'est un type de contenu à traiter
            $post_types_to_process = isset($this->settings['auto_process_post_types']) 
                ? $this->settings['auto_process_post_types'] 
                : array('post');
                
            if (in_array($post->post_type, $post_types_to_process)) {
                // Traiter l'article en arrière-plan
                $this->logger->write_log("Traitement automatique programmé pour l'article #{$post->ID}", 'AUTO');
                
                // Utiliser wp_schedule_single_event pour traiter en arrière-plan
                wp_schedule_single_event(time() + 10, 'seoai_process_post', array($post->ID));
            }
        }
    }
    
    /**
     * Action AJAX pour traiter le contenu
     */
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
            $this->logger->write_log('Début du traitement des posts via AJAX', 'AJAX');
            
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
            $this->logger->log_error('Erreur lors du traitement AJAX: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Erreur lors du traitement : ' . $e->getMessage()));
        }
    }
    
    /**
     * Action AJAX pour récupérer les posts
     */
    public function ajax_get_posts() {
        try {
            // Vérifier le nonce
            check_ajax_referer('seoai_nonce', 'nonce');
            
            // Récupérer et nettoyer les paramètres
            $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';
            $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
            $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'publish';
            $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            
            // Construire les arguments de requête
            $args = array(
                'post_type' => $post_type,
                'posts_per_page' => 50,
                'post_status' => $status === 'any' ? array('publish', 'draft', 'pending', 'future') : $status
            );
            
            // Ajouter la catégorie si spécifiée
            if (!empty($category)) {
                $args['cat'] = $category;
            }
            
            // Ajouter la recherche si spécifiée
            if (!empty($search)) {
                $args['s'] = $search;
            }
            
            // Exécuter la requête
            $query = new WP_Query($args);
            $posts = array();
            
            // Formater les résultats
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    
                    // Récupérer l'extrait ou générer un extrait à partir du contenu
                    $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 30);
                    
                    $posts[] = array(
                        'ID' => $post_id,
                        'post_title' => get_the_title(),
                        'post_date' => get_the_date(),
                        'post_status' => get_post_status(),
                        'post_excerpt' => $excerpt,
                        'post_content' => wp_trim_words(get_the_content(), 50),
                        'edit_url' => get_edit_post_link($post_id, ''),
                        'permalink' => get_permalink($post_id)
                    );
                }
            }
            
            // Restaurer les données de post originales
            wp_reset_postdata();
            
            // Envoyer la réponse
            wp_send_json_success($posts);
        } catch (Exception $e) {
            $this->logger->log_error('Erreur lors de la récupération des posts: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Erreur lors de la récupération des posts: ' . $e->getMessage()));
        }
    }
    
    /**
     * Action AJAX pour sauvegarder les paramètres
     */
    public function ajax_save_settings() {
        try {
            // Vérifier le nonce
            if (!isset($_POST['seoai_settings_nonce']) || !wp_verify_nonce($_POST['seoai_settings_nonce'], 'seoai_settings')) {
                wp_send_json_error(array('message' => 'Sécurité : nonce invalide'));
                return;
            }
            
            // Vérifier les permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Accès refusé : permissions insuffisantes'));
                return;
            }
            
            // Récupérer les paramètres existants
            $existing_settings = get_option('seoai_settings', array());
            
            // Récupérer et nettoyer les valeurs
            $settings = array();
            
            // Paramètres généraux
            $settings['plugin_name'] = isset($_POST['plugin_name']) ? sanitize_text_field($_POST['plugin_name']) : 'SEO Optimizer & AI Visual Enhancer';
            $settings['auto_process'] = isset($_POST['auto_process']) ? (bool) $_POST['auto_process'] : false;
            $settings['enable_backups'] = isset($_POST['enable_backups']) ? (bool) $_POST['enable_backups'] : true;
            
            // Paramètres d'IA textuelle
            $settings['gemini_api_key'] = isset($_POST['gemini_api_key']) ? sanitize_text_field($_POST['gemini_api_key']) : '';
            $settings['gemini_model'] = isset($_POST['gemini_model']) ? sanitize_text_field($_POST['gemini_model']) : 'gemini-2.0-flash';
            $settings['gemini_temperature'] = isset($_POST['gemini_temperature']) ? floatval($_POST['gemini_temperature']) : 0.7;
            $settings['custom_prompt_template'] = isset($_POST['custom_prompt_template']) ? sanitize_textarea_field($_POST['custom_prompt_template']) : '';
            
            // Paramètres d'IA visuelle
            $settings['replicate_api_key'] = isset($_POST['replicate_api_key']) ? sanitize_text_field($_POST['replicate_api_key']) : '';
            $settings['image_model'] = isset($_POST['image_model']) ? sanitize_text_field($_POST['image_model']) : 'stability-ai/stable-diffusion-xl-base-1.0';
            $settings['image_dimensions'] = isset($_POST['image_dimensions']) ? sanitize_text_field($_POST['image_dimensions']) : '1024x768';
            $settings['image_count'] = isset($_POST['image_count']) ? intval($_POST['image_count']) : 3;
            
            // Positions des images
            $settings['image_positions'] = isset($_POST['image_positions']) && is_array($_POST['image_positions']) 
                ? array_map('sanitize_text_field', $_POST['image_positions']) 
                : array('after_first_paragraph', 'middle', 'conclusion');
            
            // Paramètres avancés
            $settings['log_level'] = isset($_POST['log_level']) ? sanitize_text_field($_POST['log_level']) : 'INFO';
            $settings['api_timeout'] = isset($_POST['api_timeout']) ? intval($_POST['api_timeout']) : 60;
            $settings['enable_caching'] = isset($_POST['enable_caching']) ? (bool) $_POST['enable_caching'] : true;
            $settings['log_retention'] = isset($_POST['log_retention']) ? intval($_POST['log_retention']) : 30;
            
            // Fusionner avec les paramètres existants pour conserver les valeurs non modifiées
            $settings = array_merge($existing_settings, $settings);
            
            // Journaliser pour le débogage
            $this->logger->write_log('Sauvegarde des paramètres', 'SETTINGS');
            
            // Sauvegarder les paramètres
            $update_result = update_option('seoai_settings', $settings);
            
            // Mettre à jour le niveau de journalisation
            $this->logger->set_debug_mode($settings['log_level'] === 'DEBUG');
            
            if ($update_result) {
                // Vider le cache si les paramètres ont changé
                $this->clear_cache();
                
                wp_send_json_success(array('message' => 'Paramètres sauvegardés avec succès'));
            } else {
                wp_send_json_success(array('message' => 'Aucun changement détecté dans les paramètres'));
            }
        } catch (Exception $e) {
            $this->logger->log_error('Erreur lors de la sauvegarde des paramètres: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Erreur lors de la sauvegarde des paramètres: ' . $e->getMessage()));
        }
    }
    
    /**
     * Action AJAX pour vider les logs
     */
    public function ajax_clear_logs() {
        try {
            // Vérifier le nonce
            check_ajax_referer('seoai_nonce', 'nonce');
            
            // Vérifier les permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Accès refusé : permissions insuffisantes'));
                return;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'seoai_logs';
            
            // Vider la table des logs
            $wpdb->query("TRUNCATE TABLE $table_name");
            
            $this->logger->write_log('Table des logs vidée par l\'utilisateur', 'ADMIN');
            
            wp_send_json_success(array('message' => 'Logs supprimés avec succès'));
        } catch (Exception $e) {
            $this->logger->log_error('Erreur lors de la suppression des logs: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Erreur lors de la suppression des logs: ' . $e->getMessage()));
        }
    }
    
    /**
     * Action AJAX pour récupérer les logs
     */
    public function ajax_get_logs() {
        try {
            // Vérifier le nonce
            check_ajax_referer('seoai_nonce', 'nonce');
            
            // Vérifier les permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => 'Accès refusé : permissions insuffisantes'));
                return;
            }
            
            // Récupérer et nettoyer les paramètres
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
            $date_filter = isset($_POST['date_filter']) ? sanitize_text_field($_POST['date_filter']) : 'all';
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = 20;
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'seoai_logs';
            
            // Construire la requête SQL
            $sql = "SELECT * FROM $table_name WHERE 1=1";
            $count_sql = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
            
            // Filtrer par type
            if ($type === 'success') {
                $sql .= " AND status = 'success'";
                $count_sql .= " AND status = 'success'";
            } elseif ($type === 'error') {
                $sql .= " AND status = 'error'";
                $count_sql .= " AND status = 'error'";
            }
            
            // Filtrer par date
            if ($date_filter === 'today') {
                $sql .= " AND DATE(created_at) = CURDATE()";
                $count_sql .= " AND DATE(created_at) = CURDATE()";
            } elseif ($date_filter === 'yesterday') {
                $sql .= " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                $count_sql .= " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            } elseif ($date_filter === 'week') {
                $sql .= " AND YEARWEEK(created_at) = YEARWEEK(NOW())";
                $count_sql .= " AND YEARWEEK(created_at) = YEARWEEK(NOW())";
            } elseif ($date_filter === 'month') {
                $sql .= " AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
                $count_sql .= " AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
            }
            
            // Compter le nombre total de résultats
            $total_items = $wpdb->get_var($count_sql);
            
            // Ajouter l'ordre et la pagination
            $sql .= " ORDER BY created_at DESC LIMIT " . (($page - 1) * $per_page) . ", $per_page";
            
            // Exécuter la requête
            $logs = $wpdb->get_results($sql);
            
            // Formater les résultats
            $formatted_logs = array();
            if ($logs) {
                foreach ($logs as $log) {
                    $post_title = get_the_title($log->post_id);
                    
                    $formatted_logs[] = array(
                        'id' => $log->id,
                        'post_id' => $log->post_id,
                        'post_title' => $post_title,
                        'action' => $log->action,
                        'status' => $log->status,
                        'message' => $log->message,
                        'date' => date('d/m/Y H:i', strtotime($log->created_at)),
                        'edit_url' => get_edit_post_link($log->post_id, '')
                    );
                }
            }
            
            // Calculer le nombre total de pages
            $total_pages = ceil($total_items / $per_page);
            
            // Envoyer la réponse
            wp_send_json_success(array(
                'logs' => $formatted_logs,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'current_page' => $page
            ));
        } catch (Exception $e) {
            $this->logger->log_error('Erreur lors de la récupération des logs: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Erreur lors de la récupération des logs: ' . $e->getMessage()));
        }
    }
    
    /**
     * Vider le cache du plugin
     */
    private function clear_cache() {
        if (file_exists(SEOAI_CACHE_DIR)) {
            $files = glob(SEOAI_CACHE_DIR . '/*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== 'index.php' && basename($file) !== '.htaccess') {
                    unlink($file);
                }
            }
            $this->logger->write_log('Cache vidé', 'CACHE');
        }
    }
}

// Initialiser le plugin
SEOAIVisualEnhancer::get_instance();
