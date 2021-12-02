<?php

namespace SlmQueueDoctrineTest\Factory;

use PHPUnit\Framework\TestCase;
use SlmQueueDoctrine\Factory\DoctrineQueueFactory;
use SlmQueueDoctrine\Queue\DoctrineQueue;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;

class DoctrineQueueFactoryTest extends TestCase
{
    public function testCreateServiceGetsInstance(): void
    {
        $sm = ServiceManagerFactory::getServiceManager();
        $factory = new DoctrineQueueFactory();
        $service = $factory($sm, 'some-queue');

        static::assertInstanceOf(DoctrineQueue::class, $service);
    }

    public function testSpecifiedQueueOptionsOverrideModuleDefaults(): void
    {
        $sm = ServiceManagerFactory::getServiceManager();
        $config = $sm->get('config');

        $factory = new DoctrineQueueFactory();
        $service = $factory($sm, 'my-doctrine-queue');

        static::assertEquals(
            $service->getOptions()->getDeletedLifetime(),
            $config['slm_queue']['queues']['my-doctrine-queue']['deleted_lifetime']
        );
        static::assertEquals(
            $service->getOptions()->getBuriedLifetime(),
            $config['slm_queue']['queues']['my-doctrine-queue']['buried_lifetime']
        );
    }
}
