<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueueDoctrine\Options\DoctrineOptions;
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

        /** @var $queueOptions DoctrineOptions */
        $queueOptions = $parentLocator->get('SlmQueueDoctrine\Options\DoctrineOptions');
        $queuesOptions   = $parentLocator->get('SlmQueue\Options\ModuleOptions')->getQueues();

        if (isset($queuesOptions[$requestedName])) {
            $queueOptions->setFromArray($queuesOptions[$requestedName]);
        }

        /** @var $connection \Doctrine\DBAL\Connection */
        $connection       = $parentLocator->get($queueOptions->getConnection());
        $tableName        = $queueOptions->getTableName();
        $jobPluginManager = $parentLocator->get('SlmQueue\Job\JobPluginManager');

        $queue = new DoctrineQueue($connection, $tableName, $requestedName, $jobPluginManager);

        $queue->setBuriedLifetime($queueOptions->getBuriedLifetime());
        $queue->setDeletedLifetime($queueOptions->getDeletedLifetime());
        $queue->setSleepWhenIdle($queueOptions->getSleepWhenIdle());

        return $queue;
    }
}
