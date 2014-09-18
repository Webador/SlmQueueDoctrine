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
        $parentServiceLocator = $serviceLocator->getServiceLocator();

        return new DoctrineWorkerController(
            $parentServiceLocator->get('SlmQueueDoctrine\Worker\DoctrineWorker'),
            $parentServiceLocator->get('SlmQueue\Queue\QueuePluginManager')
        );
    }
}
