<?php
namespace SlmQueueDoctrine\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SlmQueueDoctrine\Worker\DoctrineWorker;

/**
 * WorkerFactory
 */
class DoctrineWorkerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $workerOptions      = $serviceLocator->get('SlmQueue\Options\WorkerOptions');
        $queuePluginManager = $serviceLocator->get('SlmQueue\Queue\QueuePluginManager');

        return new DoctrineWorker($queuePluginManager, $workerOptions);
    }
}
