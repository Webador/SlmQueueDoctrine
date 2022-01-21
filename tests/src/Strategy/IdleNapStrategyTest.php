<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use Laminas\EventManager\EventManagerInterface;
use PHPUnit\Framework\TestCase;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\Event\ProcessIdleEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use SlmQueueDoctrine\Strategy\IdleNapStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;

class IdleNapStrategyTest extends TestCase
{
    protected $queue;
    protected $worker;
    /** @var IdleNapStrategy */
    protected $listener;

    public function setUp(): void
    {
        $this->queue = $this->createMock(QueueInterface::class);
        $this->worker = new DoctrineWorker($this->createMock(EventManagerInterface::class));
        $this->listener = new IdleNapStrategy();
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
            WorkerEventInterface::EVENT_PROCESS_IDLE,
            [$this->listener, 'onIdle'],
            1
        );

        $this->listener->attach($evm, $priority);
    }

    public function testNapDurationDefault(): void
    {
        static::assertEquals(1, $this->listener->getNapDuration());
    }

    public function testNapDurationSetter(): void
    {
        $this->listener->setNapDuration(2);

        static::assertEquals(2, $this->listener->getNapDuration());
    }

    public function testOnIdleHandler(): void
    {
        $this->queue = $this->createMock(DoctrineQueueInterface::class);

        $start_time = microtime(true);
        $this->listener->onIdle(new ProcessIdleEvent($this->worker, $this->queue));
        $elapsed_time = microtime(true) - $start_time;
        static::assertGreaterThan(1, $elapsed_time);


        $this->queue = $this->createMock(QueueInterface::class);

        $start_time = microtime(true);
        $this->listener->onIdle(new ProcessIdleEvent($this->worker, $this->queue));
        $elapsed_time = microtime(true) - $start_time;
        static::assertLessThan(1, $elapsed_time);
    }
}
