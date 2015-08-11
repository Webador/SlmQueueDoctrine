<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueue\Job\JobPluginManager;
use SlmQueueDoctrine\Options\DoctrineOptions;
use SlmQueueDoctrine\Queue\DoctrineQueue;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * DoctrineQueueFactory
 */
class DoctrineQueueFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $name = '', $requestedName = '')
    {
        $parentLocator = $serviceLocator->getServiceLocator();

        $config        = $parentLocator->get('Config');
        $queuesOptions = $config['slm_queue']['queues'];
        $options       = isset($queuesOptions[$requestedName]) ? $queuesOptions[$requestedName] : [];
        $queueOptions  = new DoctrineOptions($options);

        /** @var $connection \Doctrine\DBAL\Connection */
        $connection       = $parentLocator->get($queueOptions->getConnection());
        $jobPluginManager = $parentLocator->get(JobPluginManager::class);

        $queue = new DoctrineQueue($connection, $queueOptions, $requestedName, $jobPluginManager);

        return $queue;
    }
}
