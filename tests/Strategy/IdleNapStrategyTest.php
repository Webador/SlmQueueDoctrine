<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use PHPUnit_Framework_TestCase;
use SlmQueue\Worker\Event\AbstractWorkerEvent;
use SlmQueue\Worker\Event\ProcessIdleEvent;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use SlmQueueDoctrine\Strategy\IdleNapStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;
use Zend\EventManager\EventManagerInterface;

class IdleNapStrategyTest extends PHPUnit_Framework_TestCase
{
    protected $queue;
    protected $worker;
    /** @var IdleNapStrategy */
    protected $listener;

    public function setUp()
    {
        $this->queue    = $this->getMock(\SlmQueue\Queue\QueueInterface::class);
        $this->worker   = new DoctrineWorker($this->getMock(EventManagerInterface::class));
        $this->listener = new IdleNapStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy()
    {
        static::assertInstanceOf(\SlmQueue\Strategy\AbstractStrategy::class, $this->listener);
    }

    public function testListensToCorrectEventAtCorrectPriority()
    {
        $evm      = $this->getMock(EventManagerInterface::class);
        $priority = 1;

        $evm->expects($this->at(0))->method('attach')
            ->with(AbstractWorkerEvent::EVENT_PROCESS_IDLE, [$this->listener, 'onIdle'], 1);

        $this->listener->attach($evm, $priority);
    }

    public function testNapDurationDefault()
    {
        static::assertEquals(1, $this->listener->getNapDuration());
    }

    public function testNapDurationSetter()
    {
        $this->listener->setNapDuration(2);

        static::assertEquals(2, $this->listener->getNapDuration());
    }

    public function testOnIdleHandler()
    {
        $this->queue = $this->getMock(DoctrineQueueInterface::class);

        $start_time = microtime(true);
        $this->listener->onIdle(new ProcessIdleEvent($this->worker, $this->queue));
        $elapsed_time = microtime(true) - $start_time;
        static::assertGreaterThan(1, $elapsed_time);


        $this->queue    = $this->getMock(\SlmQueue\Queue\QueueInterface::class);

        $start_time = microtime(true);
        $this->listener->onIdle(new ProcessIdleEvent($this->worker, $this->queue));
        $elapsed_time = microtime(true) - $start_time;
        static::assertLessThan(1, $elapsed_time);
    }
}
