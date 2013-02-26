<?php
namespace GoalioQueueDoctrine\Factory;

use GoalioQueueDoctrine\Worker\Worker as DoctrineWorker;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * WorkerFactory
 */
class WorkerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $workerOptions = $serviceLocator->get('SlmQueue\Options\WorkerOptions');
        $queuePluginManager = $serviceLocator->get('SlmQueue\Queue\QueuePluginManager');

        return new DoctrineWorker($queuePluginManager, $workerOptions);
    }
}