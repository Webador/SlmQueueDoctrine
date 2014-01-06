<?php
namespace SlmQueueDoctrine\Factory;

use SlmQueue\Options\ModuleOptions;
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
        /** @var ModuleOptions $moduleOptions */
        $moduleOptions      = $serviceLocator->get('SlmQueue\Options\ModuleOptions');
        $workerOptions      = $moduleOptions->getWorker();
        $queuePluginManager = $serviceLocator->get('SlmQueue\Queue\QueuePluginManager');

        return new DoctrineWorker($queuePluginManager, $workerOptions);
    }
}
