<?php

namespace SlmQueueDoctrine\Worker;

use Exception;
use SlmQueue\Job\JobInterface;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\AbstractWorker;
use SlmQueue\Worker\WorkerEvent;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use SlmQueueDoctrine\Job\Exception as JobException;

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
            return WorkerEvent::JOB_STATUS_SUCCESS;
        } catch (JobException\ReleasableException $exception) {
            $queue->release($job, $exception->getOptions());
            return WorkerEvent::JOB_STATUS_FAILURE_RECOVERABLE;
        } catch (JobException\BuryableException $exception) {
            $queue->bury($job, $exception->getOptions());
            return WorkerEvent::JOB_STATUS_FAILURE;
        } catch (Exception $exception) {
            $queue->bury($job, array('message' => $exception->getMessage(),
                                     'trace' => $exception->getTraceAsString()));
            return WorkerEvent::JOB_STATUS_FAILURE;
        }
    }
}
