<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use SlmQueue\Worker\Event\AbstractWorkerEvent;
use SlmQueue\Worker\Event\ProcessJobEvent;
use SlmQueueDoctrine\Strategy\ClearObjectManagerStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;
use SlmQueueDoctrineTest\Asset\OMJob;
use SlmQueueTest\Asset\SimpleWorker;
use Laminas\EventManager\EventManagerInterface;

class ClearObjectManagerStrategyTest extends TestCase
{
    protected $queue;
    protected $worker;
    /** @var ClearObjectManagerStrategy */
    protected $listener;

    public function setUp(): void
    {
        $this->queue    = $this->createMock(\SlmQueue\Queue\QueueInterface::class);
        $this->worker   = new DoctrineWorker($this->createMock(EventManagerInterface::class));
        $this->listener = new ClearObjectManagerStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy()
    {
        static::assertInstanceOf(\SlmQueue\Strategy\AbstractStrategy::class, $this->listener);
    }

    public function testListensToCorrectEventAtCorrectPriority()
    {
        $evm      = $this->createMock(EventManagerInterface::class);
        $priority = 1;

        $evm->expects($this->at(0))->method('attach')
            ->with(AbstractWorkerEvent::EVENT_PROCESS_JOB, [$this->listener, 'onClear'], 1000);

        $this->listener->attach($evm, $priority);
    }

    public function testOnClearHandler()
    {
        $job = new OMJob();
        $om  = $this->createMock(ObjectManager::class);

        $job->setObjectManager($om);

        $om->expects($this->once())->method('clear');

        $this->listener->onClear(new ProcessJobEvent($job, $this->worker, $this->queue));
    }
}
