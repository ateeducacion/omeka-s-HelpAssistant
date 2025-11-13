<?php
declare(strict_types=1);

namespace HelpAssistant;

return [
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
                        'options' => [
                            'route' => '/help-assistant',
                            'defaults' => [
                                '__NAMESPACE__' => 'HelpAssistant\Controller',
                                'controller' => 'Index',
                                'action' => 'tours',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
