<?php

namespace SlmQueueDoctrine\Controller;

use Exception;
use SlmQueueDoctrine\Queue\Table;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * Worker controller
 */
class WorkerController extends AbstractActionController
{
    /**
     * Process the queue
     *
     * @return string
     */
    public function processAction()
    {
        /** @var $worker \SlmQueueDoctrine\Worker\Worker */
        $worker    = $this->serviceLocator->get('SlmQueueDoctrine\Worker\Worker');
        $queueName = $this->params('queueName');
        $options   = array();

        try {
            $count = $worker->processQueue($queueName, array_filter($options));
        } catch(Exception $exception) {
            throw new Exception("An error occurred", null, $exception);
        }

        return sprintf(
            "\nWork for queue %s is done, %s jobs were processed\n\n",
            $queueName,
            $count
        );
    }

    /**
     * Recover long running jobs
     *
     * @return string
     */
    public function recoverAction()
    {
        $queueName     = $this->params('queueName');
        $executionTime = $this->params('executionTime', 0);

        /** @var $queueManager \SlmQueue\Queue\QueuepluginManager */
        $queueManager = $this->getServiceLocator()->get('SlmQueue\Queue\QueuePluginManager');
        $queue        = $queueManager->get($queueName);

        if(!$queue instanceof Table) {
            return sprintf("\nQueue % does not support the recovering of job\n\n", $queueName);
        }

        try {
            $count = $queue->recover($executionTime);
        } catch(Exception $exception) {
            throw new Exception("An error occurred", null, $exception);
        }

        return sprintf(
            "\nWork for queue %s is done, %s jobs were recovered\n\n",
            $queueName,
            $count
        );
    }
}
