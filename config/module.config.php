<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'GoalioQueueDoctrine\Options\DoctrineOptions' => 'GoalioQueueDoctrine\Factory\DoctrineOptionsFactory',
            'GoalioQueueDoctrine\Worker\Worker'           => 'GoalioQueueDoctrine\Factory\WorkerFactory'
        )
    ),

    'console'   => array(
        'router' => array(
            'routes' => array(
                'goalio-queue-doctrine-worker' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queueName> [--timeout=] --start',
                        'defaults' => array(
                            'controller' => 'GoalioQueueDoctrine\Controller\Worker',
                            'action'     => 'process'
                        ),
                    ),
                ),
                'goalio-queue-doctrine-recover' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queueName> --recover [--executiontime=]',
                        'defaults' => array(
                            'controller' => 'GoalioQueueDoctrine\Controller\Worker',
                            'action'     => 'recover'
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'GoalioQueueDoctrine\Controller\Worker' => 'GoalioQueueDoctrine\Controller\WorkerController'
        )
    ),

    'slm_queue' => array(
        'doctrine' => array(
            'connection' => 'doctrine.connection.orm_default',
            'table_name' => 'queue_default',
            'deleted_lifetime' => '60',
            'buried_lifetime' => '60'
        )
    )
);
