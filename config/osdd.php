<?php

return [
    'layers' => [
        'paths' => [
            'functional' => base_path('functional'),
            'technical' => base_path('technical'),
        ],
    ],

    'tenancy' => [
        'migrations' => 'database/migrations/tenant',
        'expose_migration_paths_to' => null,
    ],
];
