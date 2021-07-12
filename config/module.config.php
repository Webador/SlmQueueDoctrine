<?php

use SlmQueueDoctrine\Command\RecoverJobsCommand;
use SlmQueueDoctrine\Command\StartWorkerCommand;
use SlmQueueDoctrine\Factory\StartWorkerCommandFactory;
use SlmQueueDoctrine\Strategy\ClearObjectManagerStrategy;
use SlmQueueDoctrine\Strategy\IdleNapStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;

return [
    'service_manager' => [
        'factories' => [
            DoctrineWorker::class => \SlmQueue\Factory\WorkerFactory::class,
            StartWorkerCommand::class => StartWorkerCommandFactory::class,
            RecoverJobsCommand::class => \Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ]
    ],
    'laminas-cli' => [
        'commands' => [
            'slm-queue-doctrine:start' => StartWorkerCommand::class,
            'slm-queue-doctrine:recover' => RecoverJobsCommand::class,
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
