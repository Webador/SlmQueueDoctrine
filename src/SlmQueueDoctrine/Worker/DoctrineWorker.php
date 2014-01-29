<?php

namespace SlmQueueDoctrine\Worker;

use Exception;
use SlmQueue\Job\JobInterface;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\AbstractWorker;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use SlmQueueDoctrine\Job\Exception as JobException;
use Zend\Stdlib\ArrayUtils;

/**
 * Worker for Doctrine
 */
class DoctrineWorker extends AbstractWorker
{
    public function configureStrategies(array $strategies = array())
    {
        $strategies_required = array(
            array('name'=>'SlmQueueDoctrine\Strategy\IdleNapStrategy', 'options' => $this->options->toArray()),
        );

        $strategies = ArrayUtils::merge($strategies, $strategies_required);

        parent::configureStrategies($strategies);
    }

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
        } catch (JobException\ReleasableException $exception) {
            $queue->release($job, $exception->getOptions());
        } catch (JobException\BuryableException $exception) {
            $queue->bury($job, $exception->getOptions());
        } catch (Exception $exception) {
            $queue->bury($job, array('message' => $exception->getMessage(),
                                     'trace' => $exception->getTraceAsString()));
        }
    }
}
