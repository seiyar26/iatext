<?php
/**
 * Gestionnaire pour l'API Gemini de Google
 */
class SEOAI_Gemini_Handler {
    /**
     * Clé API pour Gemini
     */
    private $api_key;
    
    /**
     * Logger pour les événements
     */
    private $logger;
    
    /**
     * Modèle Gemini à utiliser
     */
    private $model;
    
    /**
     * Constructeur
     */
    public function __construct($api_key = null, $model = 'gemini-2.0-flash', $logger = null) {
        $this->api_key = $api_key;
        $this->model = $model;
        $this->logger = $logger;
        
        // Si aucune clé n'est fournie, essayer de la récupérer des paramètres
        if (empty($this->api_key)) {
            $settings = get_option('seoai_settings', array());
            $this->api_key = isset($settings['gemini_api_key']) ? $settings['gemini_api_key'] : '';
        }
        
        // Si aucun logger n'est fourni, créer un nouveau logger
        if ($this->logger === null && class_exists('SEOAI_Logger')) {
            $this->logger = new SEOAI_Logger();
        }
    }
    
    /**
     * Générer du contenu avec Gemini
     */
    public function generate_content($prompt, $system_prompt = null, $options = array()) {
        // Vérifier si la clé API est définie
        if (empty($this->api_key)) {
            if ($this->logger) {
                $this->logger->log_error('Clé API Gemini manquante', 'gemini');
            }
            return new WP_Error('api_key_missing', 'Clé API Gemini manquante');
        }
        
        // Nettoyer le nom du modèle
        $model = $this->model;
        if (strpos($model, 'google/') === 0) {
            $model = substr($model, 7); // Supprimer 'google/'
        }
        
        // Log de début de requête
        if ($this->logger) {
            $this->logger->write_log("Début de requête vers l'API Gemini (modèle: $model)", 'INFO');
            $this->logger->write_log("ENVOI REQUÊTE TIMESTAMP: " . date('Y-m-d H:i:s.u'), 'INFO');
        }
        
        $start_time = microtime(true);
        
        // Construire l'URL
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->api_key}";
        
        // Préparer les messages pour l'API
        $messages = array();
        
        // Ajouter le message système si fourni
        if (!empty($system_prompt)) {
            $messages[] = array(
                'role' => 'system',
                'text' => $system_prompt
            );
        }
        
        // Ajouter le message utilisateur (prompt principal)
        $messages[] = array(
            'role' => 'user',
            'text' => $prompt
        );
        
        // Construire le corps de la requête
        $request_body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            )
        );
        
        // Ajouter des paramètres supplémentaires si nécessaire
        if (!empty($options['temperature'])) {
            $request_body['generationConfig']['temperature'] = floatval($options['temperature']);
        }
        
        if (!empty($options['max_tokens'])) {
            $request_body['generationConfig']['maxOutputTokens'] = intval($options['max_tokens']);
        }
        
        // Convertir en JSON
        $request_json = json_encode($request_body);
        
        // Log de la requête (masquer la clé API)
        if ($this->logger) {
            $log_url = str_replace($this->api_key, '***API_KEY_MASKED***', $url);
            $this->logger->write_log("Requête API vers $log_url", 'API_REQUEST');
            $this->logger->write_log("Données: $request_json", 'API_REQUEST');
        }
        
        // Exécuter la requête
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => $request_json,
            'timeout' => 60
        ));
        
        // Calculer le temps de réponse
        $end_time = microtime(true);
        $response_time = round($end_time - $start_time, 2);
        
        // Log du temps de réponse
        if ($this->logger) {
            $this->logger->write_log("Temps de réponse API: $response_time secondes", 'PERFORMANCE');
        }
        
        // Vérifier les erreurs
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if ($this->logger) {
                $this->logger->log_error("Erreur API Gemini: $error_message", 'gemini');
            }
            return $response;
        }
        
        // Récupérer le code de statut HTTP
        $status_code = wp_remote_retrieve_response_code($response);
        
        // Log du code de statut
        if ($this->logger) {
            $this->logger->write_log("Code HTTP: $status_code", 'DEBUG');
        }
        
        // Récupérer le corps de la réponse
        $body = wp_remote_retrieve_body($response);
        
        // Log de la réponse
        if ($this->logger) {
            $this->logger->write_log("=== RÉPONSE GEMINI REÇUE ===", 'INFO');
            $this->logger->write_log("Taille de la réponse: " . strlen($body) . " octets", 'DEBUG');
            $this->logger->write_log("Réponse API de $log_url", 'API_RESPONSE');
            $this->logger->write_log("Code HTTP: $status_code", 'API_RESPONSE');
            
            // Logger seulement le début de la réponse pour éviter des logs trop volumineux
            $shortened_body = substr($body, 0, 500) . (strlen($body) > 500 ? '... [tronqué]' : '');
            $this->logger->write_log("Contenu: $shortened_body", 'API_RESPONSE');
        }
        
        // Vérifier si le statut est un succès (2xx)
        if ($status_code < 200 || $status_code >= 300) {
            if ($this->logger) {
                $this->logger->log_error("Erreur API Gemini: Code HTTP $status_code - $body", 'gemini');
            }
            return new WP_Error('api_error', "Erreur API Gemini: Code HTTP $status_code", array('status' => $status_code, 'body' => $body));
        }
        
        // Décoder la réponse JSON
        $data = json_decode($body, true);
        
        // Vérifier si le décodage a réussi
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($this->logger) {
                $this->logger->log_error("Erreur décodage JSON: " . json_last_error_msg(), 'gemini');
            }
            return new WP_Error('json_error', "Erreur décodage JSON: " . json_last_error_msg());
        }
        
        // Log de débogage sur la structure
        if ($this->logger) {
            $this->logger->write_log("=== TRAITEMENT DU CONTENU GEMINI ===", 'INFO');
            
            // Analyser la structure pour le débogage
            if (isset($data['candidates']) && is_array($data['candidates']) && !empty($data['candidates'])) {
                $this->logger->write_log("Nombre de candidats: " . count($data['candidates']), 'DEBUG');
                
                if (isset($data['candidates'][0]['content']) && isset($data['candidates'][0]['content']['parts'])) {
                    $parts = $data['candidates'][0]['content']['parts'];
                    $this->logger->write_log("Nombre de parties: " . count($parts), 'DEBUG');
                    
                    if (!empty($parts) && isset($parts[0]['text'])) {
                        $text = $parts[0]['text'];
                        $this->logger->write_log("Contenu trouvé, longueur: " . strlen($text) . " caractères", 'DEBUG');
                        $this->logger->write_log("Début du contenu: " . substr($text, 0, 100) . "... [tronqué]", 'DEBUG');
                    } else {
                        $this->logger->write_log("Pas de texte trouvé dans les parties", 'ERROR');
                    }
                } else {
                    $this->logger->write_log("Structure de contenu incomplète", 'ERROR');
                }
            } else {
                $this->logger->write_log("Pas de candidats trouvés dans la réponse", 'ERROR');
            }
        }
        
        // Extraire le contenu généré
        $generated_content = '';
        if (isset($data['candidates']) && is_array($data['candidates']) && !empty($data['candidates'])) {
            if (isset($data['candidates'][0]['content']) && isset($data['candidates'][0]['content']['parts'])) {
                $parts = $data['candidates'][0]['content']['parts'];
                if (!empty($parts) && isset($parts[0]['text'])) {
                    $generated_content = $parts[0]['text'];
                    
                    // Log de succès
                    if ($this->logger) {
                        $this->logger->write_log("=== SUCCÈS GEMINI ===", 'SUCCESS');
                        $this->logger->write_log("Réponse Gemini reçue avec succès (modèle: $model, longueur: " . strlen($generated_content) . " caractères)", 'SUCCESS');
                    }
                }
            }
        }
        
        // Si le contenu est vide, c'est une erreur
        if (empty($generated_content)) {
            if ($this->logger) {
                $this->logger->log_error("Contenu généré vide ou format inattendu", 'gemini');
            }
            return new WP_Error('empty_content', "Contenu généré vide ou format inattendu", array('data' => $data));
        }
        
        return $generated_content;
    }
}
