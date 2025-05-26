/**
 * JavaScript côté public pour SEO Optimizer & AI Visual Enhancer
 */
(function($) {
    'use strict';

    /**
     * Toutes les fonctions publiques doivent être liées à ce module
     */
    let SEOAI_Public = {
        init: function() {
            // Initialiser les actions côté client si nécessaire
            this.initLazyLoading();
            this.initImageZoom();
        },

        /**
         * Initialise le chargement différé des images générées par IA
         */
        initLazyLoading: function() {
            // Vérifier si le navigateur prend en charge l'attribut loading natif
            if ('loading' in HTMLImageElement.prototype) {
                // Le navigateur prend en charge le lazy loading natif
                // Nous utilisons déjà l'attribut loading="lazy" dans le HTML
            } else {
                // Fallback pour les navigateurs qui ne prennent pas en charge le lazy loading natif
                // Ici on pourrait implémenter une solution JavaScript si nécessaire
            }
        },

        /**
         * Ajoute une fonctionnalité de zoom pour les images générées
         */
        initImageZoom: function() {
            $('.seoai-generated-image img').on('click', function() {
                // Ici on pourrait ajouter un lightbox ou une fonctionnalité de zoom
                // Pour l'instant, nous nous contentons d'ajouter une classe active
                $(this).toggleClass('zoomed');
            });
        }
    };

    // Initialiser le module quand le DOM est prêt
    $(document).ready(function() {
        SEOAI_Public.init();
    });

})(jQuery);
