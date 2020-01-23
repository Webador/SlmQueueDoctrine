<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueue\Job\JobPluginManager;
use SlmQueueDoctrine\Options\DoctrineOptions;
use SlmQueueDoctrine\Queue\DoctrineQueue;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;

/**
 * DoctrineQueueFactory
 */
class DoctrineQueueFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): DoctrineQueue
    {
        $config        = $container->get('config');
        $queuesOptions = $config['slm_queue']['queues'];
        $options       = isset($queuesOptions[$requestedName]) ? $queuesOptions[$requestedName] : [];
        $queueOptions  = new DoctrineOptions($options);

        /** @var $connection \Doctrine\DBAL\Connection */
        $connection       = $container->get($queueOptions->getConnection());
        $jobPluginManager = $container->get(JobPluginManager::class);

        return new DoctrineQueue($connection, $queueOptions, $requestedName, $jobPluginManager);
    }

    /**
     * {@inheritDoc}
     */
    public function createService(
        ServiceLocatorInterface $serviceLocator,
        $name = '',
        $requestedName = ''
    ): DoctrineQueue {
        return $this($serviceLocator->getServiceLocator(), $requestedName);
    }
}
