<?php

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * DefaultQueue
 *
 * @ORM\Table(
 *     name="queue_default",
 *     options={"collate"="utf8_bin"},
 *     indexes={
 *          @ORM\Index(name="pop", columns={"status", "queue", "scheduled", "priority"}),
 *          @ORM\Index(name="prune", columns={"status", "queue", "finished"})
 *     }
 * )
 * @ORM\Entity()
 */
class DefaultQueue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="queue", type="string", length=64, nullable=false)
     */
    private $queue;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", nullable=false, length=16777215)
     */
    private $data;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint", length=1, nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="scheduled", type="datetime", nullable=false)
     */
    private $scheduled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="executed", type="datetime", nullable=true)
     */
    private $executed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finished", type="datetime", nullable=true)
     */
    private $finished;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer", nullable=false, options={"default" : 1024})
     */
    private $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     *
     * @ORM\Column(name="trace", type="text", nullable=true)
     */
    private $trace;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set queue
     *
     * @param string $queue
     * @return DefaultQueue
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get queue
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return DefaultQueue
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set status
     *
     * @param int $status
     * @return DefaultQueue
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return DefaultQueue
     */
    public function setCreated(DateTime $created)
    {
        $this->created = clone $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        if ($this->created) {
            return clone $this->created;
        }

        return null;
    }

    /**
     * Set scheduled
     *
     * @param \DateTime $scheduled
     * @return DefaultQueue
     */
    public function setScheduled(DateTime $scheduled)
    {
        $this->scheduled = clone $scheduled;

        return $this;
    }

    /**
     * Get scheduled
     *
     * @return \DateTime
     */
    public function getScheduled()
    {
        if ($this->scheduled) {
            return clone $this->scheduled;
        }

        return null;
    }

    /**
     * Set executed
     *
     * @param \DateTime $executed
     * @return DefaultQueue
     */
    public function setExecuted(DateTime $executed = null)
    {
        $this->executed = $executed ? clone $executed : null;

        return $this;
    }

    /**
     * Get executed
     *
     * @return \DateTime
     */
    public function getExecuted()
    {
        if ($this->executed) {
            return clone $this->executed;
        }

        return null;
    }

    /**
     * Set finished
     *
     * @param \DateTime $finished
     * @return DefaultQueue
     */
    public function setFinished(DateTime $finished = null)
    {
        $this->finished = $finished ? clone $finished : null;

        return $this;
    }

    /**
     * Get finished
     *
     * @return \DateTime
     */
    public function getFinished()
    {
        if ($this->finished) {
            return clone $this->finished;
        }

        return null;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return DefaultQueue
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set trace
     *
     * @param string $trace
     * @return DefaultQueue
     */
    public function setTrace($trace)
    {
        $this->trace = $trace;

        return $this;
    }

    /**
     * Get trace
     *
     * @return string
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /***
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }
}
