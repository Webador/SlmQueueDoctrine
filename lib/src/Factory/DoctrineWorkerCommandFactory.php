<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueue\Queue\QueuePluginManager;
use SlmQueueDoctrine\Command\DoctrineWorkerCommand;
use SlmQueueDoctrine\Worker\DoctrineWorker;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

/**
 * WorkerFactory
 */
class DoctrineWorkerCommandFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): DoctrineWorkerCommand {
        $worker             = $container->get(DoctrineWorker::class);
        $queuePluginManager = $container->get(QueuePluginManager::class);

        return new DoctrineWorkerCommand($worker, $queuePluginManager);
    }
}
