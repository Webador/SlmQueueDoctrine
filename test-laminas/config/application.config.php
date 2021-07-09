<?php


return [
    'modules' => [
        'Laminas\Router',
        'DoctrineModule',
        'DoctrineORMModule',
        'SlmQueue',
        'SlmQueueDoctrine',
        'TestModule',
    ],
    'module_listener_options' => [
        'config_glob_paths' => [
            'config/autoload/{,*.}{global,local}.php',
        ],
        'module_paths' => [
            './vendor',
            './module',
        ],
    ],
];
