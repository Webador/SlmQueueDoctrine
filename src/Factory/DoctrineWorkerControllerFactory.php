<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueue\Queue\QueuePluginManager;
use SlmQueueDoctrine\Controller\DoctrineWorkerController;
use SlmQueueDoctrine\Worker\DoctrineWorker;
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
        $worker             = $serviceLocator->get(DoctrineWorker::class);
        $queuePluginManager = $serviceLocator->get(QueuePluginManager::class);

        return new DoctrineWorkerController($worker, $queuePluginManager);
    }
}
