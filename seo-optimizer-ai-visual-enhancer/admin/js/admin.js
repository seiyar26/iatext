/**
 * Script principal d'administration pour SEO Optimizer & AI Visual Enhancer
 * 
 * Gère l'interface utilisateur, les interactions AJAX et le traitement des contenus
 */
jQuery(document).ready(function($) {
    
    // =========================================
    // Variables globales et initialisation
    // =========================================
    let processingActive = false;
    let selectedPosts = [];
    let processingStats = {
        success: 0,
        error: 0,
        total: 0,
        startTime: null
    };
    
    // Initialisation des tooltips
    if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // =========================================
    // Gestion des onglets
    // =========================================
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        
        // Mettre à jour l'URL avec le hash pour conserver l'onglet actif lors des rechargements
        if (history.pushState) {
            history.pushState(null, null, target);
        } else {
            location.hash = target;
        }
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Restaurer l'onglet actif depuis l'URL lors du chargement
    function restoreActiveTab() {
        const hash = window.location.hash;
        if (hash && $(hash).length) {
            $('.nav-tab[href="' + hash + '"]').click();
        }
    }
    
    // =========================================
    // Gestion des posts
    // =========================================
    
    // Chargement des posts avec recherche et filtres
    $('#load-posts').on('click', function() {
        loadPosts();
    });
    
    // Recherche en temps réel
    $('#post-search').on('input', debounce(function() {
        if ($(this).val().length >= 2 || $(this).val().length === 0) {
            loadPosts();
        }
    }, 500));
    
    // Changement de filtre
    $('#post-type-filter, #category-filter, #status-filter').on('change', function() {
        loadPosts();
    });
    
    // Fonction de chargement des posts
    function loadPosts() {
        const postType = $('#post-type-filter').val();
        const category = $('#category-filter').val();
        const status = $('#status-filter').val() || 'publish';
        const search = $('#post-search').val() || '';
        
        // Afficher un indicateur de chargement
        $('#posts-list').html('<div class="seoai-loading"><div class="spinner"></div><p>Chargement des contenus...</p></div>');
        
        $.ajax({
            url: seoai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'seoai_get_posts',
                post_type: postType,
                category: category,
                status: status,
                search: search,
                nonce: seoai_ajax.nonce
            },
            beforeSend: function() {
                $('#load-posts').prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> Chargement...');
            },
            success: function(response) {
                if (response.success) {
                    displayPosts(response.data);
                    
                    // Afficher un message si aucun résultat
                    if (response.data.length === 0) {
                        $('#posts-list').html('<div class="seoai-notice info">Aucun contenu trouvé avec ces critères.</div>');
                    }
                } else {
                    showNotification('Erreur lors du chargement des posts: ' + (response.data?.message || 'Erreur inconnue'), 'error');
                    $('#posts-list').html('<div class="seoai-notice error">Erreur lors du chargement des contenus.</div>');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Erreur de connexion: ' + error, 'error');
                $('#posts-list').html('<div class="seoai-notice error">Erreur de connexion au serveur.</div>');
            },
            complete: function() {
                $('#load-posts').prop('disabled', false).html('<span class="dashicons dashicons-update-alt"></span> Actualiser');
            }
        });
    }
    
    // Affichage des posts avec animations
    function displayPosts(posts) {
        if (!posts || posts.length === 0) {
            return;
        }
        
        let html = '<div class="posts-grid">';
        
        posts.forEach(function(post, index) {
            // Extraire la date formatée
            const postDate = new Date(post.post_date);
            const formattedDate = postDate.toLocaleDateString();
            
            // Préparer l'extrait
            const excerpt = post.post_excerpt || post.post_content.substring(0, 150) || 'Pas d\'extrait disponible';
            
            // Créer l'élément HTML avec animation différée
            html += `
                <div class="post-item" data-post-id="${post.ID}" style="animation-delay: ${index * 0.05}s">
                    <label class="post-checkbox">
                        <input type="checkbox" value="${post.ID}" />
                        <div class="post-info">
                            <h3>${post.post_title || 'Sans titre'}</h3>
                            <p>
                                <span class="post-id">ID: ${post.ID}</span> | 
                                <span class="post-date">Date: ${formattedDate}</span>
                                ${post.post_status !== 'publish' ? `<span class="post-status">${post.post_status}</span>` : ''}
                            </p>
                            <div class="post-excerpt">${excerpt}</div>
                        </div>
                    </label>
                    <div class="post-actions">
                        <a href="${post.edit_url || '#'}" target="_blank" class="button-link" title="Éditer">
                            <span class="dashicons dashicons-edit"></span>
                        </a>
                        <a href="${post.permalink || '#'}" target="_blank" class="button-link" title="Voir">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $('#posts-list').html(html);
        
        // Ajouter la classe pour l'animation d'entrée
        setTimeout(function() {
            $('.post-item').addClass('animated');
        }, 10);
        
        // Event listeners pour les checkboxes
        $('.post-item input[type="checkbox"]').on('change', function() {
            const postItem = $(this).closest('.post-item');
            if ($(this).is(':checked')) {
                postItem.addClass('selected');
            } else {
                postItem.removeClass('selected');
            }
            updateSelectedPosts();
        });
        
        // Mettre à jour le compteur de sélection
        updateSelectedCounter();
    }
    
    // Mise à jour de la liste des posts sélectionnés
    function updateSelectedPosts() {
        selectedPosts = [];
        $('.post-item input[type="checkbox"]:checked').each(function() {
            selectedPosts.push($(this).val());
        });
        
        // Mettre à jour le compteur et l'état du bouton de traitement
        updateSelectedCounter();
        
        // Activer/désactiver le bouton de traitement
        $('#process-selected').prop('disabled', selectedPosts.length === 0);
    }
    
    // Mise à jour du compteur de sélection
    function updateSelectedCounter() {
        const count = selectedPosts.length;
        $('.selected-count').text(count);
        
        if (count > 0) {
            $('.selection-info').fadeIn();
        } else {
            $('.selection-info').fadeOut();
        }
    }
    
    // Sélectionner tout
    $('#select-all').on('click', function() {
        $('.post-item input[type="checkbox"]').prop('checked', true);
        $('.post-item').addClass('selected');
        updateSelectedPosts();
        showNotification('Tous les articles ont été sélectionnés', 'info');
    });
    
    // Désélectionner tout
    $('#deselect-all').on('click', function() {
        $('.post-item input[type="checkbox"]').prop('checked', false);
        $('.post-item').removeClass('selected');
        updateSelectedPosts();
        showNotification('Sélection effacée', 'info');
    });
    
    // =========================================
    // Traitement des contenus
    // =========================================
    
    // Traitement des posts sélectionnés
    $('#process-selected').on('click', function() {
        if (selectedPosts.length === 0) {
            showNotification('Veuillez sélectionner au moins un article à traiter', 'warning');
            return;
        }
        
        if (processingActive) {
            showNotification('Un traitement est déjà en cours', 'warning');
            return;
        }
        
        // Confirmation avec détails
        const confirmMessage = `
            Vous allez optimiser ${selectedPosts.length} article(s).
            
            Cette action va:
            - Analyser et optimiser le contenu pour le SEO
            - Générer des images avec l'IA (si configuré)
            - Restructurer le contenu avec les images
            
            Une sauvegarde sera créée automatiquement.
            Continuer?
        `;
        
        if (confirm(confirmMessage)) {
            startBulkProcessing();
        }
    });
    
    // Démarrage du traitement en masse
    function startBulkProcessing() {
        processingActive = true;
        
        // Réinitialiser les statistiques
        processingStats = {
            success: 0,
            error: 0,
            total: selectedPosts.length,
            startTime: new Date()
        };
        
        // Basculer vers l'onglet de traitement
        $('.nav-tab[href="#bulk-processing"]').click();
        
        // Afficher la barre de progression
        $('#progress-container').show();
        $('#cancel-processing').show();
        $('#processing-summary').hide();
        
        // Réinitialiser les logs
        $('#processing-logs').html('');
        addLog('🚀 Démarrage du traitement de ' + selectedPosts.length + ' article(s)...', 'info');
        
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
        const currentPosition = index + 1;
        
        // Mise à jour de la progression
        updateProgress(progress, `Traitement de l'article ${currentPosition}/${selectedPosts.length}`);
        
        // Log du début de traitement
        addLog(`⏳ Traitement de l'article ID: ${postId} (${currentPosition}/${selectedPosts.length})`, 'info');
        
        $.ajax({
            url: seoai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'seoai_process_content',
                post_ids: postId,
                nonce: seoai_ajax.nonce
            },
            timeout: 180000, // 3 minutes timeout
            success: function(response) {
                if (response.success && response.data[0].success) {
                    processingStats.success++;
                    addLog(`✅ Article ${postId} optimisé avec succès`, 'success');
                } else {
                    processingStats.error++;
                    const errorMsg = response.data ? response.data[0].message : 'Erreur inconnue';
                    addLog(`❌ Erreur pour l'article ${postId}: ${errorMsg}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                processingStats.error++;
                addLog(`❌ Erreur technique pour l'article ${postId}: ${error}`, 'error');
                
                // Détails supplémentaires pour le débogage
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data && response.data.message) {
                            addLog(`   Détail: ${response.data.message}`, 'error');
                        }
                    } catch (e) {
                        // Ignorer les erreurs de parsing
                    }
                }
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
        
        // Changer la couleur en fonction de la progression
        if (percentage < 30) {
            $('.progress-fill').css('background', 'linear-gradient(90deg, #2271b1, #3858e9)');
        } else if (percentage < 70) {
            $('.progress-fill').css('background', 'linear-gradient(90deg, #3858e9, #8183ff)');
        } else {
            $('.progress-fill').css('background', 'linear-gradient(90deg, #8183ff, #00a32a)');
        }
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
        
        // Calculer le temps écoulé
        const endTime = new Date();
        const elapsedTime = Math.round((endTime - processingStats.startTime) / 1000);
        const minutes = Math.floor(elapsedTime / 60);
        const seconds = elapsedTime % 60;
        const timeString = minutes > 0 ? `${minutes}m ${seconds}s` : `${seconds}s`;
        
        // Ajouter un résumé
        addLog(`🎉 Traitement terminé en ${timeString}`, 'success');
        addLog(`📊 Résumé: ${processingStats.success} succès, ${processingStats.error} erreurs sur ${processingStats.total} articles`, 'info');
        
        // Afficher le résumé
        $('#processing-summary').html(`
            <div class="summary-box">
                <h3>Résumé du traitement</h3>
                <div class="summary-stats">
                    <div class="stat-item success">
                        <span class="stat-value">${processingStats.success}</span>
                        <span class="stat-label">Succès</span>
                    </div>
                    <div class="stat-item error">
                        <span class="stat-value">${processingStats.error}</span>
                        <span class="stat-label">Erreurs</span>
                    </div>
                    <div class="stat-item total">
                        <span class="stat-value">${processingStats.total}</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-item time">
                        <span class="stat-value">${timeString}</span>
                        <span class="stat-label">Durée</span>
                    </div>
                </div>
                <div class="summary-actions">
                    <button id="return-to-selection" class="button">Retour à la sélection</button>
                    <button id="view-logs" class="button">Voir les logs complets</button>
                </div>
            </div>
        `).show();
        
        $('#cancel-processing').hide();
        
        // Notification
        showNotification(`Traitement terminé: ${processingStats.success} succès, ${processingStats.error} erreurs`, processingStats.error > 0 ? 'warning' : 'success');
    }
    
    // Retour à la sélection
    $(document).on('click', '#return-to-selection', function() {
        $('.nav-tab[href="#content-selection"]').click();
        // Recharger la liste des posts
        loadPosts();
    });
    
    // Voir les logs complets
    $(document).on('click', '#view-logs', function() {
        $('.nav-tab[href="#seoai-logs"]').click();
    });
    
    // Annulation du traitement
    $('#cancel-processing').on('click', function() {
        if (confirm('Voulez-vous vraiment annuler le traitement en cours ?')) {
            processingActive = false;
            addLog('⚠️ Traitement annulé par l\'utilisateur', 'warning');
            $('#cancel-processing').hide();
            
            // Afficher un résumé partiel
            const processedCount = processingStats.success + processingStats.error;
            addLog(`📊 Résumé partiel: ${processingStats.success} succès, ${processingStats.error} erreurs sur ${processedCount} articles traités (sur ${processingStats.total} prévus)`, 'info');
            
            showNotification('Traitement annulé', 'warning');
        }
    });
    
    // =========================================
    // Utilitaires
    // =========================================
    
    // Fonction pour afficher une notification
    function showNotification(message, type = 'info') {
        // Vérifier si la fonction wp.data est disponible (WordPress 5.0+)
        if (wp && wp.data && wp.data.dispatch('core/notices')) {
            const { createNotice } = wp.data.dispatch('core/notices');
            createNotice(type, message, {
                isDismissible: true,
                type: 'snackbar'
            });
            return;
        }
        
        // Fallback pour les anciennes versions de WordPress
        const noticeId = 'seoai-notice-' + Date.now();
        const notice = $(`<div id="${noticeId}" class="seoai-notice ${type}"><p>${message}</p></div>`);
        
        // Ajouter la notification en haut de la page
        $('.wrap').first().prepend(notice);
        
        // Faire disparaître la notification après 5 secondes
        setTimeout(function() {
            notice.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Fonction debounce pour limiter les appels fréquents
    function debounce(func, wait, immediate) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    // =========================================
    // Initialisation
    // =========================================
    
    // Restaurer l'onglet actif
    restoreActiveTab();
    
    // Chargement initial des posts
    loadPosts();
    
    // Ajouter des classes CSS pour les animations
    $('body').addClass('seoai-enhanced');
});
