<?php

return [
    'Iqomp\\Migrate\\Tests\\Model\\User' => [
        'fields' => [
            'id' => [
                'type' => 'INT',
                'attrs' => [
                    'unsigned' => true,
                    'primary_key' => true,
                    'auto_increment' => true
                ],
                'index' => 0
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'attrs' => [
                    'default' => 'CURRENT_TIMESTAMP'
                ],
                'index' => 1000
            ]
        ]
    ]
];
