<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'SlmQueueDoctrine\Worker\DoctrineWorker'    => 'SlmQueue\Factory\WorkerFactory',
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
);
