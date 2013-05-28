<?php

namespace SlmQueueDoctrine\Job\Exception;

use RuntimeException;
use SlmQueueDoctrine\Exception\ExceptionInterface;

/**
 * BuryableException
 */
class BuryableException extends RuntimeException implements ExceptionInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * Valid options are:
     *      - message: Message why this has happened
     *      - trace: Stack trace for further investigation
     *
     * @param array $options
     */
    public function __construct(array $options = array())
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
