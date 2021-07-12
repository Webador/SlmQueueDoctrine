<?php

namespace SlmQueueDoctrine\Worker;

use SlmQueueDoctrine\Job\Exception\ReleasableException;
use SlmQueueDoctrine\Job\Exception\BuryableException;
use Throwable;
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
    public function processJob(JobInterface $job, QueueInterface $queue): int
    {
        if (! $queue instanceof DoctrineQueueInterface) {
            return ProcessJobEvent::JOB_STATUS_FAILURE;
        }

        try {

            $job->execute($queue);
            $queue->delete($job);

            return ProcessJobEvent::JOB_STATUS_SUCCESS;
        } catch (ReleasableException $exception) {
            $queue->release($job, $exception->getOptions());

            return ProcessJobEvent::JOB_STATUS_FAILURE_RECOVERABLE;
        } catch (BuryableException $exception) {
            $queue->bury($job, $exception->getOptions());

            return ProcessJobEvent::JOB_STATUS_FAILURE;
        } catch (Throwable $exception) {
            $queue->bury($job, [
                'message' => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString()
            ]);

            return ProcessJobEvent::JOB_STATUS_FAILURE;
        }
    }
}
