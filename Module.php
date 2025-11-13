<?php
declare(strict_types=1);

namespace HelpAssistant;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;

/**
 * HelpAssistant Module
 *
 * Provides contextual help tours for the Omeka S admin interface using Intro.js
 */
class Module extends AbstractModule
{
    /**
     * Get the module configuration array
     *
     * @return array
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Attach listeners to events
     *
     * @param SharedEventManagerInterface $sharedEventManager
     * @return void
     */
    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        // Attach listener to load assets in admin interface
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.layout',
            [$this, 'loadAdminAssets']
        );

        // Attach listener to inject "Start Tour" button in items browse page
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.browse.after',
            [$this, 'injectTourButton']
        );
    }

    /**
     * Load Intro.js assets and custom initialization script in admin interface
     *
     * @param Event $event
     * @return void
     */
    public function loadAdminAssets(Event $event): void
    {
        $view = $event->getTarget();

        if (!$view instanceof PhpRenderer) {
            return;
        }

        // Load Intro.js CSS (placeholder - library not embedded)
        $view->headLink()->appendStylesheet($view->assetUrl('css/introjs.min.css', 'HelpAssistant'));

        // Load Intro.js JavaScript (placeholder - library not embedded)
        $view->headScript()->appendFile($view->assetUrl('js/intro.min.js', 'HelpAssistant'));

        // Load custom initialization script
        $view->headScript()->appendFile($view->assetUrl('js/helpassistant-init.js', 'HelpAssistant'));
    }

    /**
     * Inject "Start Tour" button to the left of "Add new item" button
     *
     * @param Event $event
     * @return void
     */
    public function injectTourButton(Event $event): void
    {
        $view = $event->getTarget();

        if (!$view instanceof PhpRenderer) {
            return;
        }

        // Get the current action to ensure we're on the browse page
        $routeMatch = $view->params()->fromRoute();

        if (!isset($routeMatch['action']) || $routeMatch['action'] !== 'browse') {
            return;
        }

        // Inject JavaScript to add the "Start Tour" button
        $script = <<<'JS'
<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Find the "Add new item" button
        const addNewButton = document.querySelector('a.button[href*="/admin/item/add"]');

        if (!addNewButton) {
            return;
        }

        // Create the "Start Tour" button
        const tourButton = document.createElement('button');
        tourButton.type = 'button';
        tourButton.className = 'button';
        tourButton.id = 'help-assistant-tour-btn';
        tourButton.textContent = 'Start Tour';
        tourButton.style.marginRight = '10px';

        // Insert the button before the "Add new item" button
        addNewButton.parentNode.insertBefore(tourButton, addNewButton);

        // Attach click event to start the tour
        tourButton.addEventListener('click', function() {
            if (typeof window.HelpAssistant !== 'undefined' &&
                typeof window.HelpAssistant.startItemsTour === 'function') {
                window.HelpAssistant.startItemsTour();
            } else {
                console.error('HelpAssistant tour not available');
            }
        });
    });
})();
</script>
JS;

        echo $script;
    }
}
