<?php

return [
    'classes' => [
        'vocabulary' => 'Vocabularies',
        'property' => 'Properties',
        'resourceclass' => 'Resource Class',
        'vocabularyaddon' => 'Vocabulary Addon',
    ],
    'permissions' => [
        'vocabulary' => [
            'Omeka\Controller\Admin\Vocabulary' => [
                'Browse' => [
                    'browse', 'show-details', 'properties', 'classes', 'show', 'read'
                ],
                'Add' => [
                    'add', 'import'
                ],
                'Edit' => [
                    'edit', 'update'
                ],
                'Delete' => [
                    'delete', 'delete-confirm'
                ],
            ],
            'Omeka\Api\Adapter\VocabularyAdapter' => [
                'Add' => [
                    'create'
                ],
                'Edit' => [
                    'update'
                ],
                'Delete' => [
                    'delete'
                ],
            ]
        ],
        'property' => [
            'Omeka\Controller\Admin\Property' => [
                'Browse' => [
                    'browse', 'show-details', 'show', 'read'
                ],
                'Add' => [
                    'add'
                ],
                'Edit' => [
                    'edit'
                ],
                'Delete' => [
                    'delete'
                ],
            ]
        ],
        'resourceclass' => [
            'Omeka\Controller\Admin\ResourceClass' => [
                'Browse' => [
                    'browse', 'show-details', 'show', 'read'
                ],
                'Add' => [
                    'add'
                ],
                'Edit' => [
                    'edit'
                ],
                'Delete' => [
                    'delete'
                ],
            ]
        ],
        'vocabularyaddon' => [
            'VocabularyAddon\Controller\Admin\SettingsController' => [
                'Settings' => [
                    'edit'
                ],
                'Backups' => [
                    'info-about', 'details', 'delete-confirm', 'delete', 'backups', 'backuping', 'restore-confirm', 'restore'
                ],
            ]
        ]
    ]
];
