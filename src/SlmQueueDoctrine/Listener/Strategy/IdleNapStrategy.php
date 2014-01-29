<?php

namespace SlmQueueDoctrine\Listener\Strategy;

use SlmQueue\Listener\Strategy\AbstractStrategy;
use SlmQueue\Worker\WorkerEvent;
use Zend\EventManager\EventManagerInterface;

class IdleNapStrategy extends AbstractStrategy {

    protected $sleep_when_idle = 1;

    /**
     * @param int $sleep_when_idle
     */
    public function setSleepWhenIdle($sleep_when_idle)
    {
        $this->sleep_when_idle = $sleep_when_idle;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->handlers[] = $events->attach(WorkerEvent::EVENT_PROCESS_IDLE, array($this, 'onIdle'));
    }

    public function onIdle(WorkerEvent $event)
    {
        sleep($this->sleep_when_idle);
    }

}