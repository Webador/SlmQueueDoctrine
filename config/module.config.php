<?php

use SlmQueue\Factory\WorkerFactory;
use SlmQueueDoctrine\Controller\DoctrineWorkerController;
use SlmQueueDoctrine\Factory\DoctrineWorkerControllerFactory;
use SlmQueueDoctrine\Strategy\ClearObjectManagerStrategy;
use SlmQueueDoctrine\Strategy\IdleNapStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;

return [
    'service_manager' => [
        'factories' => [
            DoctrineWorker::class => WorkerFactory::class,
        ]
    ],
    'controllers'     => [
        'factories' => [
            DoctrineWorkerController::class => DoctrineWorkerControllerFactory::class,
        ],
    ],
    'console'         => [
        'router' => [
            'routes' => [
                'slm-queue-doctrine-worker'  => [
                    'type'    => 'Simple',
                    'options' => [
                        'route'    => 'queue doctrine <queue> [--timeout=] --start',
                        'defaults' => [
                            'controller' => DoctrineWorkerController::class,
                            'action'     => 'process'
                        ],
                    ],
                ],
                'slm-queue-doctrine-recover' => [
                    'type'    => 'Simple',
                    'options' => [
                        'route'    => 'queue doctrine <queue> --recover [--executionTime=]',
                        'defaults' => [
                            'controller' => DoctrineWorkerController::class,
                            'action'     => 'recover'
                        ],
                    ],
                ],
            ],
        ],
    ],
    'slm_queue'       => [
        /**
         * Worker Strategies
         */
        'worker_strategies' => [
            'default' => [
                IdleNapStrategy::class => ['nap_duration' => 1],
                ClearObjectManagerStrategy::class
            ],
            'queues'  => [
            ],
        ],
        /**
         * Strategy manager configuration
         */
        'strategy_manager'  => [
            'factories' => [
                IdleNapStrategy::class            => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                ClearObjectManagerStrategy::class => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            ],
        ],
    ],
];
