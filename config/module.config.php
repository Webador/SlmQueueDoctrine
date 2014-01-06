<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'SlmQueueDoctrine\Options\DoctrineOptions'  => 'SlmQueueDoctrine\Factory\DoctrineOptionsFactory',
            'SlmQueueDoctrine\Worker\DoctrineWorker'    => 'SlmQueueDoctrine\Factory\DoctrineWorkerFactory',
        )
    ),

    'controllers' => array(
        'factories' => array(
            'SlmQueueDoctrine\Controller\DoctrineWorkerController' => 'SlmQueueDoctrine\Factory\DoctrineWorkerControllerFactory',
        ),
    ),

    'console'   => array(
        'router' => array(
            'routes' => array(
                'slm-queue-doctrine-worker' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queue> [--timeout=] --start',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrine\Controller\DoctrineWorkerController',
                            'action'     => 'process'
                        ),
                    ),
                ),
                'slm-queue-doctrine-recover' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queue> --recover [--executionTime=]',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrine\Controller\DoctrineWorkerController',
                            'action'     => 'recover'
                        ),
                    ),
                ),
            ),
        ),
    ),

    'slm_queue' => array(
        'doctrine' => array(
            'connection'       => 'doctrine.connection.orm_default',
            'table_name'       => 'queue_default',
            'deleted_lifetime' => '60',
            'buried_lifetime'  => '60',
            'sleep_when_idle'  => 1,
        ),
    ),
);
