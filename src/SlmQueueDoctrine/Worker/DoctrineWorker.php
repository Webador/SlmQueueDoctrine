<?php

namespace SlmQueueDoctrine\Worker;

use Exception;
use SlmQueue\Job\JobInterface;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\AbstractWorker;
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
            return static::JOB_SUCCESSFUL;
        } catch (JobException\ReleasableException $exception) {
            $queue->release($job, $exception->getOptions());
            return static::JOB_RESCHEDULED;
        } catch (JobException\BuryableException $exception) {
            $queue->bury($job, $exception->getOptions());
            return static::JOB_FAILED;
        } catch (Exception $exception) {
            $queue->bury($job, array('message' => $exception->getMessage(),
                                     'trace' => $exception->getTraceAsString()));
            return static::JOB_FAILED;
        }
    }
}
