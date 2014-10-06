<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use PHPUnit_Framework_TestCase;
use SlmQueue\Worker\WorkerEvent;
use SlmQueueDoctrine\Strategy\IdleNapStrategy;
use SlmQueueDoctrine\Worker\DoctrineWorker;

class IdleNapStrategyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var IdleNapStrategy
     */
    protected $listener;

    public function setUp()
    {
        $this->listener = new IdleNapStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy()
    {
        $this->assertInstanceOf('SlmQueue\Strategy\AbstractStrategy', $this->listener);
    }

    public function testNapDurationDefault()
    {
        $this->assertTrue($this->listener->getNapDuration() == 1);
    }

    public function testNapDurationSetter()
    {
        $this->listener->setNapDuration(2);

        $this->assertTrue($this->listener->getNapDuration() == 2);
    }

    public function testListensToCorrectEvents()
    {
        $evm = $this->getMock('Zend\EventManager\EventManagerInterface');

        $evm->expects($this->at(0))->method('attach')
            ->with(WorkerEvent::EVENT_PROCESS_IDLE, array($this->listener, 'onIdle'), 1);

        $this->listener->attach($evm);
    }

    public function testOnIdleHandler()
    {
        $queue = $this->getMock('SlmQueueDoctrine\Queue\DoctrineQueueInterface');
        $ev    = $this->getMockBuilder('SlmQueue\Worker\WorkerEvent')
                      ->disableOriginalConstructor()
                      ->getMock();

        $ev->expects($this->at(0))->method('getQueue')->will($this->returnValue($queue));
        $ev->expects($this->at(1))->method('getQueue')->will($this->returnValue(null));

        $start_time = microtime(true);
        $this->listener->onIdle($ev);
        $elapsed_time = microtime(true) - $start_time;
        $this->assertGreaterThan(1, $elapsed_time);

        $start_time = microtime(true);
        $this->listener->onIdle($ev);
        $elapsed_time = microtime(true) - $start_time;
        $this->assertLessThan(1, $elapsed_time);
    }
}
