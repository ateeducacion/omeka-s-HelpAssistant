<?php declare(strict_types=1);

namespace HelpAssistantTest\Form;

use HelpAssistant\Form\ConfigForm;
use HelpAssistant\Form\MappingFieldset;
use Laminas\Form\Element\Collection;
use PHPUnit\Framework\TestCase;

class ConfigFormTest extends TestCase
{
    public function testFormAddsMappingsCollectionWithDefaults(): void
    {
        $form = new ConfigForm();
        $form->init();

        $this->assertTrue($form->has('mappings'));

        /** @var Collection $collection */
        $collection = $form->get('mappings');
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame(1, $collection->getOption('count'));
        $this->assertTrue($collection->getOption('allow_add'));
        $this->assertTrue($collection->getOption('should_create_template'));

        $targetElement = $collection->getOption('target_element');
        $this->assertSame(MappingFieldset::class, $targetElement['type'] ?? null);
    }

    public function testInputFilterMakesMappingsOptional(): void
    {
        $form = new ConfigForm();

        $spec = $form->getInputFilterSpecification();
        $this->assertArrayHasKey('mappings', $spec);
        $this->assertFalse($spec['mappings']['required']);
    }
}
