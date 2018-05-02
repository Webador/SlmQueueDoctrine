<?php

namespace SlmQueueDoctrine\Worker;

use Exception;
use SlmQueue\Job\JobInterface;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\AbstractWorker;
use SlmQueue\Worker\Event\ProcessJobEvent;
use SlmQueueDoctrine\Job\Exception as JobException;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;

/**
 * Worker for Doctrine
 */
class DoctrineWorker extends AbstractWorker
{
    /**
     * {@inheritDoc}
     */
    public function processJob(JobInterface $job, QueueInterface $queue)
    {
        if (!$queue instanceof DoctrineQueueInterface) {
            return;
        }

        try {
            $job->execute($queue);
            $queue->delete($job);

            return ProcessJobEvent::JOB_STATUS_SUCCESS;
        } catch (Exception $exception) {
            $queue->bury($job, [
                'message' => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString()
            ]);

            return ProcessJobEvent::JOB_STATUS_FAILURE;
        }
    }
}
