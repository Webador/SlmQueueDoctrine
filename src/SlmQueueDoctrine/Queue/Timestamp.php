<?php

namespace SlmQueueDoctrine\Queue;

use DateTime;

/**
 * Timestamp
 */
class Timestamp extends DateTime
{
    /**
     * @param null $timestamp
     */
    public function __construct($timestamp = null)
    {
        parent::__construct();
        $timestamp = $timestamp ?: time();
        $this->setTimestamp($timestamp);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->format('U');
    }
}
