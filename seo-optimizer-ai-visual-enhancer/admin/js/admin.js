jQuery(document).ready(function($) {
    
    // Variables globales
    let processingActive = false;
    let selectedPosts = [];
    
    // Gestion des onglets
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Chargement des posts
    $('#load-posts').on('click', function() {
        const postType = $('#post-type-filter').val();
        const category = $('#category-filter').val();
        
        $.ajax({
            url: seoai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'seoai_get_posts',
                post_type: postType,
                category: category,
                nonce: seoai_ajax.nonce
            },
            beforeSend: function() {
                $('#load-posts').prop('disabled', true).text('Chargement...');
            },
            success: function(response) {
                if (response.success) {
                    displayPosts(response.data);
                } else {
                    alert('Erreur lors du chargement des posts');
                }
            },
            complete: function() {
                $('#load-posts').prop('disabled', false).text('Charger les posts');
            }
        });
    });
    
    // Affichage des posts
    function displayPosts(posts) {
        let html = '<div class="posts-grid">';
        
        posts.forEach(function(post) {
            html += `
                <div class="post-item" data-post-id="${post.ID}">
                    <label class="post-checkbox">
                        <input type="checkbox" value="${post.ID}" />
                        <div class="post-info">
                            <h3>${post.post_title}</h3>
                            <p>ID: ${post.ID} | Date: ${post.post_date}</p>
                            <div class="post-excerpt">${post.post_excerpt || 'Pas d\'extrait disponible'}</div>
                        </div>
                    </label>
                </div>
            `;
        });
        
        html += '</div>';
        $('#posts-list').html(html);
        
        // Event listeners pour les checkboxes
        $('.post-item input[type="checkbox"]').on('change', function() {
            updateSelectedPosts();
        });
    }
    
    // Mise à jour de la liste des posts sélectionnés
    function updateSelectedPosts() {
        selectedPosts = [];
        $('.post-item input[type="checkbox"]:checked').each(function() {
            selectedPosts.push($(this).val());
        });
    }
    
    // Sélectionner tout
    $('#select-all').on('click', function() {
        $('.post-item input[type="checkbox"]').prop('checked', true);
        updateSelectedPosts();
    });
    
    // Désélectionner tout
    $('#deselect-all').on('click', function() {
        $('.post-item input[type="checkbox"]').prop('checked', false);
        updateSelectedPosts();
    });
    
    // Traitement des posts sélectionnés
    $('#process-selected').on('click', function() {
        if (selectedPosts.length === 0) {
            alert('Veuillez sélectionner au moins un post à traiter');
            return;
        }
        
        if (processingActive) {
            alert('Un traitement est déjà en cours');
            return;
        }
        
        if (confirm(`Voulez-vous vraiment optimiser ${selectedPosts.length} post(s) ? Cette action est irréversible (sauf restauration manuelle).`)) {
            startBulkProcessing();
        }
    });
    
    // Démarrage du traitement en masse
    function startBulkProcessing() {
        processingActive = true;
        
        // Basculer vers l'onglet de traitement
        $('.nav-tab[href="#bulk-processing"]').click();
        
        // Afficher la barre de progression
        $('#progress-container').show();
        $('#cancel-processing').show();
        
        // Réinitialiser les logs
        $('#processing-logs').html('<div class="log-entry info">Démarrage du traitement...</div>');
        
        // Traiter les posts un par un
        processPostsSequentially(0);
    }
    
    // Traitement séquentiel des posts
    function processPostsSequentially(index) {
        if (index >= selectedPosts.length || !processingActive) {
            // Traitement terminé
            finishProcessing();
            return;
        }
        
        const postId = selectedPosts[index];
        const progress = Math.round((index / selectedPosts.length) * 100);
        
        // Mise à jour de la progression
        updateProgress(progress, `Traitement du post ${postId}...`);
        
        // Log du début de traitement
        addLog(`Traitement du post ID: ${postId}`, 'info');
        
        $.ajax({
            url: seoai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'seoai_process_content',
                post_ids: postId,
                nonce: seoai_ajax.nonce
            },
            timeout: 120000, // 2 minutes timeout
            success: function(response) {
                if (response.success && response.data[0].success) {
                    addLog(`✅ Post ${postId} optimisé avec succès`, 'success');
                } else {
                    const errorMsg = response.data ? response.data[0].message : 'Erreur inconnue';
                    addLog(`❌ Erreur pour le post ${postId}: ${errorMsg}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                addLog(`❌ Erreur technique pour le post ${postId}: ${error}`, 'error');
            },
            complete: function() {
                // Passer au post suivant après un délai
                setTimeout(() => {
                    processPostsSequentially(index + 1);
                }, 1000);
            }
        });
    }
    
    // Mise à jour de la barre de progression
    function updateProgress(percentage, text) {
        $('.progress-fill').css('width', percentage + '%');
        $('.progress-text').text(percentage + '% - ' + text);
    }
    
    // Ajout d'un log
    function addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = `<div class="log-entry ${type}">[${timestamp}] ${message}</div>`;
        $('#processing-logs').append(logEntry);
        
        // Scroll vers le bas
        $('#processing-logs').scrollTop($('#processing-logs')[0].scrollHeight);
    }
    
    // Fin du traitement
    function finishProcessing() {
        processingActive = false;
        updateProgress(100, 'Traitement terminé !');
        addLog('🎉 Traitement en masse terminé', 'success');
        $('#cancel-processing').hide();
        
        // Recharger la liste des posts
        $('#load-posts').click();
    }
    
    // Annulation du traitement
    $('#cancel-processing').on('click', function() {
        if (confirm('Voulez-vous vraiment annuler le traitement en cours ?')) {
            processingActive = false;
            addLog('⚠️ Traitement annulé par l\'utilisateur', 'warning');
            $('#cancel-processing').hide();
        }
    });
    
    // Chargement initial
    $('#load-posts').click();
});
