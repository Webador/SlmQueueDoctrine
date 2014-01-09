<?php

namespace SlmQueueDoctrineTest\Factory;

use PHPUnit_Framework_TestCase;
use SlmQueueDoctrine\Factory\DoctrineQueueFactory;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;

class DoctrineQueueFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateServiceGetsInstance()
    {
        $sm                 = ServiceManagerFactory::getServiceManager();
        $queuePluginManager = $sm->get('SlmQueue\Queue\QueuePluginManager');
        $factory            = new DoctrineQueueFactory();
        $service            = $factory->createService($queuePluginManager);

        $this->assertInstanceOf('SlmQueueDoctrine\Queue\DoctrineQueue', $service);
    }

    public function testSpecifiedQueueOptionsOverrideModuleDefaults()
    {
        $sm                 = ServiceManagerFactory::getServiceManager();
        $queuePluginManager = $sm->get('SlmQueue\Queue\QueuePluginManager');
        $config             = $sm->get('config');

        $factory            = new DoctrineQueueFactory();
        $service            = $factory->createService($queuePluginManager, 'mydoctrinequeue', 'my-doctrine-queue');

        $this->assertEquals($service->getOptions()->getDeletedLifetime(), $config['slm_queue']['queues']['my-doctrine-queue']['deleted_lifetime']);
        $this->assertEquals($service->getOptions()->getBuriedLifetime(), $config['slm_queue']['queues']['my-doctrine-queue']['buried_lifetime']);
    }
}
