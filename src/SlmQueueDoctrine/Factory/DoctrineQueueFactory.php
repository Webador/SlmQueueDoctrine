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

        $table = new DoctrineQueue($connection, $tableName, $requestedName, $jobPluginManager);

        $config = $parentLocator->get('Config');
        $options = array_key_exists($requestedName, $config['slm_queue']['queues']) ? $config['slm_queue']['queues'][$requestedName] : array();

        if (isset($options['sleep_when_idle'])) {
            $table->setSleepWhenIdle($options['sleep_when_idle']);
        }

        $table->setBuriedLifetime($doctrineOptions->getBuriedLifetime());
        $table->setDeletedLifetime($doctrineOptions->getDeletedLifetime());

        return $table;
    }
}
