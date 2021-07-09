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
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Get the options
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
