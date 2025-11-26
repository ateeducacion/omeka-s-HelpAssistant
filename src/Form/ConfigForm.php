<?php
declare(strict_types=1);

namespace HelpAssistant\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class ConfigForm extends Form implements InputFilterProviderInterface
{
    public function init(): void
    {
        $this->add([
            'name' => 'mappings',
            'type' => Element\Collection::class,
            'options' => [
                'label' => 'Controller/action tours', // @translate
                'count' => 1,
                'allow_add' => true,
                'should_create_template' => true,
                'target_element' => [
                    'type' => MappingFieldset::class,
                ],
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'mappings' => [
                'required' => false,
            ],
        ];
    }
}
