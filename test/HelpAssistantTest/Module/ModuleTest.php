<?php declare(strict_types=1);

namespace HelpAssistantTest\Module;

require_once dirname(__DIR__, 3) . '/Module.php';

use HelpAssistant\Module;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    private function invokePrivateMethod(object $object, string $method, array $args = [])
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($object, $args);
    }

    public function testPrepareMappingsForFormAddsTrailingEmptyRow(): void
    {
        $module = new Module();
        $mappings = [
            ['controller' => 'Item', 'action' => 'browse', 'tour_json' => '{"steps": []}'],
        ];

        $result = $this->invokePrivateMethod($module, 'prepareMappingsForForm', [$mappings]);

        $this->assertCount(2, $result);
        $this->assertSame('Item', $result[0]['controller']);
        $this->assertSame('', $result[1]['controller']);
        $this->assertSame('', $result[1]['action']);
        $this->assertSame('', $result[1]['tour_json']);
    }

    public function testSanitizeMappingsKeepsValidEntriesAndNormalizesJson(): void
    {
        $module = new Module();
        $input = [
            ['controller' => ' Item ', 'action' => ' browse ', 'tour_json' => '{"steps":[1]}'],
            ['controller' => '', 'action' => 'browse', 'tour_json' => '{"steps":[2]}' ],
            ['controller' => 'Item', 'action' => 'edit', 'tour_json' => 'not-json'],
        ];

        $result = $this->invokePrivateMethod($module, 'sanitizeMappings', [$input]);

        $this->assertCount(1, $result);
        $this->assertSame('Item', $result[0]['controller']);
        $this->assertSame('browse', $result[0]['action']);
        $this->assertSame("{\n    \"steps\": [\n        1\n    ]\n}", $result[0]['tour_json']);
    }

    public function testGetControllerActionReturnsLastSegment(): void
    {
        $module = new Module();
        $routeMatch = ['controller' => 'HelpAssistant\\Controller\\Admin\\ItemController', 'action' => 'edit'];

        $result = $this->invokePrivateMethod($module, 'getControllerAction', [$routeMatch]);

        $this->assertSame('ItemController', $result['controller']);
        $this->assertSame('edit', $result['action']);
    }

    public function testLoadAdminAssetsAddsCssJsAndInlineContext(): void
    {
        $module = new Module();
        $routeMatch = ['controller' => 'Admin\\ItemController', 'action' => 'browse'];
        $view = new PhpRenderer($routeMatch);
        $event = new Event($view);

        $module->loadAdminAssets($event);

        $this->assertNotEmpty($view->headLink()->stylesheets);
        $this->assertContains('HelpAssistant/css/introjs.min.css', $view->headLink()->stylesheets);
        $this->assertContains('HelpAssistant/css/helptour.css', $view->headLink()->stylesheets);

        $this->assertContains('HelpAssistant/js/intro.min.js', $view->headScript()->files);
        $this->assertContains('HelpAssistant/js/helpassistant-init.js', $view->headScript()->files);

        $this->assertNotEmpty($view->headScript()->inline);
        $this->assertStringContainsString('HelpAssistantContext', $view->headScript()->inline[0]);
        $this->assertStringContainsString('"ItemController"', $view->headScript()->inline[0]);
        $this->assertStringContainsString('"browse"', $view->headScript()->inline[0]);
    }

    public function testLoadAdminAssetsSkipsNonAdminRoutes(): void
    {
        $module = new Module();
        $routeMatch = ['controller' => 'SiteController', 'action' => 'browse'];
        $view = new PhpRenderer($routeMatch);
        $event = new Event($view);

        $module->loadAdminAssets($event);

        $this->assertSame([], $view->headLink()->stylesheets);
        $this->assertSame([], $view->headScript()->files);
        $this->assertSame([], $view->headScript()->inline);
    }

    public function testAttachListenersRegistersViewLayout(): void
    {
        $module = new Module();
        $sharedEventManager = new class implements SharedEventManagerInterface {
            public $calls = [];
            public function attach($identifier, $event, $listener, $priority = 1)
            {
                $this->calls[] = compact('identifier', 'event', 'listener', 'priority');
            }
        };

        $module->attachListeners($sharedEventManager);

        $this->assertCount(1, $sharedEventManager->calls);
        $call = $sharedEventManager->calls[0];
        $this->assertSame('*', $call['identifier']);
        $this->assertSame('view.layout', $call['event']);
    }
}
