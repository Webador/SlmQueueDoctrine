<?php

namespace SlmQueueDoctrine\Entity;

use DateTime;

class QueueJob
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var integer
     */
    protected $status;

    /**
     * @var DateTime
     */
    protected $created;

    /**
     * @var DateTime
     */
    protected $scheduled;

    /**
     * @var DateTime
     */
    protected $executed;

    /**
     * @var DateTime
     */
    protected $finished;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $trace;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $queue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param DateTime $scheduled
     */
    public function setScheduled($scheduled)
    {
        $this->scheduled = $scheduled;
    }

    /**
     * @return DateTime
     */
    public function getScheduled()
    {
        return $this->scheduled;
    }

    /**
     * @param DateTime $executed
     */
    public function setExecuted($executed)
    {
        $this->executed = $executed;
    }

    /**
     * @return DateTime
     */
    public function getExecuted()
    {
        return $this->executed;
    }

    /**
     * @param DateTime $finished
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return DateTime
     */
    public function getFinished()
    {
        return $this->finished;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $trace
     */
    public function setTrace($trace)
    {
        $this->trace = $trace;
    }

    /**
     * @return string
     */
    public function getTrace()
    {
        return $this->trace;
    }
}
