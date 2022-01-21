<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use Doctrine\Persistence\ObjectManager;
use Laminas\EventManager\EventManagerInterface;
use PHPUnit\Framework\TestCase;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\Event\ProcessJobEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueueDoctrine\Strategy\ClearObjectManagerStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;
use SlmQueueDoctrineTest\Asset\OMJob;

class ClearObjectManagerStrategyTest extends TestCase
{
    protected $queue;
    protected $worker;
    /** @var ClearObjectManagerStrategy */
    protected $listener;

    public function setUp(): void
    {
        $this->queue = $this->createMock(QueueInterface::class);
        $this->worker = new DoctrineWorker($this->createMock(EventManagerInterface::class));
        $this->listener = new ClearObjectManagerStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy(): void
    {
        static::assertInstanceOf(AbstractStrategy::class, $this->listener);
    }

    public function testListensToCorrectEventAtCorrectPriority(): void
    {
        $evm = $this->createMock(EventManagerInterface::class);
        $priority = 1;

        $evm->expects($this->once())->method('attach')->with(
            WorkerEventInterface::EVENT_PROCESS_JOB,
            [$this->listener, 'onClear'],
            1000
        );

        $this->listener->attach($evm, $priority);
    }

    public function testOnClearHandler(): void
    {
        $job = new OMJob();
        $om = $this->createMock(ObjectManager::class);

        $job->setObjectManager($om);

        $om->expects($this->once())->method('clear');

        $this->listener->onClear(new ProcessJobEvent($job, $this->worker, $this->queue));
    }
}
