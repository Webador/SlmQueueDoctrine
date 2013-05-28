<?php

namespace SlmQueueDoctrine\Controller;

use SlmQueueDoctrine\Queue\Table;
use SlmQueue\Controller\Exception\WorkerException;
use SlmQueue\Controller\AbstractWorkerController;
use SlmQueue\Exception\ExceptionInterface;

/**
 * Worker controller
 */
class WorkerController extends AbstractWorkerController
{
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

        if(!$queue instanceof Table) {
            return sprintf("\nQueue % does not support the recovering of job\n\n", $queueName);
        }

        try {
            $count = $queue->recover($executionTime);
        } catch(ExceptionInterface $exception) {
            throw new WorkerException("An error occurred", $exception->getCode(), $exception);
        }

        return sprintf(
            "\nWork for queue %s is done, %s jobs were recovered\n\n",
            $queueName,
            $count
        );
    }
}
