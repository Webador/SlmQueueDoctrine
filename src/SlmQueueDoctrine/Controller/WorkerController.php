<?php

namespace SlmQueueDoctrine\Controller;

use Exception;
use SlmQueueDoctrine\Queue\Table;
use SlmQueue\Controller\AbstractWorkerController;
use SlmQueue\Controller\Exception\WorkerProcessException;
use SlmQueue\Exception\SlmQueueExceptionInterface;

/**
 * Worker controller
 */
class WorkerController extends AbstractWorkerController
{

    /**
     * {@inheritDoc}
     */
    protected function getWorker() {
        /** @var $worker \SlmQueueDoctrine\Worker\Worker */
        $worker    = $this->serviceLocator->get('SlmQueueDoctrine\Worker\Worker');

        return $worker;
    }

    /**
     * {@inheritDoc}
     */
    protected function getOptions() {

    }

    /**
     * {@inheritDoc}
     */
    protected function getQueueName() {
        $queueName = $this->params('queueName');

        return $queueName;
    }

    /**
     * Recover long running jobs
     *
     * @return string
     */
    public function recoverAction()
    {
        $queueName     = $this->getQueueName();
        $executionTime = $this->params('executionTime', 0);

        /** @var $queueManager \SlmQueue\Queue\QueuepluginManager */
        $queueManager = $this->getServiceLocator()->get('SlmQueue\Queue\QueuePluginManager');
        $queue        = $queueManager->get($queueName);

        if(!$queue instanceof Table) {
            return sprintf("\nQueue % does not support the recovering of job\n\n", $queueName);
        }

        try {
            $count = $queue->recover($executionTime);
        } catch(\Exception $exception) {
            throw new \Exception("An error occurred", null, $exception);
        }

        return sprintf(
            "\nWork for queue %s is done, %s jobs were recovered\n\n",
            $queueName,
            $count
        );
    }
}
