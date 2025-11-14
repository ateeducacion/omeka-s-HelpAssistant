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
     * Load a tour configuration from JSON file
     */
    window.HelpAssistant.loadTour = function(tourName, startStep) {
        // Check if Intro.js is available
        if (typeof introJs === 'undefined') {
            console.error('Intro.js library is not loaded. Please ensure intro.min.js is included.');
            alert('Help tour library is not available. Please contact your administrator.');
            return;
        }

        // Construct the JSON file path
        const basePath = window.HelpAssistant.basePath || '/modules/HelpAssistant/asset/tours/';
        const jsonPath = basePath + tourName + '.json';

        // Fetch the tour configuration
        fetch(jsonPath)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Tour configuration not found: ' + jsonPath);
                }
                return response.json();
            })
            .then(tourConfig => {
                window.HelpAssistant.startTour(tourConfig, startStep);
            })
            .catch(error => {
                console.error('Error loading tour:', error);
                alert('Unable to load help tour. Please try again later.');
            });
    };

    /**
     * Start a tour with the given configuration
     */
    window.HelpAssistant.startTour = function(tourConfig, startStep) {
        // Use the new introJs.tour() API
        const intro = introJs.tour ? introJs.tour() : introJs();

        // Determine starting step (1-based from URL)
        const startStepNumber = (startStep && !isNaN(parseInt(startStep)))
            ? parseInt(startStep)
            : 1;

        console.log('Starting tour at step:', startStepNumber);

        // Get all steps from config
        const allSteps = tourConfig.steps || [];

        // Filter steps: only include steps from startStepNumber onwards
        // This prevents showing steps from previous pages
        const filteredSteps = allSteps.slice(startStepNumber - 1);

        console.log('Total steps:', allSteps.length, 'Filtered steps:', filteredSteps.length);

        // Log element selectors to help with debugging
        filteredSteps.forEach((step, index) => {
            const element = step.element ? document.querySelector(step.element) : null;
            console.log('Step', startStepNumber + index, ':', step.element, 'Found:', !!element);
        });

        // Default options
        const options = {
            steps: filteredSteps,
            exitOnOverlayClick: false,
            showStepNumbers: true,
            showBullets: true,
            showProgress: true,
            scrollToElement: true,
            overlayOpacity: 0.7,
            nextLabel: 'Next',
            prevLabel: 'Back',
            doneLabel: 'Done'
        };

        intro.setOptions(options);

        // Track whether tour has actually been displayed
        let tourDisplayed = false;

        // Handle before step change for redirects
        intro.onbeforechange(function() {
            const currentStepIndex = intro.currentStep();

            // Map back to original step number in full tour
            const originalStepIndex = (startStepNumber - 1) + currentStepIndex;

            console.log('onbeforechange - currentStep in filtered:', currentStepIndex, 'original step:', originalStepIndex, 'tourDisplayed:', tourDisplayed);

            // Allow initial step to display
            if (!tourDisplayed) {
                tourDisplayed = true;
                console.log('Displaying initial step');
                return true;
            }

            // Check if current step (the one we're leaving) has a redirect
            const currentStep = filteredSteps[currentStepIndex];

            if (currentStep && currentStep.redirect) {
                console.log('Redirect detected, navigating to:', currentStep.redirect);
                setTimeout(() => {
                    intro.exit(false);
                    window.location.href = currentStep.redirect;
                }, 100);
                return false; // Prevent step change
            }

            return true; // Allow normal navigation
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
     * Legacy function for backward compatibility
     */
    window.HelpAssistant.startItemsTour = function() {
        window.HelpAssistant.loadTour('add-item');
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
        const urlParams = new URLSearchParams(window.location.search);

        // Check for tour parameter with autostart
        if (urlParams.has('tour') && urlParams.get('autostart') === 'true') {
            const tourName = urlParams.get('tour');
            const startStep = urlParams.get('step');

            // Small delay to ensure DOM is fully ready
            setTimeout(() => {
                window.HelpAssistant.loadTour(tourName, startStep);
            }, 500);
        }

        // Legacy support: help-tour parameter
        else if (urlParams.has('help-tour')) {
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
