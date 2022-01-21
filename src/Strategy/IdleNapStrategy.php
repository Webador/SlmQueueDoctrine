<?php

namespace SlmQueueDoctrine\Strategy;

use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\Event\AbstractWorkerEvent;
use SlmQueue\Worker\Event\ProcessIdleEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use Laminas\EventManager\EventManagerInterface;

class IdleNapStrategy extends AbstractStrategy
{
    /**
     * How long should we sleep when the worker is idle before trying again
     *
     * @var int
     */
    protected $napDuration = 1;

    public function setNapDuration(int $napDuration): void
    {
        $this->napDuration = $napDuration;
    }

    public function getNapDuration(): int
    {
        return $this->napDuration;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            WorkerEventInterface::EVENT_PROCESS_IDLE,
            [$this, 'onIdle'],
            1
        );
    }

    public function onIdle(ProcessIdleEvent $event): void
    {
        $queue = $event->getQueue();

        if ($queue instanceof DoctrineQueueInterface) {
            sleep($this->napDuration);
        }
    }
}
