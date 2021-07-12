<?php

use Doctrine\DBAL\Driver\PDOSqlite\Driver;

return [
    'slm_queue' => [
        'queues' => [
            'default' => [],
        ],
        'queue_manager' => [
            'factories' => [
                'default' => \SlmQueueDoctrine\Factory\DoctrineQueueFactory::class,
            ],
        ],
        'worker_strategies' => [
            'default' => [
                \SlmQueue\Strategy\MaxRunsStrategy::class => ['max_runs' => 1],
                \SlmQueue\Strategy\WorkerLifetimeStrategy::class => ['lifetime' => 3],
            ],
        ],
    ],
    'dependencies' => [
        'factories' => [
            'doctrine.entity_manager.orm_default' => \Roave\PsrContainerDoctrine\EntityManagerFactory::class,
            'doctrine.connection.orm_default' => \Roave\PsrContainerDoctrine\ConnectionFactory::class,
        ]
    ],
    'doctrine'  => [
        'connection' => [
            'orm_default' => [
                'driverClass' => Driver::class,
                'params'      => [
                    'url' => 'sqlite:///temp/database.sqlite',
                ],
            ],
        ],
    ],

];
