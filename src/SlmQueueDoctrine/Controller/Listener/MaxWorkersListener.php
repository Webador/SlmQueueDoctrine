<?php

namespace SlmQueueDoctrine\Controller\Listener;


use SlmQueue\Worker\WorkerEvent;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

class MaxWorkersListener implements SharedListenerAggregateInterface
{
    /**
     * @var string path to file used to keep track of count
     */
    protected $lockfile;

    public function __construct($lockfile)
    {
        $this->lockfile = $lockfile;
    }

    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('SlmQueueDoctrine\Worker\DoctrineWorker', WorkerEvent::EVENT_PROCESS_QUEUE_PRE, array($this, 'lock'), 100);
        $this->listeners[] = $events->attach('SlmQueueDoctrine\Worker\DoctrineWorker', WorkerEvent::EVENT_PROCESS_QUEUE_POST, array($this, 'release'), -100);
    }

    public function detachShared(SharedEventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    public function check($max)
    {
        $cnt = file_exists($this->lockfile) ? (int) file_get_contents($this->lockfile) : 0;

        return $cnt >= $max;
    }

    public function lock(EventInterface $e)
    {
        $cnt = file_exists($this->lockfile) ? (int) file_get_contents($this->lockfile) : 0;

        file_put_contents($this->lockfile, ++$cnt);
    }

    public function release(EventInterface $e)
    {
        $cnt = file_exists($this->lockfile) ? (int) file_get_contents($this->lockfile) : 1; // at least one is running

        $cnt--;

        if ($cnt > 0) {
            file_put_contents($this->lockfile, $cnt);
        } elseif (file_exists($this->lockfile)) {
            unlink($this->lockfile);
        }
    }
}
