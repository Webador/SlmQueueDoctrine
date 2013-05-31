<?php

namespace SlmQueueDoctrine\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SlmQueueDoctrine\Queue\DoctrineQueue;

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

        /** @var $doctrineOptions \SlmQueueDoctrine\Options\DoctrineOptions */
        $doctrineOptions = $parentLocator->get('SlmQueueDoctrine\Options\DoctrineOptions');

        /** @var $connection \Doctrine\DBAL\Connection */
        $connection       = $parentLocator->get($doctrineOptions->getConnection());
        $tableName        = $doctrineOptions->getTableName();
        $jobPluginManager = $parentLocator->get('SlmQueue\Job\JobPluginManager');

        $queue = new DoctrineQueue($connection, $tableName, $requestedName, $jobPluginManager);

        $config = $parentLocator->get('Config');
        $options = isset($config['slm_queue']['queues'][$requestedName]) ? $config['slm_queue']['queues'][$requestedName] : array();

        if (isset($options['sleep_when_idle'])) {
            $queue->setSleepWhenIdle($options['sleep_when_idle']);
        }

        $queue->setBuriedLifetime($doctrineOptions->getBuriedLifetime());
        $queue->setDeletedLifetime($doctrineOptions->getDeletedLifetime());

        return $queue;
    }
}
