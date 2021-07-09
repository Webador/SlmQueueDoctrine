<?php

namespace SlmQueueDoctrine\Strategy;

use DoctrineModule\Persistence\ObjectManagerAwareInterface as DMObjectManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\Event\AbstractWorkerEvent;
use SlmQueue\Worker\Event\ProcessJobEvent;
use SlmQueueDoctrine\Persistence\ObjectManagerAwareInterface;

class ClearObjectManagerStrategy extends AbstractStrategy
{
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            AbstractWorkerEvent::EVENT_PROCESS_JOB,
            [$this, 'onClear'],
            1000
        );
    }

    public function onClear(ProcessJobEvent $event): void
    {
        /** @var ObjectManagerAwareInterface $job */
        $job = $event->getJob();


        if (! ($job instanceof ObjectManagerAwareInterface || $job instanceof DMObjectManagerAwareInterface)) {
            return;
        }

        if (!$manager = $job->getObjectManager()) {
            return;
        }

        $manager->clear();
    }
}
