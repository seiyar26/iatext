<?php

class SEOAI_Content_Processor {
    
    private $api_handler;
    
    public function __construct() {
        $this->api_handler = new SEOAI_API_Handler();
    }
    
    public function process_posts($post_ids) {
        $post_ids = explode(',', $post_ids);
        $results = array();
        
        foreach ($post_ids as $post_id) {
            $result = $this->process_single_post($post_id);
            $results[] = $result;
            
            // Log de l'action
            $this->log_action($post_id, 'process', $result['success'] ? 'success' : 'error', 
                             $result['success'] ? 'Post traité avec succès' : $result['message']);
        }
        
        return $results;
    }
    
    public function process_single_post($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return array('success' => false, 'message' => 'Post introuvable');
        }
        
        try {
            // Backup du contenu original
            $this->create_backup($post);
            
            // Optimisation du contenu avec LLaMA
            $llama_result = $this->api_handler->optimize_content_with_llama($post->post_content, $post->post_title);
            
            if (is_wp_error($llama_result)) {
                return array('success' => false, 'message' => $llama_result->get_error_message());
            }
            
            // Génération des images
            $images = array();
            if (!empty($llama_result['image_prompts'])) {
                $generated_images = $this->api_handler->generate_images(array_slice($llama_result['image_prompts'], 0, 3));
                
                if (!is_wp_error($generated_images)) {
                    $images = $this->upload_images_to_media_library($generated_images, $post_id);
                }
            }
            
            // Restructuration du contenu avec images
            $new_content = $this->restructure_content_with_images($llama_result['optimized_content'], $images);
            
            // Mise à jour du post directement via la base de données pour éviter les hooks
            // qui peuvent causer des conflits avec d'autres plugins
            global $wpdb;
            
            // Log pour le débogage
            error_log('SEOAI: Mise à jour du contenu du post ' . $post_id);
            
            // Mise à jour sécurisée via wpdb
            $result = $wpdb->update(
                $wpdb->posts,
                array('post_content' => $new_content),
                array('ID' => $post_id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                // Vider le cache pour cet article
                clean_post_cache($post_id);
                
                // Déclencher notre propre action personnalisée
                do_action('seoai_post_updated', $post_id, $new_content);
                
                return array('success' => true, 'message' => 'Post traité avec succès');
            } else {
                return array('success' => false, 'message' => 'Erreur lors de la mise à jour du post');
            }
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    private function create_backup($post) {
        $backup_data = array(
            'post_id' => $post->ID,
            'original_content' => $post->post_content,
            'original_title' => $post->post_title,
            'backup_date' => current_time('mysql')
        );
        
        $upload_dir = wp_upload_dir();
        $backup_file = $upload_dir['basedir'] . '/seoai-backups/backup_' . $post->ID . '_' . time() . '.json';
        
        file_put_contents($backup_file, json_encode($backup_data));
        
        // Sauvegarder aussi en base de données
        update_post_meta($post->ID, '_seoai_original_content', $post->post_content);
    }
    
    private function upload_images_to_media_library($image_urls, $post_id) {
        $uploaded_images = array();
        
        foreach ($image_urls as $index => $image_url) {
            $image_data = wp_remote_get($image_url);
            
            if (!is_wp_error($image_data)) {
                $filename = 'seoai_generated_' . $post_id . '_' . ($index + 1) . '.webp';
                
                $upload = wp_upload_bits($filename, null, wp_remote_retrieve_body($image_data));
                
                if (!$upload['error']) {
                    $attachment = array(
                        'post_mime_type' => 'image/webp',
                        'post_title' => sanitize_file_name($filename),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    
                    $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
                    
                    if (!is_wp_error($attachment_id)) {
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                        wp_update_attachment_metadata($attachment_id, $attachment_data);
                        
                        $uploaded_images[] = array(
                            'id' => $attachment_id,
                            'url' => wp_get_attachment_url($attachment_id)
                        );
                    }
                }
            }
        }
        
        return $uploaded_images;
    }
    
    /**
     * Restructure le contenu en intégrant les images générées
     * 
     * @param string $content Contenu optimisé
     * @param array $images Images générées
     * @return string Contenu restructuré avec images
     */
    private function restructure_content_with_images($content, $images) {
        // Diviser le contenu en paragraphes
        $paragraphs = explode("\n", $content);
        $paragraphs = array_filter($paragraphs, function($p) { return !empty(trim($p)); });
        $paragraphs = array_values($paragraphs); // Réindexer le tableau
        
        // Commencer l'article avec la classe optimisée
        $new_content = '<article class="seoai-optimized-content">';
        
        // Créer une section unique pour tout le contenu principal
        $new_content .= '<section class="content-section fade-in visible">';
        
        $paragraph_count = count($paragraphs);
        $image_index = 0;
        $image_positions = array();
        
        // Récupérer les paramètres pour les positions d'images
        $settings = get_option('seoai_settings', array());
        $image_positions_setting = isset($settings['image_positions']) ? $settings['image_positions'] : array('after_first_paragraph', 'middle', 'conclusion');
        
        // Déterminer les positions d'insertion des images
        if (in_array('after_first_paragraph', $image_positions_setting) && $paragraph_count > 1) {
            $image_positions[] = 0; // Après le premier paragraphe
        }
        if (in_array('middle', $image_positions_setting) && $paragraph_count > 2) {
            $image_positions[] = floor($paragraph_count / 2); // Au milieu
        }
        if (in_array('conclusion', $image_positions_setting)) {
            $image_positions[] = $paragraph_count - 1; // À la fin
        }
        
        // Ajouter les paragraphes et insérer les images aux positions stratégiques
        foreach ($paragraphs as $index => $paragraph) {
            $paragraph = trim($paragraph);
            
            // Vérifier si le paragraphe est un titre (commence par # ou ##)
            if (preg_match('/^#+\s+(.+)$/i', $paragraph, $matches)) {
                $heading_level = substr_count(trim($paragraph), '#');
                $heading_text = $matches[1];
                
                // Limiter le niveau de titre entre h2 et h4
                $heading_level = min(max($heading_level, 2), 4);
                
                $new_content .= "<h{$heading_level}>{$heading_text}</h{$heading_level}>";
            } else {
                // Paragraphe normal
                $new_content .= '<p>' . $paragraph . '</p>';
            }
            
            // Insérer les images aux positions stratégiques
            if ($image_index < count($images) && in_array($index, $image_positions)) {
                $image = $images[$image_index];
                
                // Créer la figure avec l'image
                $new_content .= '<figure class="seoai-generated-image">';
                $new_content .= '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr(isset($image['alt']) ? $image['alt'] : 'Image générée par IA') . '" loading="lazy" decoding="async" />';
                
                // Ajouter une légende si disponible
                if (!empty($image['caption'])) {
                    $new_content .= '<figcaption>' . esc_html($image['caption']) . '</figcaption>';
                }
                
                $new_content .= '</figure>';
                
                $image_index++;
            }
        }
        
        // Fermer la section et l'article
        $new_content .= '</section>';
        $new_content .= '</article>';
        
        return $new_content;
    }
    
    private function log_action($post_id, $action, $status, $message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seoai_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'action' => $action,
                'status' => $status,
                'message' => $message
            )
        );
    }
    
    public function restore_post($post_id) {
        $original_content = get_post_meta($post_id, '_seoai_original_content', true);
        
        if ($original_content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $original_content
            ));
            
            delete_post_meta($post_id, '_seoai_original_content');
            
            $this->log_action($post_id, 'restore', 'success', 'Contenu restauré');
            
            return true;
        }
        
        return false;
    }
}
