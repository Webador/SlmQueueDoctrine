<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use PHPUnit_Framework_TestCase;
use SlmQueue\Worker\WorkerEvent;
use SlmQueueDoctrine\Listener\Strategy\ClearOMStrategy;
use SlmQueueDoctrineTest\Asset\OMJob;

class ClearOMStrategyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ClearOMStrategy
     */
    protected $listener;

    public function setUp()
    {
        $this->listener = new ClearOMStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy()
    {
        $this->assertInstanceOf('SlmQueue\Listener\Strategy\AbstractStrategy', $this->listener);
    }


    public function testListensToCorrectEvents()
    {
        $evm = $this->getMock('Zend\EventManager\EventManagerInterface');

        $evm->expects($this->at(0))->method('attach')
            ->with(WorkerEvent::EVENT_PROCESS_JOB_PRE, array($this->listener, 'onClear'));

        $this->listener->attach($evm);
    }

    public function testOnClearHandler()
    {
        $queue = $this->getMockBuilder('SlmQueue\Queue\AbstractQueue')
            ->disableOriginalConstructor()
            ->getMock();

        $ev    = new WorkerEvent($queue);
        $job   = new OMJob();
        $om    = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $job->setObjectManager($om);
        $ev->setJob($job);

        $om->expects($this->once())->method('clear');

        $this->listener->onClear($ev);
    }
}
