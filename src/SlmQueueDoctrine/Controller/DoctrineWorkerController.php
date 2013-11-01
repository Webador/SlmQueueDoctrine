<?php

namespace SlmQueueDoctrine\Controller;

use SlmQueue\Worker\WorkerEvent;
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
            if (file_exists($lockfile)) {
                $cnt = (int) file_get_contents($lockfile);

                if ($cnt >= (int) $options['max-workers']) {
                    return sprintf(
                        "Exceeding maximum workers '%s' this queue '%s'\n",
                        $cnt,
                        $options['queue']
                    );
                }
            } else {
                $cnt      = 0;
            }

            $this->getEventManager()->getSharedManager()->attach('SlmQueueDoctrine\Worker\DoctrineWorker', WorkerEvent::EVENT_PROCESS_QUEUE_PRE, function(WorkerEvent $e) use ($lockfile, $cnt) {
                file_put_contents($lockfile, ++$cnt);
            });

            $this->getEventManager()->getSharedManager()->attach('SlmQueueDoctrine\Worker\DoctrineWorker', WorkerEvent::EVENT_PROCESS_QUEUE_POST, function(WorkerEvent $e) use ($lockfile, $cnt) {
                if ($cnt >= 1) {
                    file_put_contents($lockfile, $cnt);
                } else {
                    if (file_exists($lockfile)) {
                        unlink($lockfile);
                    }
                }
            });
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
