<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueueDoctrine\Controller\DoctrineWorkerController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * WorkerFactory
 */
class DoctrineWorkerControllerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $worker = $serviceLocator->getServiceLocator()
                                 ->get('SlmQueueDoctrine\Worker\DoctrineWorker');

        $queuePluginManager = $serviceLocator->getServiceLocator()
                                             ->get('SlmQueue\Queue\QueuePluginManager');

        return new DoctrineWorkerController($worker, $queuePluginManager);
    }
}
