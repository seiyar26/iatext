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
    
    private function restructure_content_with_images($content, $images) {
        $paragraphs = explode("\n", $content);
        $paragraphs = array_filter($paragraphs, function($p) { return !empty(trim($p)); });
        
        $new_content = '<article class="seoai-optimized-content">';
        
        $paragraph_count = count($paragraphs);
        $image_index = 0;
        
        foreach ($paragraphs as $index => $paragraph) {
            $new_content .= '<section class="content-section">';
            $new_content .= '<p>' . $paragraph . '</p>';
            $new_content .= '</section>';
            
            // Insérer les images aux positions stratégiques
            if ($image_index < count($images)) {
                if (($index === 0 && $paragraph_count > 1) || // Après le premier paragraphe
                    ($index === floor($paragraph_count / 2)) || // Au milieu
                    ($index === $paragraph_count - 1)) { // À la fin
                    
                    $image = $images[$image_index];
                    $new_content .= '<figure class="seoai-generated-image">';
                    $new_content .= '<img src="' . esc_url($image['url']) . '" alt="Image générée par IA" loading="lazy" />';
                    $new_content .= '</figure>';
                    
                    $image_index++;
                }
            }
        }
        
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
