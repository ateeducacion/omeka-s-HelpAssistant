(function() {
    'use strict';

    window.HelpAssistant = window.HelpAssistant || {};

    const ACTIVE_ICON_COLOR = '#1a73e8';
    const INACTIVE_ICON_COLOR = '#9e9e9e';
    const TOURS_MAP_URL = '/admin/help-assistant/tours-map';
    const GENERIC_TOUR_CONFIG = {
        showBullets: false,
        showStepNumbers: false,
        exitOnOverlayClick: true,
        steps: [{
            // &#8505; renders the info icon while keeping the source ASCII
            intro: '&#8505; No hay ayuda disponible para esta secci&oacute;n. Disculpe las molestias.'
        }]
    };

    let toursConfigPromise = null;

    function ensureMaterialIcons() {
        if (document.querySelector('link[data-helpassistant-icons]')) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=help';
        link.setAttribute('data-helpassistant-icons', 'true');

        document.head.appendChild(link);
    }

    function loadToursConfig() {
        if (toursConfigPromise) {
            return toursConfigPromise;
        }

        toursConfigPromise = fetch(TOURS_MAP_URL)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Tours map not found');
                }
                return response.json();
            })
            .then(data => {
                return data && typeof data === 'object' && data.tours ? data.tours : {};
            })
            .catch(error => {
                console.error('Tour availability error:', error);
                return {};
            });

        return toursConfigPromise;
    }

    function getTourInfo() {
        if (!window.HelpAssistantContext) {
            return Promise.resolve({ available: false, tourConfig: null });
        }

        const controller = window.HelpAssistantContext.controller;
        const action = window.HelpAssistantContext.action;
        const tourKey = controller + ':' + action;

        return loadToursConfig().then(toursMap => {
            const tourConfig = toursMap[tourKey];
            return {
                available: !!tourConfig,
                tourConfig: tourConfig || null
            };
        });
    }

    function setIconState(button, isActive) {
        const icon = button.querySelector('.material-symbols-outlined');
        const color = isActive ? ACTIVE_ICON_COLOR : INACTIVE_ICON_COLOR;

        if (icon) {
            icon.style.color = color;
        }

        button.dataset.tourAvailable = isActive ? 'true' : 'false';
        button.title = isActive ? 'Start guided tour' : 'No tour available for this page';
        button.style.opacity = isActive ? '1' : '0.7';
    }

    function injectTourButton() {
        const logo = document.querySelector('header > .logo');


        if (!logo || !window.HelpAssistantContext) {
            return;
        }

        const controller = window.HelpAssistantContext.controller;
        const action = window.HelpAssistantContext.action;

        ensureMaterialIcons();

        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'helpassistant-start-tour';
        button.className = 'helpassistant-button helpassistant-icon-button';
        button.setAttribute('data-controller', controller);
        button.setAttribute('data-action', action);
        button.setAttribute('aria-label', 'Start help tour');
        button.style.cssText = 'margin-left: 10px; margin-bottom: 0px; background: transparent; border: none; padding: 4px; cursor: pointer; display: inline-flex; align-items: center; box-shadow:none';

        const icon = document.createElement('span');
        icon.className = 'material-symbols-outlined';
        icon.textContent = 'help';
        icon.style.fontSize = '28px';
        icon.style.lineHeight = '1';
        button.appendChild(icon);

        setIconState(button, false);

        logo.appendChild(button);

        button.addEventListener('click', startTour);

        getTourInfo().then(info => {
            setIconState(button, info.available);
        });
    }

    function startTour() {
        const button = document.getElementById('helpassistant-start-tour');

        getTourInfo()
            .then(info => {
                if (!info.available || !info.tourConfig) {
                    if (button) {
                        setIconState(button, false);
                    }
                    return { tourConfig: GENERIC_TOUR_CONFIG, isGeneric: true };
                }

                if (button) {
                    setIconState(button, true);
                }

                return { tourConfig: info.tourConfig, isGeneric: false };
            })
            .then(result => {
                if (!result || !result.tourConfig) {
                    return;
                }

                if (typeof introJs === 'undefined') {
                    alert('Intro.js library is not loaded');
                    return;
                }

                runTour(result.tourConfig, 0);
            })
            .catch(error => {
                console.error('Tour error:', error);
            });
    }

    function runTour(tourConfig, startStep) {
        const intro = introJs.tour ? introJs.tour() : introJs();
        const allSteps = tourConfig.steps || [];

        // If resuming mid-tour, slice the steps array to only include steps from startStep onwards
        // This prevents the redirect logic from triggering for steps on previous pages
        const steps = startStep > 0 ? allSteps.slice(startStep) : allSteps;

        console.log('Running tour from step', startStep, '- using', steps.length, 'steps');

        // Track the current step we're displaying (0-indexed within the sliced array)
        let displayedStep = 0;

        // Create a modified config with the sliced steps
        const modifiedConfig = Object.assign({}, tourConfig, { steps: steps });
        intro.setOptions(modifiedConfig);

        // onbeforechange fires BEFORE changing to a new step
        intro.onbeforechange(function() {
            const goingToStep = intro.currentStep();
            const leavingStep = displayedStep;
            const stepConfig = steps[leavingStep];

            console.log('Tour: leaving step', leavingStep, '-> going to step', goingToStep);

            // Check if the step we're LEAVING has a redirect
            if (stepConfig && stepConfig.redirect && goingToStep > leavingStep) {
                const redirect = stepConfig.redirect;

                // Check if it's an anchor-only redirect (same page navigation)
                if (redirect.startsWith('#')) {
                    console.log('Anchor redirect detected:', redirect);

                    // Click the anchor/tab element to activate it
                    const anchorTarget = document.querySelector('a[href="' + redirect + '"]');
                    if (anchorTarget) {
                        anchorTarget.click();
                    }

                    // Scroll to the element
                    const targetElement = document.querySelector(redirect);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }

                    // Allow the tour to continue to the next step (don't block)
                    return true;
                }

                // Full page redirect
                const originalStepIndex = startStep + leavingStep;
                console.log('Page redirect detected on original step', originalStepIndex, ':', redirect);

                sessionStorage.setItem('helpassistant_tour', JSON.stringify({
                    config: tourConfig,  // Save the ORIGINAL full config
                    nextStep: originalStepIndex + 1
                }));

                setTimeout(function() {
                    window.location.href = redirect;
                }, 100);

                return false;
            }

            return true;
        });

        // Update displayedStep after successful step change
        intro.onafterchange(function() {
            displayedStep = intro.currentStep();
            console.log('Tour: now displaying step', displayedStep);
        });

        intro.onexit(function() {
            sessionStorage.removeItem('helpassistant_tour');
        });

        intro.oncomplete(function() {
            sessionStorage.removeItem('helpassistant_tour');
        });

        // Start the tour (always from step 0 of the sliced array)
        intro.start();
    }

    function checkContinueTour() {
        const tourData = sessionStorage.getItem('helpassistant_tour');
        
        if (!tourData) {
            return;
        }

        try {
            const data = JSON.parse(tourData);
            
            setTimeout(function() {
                runTour(data.config, data.nextStep);
            }, 500);
        } catch (e) {
            console.error('Error continuing tour:', e);
            sessionStorage.removeItem('helpassistant_tour');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        injectTourButton();
        checkContinueTour();
    });

})();
