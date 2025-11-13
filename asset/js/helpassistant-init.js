/**
 * HelpAssistant - Intro.js Tour Initialization
 *
 * This script initializes contextual help tours for the Omeka S admin interface
 * using Intro.js library.
 */

(function() {
    'use strict';

    /**
     * Initialize HelpAssistant namespace
     */
    window.HelpAssistant = window.HelpAssistant || {};

    /**
     * Tour: How to add a new item
     *
     * This tour guides users through the process of creating a new item in Omeka S
     */
    window.HelpAssistant.startItemsTour = function() {
        // Check if Intro.js is available
        if (typeof introJs === 'undefined') {
            console.error('Intro.js library is not loaded. Please ensure intro.min.js is included.');
            alert('Help tour library is not available. Please contact your administrator.');
            return;
        }

        // Define tour steps
        const intro = introJs();

        intro.setOptions({
            steps: [
                {
                    element: document.querySelector('a.button[href*="/admin/item/add"]'),
                    intro: 'Click this button to start adding a new item to your collection.',
                    position: 'bottom'
                },
                {
                    element: document.querySelector('#content'),
                    intro: 'After clicking "Add new item", you will be taken to a form where you can enter details about your item. Look for fields like Title, Description, and other metadata fields.',
                    position: 'top'
                },
                {
                    intro: 'Fill in the required fields with information about your item. The Title field is typically required, while other fields are optional but recommended for better organization.',
                    position: 'top'
                },
                {
                    intro: 'You can also attach media files (images, documents, videos) to your item using the Media tab.',
                    position: 'top'
                },
                {
                    intro: 'Once you have filled in all necessary information, look for the "Save" or "Add" button at the bottom of the form to save your new item.',
                    position: 'top'
                }
            ],
            exitOnOverlayClick: false,
            showStepNumbers: true,
            showBullets: true,
            showProgress: true,
            scrollToElement: true,
            overlayOpacity: 0.7,
            nextLabel: 'Next',
            prevLabel: 'Back',
            doneLabel: 'Done'
        });

        // Handle tour completion
        intro.oncomplete(function() {
            console.log('Tour completed successfully');
        });

        // Handle tour exit
        intro.onexit(function() {
            console.log('Tour exited');
        });

        // Start the tour
        intro.start();
    };

    /**
     * Placeholder for additional tours
     *
     * Add more tour functions here following the same pattern:
     *
     * window.HelpAssistant.startMediaTour = function() { ... };
     * window.HelpAssistant.startCollectionsTour = function() { ... };
     * window.HelpAssistant.startSitePagesTour = function() { ... };
     */

    /**
     * Auto-initialize tours on specific pages (optional)
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Example: Auto-start tour if URL parameter is present
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('help-tour')) {
            const tourName = urlParams.get('help-tour');

            switch(tourName) {
                case 'items':
                    if (typeof window.HelpAssistant.startItemsTour === 'function') {
                        window.HelpAssistant.startItemsTour();
                    }
                    break;
                // Add more cases for other tours here
                default:
                    console.warn('Unknown tour:', tourName);
            }
        }
    });

})();
