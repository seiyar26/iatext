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
        
        <table class="form-table">
            <tr>
                <th scope="row">Nom du plugin</th>
                <td>
                    <input type="text" name="plugin_name" value="<?php echo esc_attr(get_option('seoai_settings')['plugin_name'] ?? 'SEO Optimizer & AI Visual Enhancer'); ?>" class="regular-text" />
                    <p class="description">Nom personnalisé du plugin dans le menu WordPress</p>
                </td>
            </tr>
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
                <th scope="row">Niveau de journalisation</th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_detailed_logging" value="1" <?php checked(get_option('seoai_settings')['enable_detailed_logging'] ?? false, true); ?> />
                        Activer la journalisation détaillée
                    </label>
                    <p class="description">Enregistrer des journaux détaillés pour le débogage (peut affecter les performances)</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les modifications">
        </p>
    </form>
    
    <div class="seoai-settings-footer">
        <p><strong>Note :</strong> Les modifications des paramètres prendront effet immédiatement.</p>
    </div>
</div>
