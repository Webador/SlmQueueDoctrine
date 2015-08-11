<?php

namespace SlmQueueDoctrine\Strategy;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\WorkerEvent;
use Zend\EventManager\EventManagerInterface;

class ClearObjectManagerStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(
            WorkerEvent::EVENT_PROCESS_JOB,
            [$this, 'onClear'],
            -1000
        );
    }

    /**
     * @param WorkerEvent $event
     */
    public function onClear(WorkerEvent $event)
    {
        /** @var ObjectManagerAwareInterface $job */
        $job = $event->getJob();

        if ($job instanceof ObjectManagerAwareInterface && $job->getObjectManager()) {
            $job->getObjectManager()->clear();
        }
    }
}
