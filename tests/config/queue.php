<?php

return [
    'default' => 'file',
    'connections' => [
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/queue',
            'is_absolute' => true,
            'permission' => 0777,
        ],
    ],
];
