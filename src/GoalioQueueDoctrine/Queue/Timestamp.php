<?php
namespace GoalioQueueDoctrine\Queue;

use DateTime;

class Timestamp extends DateTime {

    public function __construct($timestamp = null) {
        parent::__construct();
        $timestamp = $timestamp ?: time();
        $this->setTimestamp($timestamp);
    }

    public function __toString() {
        return $this->format('U');
    }

}