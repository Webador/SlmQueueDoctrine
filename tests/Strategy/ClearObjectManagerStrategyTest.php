<?php

namespace SlmQueueDoctrineTest\Listener\Strategy;

use Doctrine\Common\Persistence\ObjectManager;
use PHPUnit_Framework_TestCase;
use SlmQueue\Queue\AbstractQueue;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\WorkerEvent;
use SlmQueue\Worker\WorkerInterface;
use SlmQueueDoctrine\Strategy\ClearObjectManagerStrategy;
use SlmQueueDoctrineTest\Asset\OMJob;
use SlmQueueDoctrineTest\Asset\SimpleJob;
use Zend\EventManager\EventManagerInterface;

class ClearObjectManagerStrategyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ClearObjectManagerStrategy
     */
    protected $listener;

    /**
     * @var WorkerEvent
     */
    protected $event;

    public function setUp()
    {
        $queue  = $this->getMockBuilder(AbstractQueue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $worker = $this->getMock(WorkerInterface::class);

        $ev  = new WorkerEvent($worker, $queue);
        $job = new SimpleJob();

        $ev->setJob($job);

        $this->listener = new ClearObjectManagerStrategy();
        $this->event    = $ev;
    }

    public function testListenerInstanceOfAbstractStrategy()
    {
        $this->assertInstanceOf(AbstractStrategy::class, $this->listener);
    }


    public function testListensToCorrectEvents()
    {
        $evm = $this->getMock(EventManagerInterface::class);

        $evm->expects($this->at(0))->method('attach')
            ->with(WorkerEvent::EVENT_PROCESS_JOB, [$this->listener, 'onClear'], -1000);

        $this->listener->attach($evm);
    }

    public function testOnClearHandler()
    {
        $job = new OMJob();
        $om  = $this->getMock(ObjectManager::class);

        $job->setObjectManager($om);

        $this->event->setJob($job);

        $om->expects($this->once())->method('clear');

        $this->listener->onClear($this->event);
    }
}
