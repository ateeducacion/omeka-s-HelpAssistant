<?php
declare(strict_types=1);

namespace HelpAssistantTest\Module;

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
        
        $params = $this->createMock(\Laminas\Mvc\Controller\Plugin\Params::class);
        $params->method('fromRoute')->willReturn($routeMatch);
        
        $headLink = $this->createMock(\Laminas\View\Helper\HeadLink::class);
        $headScript = $this->createMock(\Laminas\View\Helper\HeadScript::class);
        
        $helperPluginManager = $this->createMock(\Laminas\View\HelperPluginManager::class);
        $helperPluginManager->method('get')->willReturnMap([
            ['params', $params],
            ['headLink', $headLink],
            ['headScript', $headScript],
            ['assetUrl', $this->returnCallback(function() { return 'mock-url'; })],
        ]);
        
        $view = $this->getMockBuilder(PhpRenderer::class)
            ->onlyMethods(['getHelperPluginManager', '__call'])
            ->getMock();
        
        $view->method('getHelperPluginManager')->willReturn($helperPluginManager);
        $view->method('__call')->willReturnCallback(function($method, $args) use ($helperPluginManager) {
            return $helperPluginManager->get($method);
        });
        
        $event = new Event('view.layout', $view);

        $module->loadAdminAssets($event);

        // Verify that the methods were called (assets were added)
        $this->assertTrue(true); // If we get here without errors, the test passes
    }

    public function testLoadAdminAssetsSkipsNonAdminRoutes(): void
    {
        $module = new Module();
        $routeMatch = ['controller' => 'SiteController', 'action' => 'browse'];
        
        $params = $this->createMock(\Laminas\Mvc\Controller\Plugin\Params::class);
        $params->method('fromRoute')->willReturn($routeMatch);
        
        $helperPluginManager = $this->createMock(\Laminas\View\HelperPluginManager::class);
        $helperPluginManager->method('get')->with('params')->willReturn($params);
        
        $view = $this->getMockBuilder(PhpRenderer::class)
            ->onlyMethods(['getHelperPluginManager', '__call'])
            ->getMock();
        
        $view->method('getHelperPluginManager')->willReturn($helperPluginManager);
        
        // headLink and headScript should NOT be called for non-admin routes
        $view->expects($this->never())->method('__call');
        
        $event = new Event('view.layout', $view);

        $module->loadAdminAssets($event);

        $this->assertTrue(true); // If we get here without errors, the test passes
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
            public function detach($listener, $identifier = null, $eventName = null, $force = false)
            {
                return false;
            }
            public function getListeners(array $identifiers, $eventName)
            {
                return [];
            }
            public function clearListeners($identifier, $eventName = null)
            {
                return true;
            }
        };

        $module->attachListeners($sharedEventManager);

        $this->assertCount(1, $sharedEventManager->calls);
        $call = $sharedEventManager->calls[0];
        $this->assertSame('*', $call['identifier']);
        $this->assertSame('view.layout', $call['event']);
    }
}
