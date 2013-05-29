<?php

namespace SlmQueueDoctrine\Queue;

use DateTime;
use DateTimeZone;
use DateInterval;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Type;
use SlmQueue\Queue\AbstractQueue;
use SlmQueue\Job\JobInterface;
use SlmQueue\Job\JobPluginManager;
use SlmQueueDoctrine\Exception;

class Table extends AbstractQueue implements TableInterface
{
    const STATUS_PENDING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_DELETED = 3;
    const STATUS_BURIED  = 4;

    const LIFETIME_DISABLED  = 0;
    const LIFETIME_UNLIMITED = -1;

    /**
     * @var \Doctrine\DBAL\Connection;
     */
    protected $connection;

    /**
     * How long to keep deleted (successful) jobs (in minutes)
     *
     * @var int
     */
    protected $deletedLifetime;

    /**
     * How long to keep buried (failed) jobs (in minutes)
     *
     * @var int
     */
    protected $buriedLifetime;

    /**
     * Table which should be used
     *
     * @var string
     */
    protected $tableName;


    /**
     * Constructor
     *
     * @param Connection       $connection
     * @param string           $tableName
     * @param string           $name
     * @param JobPluginManager $jobPluginManager
     */
    public function __construct(Connection $connection, $tableName, $name, JobPluginManager $jobPluginManager)
    {
        $this->connection = $connection;
        $this->tableName  = $tableName;

        $this->deletedLifetime = static::LIFETIME_DISABLED;
        $this->buriedLifetime  = static::LIFETIME_DISABLED;

        parent::__construct($name, $jobPluginManager);
    }

    /**
     * {@inheritDoc}
     */
    public function setBuriedLifetime($buriedLifetime)
    {
        $this->buriedLifetime = (int) $buriedLifetime;
    }

    /**
     * {@inheritDoc}
     */
    public function getBuriedLifetime()
    {
        return $this->buriedLifetime;
    }

    /**
     * {@inheritDoc}
     */
    public function setDeletedLifetime($deletedLifetime)
    {
        $this->deletedLifetime = (int) $deletedLifetime;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeletedLifetime()
    {
        return $this->deletedLifetime;
    }

    /**
     * Valid options are:
     *      - scheduled: the time when the job should run the next time OR
     *      - delay: the delay before a job becomes available to be popped (defaults no delay)
     *          - accepts an numeric integer as seconds
     *          - a string that is acceptable to the constructor of DateInterval (eg. P1Y or PT45S)
     *          - a string that is acceptable to DateInterval::createFromDateString (eg. 1 week, next thuesday)
     *          - a configured DateInterval instance
     *
     * @see http://en.wikipedia.org/wiki/Iso8601#Durations
     * @see http://www.php.net/manual/en/datetime.formats.relative.php
     *
     * {@inheritDoc}
     */
    public function push(JobInterface $job, array $options = array())
    {
        $now       = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
        $scheduled = clone ($now);

        if (isset($options['scheduled'])) {
            switch (true) {
                case is_numeric($options['scheduled']):
                    $scheduled = new DateTime(sprintf("@%d", (int) $options['scheduled']),
                        new DateTimeZone(date_default_timezone_get()));
                    break;
                case is_string($options['scheduled']):
                    $scheduled = new DateTime($options['scheduled'], new DateTimeZone(date_default_timezone_get()));
                    break;
                case $options['scheduled'] instanceof DateTime:
                    $scheduled = $options['scheduled'];
                    break;
            }
        }

        if (isset($options['delay'])) {
            switch (true) {
                case is_numeric($options['delay']):
                    $delay = new DateInterval(sprintf("PT%dS", abs((int) $options['delay'])));
                    $delay->invert = ($options['delay'] < 0) ? 1 : 0;
                    break;
                case is_string($options['delay']):
                    try {
                        // first try ISO 8601 duration specification
                        $delay = new DateInterval($options['delay']);
                    } catch (\Exception $e) {
                        // then try normal date parser
                        $delay = DateInterval::createFromDateString($options['delay']);
                    }
                    break;
                case $options['delay'] instanceof DateInterval:
                    $delay = $options['delay'];
                    break;
                default:
                    $delay = null;
            }

            if ($delay instanceof DateInterval) {
                $scheduled->add($delay);
            }
        }

        $this->connection->insert($this->tableName, array(
                'queue'     => $this->getName(),
                'status'    => self::STATUS_PENDING,
                'created'   => $now,
                'data'      => $job->jsonSerialize(),
                'scheduled' => $scheduled
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
     * {@inheritDoc}
     * @throws \SlmQueueDoctrine\Exception\RuntimeException
     * @throws \SlmQueueDoctrine\Exception\LogicException
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
                       'FROM ' . $platform->appendLockHint($this->tableName, LockMode::PESSIMISTIC_WRITE) . ' ' .
                       'WHERE status = ? AND queue = ? AND scheduled <= ? ' .
                       'ORDER BY scheduled ASC '.
                       'LIMIT 1 ' .
                       $platform->getWriteLockSQL();

            $stmt = $conn->executeQuery($select, array(static::STATUS_PENDING, $this->getName(), new DateTime),
                array(Type::SMALLINT, Type::STRING, Type::DATETIME));


            if($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $update = 'UPDATE ' . $this->tableName . ' ' .
                          'SET status = ?, executed = ? ' .
                          'WHERE id = ? AND status = ?';

                $rows = $conn->executeUpdate($update,
                    array(static::STATUS_RUNNING, new DateTime, $row['id'], static::STATUS_PENDING),
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

        if ($row === false) {
            return null;
        }

        $data = json_decode($row['data'], true);

        return $this->createJob($data['class'], $data['content'], array('id' => $row['id']));
    }

    /**
     * {@inheritDoc}
     * @throws \Doctrine\DBAL\DBALException
     */
    public function delete(JobInterface $job, array $options = array())
    {
        if ($this->getDeletedLifetime() > static::LIFETIME_DISABLED) {
            $update = 'UPDATE ' . $this->tableName . ' ' .
                      'SET status = ?, finished = ? ' .
                      'WHERE id = ? AND status = ?';

            $rows = $this->connection->executeUpdate($update,
                 array(static::STATUS_DELETED, new DateTime, $job->getId(), static::STATUS_RUNNING),
                 array(Type::SMALLINT, Type::DATETIME, Type::INTEGER, Type::SMALLINT));

            if ($rows != 1) {
                throw new Exception\LogicException("Race-condition detected while updating item in queue.");
            }
        } else {
            $this->connection->delete($this->tableName, array('id' => $job->getId()));
        }
    }


    /**
     * Valid options are:
     *      - message: Message why this has happened
     *      - trace: Stack trace for further investigation
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
                 array(static::STATUS_BURIED, new DateTime, $message, $trace, $job->getId(), static::STATUS_RUNNING),
                 array(Type::SMALLINT, Type::DATETIME, TYPE::STRING, TYPE::TEXT, TYPE::INTEGER, TYPE::SMALLINT));

            if ($rows != 1) {
                throw new Exception\LogicException("Race-condition detected while updating item in queue.");
            }
        } else {
            $this->connection->delete($this->tableName, array('id' => $job->getId()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function recover($executionTime)
    {
        $executedLifetime = new DateTime('@' . (time() - ($executionTime * 60)));

        $update = 'UPDATE ' . $this->tableName . ' ' .
                  'SET status = ? ' .
                  'WHERE executed < ? AND status = ? AND queue = ? AND finished IS NULL';

        $rows = $this->connection->executeUpdate($update,
            array(static::STATUS_PENDING, $executedLifetime, static::STATUS_RUNNING, $this->getName()),
            array(Type::SMALLINT, Type::DATETIME, Type::SMALLINT, Type::STRING));

        return $rows;
    }


    /**
     * {@inheritDoc}
     */
    public function peek($id)
    {
        $sql  = 'SELECT * FROM ' . $this->tableName.' WHERE id = ?';
        $row  = $this->connection->fetchAssoc($sql, array($id));
        $data = json_decode($row['data'], true);

        return $this->createJob($data['class'], $data['content'], array('id' => $row['id']));
    }

    /**
     * Valid options are:
     *      - scheduled: the time when the job should run the next time OR
     *      - delay: the delay in seconds before a job become available to be popped (default to 0 - no delay -)
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

        $rows = $this->connection->executeUpdate($update,
            array(static::STATUS_PENDING, new DateTime, new DateTime('@'.$scheduleTime), $job->jsonSerialize(), $job->getId(), static::STATUS_RUNNING),
            array(Type::SMALLINT, Type::DATETIME, Type::DATETIME, Type::STRING, Type::INTEGER, Type::SMALLINT)
        );

        if ($rows != 1) {
            throw new Exception\LogicException("Race-condition detected while updating item in queue.");
        }
    }

    /**
     * Cleans old jobs in the table according to the configured lifetime of successful and failed jobs.
     *
     * Valid options are:
     *      - buried_lifetime
     *      - deleted_lifetime
     *
     * @param array $options
     * @return void
     */
    protected function purge(array $options = array())
    {
        $conn = $this->connection;
        $now = time();

        $buriedLifetime  = isset($options['buried_lifetime']) ? $options['buried_lifetime'] : $this->getBuriedLifetime();
        $deletedLifetime = isset($options['deleted_lifetime']) ? $options['deleted_lifetime'] : $this->getDeletedLifetime();

        if ($buriedLifetime > static::LIFETIME_UNLIMITED) {
            $buriedLifetime = new DateTime('@' . ($now - ($buriedLifetime * 60)));
            $delete = 'DELETE FROM ' . $this->tableName. ' ' .
                      'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';
            $conn->executeUpdate($delete, array($buriedLifetime, static::STATUS_BURIED, $this->getName()),
                array(Type::DATETIME, Type::INTEGER, Type::STRING));
        }

        if ($deletedLifetime > static::LIFETIME_UNLIMITED) {
            $deletedLifetime = new DateTime('@' . ($now - ($deletedLifetime * 60)));
            $delete = 'DELETE FROM ' . $this->tableName. ' ' .
                      'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';
            $conn->executeUpdate($delete, array($deletedLifetime, static::STATUS_DELETED, $this->getName()),
                array(Type::DATETIME, Type::INTEGER, Type::STRING));
        }
    }
}
