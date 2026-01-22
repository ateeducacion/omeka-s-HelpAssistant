<?php
declare(strict_types=1);

namespace HelpAssistant;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\View\Renderer\PhpRenderer;
use HelpAssistant\Form\ConfigForm;
use Omeka\Module\AbstractModule;
use Laminas\Mvc\Controller\AbstractController;
use Omeka\Settings\Settings;

class Module extends AbstractModule
{
    public function getConfig(): array
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getConfigForm(PhpRenderer $renderer): string
    {
        $services = $this->getServiceLocator();
/** @var \Laminas\Form\FormElementManager $formManager */
        $formManager = $services->get('FormElementManager');
/** @var ConfigForm $form */
        $form = $formManager->get(ConfigForm::class);
        $form->init();
        $settings = $services->get('Omeka\Settings');
        $mappings = $settings->get('helpassistant_tour_mappings', []);
        $rows = $this->prepareMappingsForForm($mappings);
        $form->get('mappings')->setOption('count', max(1, count($rows)));
        $form->setData(['mappings' => $rows]);
        $form->prepare();
        return $renderer->formCollection($form);
    }

    public function handleConfigForm(AbstractController $controller): void
    {
        $services = $controller->getEvent()->getApplication()->getServiceManager();
/** @var \Laminas\Form\FormElementManager $formManager */
        $formManager = $services->get('FormElementManager');
/** @var ConfigForm $form */
        $form = $formManager->get(ConfigForm::class);
        $form->init();
        $post = $controller->getRequest()->getPost()->toArray();
        if (isset($post['mappings']) && is_array($post['mappings'])) {
            $form->get('mappings')->setOption('count', max(1, count($post['mappings'])));
        }

        $form->setData($post);
        if (!$form->isValid()) {
            return;
        }

        $data = $form->getData();
        $mappings = $this->sanitizeMappings($data['mappings'] ?? []);
/** @var Settings $settings */
        $settings = $services->get('Omeka\Settings');
        $settings->set('helpassistant_tour_mappings', $mappings);
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach('*', 'view.layout', [$this, 'loadAdminAssets']);
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

        // Generate the tours-map URL using the url() view helper
        // This ensures the correct base path is used when behind a reverse proxy
        $toursMapUrl = '';
        try {
            $urlHelper = $view->getHelperPluginManager()->get('url');
            $toursMapUrl = $urlHelper('admin/help-assistant-tours-map');
        } catch (\Exception $e) {
            // Fallback to hardcoded path if url helper fails
            $toursMapUrl = '/admin/help-assistant/tours-map';
        }

        $inlineScript = sprintf(
            'window.HelpAssistantContext = { controller: %s, action: %s, toursMapUrl: %s };',
            json_encode($controllerAction['controller']),
            json_encode($controllerAction['action']),
            json_encode($toursMapUrl)
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

    private function prepareMappingsForForm(array $mappings): array
    {
        $rows = [];
        foreach ($mappings as $mapping) {
            $rows[] = [
                'controller' => $mapping['controller'] ?? '',
                'action' => $mapping['action'] ?? '',
                'tour_json' => $mapping['tour_json'] ?? '',
            ];
        }

        // Always provide at least one empty row for adding new mappings
        $rows[] = ['controller' => '', 'action' => '', 'tour_json' => ''];
        return $rows;
    }

    private function sanitizeMappings(array $mappings): array
    {
        $clean = [];
        foreach ($mappings as $mapping) {
            $controller = trim((string) ($mapping['controller'] ?? ''));
            $action = trim((string) ($mapping['action'] ?? ''));
            $tourJson = trim((string) ($mapping['tour_json'] ?? ''));
            if ($controller === '' || $action === '' || $tourJson === '') {
                continue;
            }

            $decoded = json_decode($tourJson, true);
            if (!is_array($decoded)) {
                continue;
            }

            $encoded = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            $clean[] = [
                'controller' => $controller,
                'action' => $action,
                'tour_json' => $encoded,
            ];
        }

        return $clean;
    }
}
