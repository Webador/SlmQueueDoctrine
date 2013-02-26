<?php

namespace SlmQueueDoctrine\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SlmQueueDoctrine\Queue\Table;

/**
 * TableFactory
 */
class TableFactory implements FactoryInterface
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

       $table = new Table($connection, $tableName, $requestedName, $jobPluginManager);

       $table->setBuriedLifetime($doctrineOptions->getBuriedLifetime());
       $table->setDeletedLifetime($doctrineOptions->getDeletedLifetime());

       return $table;
   }
}
