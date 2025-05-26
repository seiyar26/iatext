<?php
// Protection contre l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="seoai-live-logs-container">
        <div class="seoai-live-logs-header">
            <h2>Logs en direct</h2>
            <div class="seoai-live-logs-controls">
                <button id="seoai-pause-logs" class="button"><?php _e('Pause', 'seo-optimizer-ai-visual-enhancer'); ?></button>
                <button id="seoai-resume-logs" class="button" style="display:none;"><?php _e('Reprendre', 'seo-optimizer-ai-visual-enhancer'); ?></button>
                <button id="seoai-clear-logs" class="button"><?php _e('Effacer', 'seo-optimizer-ai-visual-enhancer'); ?></button>
                <select id="seoai-log-level">
                    <option value="all"><?php _e('Tous les niveaux', 'seo-optimizer-ai-visual-enhancer'); ?></option>
                    <option value="INFO"><?php _e('Info', 'seo-optimizer-ai-visual-enhancer'); ?></option>
                    <option value="DEBUG"><?php _e('Debug', 'seo-optimizer-ai-visual-enhancer'); ?></option>
                    <option value="SUCCESS"><?php _e('Succès', 'seo-optimizer-ai-visual-enhancer'); ?></option>
                    <option value="WARNING"><?php _e('Avertissement', 'seo-optimizer-ai-visual-enhancer'); ?></option>
                    <option value="ERROR"><?php _e('Erreur', 'seo-optimizer-ai-visual-enhancer'); ?></option>
                </select>
                <input type="text" id="seoai-log-filter" placeholder="Filtrer les logs..." />
            </div>
        </div>
        
        <div id="seoai-live-logs-stats">
            <span class="seoai-log-stat info-count">Info: <b>0</b></span>
            <span class="seoai-log-stat debug-count">Debug: <b>0</b></span>
            <span class="seoai-log-stat success-count">Succès: <b>0</b></span>
            <span class="seoai-log-stat warning-count">Avertissement: <b>0</b></span>
            <span class="seoai-log-stat error-count">Erreur: <b>0</b></span>
        </div>
        
        <div class="seoai-live-logs-wrapper">
            <pre id="seoai-live-logs-content" class="seoai-logs-pre"></pre>
        </div>
        
        <div class="seoai-live-logs-status">
            <span id="seoai-logs-status">Connecté - Actualisation automatique toutes les 2 secondes</span>
            <span id="seoai-logs-last-update"></span>
        </div>
    </div>
</div>
