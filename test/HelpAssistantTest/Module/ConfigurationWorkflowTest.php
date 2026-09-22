<?php
declare(strict_types=1);

namespace HelpAssistantTest\Module;

use HelpAssistant\Module;
use HelpAssistant\Form\ConfigForm;
use HelpAssistant\Controller\IndexController;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Settings\Settings;
use PHPUnit\Framework\TestCase;

class ConfigurationWorkflowTest extends TestCase
{
    private function services($settings, $form = null): ServiceManager
    {
        $forms = $this->createMock(\Laminas\Form\FormElementManager::class);
        $forms->method('get')->willReturn($form ?? new ConfigForm());
        return new ServiceManager(['services' => [
            'Omeka\Settings' => $settings,
            'FormElementManager' => $forms,
        ]]);
    }

    private function controller(ServiceManager $services, array $post = []): IndexController
    {
        $app = $this->createMock(Application::class);
        $app->method('getServiceManager')->willReturn($services);
        $request = new Request();
        $request->getPost()->fromArray($post);
        $event = new MvcEvent();
        $event->setApplication($app);
        $event->setRequest($request);
        $controller = new IndexController();
        $controller->setEvent($event);
        return $controller;
    }

    public function testConfigFormLoadsSavedMappingsAndOffersEmptyRow(): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->method('get')->willReturn([
            ['controller' => 'Item', 'action' => 'browse', 'tour_json' => '{"steps":[]}'],
        ]);
        $form = new ConfigForm();
        $module = new Module();
        $module->setServiceLocator($this->services($settings, $form));
        $renderer = $this->getMockBuilder(PhpRenderer::class)->onlyMethods(['__call'])->getMock();
        $renderer->expects($this->once())->method('__call')->with('formCollection', [$form])
            ->willReturn('<form>mappings</form>');
        $this->assertSame('<form>mappings</form>', $module->getConfigForm($renderer));
        $this->assertSame(2, $form->get('mappings')->getOption('count'));
        $this->assertIsArray($module->getConfig());
    }

    public function testConfigSubmissionSavesOnlyValidMappings(): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->expects($this->once())->method('set')->with('helpassistant_tour_mappings', [
            ['controller' => 'Item', 'action' => 'browse', 'tour_json' => "{\n    \"steps\": []\n}"],
        ]);
        $module = new Module();
        $form = $this->getMockBuilder(ConfigForm::class)->onlyMethods(['isValid', 'getData'])->getMock();
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['mappings' => [
            ['controller' => ' Item ', 'action' => ' browse ', 'tour_json' => '{"steps":[]}'],
        ]]);
        $controller = $this->controller($this->services($settings, $form), ['mappings' => [
            ['controller' => ' Item ', 'action' => ' browse ', 'tour_json' => '{"steps":[]}'],
        ]]);
        $module->handleConfigForm($controller);
    }

    public function testInvalidFormDoesNotPersistSettings(): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->expects($this->never())->method('set');
        $form = $this->getMockBuilder(ConfigForm::class)->onlyMethods(['isValid'])->getMock();
        $form->method('isValid')->willReturn(false);
        (new Module())->handleConfigForm($this->controller($this->services($settings, $form)));
    }

    public function testTourEndpointCombinesStaticToursAndValidCustomOverrides(): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->method('get')->willReturn([
            ['controller' => ' Item ', 'action' => ' browse ', 'tour_json' => '{"steps":[{"intro":"Custom"}]}'],
            ['controller' => '', 'action' => 'browse', 'tour_json' => '{}'],
            ['controller' => 'Item', 'action' => 'edit', 'tour_json' => 'invalid'],
        ]);
        $controller = $this->controller($this->services($settings));
        $this->assertSame('admin/help-assistant/tours', $controller->toursAction()->getTemplate());
        $tours = $controller->toursMapAction()->getVariable('tours');
        $this->assertSame(['steps' => [['intro' => 'Custom']]], $tours['Item:browse']);
        $this->assertCount(1, $tours);
        $this->assertArrayNotHasKey('Item:edit', $tours);
    }
    public function testTourEndpointLoadsReadableStaticFilesAndIgnoresInvalidEntries(): void
    {
        $directory = dirname(__DIR__, 3) . '/asset/tours';
        $map = $directory . '/tours-map.json';
        $original = is_file($map) ? file_get_contents($map) : null;
        $fixture = $directory . '/coverage-fixture.json';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        try {
            file_put_contents($fixture, '{"steps":[{"intro":"Static"}]}');
            file_put_contents($map, json_encode([
                'User:browse' => basename($fixture),
                'Missing:browse' => 'missing-coverage-fixture.json',
            ]));
            $settings = $this->createMock(Settings::class);
            $settings->method('get')->willReturn([]);
            $controller = $this->controller($this->services($settings));
            $tours = $controller->toursMapAction()->getVariable('tours');
            $this->assertSame(['steps' => [['intro' => 'Static']]], $tours['User:browse']);
            $this->assertArrayNotHasKey('Missing:browse', $tours);
            file_put_contents($map, 'invalid');
            $this->assertSame([], $controller->toursMapAction()->getVariable('tours'));
        } finally {
            unlink($fixture);
            if ($original === null) {
                unlink($map);
            } else {
                file_put_contents($map, $original);
            }
        }
    }
}
