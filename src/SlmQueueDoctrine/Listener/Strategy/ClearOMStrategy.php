<?php

namespace SlmQueueDoctrine\Listener\Strategy;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use SlmQueue\Listener\Strategy\AbstractStrategy;
use SlmQueue\Worker\WorkerEvent;
use Zend\EventManager\EventManagerInterface;

class ClearOMStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(WorkerEvent::EVENT_PROCESS_JOB_PRE, array($this, 'onClear'));
    }

    /**
     * @param WorkerEvent $event
     */
    public function onClear(WorkerEvent $event)
    {
        /** @var ObjectManagerAwareInterface $job */
        $job = $event->getJob();

        if ($job instanceof ObjectManagerAwareInterface) {
            $job->getObjectManager()->clear();
        }
    }

}
