<?php declare(strict_types=1);

namespace HelpAssistantTest\Config;

use HelpAssistant\Controller\IndexController;
use HelpAssistant\Form\ConfigForm;
use HelpAssistant\Form\MappingFieldset;
use Laminas\ServiceManager\Factory\InvokableFactory;
use PHPUnit\Framework\TestCase;

class ModuleConfigTest extends TestCase
{
    public function testModuleConfigRegistersControllersFormsAndRoutes(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/module.config.php';

        $this->assertArrayHasKey('controllers', $config);
        $this->assertSame(InvokableFactory::class, $config['controllers']['factories'][IndexController::class]);
        $this->assertArrayHasKey('HelpAssistant\\Controller\\Index', $config['controllers']['aliases']);

        $this->assertArrayHasKey('form_elements', $config);
        $this->assertSame(InvokableFactory::class, $config['form_elements']['factories'][ConfigForm::class]);
        $this->assertSame(InvokableFactory::class, $config['form_elements']['factories'][MappingFieldset::class]);

        $routes = $config['router']['routes']['admin']['child_routes'];
        $this->assertArrayHasKey('help-assistant', $routes);
        $this->assertArrayHasKey('help-assistant-tours-map', $routes);
        $this->assertSame('/help-assistant', $routes['help-assistant']['options']['route']);
        $this->assertSame('tours-map', $routes['help-assistant-tours-map']['options']['defaults']['action']);
    }
}
