<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use PHPUnit_Framework_TestCase;
use SlmQueue\Worker\WorkerEvent;
use SlmQueueDoctrine\Listener\Strategy\ClearOMStrategy;
use SlmQueueDoctrineTest\Asset\OMJob;
use SlmQueueDoctrineTest\Asset\SimpleJob;

class ClearOMStrategyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ClearOMStrategy
     */
    protected $listener;

    /**
     * @var WorkerEvent
     */
    protected $event;

    public function setUp()
    {
        $queue = $this->getMockBuilder('SlmQueue\Queue\AbstractQueue')
            ->disableOriginalConstructor()
            ->getMock();
        $worker = $this->getMock('SlmQueue\Worker\WorkerInterface');

        $ev     = new WorkerEvent($worker, $queue);
        $job    = new SimpleJob();

        $ev->setJob($job);

        $this->listener = new ClearOMStrategy();
        $this->event    = $ev;
    }

    public function testListenerInstanceOfAbstractStrategy()
    {
        $this->assertInstanceOf('SlmQueue\Listener\Strategy\AbstractStrategy', $this->listener);
    }


    public function testListensToCorrectEvents()
    {
        $evm = $this->getMock('Zend\EventManager\EventManagerInterface');

        $evm->expects($this->at(0))->method('attach')
            ->with(WorkerEvent::EVENT_PROCESS, array($this->listener, 'onClear'), -1000);

        $this->listener->attach($evm);
    }

    public function testOnClearHandler()
    {
        $job   = new OMJob();
        $om    = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $job->setObjectManager($om);

        $this->event->setJob($job);

        $om->expects($this->once())->method('clear');

        $this->listener->onClear($this->event);
    }
}
