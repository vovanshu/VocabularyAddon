<?php

namespace VocabularyAddon;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
        'controller_map' => [
            Controller\Admin\VocabularyControllerDelegator::class => 'omeka/admin/vocabulary',
            Controller\Admin\PropertyControllerDelegator::class => 'omeka/admin/property',
            Controller\Admin\ResourceClassControllerDelegator::class => 'omeka/admin/resource-class',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'VocabularyAddonCommon' => Service\ControllerPlugin\CommonPluginFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\Admin\SettingsController::class => Service\Controller\Admin\SettingsControllerFactory::class,
            \Omeka\Controller\Admin\PropertyController::class => Service\Controller\Admin\PropertyControllerFactory::class,
            \Omeka\Controller\Admin\ResourceClassController::class => Service\Controller\Admin\ResourceClassControllerFactory::class,
        ],
        'delegators' => [
            'Omeka\Controller\Admin\Vocabulary' => [
                Service\Controller\Admin\VocabularyControllerDelegatorFactory::class
            ],
        ]
    ],
    'service_manager' => [
        'factories' => [
            'VocabularyAddon\Common' => Service\ControllerPlugin\CommonPluginFactory::class
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'vocabulary-settings' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/vocabulary-settings[/:action][/:name]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'name' => '[.a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'VocabularyAddon\Controller\Admin',
                                'controller' => Controller\Admin\SettingsController::class,
                                'action' => 'edit',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'VocabularyAddon' => [
        'config' => [
            'backups' => OMEKA_PATH.'/files/backups/VocabularyAddon/',
            'path_permissions' => dirname(__DIR__).'/data/permissions',
            'options' =>  [
                'editall' => 'vocabulary_addon_edit_all',
                'candelete' => 'vocabulary_addon_can_delete',
                'backuprestpl' => 'vocabulary_addon_backup_resource_template',
            ]
        ]
    ]
];
