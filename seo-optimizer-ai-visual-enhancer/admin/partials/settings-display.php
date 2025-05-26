<?php
// Formulaire des paramètres du plugin
/* Fonctions WordPress natives utilisées:
 * wp_nonce_field
 * esc_attr
 * get_option
 * selected
 * checked
 * Ces fonctions sont fournies par WordPress, le linter peut les marquer comme non définies
 */
?>

<div class="wrap">
    <h1>Paramètres SEO Optimizer & AI Visual Enhancer</h1>
    
    <div class="notice notice-info">
        <p>Configurez vos paramètres pour optimiser les performances du plugin et personnaliser son comportement.</p>
    </div>
    
    <form id="seoai-settings-form" method="post" action="">
        <?php wp_nonce_field('seoai_settings', 'seoai_settings_nonce'); ?>
        <input type="hidden" name="action" value="seoai_save_settings">
        
        <div class="nav-tab-wrapper">
            <a href="#general-settings" class="nav-tab nav-tab-active">Général</a>
            <a href="#text-ai-settings" class="nav-tab">IA Textuelle</a>
            <a href="#image-ai-settings" class="nav-tab">IA Visuelle</a>
            <a href="#advanced-settings" class="nav-tab">Avancé</a>
        </div>
        
        <div id="general-settings" class="tab-content active">
            <h2>Paramètres généraux</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Nom du plugin</th>
                    <td>
                        <input type="text" name="plugin_name" value="<?php echo esc_attr(get_option('seoai_settings')['plugin_name'] ?? 'SEO Optimizer & AI Visual Enhancer'); ?>" class="regular-text" />
                        <p class="description">Nom personnalisé du plugin dans le menu WordPress</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Traitement automatique</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_process" value="1" <?php checked(get_option('seoai_settings')['auto_process'] ?? false, true); ?> />
                            Optimiser automatiquement les nouveaux articles
                        </label>
                        <p class="description">Lorsqu'activé, les nouveaux articles seront automatiquement optimisés lors de leur publication</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Sauvegarde des contenus</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_backups" value="1" <?php checked(get_option('seoai_settings')['enable_backups'] ?? true, true); ?> />
                            Activer les sauvegardes automatiques
                        </label>
                        <p class="description">Crée une sauvegarde du contenu original avant optimisation (recommandé)</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="text-ai-settings" class="tab-content">
            <h2>Paramètres d'IA Textuelle</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Clé API Gemini</th>
                    <td>
                        <input type="password" name="gemini_api_key" value="<?php echo esc_attr(get_option('seoai_settings')['gemini_api_key'] ?? 'AIzaSyADrxdKGRxKAzs8pJQpz8GZQETcy_VQtvQ'); ?>" class="regular-text" />
                        <p class="description">Votre clé API Gemini pour l'optimisation de contenu avec l'IA Google</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Modèle Gemini</th>
                    <td>
                        <select name="gemini_model" class="regular-text">
                            <option value="gemini-2.0-flash" <?php selected(get_option('seoai_settings')['gemini_model'] ?? 'gemini-2.0-flash', 'gemini-2.0-flash'); ?>>Gemini 2.0 Flash (Rapide)</option>
                            <option value="gemini-2.0-pro" <?php selected(get_option('seoai_settings')['gemini_model'] ?? 'gemini-2.0-flash', 'gemini-2.0-pro'); ?>>Gemini 2.0 Pro (Haute qualité)</option>
                            <option value="gemini-1.5-flash" <?php selected(get_option('seoai_settings')['gemini_model'] ?? 'gemini-2.0-flash', 'gemini-1.5-flash'); ?>>Gemini 1.5 Flash</option>
                            <option value="gemini-1.5-pro" <?php selected(get_option('seoai_settings')['gemini_model'] ?? 'gemini-2.0-flash', 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro</option>
                        </select>
                        <p class="description">Sélectionnez le modèle Gemini à utiliser. Les modèles Pro offrent de meilleures performances mais utilisent plus de crédits.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Température</th>
                    <td>
                        <input type="range" name="gemini_temperature" min="0" max="1" step="0.1" value="<?php echo esc_attr(get_option('seoai_settings')['gemini_temperature'] ?? 0.7); ?>" class="seoai-range" />
                        <span class="range-value"><?php echo esc_attr(get_option('seoai_settings')['gemini_temperature'] ?? 0.7); ?></span>
                        <p class="description">Contrôle la créativité du modèle. Valeurs plus basses = plus conservateur, valeurs plus hautes = plus créatif.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Personnalisation du prompt</th>
                    <td>
                        <textarea name="custom_prompt_template" rows="5" class="large-text code"><?php echo esc_textarea(get_option('seoai_settings')['custom_prompt_template'] ?? "Titre: {{title}}\n\nContenu: {{content}}"); ?></textarea>
                        <p class="description">Personnalisez le prompt envoyé à l'IA. Utilisez {{title}} et {{content}} comme variables.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="image-ai-settings" class="tab-content">
            <h2>Paramètres d'IA Visuelle</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Clé API Replicate</th>
                    <td>
                        <input type="password" name="replicate_api_key" value="<?php echo esc_attr(get_option('seoai_settings')['replicate_api_key'] ?? ''); ?>" class="regular-text" />
                        <p class="description">Votre clé API Replicate pour la génération d'images (obligatoire pour la génération d'images)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Modèle de génération d'images</th>
                    <td>
                        <select name="image_model" class="regular-text">
                            <option value="stability-ai/stable-diffusion-xl-base-1.0" <?php selected(get_option('seoai_settings')['image_model'] ?? 'stability-ai/stable-diffusion-xl-base-1.0', 'stability-ai/stable-diffusion-xl-base-1.0'); ?>>Stable Diffusion XL (Recommandé)</option>
                            <option value="stability-ai/sdxl" <?php selected(get_option('seoai_settings')['image_model'] ?? 'stability-ai/stable-diffusion-xl-base-1.0', 'stability-ai/sdxl'); ?>>Stable Diffusion XL (Dernière version)</option>
                            <option value="midjourney/midjourney-diffusion" <?php selected(get_option('seoai_settings')['image_model'] ?? 'stability-ai/stable-diffusion-xl-base-1.0', 'midjourney/midjourney-diffusion'); ?>>Midjourney Diffusion</option>
                        </select>
                        <p class="description">Sélectionnez le modèle de génération d'images à utiliser.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Dimensions des images</th>
                    <td>
                        <select name="image_dimensions" class="regular-text">
                            <option value="1024x768" <?php selected(get_option('seoai_settings')['image_dimensions'] ?? '1024x768', '1024x768'); ?>>1024x768 (Paysage)</option>
                            <option value="768x1024" <?php selected(get_option('seoai_settings')['image_dimensions'] ?? '1024x768', '768x1024'); ?>>768x1024 (Portrait)</option>
                            <option value="1024x1024" <?php selected(get_option('seoai_settings')['image_dimensions'] ?? '1024x768', '1024x1024'); ?>>1024x1024 (Carré)</option>
                            <option value="1280x720" <?php selected(get_option('seoai_settings')['image_dimensions'] ?? '1024x768', '1280x720'); ?>>1280x720 (16:9)</option>
                        </select>
                        <p class="description">Dimensions des images générées.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Positions des images</th>
                    <td>
                        <label><input type="checkbox" name="image_positions[]" value="after_first_paragraph" <?php checked(in_array('after_first_paragraph', get_option('seoai_settings')['image_positions'] ?? array('after_first_paragraph', 'middle', 'conclusion'))); ?>> Après le premier paragraphe</label><br>
                        <label><input type="checkbox" name="image_positions[]" value="middle" <?php checked(in_array('middle', get_option('seoai_settings')['image_positions'] ?? array('after_first_paragraph', 'middle', 'conclusion'))); ?>> Au milieu de l'article</label><br>
                        <label><input type="checkbox" name="image_positions[]" value="conclusion" <?php checked(in_array('conclusion', get_option('seoai_settings')['image_positions'] ?? array('after_first_paragraph', 'middle', 'conclusion'))); ?>> À la fin de l'article</label><br>
                        <p class="description">Positions où les images générées seront insérées dans le contenu.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Nombre d'images</th>
                    <td>
                        <select name="image_count" class="regular-text">
                            <option value="1" <?php selected(get_option('seoai_settings')['image_count'] ?? '3', '1'); ?>>1 image</option>
                            <option value="2" <?php selected(get_option('seoai_settings')['image_count'] ?? '3', '2'); ?>>2 images</option>
                            <option value="3" <?php selected(get_option('seoai_settings')['image_count'] ?? '3', '3'); ?>>3 images</option>
                            <option value="4" <?php selected(get_option('seoai_settings')['image_count'] ?? '3', '4'); ?>>4 images</option>
                            <option value="5" <?php selected(get_option('seoai_settings')['image_count'] ?? '3', '5'); ?>>5 images</option>
                        </select>
                        <p class="description">Nombre d'images à générer par article.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="advanced-settings" class="tab-content">
            <h2>Paramètres avancés</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Niveau de journalisation</th>
                    <td>
                        <select name="log_level" class="regular-text">
                            <option value="ERROR" <?php selected(get_option('seoai_settings')['log_level'] ?? 'INFO', 'ERROR'); ?>>Erreurs uniquement</option>
                            <option value="INFO" <?php selected(get_option('seoai_settings')['log_level'] ?? 'INFO', 'INFO'); ?>>Informations (recommandé)</option>
                            <option value="DEBUG" <?php selected(get_option('seoai_settings')['log_level'] ?? 'INFO', 'DEBUG'); ?>>Débogage (verbeux)</option>
                        </select>
                        <p class="description">Niveau de détail des journaux générés par le plugin.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Délai d'expiration API</th>
                    <td>
                        <input type="number" name="api_timeout" min="30" max="300" step="10" value="<?php echo esc_attr(get_option('seoai_settings')['api_timeout'] ?? 60); ?>" class="small-text" /> secondes
                        <p class="description">Délai d'attente maximum pour les requêtes API.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Mise en cache</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_caching" value="1" <?php checked(get_option('seoai_settings')['enable_caching'] ?? true, true); ?> />
                            Activer la mise en cache des résultats d'API
                        </label>
                        <p class="description">Améliore les performances et réduit les coûts d'API en mettant en cache les résultats.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Durée de conservation des logs</th>
                    <td>
                        <select name="log_retention" class="regular-text">
                            <option value="7" <?php selected(get_option('seoai_settings')['log_retention'] ?? '30', '7'); ?>>7 jours</option>
                            <option value="14" <?php selected(get_option('seoai_settings')['log_retention'] ?? '30', '14'); ?>>14 jours</option>
                            <option value="30" <?php selected(get_option('seoai_settings')['log_retention'] ?? '30', '30'); ?>>30 jours</option>
                            <option value="90" <?php selected(get_option('seoai_settings')['log_retention'] ?? '30', '90'); ?>>90 jours</option>
                            <option value="0" <?php selected(get_option('seoai_settings')['log_retention'] ?? '30', '0'); ?>>Indéfiniment</option>
                        </select>
                        <p class="description">Durée de conservation des fichiers de logs avant suppression automatique.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les modifications">
        </p>
    </form>
    
    <div class="seoai-settings-footer">
        <p><strong>Note :</strong> Les modifications des paramètres prendront effet immédiatement.</p>
        <p>Version du plugin: <?php echo SEOAI_VERSION; ?> | <a href="https://example.com/documentation" target="_blank">Documentation</a> | <a href="https://example.com/support" target="_blank">Support</a></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Mise à jour des valeurs des sliders
    $('input[type="range"]').on('input', function() {
        $(this).next('.range-value').text($(this).val());
    });
});
</script>
