<?php
declare(strict_types=1);

namespace HelpAssistant;

use HelpAssistant\Controller\IndexController;
use HelpAssistant\Form\ConfigForm;
use HelpAssistant\Form\MappingFieldset;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'controllers' => [
        'factories' => [
            IndexController::class => InvokableFactory::class,
        ],
        'aliases' => [
            'HelpAssistant\Controller\Index' => IndexController::class,
            'Index' => IndexController::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            ConfigForm::class => InvokableFactory::class,
            MappingFieldset::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'router' => [

        'routes' => [
            'admin' => [
                'child_routes' => [
                    'help-assistant' => [
                        'type' => 'Literal',
                        'may_terminate' => true,
                        'options' => [
                            'route' => '/help-assistant',
                            'defaults' => [
                                '__NAMESPACE__' => 'HelpAssistant\Controller',
                                'controller' => 'Index',
                                'action' => 'tours',
                            ],
                        ],
                    ],
                    'help-assistant-tours-map' => [
                        'type' => 'Literal',
                        'may_terminate' => true,
                        'options' => [
                            'route' => '/help-assistant/tours-map',
                            'defaults' => [
                                '__NAMESPACE__' => 'HelpAssistant\Controller',
                                'controller' => 'Index',
                                'action' => 'tours-map',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
