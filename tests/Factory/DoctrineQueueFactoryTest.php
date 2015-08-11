<?php

namespace SlmQueueDoctrineTest\Factory;

use PHPUnit_Framework_TestCase;
use SlmQueue\Queue\QueuePluginManager;
use SlmQueueDoctrine\Factory\DoctrineQueueFactory;
use SlmQueueDoctrine\Queue\DoctrineQueue;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;

class DoctrineQueueFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateServiceGetsInstance()
    {
        $sm                 = ServiceManagerFactory::getServiceManager();
        $queuePluginManager = $sm->get(QueuePluginManager::class);
        $factory            = new DoctrineQueueFactory();
        $service            = $factory->createService($queuePluginManager);

        $this->assertInstanceOf(DoctrineQueue::class, $service);
    }

    public function testSpecifiedQueueOptionsOverrideModuleDefaults()
    {
        $sm                 = ServiceManagerFactory::getServiceManager();
        $queuePluginManager = $sm->get(QueuePluginManager::class);
        $config             = $sm->get('config');

        $factory            = new DoctrineQueueFactory();
        $service            = $factory->createService($queuePluginManager, 'mydoctrinequeue', 'my-doctrine-queue');

        $this->assertEquals($service->getOptions()->getDeletedLifetime(), $config['slm_queue']['queues']['my-doctrine-queue']['deleted_lifetime']);
        $this->assertEquals($service->getOptions()->getBuriedLifetime(), $config['slm_queue']['queues']['my-doctrine-queue']['buried_lifetime']);
    }
}
