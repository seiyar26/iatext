/**
 * Gestion des logs en temps réel
 */
jQuery(document).ready(function($) {
    // Variables globales
    let isLogsPaused = false;
    let logStats = {
        INFO: 0,
        DEBUG: 0,
        SUCCESS: 0,
        WARNING: 0,
        ERROR: 0
    };
    let lastLogTimestamp = 0;
    let pollInterval = 2000; // 2 secondes par défaut
    let activeFilters = {
        level: 'all',
        text: ''
    };
    
    // Éléments DOM
    const $logsContent = $('#seoai-live-logs-content');
    const $pauseButton = $('#seoai-pause-logs');
    const $resumeButton = $('#seoai-resume-logs');
    const $clearButton = $('#seoai-clear-logs');
    const $levelFilter = $('#seoai-log-level');
    const $textFilter = $('#seoai-log-filter');
    const $logStatus = $('#seoai-logs-status');
    const $lastUpdate = $('#seoai-logs-last-update');
    
    /**
     * Fonction principale pour récupérer les logs
     */
    function fetchLogs() {
        if (isLogsPaused) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'seoai_get_live_logs',
                nonce: seoai_ajax.nonce,
                timestamp: lastLogTimestamp
            },
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    
                    if (data.success) {
                        if (data.logs && data.logs.length > 0) {
                            appendLogs(data.logs);
                            lastLogTimestamp = data.timestamp;
                            updateLastUpdate();
                        }
                    } else {
                        console.error('Erreur de récupération des logs:', data.message);
                        $logStatus.text('Erreur: ' + data.message);
                    }
                } catch (e) {
                    console.error('Erreur de parsing des logs:', e);
                    $logStatus.text('Erreur de connexion - nouvelle tentative...');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur Ajax:', error);
                $logStatus.text('Erreur de connexion - nouvelle tentative...');
            },
            complete: function() {
                if (!isLogsPaused) {
                    setTimeout(fetchLogs, pollInterval);
                }
            }
        });
    }
    
    /**
     * Ajouter les nouveaux logs au conteneur
     */
    function appendLogs(logs) {
        let logsHTML = '';
        let newLogsCount = 0;
        
        logs.forEach(log => {
            // Mettre à jour les statistiques
            if (logStats.hasOwnProperty(log.level)) {
                logStats[log.level]++;
            }
            
            // Filtrer par niveau de log si nécessaire
            if (activeFilters.level !== 'all' && log.level !== activeFilters.level) {
                return;
            }
            
            // Filtrer par texte si nécessaire
            if (activeFilters.text && !log.message.toLowerCase().includes(activeFilters.text.toLowerCase())) {
                return;
            }
            
            // Formater l'heure pour l'affichage
            const logTime = new Date(log.timestamp * 1000);
            const formattedTime = logTime.toLocaleTimeString('fr-FR');
            
            // Construire l'entrée de log
            logsHTML += `<div class="log-entry ${log.level}" data-timestamp="${log.timestamp}">`;
            logsHTML += `[${formattedTime}] [${log.level}] ${log.message}`;
            logsHTML += `</div>`;
            
            newLogsCount++;
        });
        
        // Ajouter les nouveaux logs au conteneur
        if (newLogsCount > 0) {
            $logsContent.append(logsHTML);
            
            // Scroller vers le bas
            const $logWrapper = $('.seoai-live-logs-wrapper');
            $logWrapper.scrollTop($logWrapper[0].scrollHeight);
            
            // Mettre à jour le compteur de statistiques
            updateStats();
        }
    }
    
    /**
     * Mettre à jour les statistiques de logs
     */
    function updateStats() {
        $('.info-count b').text(logStats.INFO);
        $('.debug-count b').text(logStats.DEBUG);
        $('.success-count b').text(logStats.SUCCESS);
        $('.warning-count b').text(logStats.WARNING);
        $('.error-count b').text(logStats.ERROR);
    }
    
    /**
     * Mettre à jour l'horodatage de la dernière mise à jour
     */
    function updateLastUpdate() {
        const now = new Date();
        $lastUpdate.text('Dernière mise à jour: ' + now.toLocaleTimeString('fr-FR'));
    }
    
    /**
     * Initialiser les écouteurs d'événements
     */
    function initEventListeners() {
        // Bouton pause
        $pauseButton.on('click', function() {
            isLogsPaused = true;
            $pauseButton.hide();
            $resumeButton.show();
            $logStatus.text('Logs en pause');
        });
        
        // Bouton reprendre
        $resumeButton.on('click', function() {
            isLogsPaused = false;
            $resumeButton.hide();
            $pauseButton.show();
            $logStatus.text('Logs actifs - Actualisation automatique toutes les ' + (pollInterval/1000) + ' secondes');
            fetchLogs(); // Redémarrer immédiatement
        });
        
        // Bouton effacer
        $clearButton.on('click', function() {
            $logsContent.empty();
            // Réinitialiser les statistiques
            for (let key in logStats) {
                logStats[key] = 0;
            }
            updateStats();
        });
        
        // Filtre par niveau
        $levelFilter.on('change', function() {
            activeFilters.level = $(this).val();
            applyFilters();
        });
        
        // Filtre par texte (avec debounce)
        let debounceTimeout;
        $textFilter.on('input', function() {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(function() {
                activeFilters.text = $textFilter.val();
                applyFilters();
            }, 300);
        });
    }
    
    /**
     * Appliquer les filtres aux logs existants
     */
    function applyFilters() {
        $('.log-entry').each(function() {
            const $entry = $(this);
            const level = $entry.attr('class').split(' ')[1]; // La deuxième classe est le niveau de log
            const text = $entry.text();
            
            let showEntry = true;
            
            // Filtre par niveau
            if (activeFilters.level !== 'all' && level !== activeFilters.level) {
                showEntry = false;
            }
            
            // Filtre par texte
            if (activeFilters.text && !text.toLowerCase().includes(activeFilters.text.toLowerCase())) {
                showEntry = false;
            }
            
            // Afficher ou masquer l'entrée
            $entry.toggle(showEntry);
        });
    }
    
    /**
     * Initialisation
     */
    function init() {
        // Ajouter un message de démarrage
        const startMessage = `Initialisation du système de logs en direct...`;
        $logsContent.html(`<div class="log-entry INFO">[${new Date().toLocaleTimeString('fr-FR')}] [INFO] ${startMessage}</div>`);
        
        // Initialiser les écouteurs d'événements
        initEventListeners();
        
        // Démarrer la récupération des logs
        fetchLogs();
    }
    
    // Démarrer l'application
    init();
});
