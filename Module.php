<?php
declare(strict_types=1);

namespace HelpAssistant;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach(
            '*',
            'view.layout',
            [$this, 'loadAdminAssets']
        );
    }

    public function loadAdminAssets(Event $event): void
    {
        $view = $event->getTarget();

        if (!$view instanceof PhpRenderer) {
            return;
        }

        $routeMatch = $view->getHelperPluginManager()->get('params')->fromRoute();
        $controller = $routeMatch['controller'] ?? '';

        if (strpos($controller, 'Admin') === false) {
            return;
        }

        $view->headLink()->appendStylesheet($view->assetUrl('css/introjs.min.css', 'HelpAssistant'));
        $view->headScript()->appendFile($view->assetUrl('js/intro.min.js', 'HelpAssistant'));
        $view->headLink()->appendStylesheet($view->assetUrl('css/helptour.css', 'HelpAssistant'));

        
        $controllerAction = $this->getControllerAction($routeMatch);
        
        $inlineScript = sprintf(
            'window.HelpAssistantContext = { controller: %s, action: %s };',
            json_encode($controllerAction['controller']),
            json_encode($controllerAction['action'])
        );
        
        $view->headScript()->appendScript($inlineScript);
        $view->headScript()->appendFile($view->assetUrl('js/helpassistant-init.js', 'HelpAssistant'));
    }

    private function getControllerAction(array $routeMatch): array
    {
        $controller = $routeMatch['controller'] ?? '';
        $action = $routeMatch['action'] ?? '';

        $controllerParts = explode('\\', $controller);
        $controllerName = end($controllerParts);

        return [
            'controller' => $controllerName,
            'action' => $action
        ];
    }
}
