<?php
namespace GoalioQueueDoctrine\Queue;

use Doctrine\DBAL\DBALException;
use GoalioQueueDoctrine\Queue\Timestamp;
use GoalioQueueDoctrine\Exception;
use SlmQueue\Queue\AbstractQueue;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use SlmQueue\Job\JobInterface;
use SlmQueue\Job\JobPluginManager;

class Table extends AbstractQueue implements TableInterface {

    const STATUS_PENDING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_DELETED = 3;
    const STATUS_BURIED  = 4;

    const LIFETIME_DISABLED = 0;
    const LIFETIME_UNLIMITED = -1;

    /**
     * @var \Doctrine\DBAL\Connection;
     */
    protected $connection;

    /**
     * how long to keep deleted (successful) jobs (in minutes)
     * @var int
     */
    protected $deletedLifetime;

    /**
     * how long to keep buried (failed) jobs (in minutes)
     * @var int
     */
    protected $buriedLifetime;

    /**
     * Table which should be used
     *
     * @var string
     */
    protected $tableName;


    public function __construct(Connection $connection, $tableName, $name, JobPluginManager $jobPluginManager) {
        $this->connection = $connection;
        $this->tableName = $tableName;

        $this->deletedLifetime = static::LIFETIME_DISABLED;
        $this->buriedLifetime  = static::LIFETIME_DISABLED;

        parent::__construct($name, $jobPluginManager);
    }

    /**
     * @param int $buriedLifetime
     */
    public function setBuriedLifetime($buriedLifetime)
    {
        $this->buriedLifetime = $buriedLifetime;
    }

    /**
     * @return int
     */
    public function getBuriedLifetime()
    {
        return $this->buriedLifetime;
    }

    /**
     * @param int $deletedLifetime
     */
    public function setDeletedLifetime($deletedLifetime)
    {
        $this->deletedLifetime = $deletedLifetime;
    }

    /**
     * @return int
     */
    public function getDeletedLifetime()
    {
        return $this->deletedLifetime;
    }


    /**
     * Push a new job into the queue
     *
     * @param  JobInterface $job
     * @param  array        $options
     * @return void
     */
    public function push(JobInterface $job, array $options = array())
    {
        $now = time();
        $delay = isset($options['delay']) ? $options['delay'] : 0;
        $scheduled = isset($options['scheduled']) ? $options['scheduled'] : ($now + $delay);

        $this->connection->insert($this->tableName,array(
                'queue'=> $this->getName(),
                'status' => self::STATUS_PENDING,
                'created' => new Timestamp($now),
                'data' => $job->jsonSerialize(),
                'scheduled' => new Timestamp($scheduled),
            ), array(
                Type::STRING,
                Type::SMALLINT,
                Type::DATETIME,
                Type::TEXT,
                Type::DATETIME,
            )
        );
        $id = $this->connection->lastInsertId();
        $job->setId($id);
    }


    /**
     * Pop a job (or multiple jobs) from the queue
     *
     * @param  array $options
     * @throws \GoalioQueueDoctrine\Exception\RuntimeException
     * @throws \GoalioQueueDoctrine\Exception\LogicException
     * @return JobInterface|JobInterface[]
     */
    public function pop(array $options = array())
    {
        // First run garbage collection
        $this->purge();

        $conn = $this->connection;

        $conn->beginTransaction();
        try {
            $platform = $conn->getDatabasePlatform();
            $select =  'SELECT * ' .
                       'FROM '.$platform->appendLockHint($this->tableName, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE) . ' ' .
                       'WHERE status = ? AND queue = ? AND scheduled > ?' .
                       'ORDER BY scheduled DESC '.
                       'LIMIT 1 ' .
                       $platform->getWriteLockSQL();

            $stmt = $conn->executeQuery($select, array(static::STATUS_PENDING, $this->getName(), time()));
            if($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $update = 'UPDATE ' . $this->tableName . ' ' .
                          'SET status = ?, executed = ? ' .
                          'WHERE id = ? AND status = ?';

                $rows = $conn->executeUpdate($update,
                    array(static::STATUS_RUNNING, new Timestamp, $row['id'], static::STATUS_PENDING),
                    array(Type::SMALLINT, Type::DATETIME, Type::INTEGER, Type::SMALLINT));

                if ($rows != 1) {
                    throw new Exception\LogicException("Race-condition detected while updating item in queue.");
                }
            }
            $conn->commit();

        } catch (DBALException $e) {
            $conn->rollback();
            $conn->close();
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if($row === false) {
            return null;
        }

        $data = json_decode($row['data'], true);

        return $this->createJob($data['class'], $data['content'], array('id' => $row['id']));
    }

    /**
     * Delete a job from the queue
     *
     * @param  JobInterface $job
     * @param array $options
     * @throws \Doctrine\DBAL\DBALException
     * @return void
     */
    public function delete(JobInterface $job, array $options = array())
    {
        if($this->getDeletedLifetime() > static::LIFETIME_DISABLED)  {
            $update = 'UPDATE ' . $this->tableName . ' ' .
                      'SET status = ?, finished = ? ' .
                      'WHERE id = ? AND status = ?';

            $rows = $this->connection->executeUpdate($update,
                 array(static::STATUS_DELETED, new Timestamp, $job->getId(), static::STATUS_RUNNING),
                 array(Type::SMALLINT, Type::DATETIME, Type::INTEGER, Type::SMALLINT));

            if ($rows != 1) {
                throw new Exception\LogicException("Race-condition detected while updating item in queue.");
            }
        }
        else {
            $this->connection->delete($this->tableName, array('id' => $job->getId()));
        }
    }


    /**
     * Valid option is:
     * - message: Message why this has happened
     * - trace: Stack trace for further investigation
     *
     * {@inheritDoc}
     */
    public function bury(JobInterface $job, array $options = array())
    {
        if($this->getBuriedLifetime() > static::LIFETIME_DISABLED)  {
            $message = isset($options['message']) ? $options['message'] : null;
            $trace   = isset($options['trace']) ? $options['trace'] : null;

            $update = 'UPDATE ' . $this->tableName . ' ' .
                      'SET status = ?, finished = ?, message = ?, trace = ? ' .
                      'WHERE id = ? AND status = ?';

            $rows = $this->connection->executeUpdate($update,
                 array(static::STATUS_BURIED, new Timestamp, $message, $trace, $job->getId(), static::STATUS_RUNNING),
                 array(Type::SMALLINT, Type::DATETIME, TYPE::STRING, TYPE::TEXT, TYPE::INTEGER, TYPE::SMALLINT));

            if ($rows != 1) {
                throw new Exception\LogicException("Race-condition detected while updating item in queue.");
            }
        }
        else {
            $this->connection->delete($this->tableName, array('id' => $job->getId()));
        }
    }

    /**
     * Cleans old jobs in the table according to
     * the configured lifetime of successful and
     * failed jobs.
     *
     * @param array $options
     * @return void
     */
    protected function purge(array $options = array()) {
        $conn = $this->connection;
        $now = time();

        $buriedLifetime    =  isset($options['buriedLifetime']) ? $options['buriedLifetime'] : $this->getBuriedLifetime();
        $deletedLifetime   =  isset($options['deletedLifetime']) ? $options['deletedLifetime'] : $this->getDeletedLifetime();

        if($buriedLifetime > static::LIFETIME_UNLIMITED) {
            $buriedLifetime = new Timestamp($now - ($buriedLifetime * 60));
            $delete = 'DELETE FROM '.$this->tableName. ' ' .
                      'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';
            $conn->executeUpdate($delete, array(static::STATUS_BURIED, $buriedLifetime, $this->getName()),
                                          array(Type::INTEGER, Type::DATETIME, Type::STRING));
        }

        if($deletedLifetime > static::LIFETIME_UNLIMITED) {
            $deletedLifetime = new Timestamp($now - ($deletedLifetime * 60));
            $delete = 'DELETE FROM '.$this->tableName. ' ' .
                      'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';
            $conn->executeUpdate($delete, array(static::STATUS_DELETED, $deletedLifetime, $this->getName()),
                                          array(Type::INTEGER, Type::DATETIME, Type::STRING));
        }

    }

    /**
     * Recover jobs which are in the state 'running'
     * for more then $executionTime minutes
     *
     * @param int $executionTime
     * @return int|mixed
     */
    public function recover($executionTime) {
        $executedLifetime = new Timestamp(time() - ($executionTime * 60));

        dump($executedLifetime);

        $update = 'UPDATE ' . $this->tableName . ' ' .
                  'SET status = ? ' .
                  'WHERE executed < ? AND status = ? AND queue = ? AND finished IS NULL';

        $rows = $this->connection->executeUpdate($update,
            array(static::STATUS_PENDING, $executedLifetime, static::STATUS_RUNNING, $this->getName()),
            array(Type::SMALLINT, Type::DATETIME, Type::SMALLINT, Type::STRING));

        return $rows;
    }


    /**
     * Get a job from the queue without processing it
     *
     * @param $id
     * @return \SlmQueue\Job\JobInterface
     */
    public function peek($id) {
        $sql = 'SELECT * FROM '.$this->tableName.' WHERE id = ?';
        $row = $this->connection->fetchAssoc($sql, array($id));
        $data = json_decode($row['data'], true);
        return $this->createJob($data['class'], $data['content'], array('id' => $row['id']));
    }

    /**
     * Valid options are:
     * - scheduled: the time when the job should run the next time OR
     * - delay: the delay in seconds before a job become available to be popped (default to 0 - no delay -)
     *
     * {@inheritDoc}
     */
    public function release(JobInterface $job, array $options = array())
    {
        $now = time();
        $delay        = isset($options['delay']) ? $options['delay'] : 0;
        $scheduleTime = isset($options['scheduled']) ? $options['scheduled'] : $now;
        $scheduleTime = $scheduleTime + $delay;

        $update = 'UPDATE ' . $this->tableName . ' ' .
                  'SET status = ?, finished = ? , scheduled = ?, data = ? ' .
                  'WHERE id = ? AND status = ?';

        $rows = $this->connection->executeUpdate($update, array(static::STATUS_PENDING,
                                                                time(),
                                                                $scheduleTime,
                                                                $job->jsonSerialize(),
                                                                $job->getId(),
                                                                static::STATUS_RUNNING));
        if ($rows != 1) {
            throw new \Doctrine\DBAL\DBALException("Race-condition detected while updating item in queue.");
        }

        return;
    }
}