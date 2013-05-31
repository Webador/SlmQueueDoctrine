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
}
