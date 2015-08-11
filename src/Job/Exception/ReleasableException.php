<?php

namespace SlmQueueDoctrine\Job\Exception;

use RuntimeException;
use SlmQueueDoctrine\Exception\ExceptionInterface;

/**
 * ReleasableException. Throw this exception in the "execute" method of your job so that the worker
 * puts back the job into the queue
 */
class ReleasableException extends RuntimeException implements ExceptionInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Valid options are:
     *      - scheduled: the time when the job should run the next time
     *      - delay: the delay in seconds before a job become available to be popped (default to 0 - no delay -)
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Get the options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
