<?php

class SEOAI_API_Handler {
    
    private $logger;
    private $settings;
    
    public function __construct() {
        $this->logger = SEOAI_Logger::get_instance();
        $this->settings = get_option('seoai_settings', array());
        
        // Initialiser les paramètres par défaut si nécessaire
        if (!isset($this->settings['gemini_model'])) {
            $this->settings['gemini_model'] = 'gemini-2.0-flash';
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
        $prompt = "Optimise cet article WordPress pour le SEO. Titre: $title\n\nContenu: $content\n\nGénère également 3 prompts pour créer des images pertinentes pour cet article.";
        
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
                            'text' => "Tu es un expert SEO et rédacteur web. Optimise le contenu pour le référencement naturel et génère des prompts d'images cohérents.\n\n$prompt"
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
}
