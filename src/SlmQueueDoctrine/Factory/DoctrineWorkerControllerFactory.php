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
        $serviceLocator     = $serviceLocator->getServiceLocator();
        $worker             = $serviceLocator->get('SlmQueueDoctrine\Worker\DoctrineWorker');
        $queuePluginManager = $serviceLocator->get('SlmQueue\Queue\QueuePluginManager');

        return new DoctrineWorkerController($worker, $queuePluginManager);
    }
}
