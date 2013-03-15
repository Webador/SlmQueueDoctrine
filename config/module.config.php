<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'SlmQueueDoctrine\Options\DoctrineOptions' => 'SlmQueueDoctrine\Factory\DoctrineOptionsFactory',
            'SlmQueueDoctrine\Worker\Worker'           => 'SlmQueueDoctrine\Factory\WorkerFactory'
        )
    ),

    'console'   => array(
        'router' => array(
            'routes' => array(
                'slm-queue-doctrine-worker' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queueName> [--timeout=] --start',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrine\Controller\Worker',
                            'action'     => 'process'
                        ),
                    ),
                ),
                'slm-queue-doctrine-recover' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queueName> --recover [--executionTime=]',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrine\Controller\Worker',
                            'action'     => 'recover'
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'SlmQueueDoctrine\Controller\Worker' => 'SlmQueueDoctrine\Controller\WorkerController'
        )
    ),

    'slm_queue' => array(
        'doctrine' => array(
            'connection'       => 'doctrine.connection.orm_default',
            'table_name'       => 'queue_default',
            'deleted_lifetime' => '60',
            'buried_lifetime'  => '60'
        )
    ),

    'doctrine' => array(
        'driver' => array(
            'queuejob_entity' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
                'paths' => __DIR__ . '/../data/doctrine'
            ),
            'orm_default' => array(
                'drivers' => array(
                    'SlmQueueDoctrine\Entity'  => 'queuejob_entity'
                )
            )
        )
    ),

);
