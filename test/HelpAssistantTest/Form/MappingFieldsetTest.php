<?php declare(strict_types=1);

namespace HelpAssistantTest\Form;

use HelpAssistant\Form\MappingFieldset;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Textarea;
use PHPUnit\Framework\TestCase;

class MappingFieldsetTest extends TestCase
{
    public function testFieldsetCreatesControllerActionAndJsonFields(): void
    {
        $fieldset = new MappingFieldset();
        $fieldset->init();

        $this->assertTrue($fieldset->has('controller'));
        $this->assertInstanceOf(Select::class, $fieldset->get('controller'));
        $controllerOptions = $fieldset->get('controller')->getValueOptions();
        $this->assertArrayHasKey('Item', $controllerOptions);
        $this->assertArrayHasKey('User', $controllerOptions);

        $this->assertTrue($fieldset->has('action'));
        $this->assertInstanceOf(Select::class, $fieldset->get('action'));
        $actionOptions = $fieldset->get('action')->getValueOptions();
        $this->assertArrayHasKey('browse', $actionOptions);
        $this->assertArrayHasKey('edit', $actionOptions);

        $this->assertTrue($fieldset->has('tour_json'));
        $this->assertInstanceOf(Textarea::class, $fieldset->get('tour_json'));
        $this->assertSame(8, $fieldset->get('tour_json')->getAttributes()['rows']);
    }

    public function testInputFilterAllowsEmptyValues(): void
    {
        $fieldset = new MappingFieldset();

        $spec = $fieldset->getInputFilterSpecification();
        $this->assertFalse($spec['controller']['required']);
        $this->assertTrue($spec['controller']['allow_empty']);
        $this->assertFalse($spec['action']['required']);
        $this->assertTrue($spec['tour_json']['allow_empty']);
    }
}
