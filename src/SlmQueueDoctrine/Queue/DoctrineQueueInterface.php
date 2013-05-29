<?php

namespace SlmQueueDoctrine\Queue;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Job\JobInterface;

interface DoctrineQueueInterface extends QueueInterface
{
    /**
     * Put a job that was popped back to the queue
     *
     * @param  JobInterface $job
     * @param  array $options
     * @return void
     */
    public function release(JobInterface $job, array $options = array());

    /**
     * Bury a job. When a job is buried, it won't be retrieved from the queue
     *
     * @param  JobInterface $job
     * @param  array $options
     * @return void
     */
    public function bury(JobInterface $job, array $options = array());

    /**
     * Recover jobs which are in the state 'running' for more then $executionTime minutes
     *
     * @param  int $executionTime
     * @return mixed
     */
    public function recover($executionTime);

    /**
     * Get a job from the queue without processing it
     *
     * @param  int $id
     * @return JobInterface
     */
    public function peek($id);
}
