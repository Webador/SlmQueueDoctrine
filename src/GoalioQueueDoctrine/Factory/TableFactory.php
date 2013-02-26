<?php
namespace GoalioQueueDoctrine\Factory;

use Zend\ServiceManager\FactoryInterface;
use GoalioQueueDoctrine\Queue\Table;
use Zend\ServiceManager\ServiceLocatorInterface;

class TableFactory implements FactoryInterface {

   /**
     *  {@inheritDoc}
     */
   public function createService(ServiceLocatorInterface $serviceLocator, $name = '', $requestedName = '')
   {
       $parentLocator = $serviceLocator->getServiceLocator();

       /**
        * @var $doctrineOptions \GoalioQueueDoctrine\Options\DoctrineOptions
        */
       $doctrineOptions = $parentLocator->get('GoalioQueueDoctrine\Options\DoctrineOptions');

       /**
        * @var $connection \Doctrine\DBAL\Connection
        */
       $connection       = $parentLocator->get($doctrineOptions->getConnection());
       $tableName        = $doctrineOptions->getTableName();
       $jobPluginManager = $parentLocator->get('SlmQueue\Job\JobPluginManager');

       $table = new Table($connection, $tableName, $requestedName, $jobPluginManager);

       $table->setBuriedLifetime($doctrineOptions->getBuriedLifetime());
       $table->setDeletedLifetime($doctrineOptions->getDeletedLifetime());

       return $table;
   }
}