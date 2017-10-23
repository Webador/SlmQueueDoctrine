<?php

namespace SlmQueueDoctrine\Strategy;

use DoctrineModule\Persistence\ObjectManagerAwareInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\Event\AbstractWorkerEvent;
use SlmQueue\Worker\Event\ProcessJobEvent;
use Zend\EventManager\EventManagerInterface;

class ClearObjectManagerStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            AbstractWorkerEvent::EVENT_PROCESS_JOB,
            [$this, 'onClear'],
            1000
        );
    }

    /**
     * @param ProcessJobEvent $event
     */
    public function onClear(ProcessJobEvent $event)
    {
        /** @var ObjectManagerAwareInterface $job */
        $job = $event->getJob();

        if ($job instanceof ObjectManagerAwareInterface && $job->getObjectManager()) {
            $job->getObjectManager()->clear();
        }
    }
}
