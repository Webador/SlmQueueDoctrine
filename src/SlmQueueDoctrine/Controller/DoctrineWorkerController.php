<?php

namespace SlmQueueDoctrine\Controller;

use SlmQueue\Worker\WorkerEvent;
use SlmQueueDoctrine\Controller\Listener\MaxWorkersListener;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use SlmQueue\Controller\Exception\WorkerException;
use SlmQueue\Controller\AbstractWorkerController;
use SlmQueue\Exception\ExceptionInterface;

/**
 * Worker controller
 */
class DoctrineWorkerController extends AbstractWorkerController
{
    /**
     * @inheritdoc
     */
    public function processAction()
    {
        $options = $this->params()->fromRoute();

        if (isset($options['max-workers']) && is_numeric($options['max-workers'])) {
            $lockfile = sprintf('data/slm-queue-doctrine-worker-%s.cnt', $options['queue']);

            $listener = new MaxWorkersListener($lockfile);

            if ($listener->check($options['max-workers'])) {
                return sprintf("Exceeding maximum workers '%s' this queue '%s'\n",
                        $options['max-workers'],
                        $options['queue']
                    );
            }

            $this->getEventManager()->getSharedManager()->attachAggregate($listener);
        }

        return parent::processAction();
    }

    /**
     * Recover long running jobs
     *
     * @return string
     */
    public function recoverAction()
    {
        $queueName     = $this->params('queue');
        $executionTime = $this->params('executionTime', 0);

        /** @var $queueManager \SlmQueue\Queue\QueuePluginManager */
        $queueManager = $this->getServiceLocator()->get('SlmQueue\Queue\QueuePluginManager');
        $queue        = $queueManager->get($queueName);

        if (!$queue instanceof DoctrineQueueInterface) {
            return sprintf("\nQueue % does not support the recovering of job\n\n", $queueName);
        }

        try {
            $count = $queue->recover($executionTime);
        } catch (ExceptionInterface $exception) {
            throw new WorkerException("An error occurred", $exception->getCode(), $exception);
        }

        return sprintf(
            "\nWork for queue %s is done, %s jobs were recovered\n\n",
            $queueName,
            $count
        );
    }
}
