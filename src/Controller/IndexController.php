<?php
declare(strict_types=1);

namespace HelpAssistant\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function toursAction(): ViewModel
    {
        $view = new ViewModel();
        $view->setTemplate('admin/help-assistant/tours');
        return $view;
    }

    public function toursMapAction(): JsonModel
    {
        $tours = $this->loadStaticTours();
        $settings = $this->getSettings();
        $customMappings = $settings->get('helpassistant_tour_mappings', []);

        foreach ($customMappings as $mapping) {
            $controller = trim((string) ($mapping['controller'] ?? ''));
            $action = trim((string) ($mapping['action'] ?? ''));
            $tourJson = trim((string) ($mapping['tour_json'] ?? ''));

            if ($controller === '' || $action === '' || $tourJson === '') {
                continue;
            }

            $config = json_decode($tourJson, true);
            if (!is_array($config)) {
                continue;
            }

            $key = $controller . ':' . $action;
            $tours[$key] = $config;
        }

        return new JsonModel([
            'tours' => $tours,
        ]);
    }

    private function getSettings()
    {
        return $this->getEvent()->getApplication()->getServiceManager()->get('Omeka\Settings');
    }

    private function loadStaticTours(): array
    {
        $baseDir = dirname(__DIR__, 2) . '/asset/tours';
        $mapPath = $baseDir . '/tours-map.json';

        if (!is_readable($mapPath)) {
            return [];
        }

        $mapContent = file_get_contents($mapPath) ?: '';
        $toursMap = json_decode($mapContent, true);

        if (!is_array($toursMap)) {
            return [];
        }

        $configs = [];

        foreach ($toursMap as $key => $fileName) {
            $tourPath = $baseDir . '/' . $fileName;
            if (!is_readable($tourPath)) {
                continue;
            }

            $tourContent = file_get_contents($tourPath) ?: '';
            $tourConfig = json_decode($tourContent, true);

            if (is_array($tourConfig)) {
                $configs[$key] = $tourConfig;
            }
        }

        return $configs;
    }
}
