<?php
declare(strict_types=1);

namespace HelpAssistant\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;

class MappingFieldset extends Fieldset implements InputFilterProviderInterface
{
    private const CONTROLLER_OPTIONS = [
        'Item' => 'Items', // @translate
        'ItemSet' => 'Item sets', // @translate
        'Site' => 'Sites', // @translate
        'User' => 'Users', // @translate
    ];

    private const ACTION_OPTIONS = [
        'browse' => 'Browse', // @translate
        'add' => 'Add', // @translate
        'edit' => 'Edit', // @translate
    ];

    public function init(): void
    {
        $this->add([
            'name' => 'controller',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Controller', // @translate
                'value_options' => self::CONTROLLER_OPTIONS,
                'empty_option' => 'Select a controller', // @translate
            ],
        ]);

        $this->add([
            'name' => 'action',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Action', // @translate
                'value_options' => self::ACTION_OPTIONS,
                'empty_option' => 'Select an action', // @translate
            ],
        ]);

        $this->add([
            'name' => 'tour_json',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Tour JSON', // @translate
                'info' => 'Paste the Intro.js tour configuration JSON for this controller/action.', // @translate
            ],
            'attributes' => [
                'rows' => 8,
                'class' => 'code',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'controller' => ['required' => false, 'allow_empty' => true],
            'action' => ['required' => false, 'allow_empty' => true],
            'tour_json' => ['required' => false, 'allow_empty' => true],
        ];
    }
}
