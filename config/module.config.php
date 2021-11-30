<?php

use SlmQueueDoctrine\Command\RecoverJobsCommand;
use SlmQueueDoctrine\Strategy\ClearObjectManagerStrategy;
use SlmQueueDoctrine\Strategy\IdleNapStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;

return [
    'service_manager' => [
        'factories' => [
            DoctrineWorker::class => \SlmQueue\Factory\WorkerFactory::class,
            RecoverJobsCommand::class => \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ]
    ],
    'laminas-cli' => [
        'commands' => [
            'slm-queue:doctrine:recover' => RecoverJobsCommand::class,
        ],
    ],
    'slm_queue' => [
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
