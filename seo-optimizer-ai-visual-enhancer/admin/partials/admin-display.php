<div class="wrap seoai-admin">
    <h1>SEO AI Optimizer & Visual Enhancer</h1>
    
    <div class="seoai-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#content-selection" class="nav-tab nav-tab-active">Sélection de contenu</a>
            <a href="#bulk-processing" class="nav-tab">Traitement en masse</a>
        </nav>
        
        <div id="content-selection" class="tab-content active">
            <h2>Sélectionner le contenu à optimiser</h2>
            
            <div class="filters">
                <select id="post-type-filter">
                    <option value="post">Articles</option>
                    <option value="page">Pages</option>
                </select>
                
                <select id="category-filter">
                    <option value="">Toutes les catégories</option>
                    <?php
                    $categories = get_categories();
                    foreach ($categories as $category) {
                        echo '<option value="' . $category->term_id . '">' . $category->name . '</option>';
                    }
                    ?>
                </select>
                
                <button id="load-posts" class="button">Charger les posts</button>
            </div>
            
            <div id="posts-list" class="posts-container">
                <!-- Les posts seront chargés ici via AJAX -->
            </div>
            
            <div class="bulk-actions">
                <button id="select-all" class="button">Tout sélectionner</button>
                <button id="deselect-all" class="button">Tout désélectionner</button>
                <button id="process-selected" class="button button-primary">Optimiser la sélection</button>
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
            
            <div id="processing-logs" class="logs-container">
                <!-- Les logs de traitement apparaîtront ici -->
            </div>
            
            <button id="cancel-processing" class="button" style="display: none;">Annuler le traitement</button>
        </div>
    </div>
</div>
