<div class="wrap seoai-admin">
    <h1>
        <span class="dashicons dashicons-superhero-alt"></span>
        SEO AI Optimizer & Visual Enhancer
        <span class="version-badge">v<?php echo SEOAI_VERSION; ?></span>
    </h1>
    
    <div class="seoai-dashboard-header">
        <div class="seoai-stats">
            <div class="stat-card">
                <span class="stat-icon dashicons dashicons-edit"></span>
                <div class="stat-content">
                    <span class="stat-value"><?php echo wp_count_posts()->publish; ?></span>
                    <span class="stat-label">Articles publiés</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon dashicons dashicons-chart-line"></span>
                <div class="stat-content">
                    <span class="stat-value"><?php 
                        global $wpdb;
                        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}seoai_logs WHERE status = 'success'");
                        echo $count ? $count : '0';
                    ?></span>
                    <span class="stat-label">Optimisations réussies</span>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon dashicons dashicons-images-alt2"></span>
                <div class="stat-content">
                    <span class="stat-value"><?php 
                        $args = array(
                            'post_type' => 'attachment',
                            'meta_query' => array(
                                array(
                                    'key' => '_wp_attachment_metadata',
                                    'value' => 'seoai_generated',
                                    'compare' => 'LIKE'
                                )
                            ),
                            'posts_per_page' => -1
                        );
                        $query = new WP_Query($args);
                        echo $query->found_posts;
                    ?></span>
                    <span class="stat-label">Images générées</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="seoai-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#content-selection" class="nav-tab nav-tab-active">
                <span class="dashicons dashicons-admin-page"></span> Sélection de contenu
            </a>
            <a href="#bulk-processing" class="nav-tab">
                <span class="dashicons dashicons-performance"></span> Traitement en masse
            </a>
            <a href="#seoai-logs" class="nav-tab">
                <span class="dashicons dashicons-list-view"></span> Logs
            </a>
        </nav>
        
        <div id="content-selection" class="tab-content active">
            <h2>Sélectionner le contenu à optimiser</h2>
            
            <div class="filters">
                <div class="filter-group">
                    <label for="post-type-filter">Type:</label>
                    <select id="post-type-filter">
                        <option value="post">Articles</option>
                        <option value="page">Pages</option>
                        <?php 
                        // Récupérer les types de posts personnalisés
                        $custom_post_types = get_post_types(array('_builtin' => false, 'public' => true), 'objects');
                        foreach ($custom_post_types as $post_type) {
                            echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category-filter">Catégorie:</label>
                    <select id="category-filter">
                        <option value="">Toutes les catégories</option>
                        <?php
                        $categories = get_categories();
                        foreach ($categories as $category) {
                            echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status-filter">Statut:</label>
                    <select id="status-filter">
                        <option value="publish">Publié</option>
                        <option value="draft">Brouillon</option>
                        <option value="pending">En attente</option>
                        <option value="future">Planifié</option>
                        <option value="any">Tous</option>
                    </select>
                </div>
                
                <div class="filter-group search-group">
                    <label for="post-search">Recherche:</label>
                    <input type="text" id="post-search" placeholder="Rechercher..." />
                </div>
                
                <button id="load-posts" class="button">
                    <span class="dashicons dashicons-update-alt"></span> Actualiser
                </button>
            </div>
            
            <div class="selection-info" style="display: none;">
                <span class="selected-count">0</span> articles sélectionnés
            </div>
            
            <div id="posts-list" class="posts-container">
                <!-- Les posts seront chargés ici via AJAX -->
                <div class="seoai-loading">
                    <div class="spinner"></div>
                    <p>Chargement des contenus...</p>
                </div>
            </div>
            
            <div class="bulk-actions">
                <button id="select-all" class="button">
                    <span class="dashicons dashicons-yes"></span> Tout sélectionner
                </button>
                <button id="deselect-all" class="button">
                    <span class="dashicons dashicons-no"></span> Tout désélectionner
                </button>
                <button id="process-selected" class="button button-primary" disabled>
                    <span class="dashicons dashicons-superhero-alt"></span> Optimiser la sélection
                </button>
            </div>
        </div>
        
        <div id="bulk-processing" class="tab-content">
            <h2>Traitement en cours</h2>
            
            <div id="progress-container" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text">0% - En préparation...</div>
            </div>
            
            <div id="processing-summary" style="display: none;">
                <!-- Le résumé du traitement sera affiché ici -->
            </div>
            
            <div id="processing-logs" class="logs-container">
                <!-- Les logs de traitement apparaîtront ici -->
                <div class="log-entry info">Sélectionnez des articles et cliquez sur "Optimiser la sélection" pour commencer.</div>
            </div>
            
            <button id="cancel-processing" class="button button-secondary" style="display: none;">
                <span class="dashicons dashicons-no-alt"></span> Annuler le traitement
            </button>
        </div>
        
        <div id="seoai-logs" class="tab-content">
            <h2>Historique des traitements</h2>
            
            <div class="logs-filter">
                <select id="log-type-filter">
                    <option value="all">Tous les types</option>
                    <option value="success">Succès uniquement</option>
                    <option value="error">Erreurs uniquement</option>
                </select>
                
                <select id="log-date-filter">
                    <option value="all">Toutes les dates</option>
                    <option value="today">Aujourd'hui</option>
                    <option value="yesterday">Hier</option>
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                </select>
                
                <button id="refresh-logs" class="button">
                    <span class="dashicons dashicons-update-alt"></span> Actualiser
                </button>
                
                <button id="clear-logs" class="button">
                    <span class="dashicons dashicons-trash"></span> Vider les logs
                </button>
            </div>
            
            <div id="logs-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Article</th>
                            <th>Action</th>
                            <th>Statut</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        <?php
                        global $wpdb;
                        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}seoai_logs ORDER BY created_at DESC LIMIT 20");
                        
                        if ($logs) {
                            foreach ($logs as $log) {
                                $post_title = get_the_title($log->post_id);
                                $status_class = $log->status === 'success' ? 'success' : 'error';
                                
                                echo '<tr>';
                                echo '<td>' . date('d/m/Y H:i', strtotime($log->created_at)) . '</td>';
                                echo '<td><a href="' . get_edit_post_link($log->post_id) . '">' . esc_html($post_title) . '</a></td>';
                                echo '<td>' . esc_html($log->action) . '</td>';
                                echo '<td><span class="log-status ' . $status_class . '">' . esc_html($log->status) . '</span></td>';
                                echo '<td>' . esc_html($log->message) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5">Aucun log disponible</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="seoai-admin-footer">
        <p>
            <strong>SEO Optimizer & AI Visual Enhancer</strong> - Optimisez votre contenu et générez des images avec l'IA
            | <a href="<?php echo admin_url('admin.php?page=seoai-settings'); ?>">Paramètres</a>
            | <a href="https://example.com/documentation" target="_blank">Documentation</a>
            | <a href="https://example.com/support" target="_blank">Support</a>
        </p>
    </div>
</div>

<style>
/* Styles spécifiques à cette page */
.seoai-dashboard-header {
    margin: 20px 0;
}

.seoai-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    flex: 1;
    min-width: 200px;
}

.stat-icon {
    font-size: 36px;
    margin-right: 15px;
    color: #2271b1;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #1d2327;
}

.stat-label {
    display: block;
    color: #50575e;
    font-size: 14px;
}

.version-badge {
    font-size: 12px;
    background: #f0f0f1;
    padding: 3px 8px;
    border-radius: 10px;
    margin-left: 10px;
    vertical-align: middle;
    color: #50575e;
}

.filter-group {
    display: flex;
    flex-direction: column;
    margin-right: 15px;
}

.filter-group label {
    margin-bottom: 5px;
    font-weight: 500;
}

.search-group {
    flex-grow: 1;
    max-width: 300px;
}

.selection-info {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 10px 15px;
    margin: 15px 0;
    display: flex;
    align-items: center;
}

.selected-count {
    font-weight: bold;
    font-size: 16px;
    margin-right: 5px;
}

.seoai-loading {
    text-align: center;
    padding: 30px;
}

.seoai-loading .spinner {
    display: inline-block;
    width: 30px;
    height: 30px;
    border: 3px solid rgba(0,0,0,0.1);
    border-radius: 50%;
    border-top-color: #2271b1;
    animation: spin 1s ease-in-out infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.post-item {
    position: relative;
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.post-item.animated {
    opacity: 1;
    transform: translateY(0);
}

.post-actions {
    position: absolute;
    top: 15px;
    right: 15px;
    display: flex;
    gap: 5px;
}

.button-link {
    color: #2271b1;
    text-decoration: none;
    padding: 3px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.button-link:hover {
    background: rgba(34, 113, 177, 0.1);
    color: #135e96;
}

.post-status {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    background: #f0f0f1;
    color: #50575e;
    margin-left: 5px;
}

.summary-box {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}

.summary-stats {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
    flex-wrap: wrap;
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    border-radius: 6px;
    min-width: 100px;
    flex: 1;
}

.stat-item.success {
    background: rgba(0, 163, 42, 0.1);
}

.stat-item.error {
    background: rgba(214, 54, 56, 0.1);
}

.stat-item.total {
    background: rgba(34, 113, 177, 0.1);
}

.stat-item.time {
    background: rgba(219, 166, 23, 0.1);
}

.summary-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
}

.logs-filter {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    align-items: center;
}

.log-status {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}

.log-status.success {
    background: rgba(0, 163, 42, 0.1);
    color: #00a32a;
}

.log-status.error {
    background: rgba(214, 54, 56, 0.1);
    color: #d63638;
}

.seoai-admin-footer {
    margin-top: 30px;
    padding-top: 15px;
    border-top: 1px solid #dcdcde;
    color: #50575e;
    font-size: 13px;
    text-align: center;
}

.dashicons-update-alt.spin {
    animation: spin 2s linear infinite;
}

@media screen and (max-width: 782px) {
    .seoai-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .filters {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-group {
        width: 100%;
        margin-right: 0;
    }
    
    .search-group {
        max-width: 100%;
    }
    
    .summary-stats {
        flex-direction: column;
        gap: 10px;
    }
}
</style>
