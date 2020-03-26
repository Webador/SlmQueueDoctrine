<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueue\Queue\QueuePluginManager;
use SlmQueueDoctrine\Controller\DoctrineWorkerController;
use SlmQueueDoctrine\Worker\DoctrineWorker;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

/**
 * WorkerFactory
 */
class DoctrineWorkerControllerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): DoctrineWorkerController {
        $worker             = $container->get(DoctrineWorker::class);
        $queuePluginManager = $container->get(QueuePluginManager::class);

        return new DoctrineWorkerController($worker, $queuePluginManager);
    }
}
