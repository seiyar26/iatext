<?php

class SEOAI_API_Handler {
    
    private $logger;
    private $settings;
    private $cache;
    
    public function __construct() {
        $this->logger = SEOAI_Logger::get_instance();
        $this->settings = get_option('seoai_settings', array());
        $this->cache = array();
        
        // Initialiser les paramètres par défaut si nécessaire
        if (!isset($this->settings['gemini_model'])) {
            $this->settings['gemini_model'] = 'gemini-2.0-flash';
        }
        
        // Initialiser les paramètres de génération d'images par défaut
        if (!isset($this->settings['image_model'])) {
            $this->settings['image_model'] = 'stability-ai/stable-diffusion-xl-base-1.0';
        }
    }
    
    /**
     * Optimisation du contenu avec l'API Gemini
     */
    public function optimize_content_with_llama($content, $title, $settings = null) {
        if (!$settings) {
            $settings = $this->settings;
        }
        
        // Utiliser Gemini directement
        $model = isset($settings['gemini_model']) ? $settings['gemini_model'] : 'gemini-2.0-flash';
        return $this->optimize_content_with_gemini($content, $title, $settings, $model);
    }
    
    /**
     * Optimisation du contenu via l'API Gemini
     */
    public function optimize_content_with_gemini($content, $title, $settings = null, $model = 'gemini-2.0-flash') {
        if (!$settings) {
            $settings = $this->settings;
        }
        
        // Récupérer la clé API
        $gemini_api_key = isset($settings['gemini_api_key']) ? $settings['gemini_api_key'] : 'AIzaSyADrxdKGRxKAzs8pJQpz8GZQETcy_VQtvQ';
        
        if (empty($gemini_api_key)) {
            return new WP_Error('no_api_key', 'Clé API Gemini manquante');
        }
        
        // Préparation du prompt
        $prompt = "Titre: $title\n\nContenu: $content";
        
        // Journaliser les informations de la requête
        $this->logger->write_log("=== DÉBUT REQUÊTE GEMINI (modèle: $model) ===", 'INFO');
        $this->logger->write_log("Longueur du contenu à optimiser: " . strlen($content) . " caractères", 'DEBUG');
        
        // Extraire le nom du modèle (sans le préfixe google/)
        $model_name = str_replace('google/', '', $model);
        
        // Construction de la requête Gemini
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => "Tu es un expert en rédaction web qui va améliorer le contenu de cet article pour le rendre plus engageant, informatif et optimisé pour le référencement naturel. Analyse le sujet principal de l'article et développe-le en profondeur. Utilise une structure claire avec introduction, développement et conclusion. Crée des titres et sous-titres pertinents et accrocheurs. Ajoute des faits, chiffres et exemples concrets pour enrichir le contenu. Rédige dans un style fluide, accessible et engageant. Inclus naturellement les mots-clés pertinents sans bourrage. Termine par une conclusion avec un appel à l'action. Pour les images, suggère 3 descriptions visuelles qui illustreront parfaitement les points clés de l'article. IMPORTANT: Concentre-toi uniquement sur le sujet de l'article. Ne parle pas de SEO, de mots-clés ou d'aspects techniques. Ne mets pas d'astérisques, de crochets ou de commentaires techniques. Écris directement le contenu de l'article comme s'il était destiné à être publié tel quel.\n\n$prompt"
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'topP' => 0.95,
                'topK' => 40,
                'maxOutputTokens' => 8192
            )
        );
        
        // Convertir en JSON
        $request_json = json_encode($data);
        
        // Log de la requête
        $this->logger->write_log("PARAMÈTRES: temperature=0.7, model=$model_name", 'DEBUG');
        $this->logger->write_log("CONTENU: " . (strlen($prompt) > 100 ? substr($prompt, 0, 100) . '...' : $prompt), 'DEBUG');
        
        // Construire l'URL de l'API
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model_name}:generateContent?key={$gemini_api_key}";
        
        // Enregistrer le temps de début
        $start_time = microtime(true);
        
        // Envoyer la requête
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => $request_json,
            'timeout' => 60
        ));
        
        // Calculer le temps de réponse
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        $this->logger->write_log("Temps de réponse API Gemini: {$duration} secondes", 'PERFORMANCE');
        
        // Gestion des erreurs de connexion
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->write_log("=== ERREUR DE CONNEXION GEMINI ===", 'ERROR');
            $this->logger->write_log("Détail de l'erreur WP: $error_message", 'ERROR');
            return $response;
        }
        
        // Traitement de la réponse
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $this->logger->write_log("=== RÉPONSE GEMINI REÇUE ===", 'INFO');
        $this->logger->write_log("Code HTTP: $status_code", 'DEBUG');
        $this->logger->write_log("Taille de la réponse: " . strlen($body) . " octets", 'DEBUG');
        
        // Parsing de la réponse JSON
        try {
            // Vérifier que la réponse n'est pas vide
            if (empty($body)) {
                throw new Exception("Réponse vide reçue de l'API Gemini");
            }
            
            // Tentative de parsing JSON
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Erreur de parsing JSON: " . json_last_error_msg());
            }
            
            // Vérification du statut HTTP
            if ($status_code < 200 || $status_code >= 300) {
                $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Erreur HTTP ' . $status_code;
                $this->logger->write_log("=== ERREUR HTTP GEMINI ===", 'ERROR');
                $this->logger->write_log("Code: $status_code, Message: $error_message", 'ERROR');
                return new WP_Error('api_error', $error_message);
            }
            
            // Extraction du contenu de la réponse
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $data['candidates'][0]['content']['parts'][0]['text'];
                $content_length = strlen($content);
                $this->logger->write_log("Contenu trouvé, longueur: $content_length caractères", 'DEBUG');
                
                // Préaperçu du contenu
                $preview = substr($content, 0, 100);
                if ($content_length > 100) $preview .= '... [tronqué]';
                $this->logger->write_log("Début du contenu: $preview", 'DEBUG');
                
                // Information de succès
                $this->logger->write_log("=== SUCCÈS GEMINI ===", 'SUCCESS');
                
                // Analyse et traitement du contenu
                $result = $this->parse_llama_response($content);
                
                return $result;
            } else {
                $this->logger->write_log("Structure de réponse Gemini inattendue", 'ERROR');
                return new WP_Error('api_error', 'Structure de réponse Gemini inattendue');
            }
        } catch (Exception $e) {
            // Capturer toutes les erreurs de parsing et les journaliser
            $error = $e->getMessage();
            $this->logger->write_log("=== ERREUR DE PARSING GEMINI ===", 'ERROR');
            $this->logger->write_log("Exception: $error", 'ERROR');
            return new WP_Error('api_error', "Erreur lors du traitement de la réponse: $error");
        }
    }
    
    /**
     * Analyser la réponse de l'IA et extraire le contenu et les prompts d'image
     */
    private function parse_llama_response($response) {
        $result = array(
            'optimized_content' => '',
            'image_prompts' => array()
        );
        
        // Nettoyage de la réponse
        $response = trim($response);
        
        // Recherche de sections de contenu et de prompts d'image
        if (preg_match('/## Contenu optimisé(.*?)(?:## Prompts d\'images|$)/s', $response, $matches)) {
            $result['optimized_content'] = trim($matches[1]);
        } elseif (preg_match('/# Contenu optimisé(.*?)(?:# Prompts d\'images|$)/s', $response, $matches)) {
            $result['optimized_content'] = trim($matches[1]);
        } else {
            // Si pas de marqueurs clairs, considérer tout comme contenu
            $result['optimized_content'] = $response;
        }
        
        // Extraction des prompts d'images
        if (preg_match('/(?:## Prompts d\'images|# Prompts d\'images)(.*?)$/s', $response, $matches)) {
            $prompts_text = trim($matches[1]);
            $prompts = preg_split('/\r?\n/', $prompts_text);
            
            // Filtrer les lignes vides et les séparateurs
            foreach ($prompts as $prompt) {
                $prompt = trim($prompt);
                // Enlever les préfixes numériques et tirets
                $prompt = preg_replace('/^[0-9\-\.\)]+\.?\s*/', '', $prompt);
                
                if (!empty($prompt)) {
                    $result['image_prompts'][] = $prompt;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Génère des images à partir de prompts en utilisant l'API Replicate
     * 
     * @param array $prompts Liste des prompts pour générer des images
     * @param array $settings Paramètres optionnels pour la génération d'images
     * @return array|WP_Error Liste des URLs des images générées ou objet d'erreur
     */
    /**
     * Génère des images à partir de prompts en utilisant Google Imagen 4 via Replicate
     * 
     * @param array $prompts Liste des prompts pour générer des images
     * @param array $settings Paramètres optionnels
     * @return array|WP_Error Images générées ou erreur
     */
    public function generate_images($prompts, $settings = null) {
        if (!$settings) {
            $settings = $this->settings;
        }
        
        // Récupérer la clé API Replicate
        $replicate_api_key = isset($settings['replicate_api_key']) ? $settings['replicate_api_key'] : '';
        
        if (empty($replicate_api_key)) {
            return new WP_Error('no_api_key', 'Clé API Replicate manquante');
        }
        
        // Modèle à utiliser (par défaut: Google Imagen 4)
        $model = isset($settings['image_model']) ? $settings['image_model'] : 'google/imagen-4';
        
        // Format d'aspect pour Imagen 4
        $aspect_ratio = isset($settings['aspect_ratio']) ? $settings['aspect_ratio'] : '16:9';
        
        // Niveau de filtre de sécurité pour Imagen 4
        $safety_filter = isset($settings['safety_filter_level']) ? $settings['safety_filter_level'] : 'block_medium_and_above';
        
        $this->logger->write_log("=== DÉBUT GÉNÉRATION D'IMAGES (modèle: $model) ===", 'INFO');
        $this->logger->write_log("Nombre de prompts: " . count($prompts), 'DEBUG');
        
        $image_urls = array();
        
        foreach ($prompts as $index => $prompt) {
            // Vérifier si l'image est déjà en cache
            $cache_key = md5($prompt . $model . $aspect_ratio);
            if (isset($this->cache[$cache_key])) {
                $this->logger->write_log("Image trouvée en cache pour le prompt: " . substr($prompt, 0, 50) . "...", 'DEBUG');
                $image_urls[] = $this->cache[$cache_key];
                continue;
            }
            
            // Préparation des paramètres selon le modèle
            if ($model === 'google/imagen-4') {
                // Configuration pour Google Imagen 4
                $api_url = 'https://api.replicate.com/v1/models/google/imagen-4/predictions';
                $data = array(
                    'input' => array(
                        'prompt' => $prompt,
                        'aspect_ratio' => $aspect_ratio,
                        'safety_filter_level' => $safety_filter
                    )
                );
                $auth_header = 'Bearer ' . $replicate_api_key;
            } else {
                // Configuration pour les autres modèles (comme Stable Diffusion)
                $api_url = 'https://api.replicate.com/v1/predictions';
                $data = array(
                    'version' => $this->get_model_version($model),
                    'input' => array(
                        'prompt' => $prompt,
                        'width' => 1024,
                        'height' => 768,
                        'num_outputs' => 1,
                        'guidance_scale' => 7.5,
                        'num_inference_steps' => 50,
                        'negative_prompt' => 'low quality, blurry, distorted, deformed, disfigured, watermark, signature'
                    )
                );
                $auth_header = 'Token ' . $replicate_api_key;
            }
            
            // Convertir en JSON
            $request_json = json_encode($data);
            
            // Log de la requête
            $this->logger->write_log("GÉNÉRATION IMAGE #" . ($index + 1) . ": " . substr($prompt, 0, 100) . (strlen($prompt) > 100 ? '...' : ''), 'DEBUG');
            $this->logger->write_log("Modèle utilisé: " . $model . ", Format: " . ($model === 'google/imagen-4' ? $aspect_ratio : '1024x768'), 'DEBUG');
            
            // Enregistrer le temps de début
            $start_time = microtime(true);
            
            // Envoyer la requête pour démarrer la prédiction
            $response = wp_remote_post($api_url, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => $auth_header,
                    'Prefer' => 'wait' // Essayer d'attendre la réponse complète
                ),
                'body' => $request_json,
                'timeout' => 120 // Timeout plus long pour Imagen 4
            ));
            
            // Gestion des erreurs de connexion
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $this->logger->write_log("=== ERREUR DE CONNEXION REPLICATE ===", 'ERROR');
                $this->logger->write_log("Détail de l'erreur WP: $error_message", 'ERROR');
                continue;
            }
            
            // Traitement de la réponse initiale
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            
            if ($status_code < 200 || $status_code >= 300) {
                $this->logger->write_log("Erreur HTTP $status_code lors de la création de la prédiction", 'ERROR');
                continue;
            }
            
            $prediction_data = json_decode($body, true);
            
            if (!isset($prediction_data['id'])) {
                $this->logger->write_log("ID de prédiction non trouvé dans la réponse", 'ERROR');
                continue;
            }
            
            $prediction_id = $prediction_data['id'];
            $this->logger->write_log("Prédiction créée avec ID: $prediction_id", 'DEBUG');
            
            // Attendre que la prédiction soit terminée
            $max_attempts = 30; // 5 minutes maximum (10 secondes * 30)
            $attempts = 0;
            $prediction_url = "https://api.replicate.com/v1/predictions/{$prediction_id}";
            
            while ($attempts < $max_attempts) {
                $attempts++;
                
                // Attendre 10 secondes entre chaque vérification
                sleep(10);
                
                // Vérifier l'état de la prédiction
                $status_response = wp_remote_get($prediction_url, array(
                    'headers' => array(
                        'Authorization' => 'Token ' . $replicate_api_key
                    ),
                    'timeout' => 15
                ));
                
                if (is_wp_error($status_response)) {
                    $this->logger->write_log("Erreur lors de la vérification de l'état: " . $status_response->get_error_message(), 'ERROR');
                    continue 2; // Passer au prompt suivant
                }
                
                $status_body = wp_remote_retrieve_body($status_response);
                $status_data = json_decode($status_body, true);
                
                if (isset($status_data['status'])) {
                    $this->logger->write_log("État de la prédiction: " . $status_data['status'], 'DEBUG');
                    
                    if ($status_data['status'] === 'succeeded') {
                        // La prédiction est terminée
                        if (isset($status_data['output']) && is_array($status_data['output']) && !empty($status_data['output'])) {
                            $image_url = $status_data['output'][0]; // Premier résultat
                            $image_urls[] = $image_url;
                            
                            // Mettre en cache le résultat
                            $this->cache[$cache_key] = $image_url;
                            
                            $this->logger->write_log("Image générée avec succès: " . $image_url, 'SUCCESS');
                            break;
                        } else {
                            $this->logger->write_log("Aucune image dans la sortie de la prédiction", 'ERROR');
                            break;
                        }
                    } elseif ($status_data['status'] === 'failed') {
                        $error = isset($status_data['error']) ? $status_data['error'] : 'Raison inconnue';
                        $this->logger->write_log("La prédiction a échoué: " . $error, 'ERROR');
                        break;
                    }
                    // Sinon, continuer à attendre
                }
            }
            
            if ($attempts >= $max_attempts) {
                $this->logger->write_log("Délai d'attente dépassé pour la prédiction", 'ERROR');
            }
            
            // Calculer le temps de génération
            $end_time = microtime(true);
            $duration = round($end_time - $start_time, 2);
            $this->logger->write_log("Temps de génération d'image: {$duration} secondes", 'PERFORMANCE');
        }
        
        $this->logger->write_log("=== FIN GÉNÉRATION D'IMAGES ===", 'INFO');
        $this->logger->write_log("Images générées: " . count($image_urls) . "/" . count($prompts), 'INFO');
        
        return $image_urls;
    }
    
    /**
     * Obtient la version correcte du modèle Replicate
     * 
     * @param string $model_name Nom du modèle
     * @return string Version du modèle
     */
    private function get_model_version($model_name) {
        $versions = array(
            'stability-ai/stable-diffusion-xl-base-1.0' => 'be04660a5b93ef2aff61e3668dedb4cbeb14941e62a3fd5998364a32d613e35e',
            'stability-ai/sdxl' => '39ed52f2a78e934b3ba6e2a89f5b1c712de7dfea535525255b1aa35c5565e08b',
            'midjourney/midjourney-diffusion' => '436b051ebd8f68d23e83d22de5e198e0995357afef113768c20f0b6fcef23c8b'
        );
        
        return isset($versions[$model_name]) ? $versions[$model_name] : $versions['stability-ai/stable-diffusion-xl-base-1.0'];
    }
}
