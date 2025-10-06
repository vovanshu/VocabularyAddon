<?php

namespace VocabularyAddon;

return [
    'permissions' => [
        'classes' => [
            'vocabulary' => 'Vocabularies', // @translate
            'properties' => 'Properties', // @translate
            'resourceclasses' => 'Resource Classes', // @translate
        ],
        'labels' => [
            'settings_vocabularyaddon' => 'Settings Vocabulary Addition', // @translate
        ],
        'rules' => [
            'vocabulary' => [
                'Omeka\Controller\Admin\Vocabulary' => [
                    'browse' => [
                        'browse', 'properties', 'classes',
                    ],
                    'show' => [
                        'show-details', 'show', 'read',
                    ],
                    'add' => [
                        'add', 'import'
                    ],
                    'edit' => [
                        'edit', 'update'
                    ],
                    'delete' => [
                        'delete', 'delete-confirm'
                    ],
                ],
                'Omeka\Api\Adapter\VocabularyAdapter' => [
                    'add' => [
                        'create'
                    ],
                    'edit' => [
                        'update'
                    ],
                    'delete' => [
                        'delete'
                    ],
                ]
            ],
            'properties' => [
                'Omeka\Controller\Admin\Property' => [
                    'browse' => [
                        'browse',
                    ],
                    'show' => [
                        'show-details', 'show', 'read'
                    ],
                    'add' => [
                        'add'
                    ],
                    'edit' => [
                        'edit'
                    ],
                    'delete' => [
                        'delete'
                    ],
                ]
            ],
            'resourceclasses' => [
                'Omeka\Controller\Admin\ResourceClass' => [
                    'browse' => [
                        'browse', 'show-details', 'show', 'read'
                    ],
                    'show' => [
                        'show-details', 'show', 'read'
                    ],
                    'add' => [
                        'add'
                    ],
                    'edit' => [
                        'edit'
                    ],
                    'delete' => [
                        'delete'
                    ],
                ]
            ],
            'modules' => [
                'VocabularyAddon\Controller\Admin\SettingsController' => [
                    'settings_vocabularyaddon' => [
                        'edit', 'info-about', 'details', 'delete-confirm', 'delete', 'backups', 'backuping', 'restore-confirm', 'restore'
                    ],
                ],
            ],
        ],
    ],
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
            [
                'type' => 'gettext',
                'base_dir' => OMEKA_PATH . '/files/languages/VocabularyAddon',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'vocabularyaddon' => [
        'backups' => OMEKA_PATH.'/files/backup/VocabularyAddon/',
        'settings' => [
            'vocabulary_addon_edit_all' => 'false',
            'vocabulary_addon_can_delete' => 'false',
            'vocabulary_addon_backup_resource_template' => 'false',
        ],
        'options' =>  [
            'editall' => 'vocabulary_addon_edit_all',
            'candelete' => 'vocabulary_addon_can_delete',
            'backuprestpl' => 'vocabulary_addon_backup_resource_template',
        ]
    ]
];
