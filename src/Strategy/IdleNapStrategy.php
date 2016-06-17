<?php

namespace SlmQueueDoctrine\Strategy;

use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Worker\Event\AbstractWorkerEvent;
use SlmQueue\Worker\Event\ProcessIdleEvent;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use Zend\EventManager\EventManagerInterface;

class IdleNapStrategy extends AbstractStrategy
{
    /**
     * How long should we sleep when the worker is idle before trying again
     *
     * @var int
     */
    protected $napDuration = 1;

    /**
     * @param int $napDuration
     */
    public function setNapDuration($napDuration)
    {
        $this->napDuration = (int) $napDuration;
    }

    /**
     * @return int
     */
    public function getNapDuration()
    {
        return $this->napDuration;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            AbstractWorkerEvent::EVENT_PROCESS_IDLE,
            [$this, 'onIdle'],
            1
        );
    }

    /**
     * @param ProcessIdleEvent $event
     */
    public function onIdle(ProcessIdleEvent $event)
    {
        $queue = $event->getQueue();

        if ($queue instanceof DoctrineQueueInterface) {
            sleep($this->napDuration);
        }
    }
}
