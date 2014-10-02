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
use SlmQueueDoctrine\Options\DoctrineOptions;

class DoctrineQueue extends AbstractQueue implements DoctrineQueueInterface
{
    const STATUS_PENDING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_DELETED = 3;
    const STATUS_BURIED  = 4;

    const LIFETIME_DISABLED  = 0;
    const LIFETIME_UNLIMITED = -1;

    const DATABASE_PLATFORM_POSTGRES = 'postgresql';

    /**
     * Options for this queue
     *
     * @var DoctrineOptions $options
     */
    protected $options;

    /**
     * Constructor
     *
     * @param Connection       $connection
     * @param string           $tableName
     * @param string           $name
     * @param JobPluginManager $jobPluginManager
     */
    public function __construct(
        Connection $connection,
        DoctrineOptions $options,
        $name,
        JobPluginManager $jobPluginManager
    ) {
        $this->connection = $connection;
        $this->options    = clone $options;

        parent::__construct($name, $jobPluginManager);
    }

    /**
     * @return \SlmQueueDoctrine\Options\DoctrineOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     *
     * Note : see DoctrineQueue::parseOptionsToDateTime for schedule and delay options
     */
    public function push(JobInterface $job, array $options = array())
    {
        $scheduled = $this->parseOptionsToDateTime($options);

        $this->connection->insert($this->options->getTableName(), array(
            'queue'     => $this->getName(),
            'status'    => self::STATUS_PENDING,
            'created'   => new DateTime(null, new DateTimeZone(date_default_timezone_get())),
            'data'      => $this->serializeJob($job),
            'scheduled' => $scheduled
        ), array(
            Type::STRING,
            Type::SMALLINT,
            Type::DATETIME,
            Type::TEXT,
            Type::DATETIME,
        ));

        if (self::DATABASE_PLATFORM_POSTGRES == $this->connection->getDatabasePlatform()->getName()) {
            $id = $this->connection->lastInsertId($this->options->getTableName() . '_id_seq');
        } else {
            $id = $this->connection->lastInsertId();
        }

        $job->setId($id);
    }

    /**
     * {@inheritDoc}
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
                'FROM ' . $platform->appendLockHint($this->options->getTableName(), LockMode::PESSIMISTIC_WRITE) . ' ' .
                'WHERE status = ? AND queue = ? AND scheduled <= ? ' .
                'ORDER BY scheduled ASC '.
                'LIMIT 1 ' .
                $platform->getWriteLockSQL();

            $stmt = $conn->executeQuery(
                $select,
                array(static::STATUS_PENDING, $this->getName(), new DateTime),
                array(Type::SMALLINT, Type::STRING, Type::DATETIME)
            );

            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
                    'SET status = ?, executed = ? ' .
                    'WHERE id = ? AND status = ?';

                $rows = $conn->executeUpdate(
                    $update,
                    array(static::STATUS_RUNNING, new DateTime, $row['id'], static::STATUS_PENDING),
                    array(Type::SMALLINT, Type::DATETIME, Type::INTEGER, Type::SMALLINT)
                );

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

        // Add job ID to meta data
        return $this->unserializeJob($row['data'], array('id' => $row['id']));
    }

    /**
     * {@inheritDoc}
     *
     * Note: When $deletedLifetime === 0 the job will be deleted immediately
     */
    public function delete(JobInterface $job, array $options = array())
    {
        if ($this->options->getDeletedLifetime() === static::LIFETIME_DISABLED) {
            $this->connection->delete($this->options->getTableName(), array('id' => $job->getId()));
        } else {
            $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
                'SET status = ?, finished = ? ' .
                'WHERE id = ? AND status = ?';

            $rows = $this->connection->executeUpdate(
                $update,
                array(
                    static::STATUS_DELETED,
                    new DateTime(null, new DateTimeZone(date_default_timezone_get())),
                    $job->getId(),
                    static::STATUS_RUNNING
                ),
                array(Type::SMALLINT, Type::DATETIME, Type::INTEGER, Type::SMALLINT)
            );

            if ($rows != 1) {
                throw new Exception\LogicException("Race-condition detected while updating item in queue.");
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * Note: When $buriedLifetime === 0 the job will be deleted immediately
     */
    public function bury(JobInterface $job, array $options = array())
    {
        if ($this->options->getBuriedLifetime() === static::LIFETIME_DISABLED) {
            $this->connection->delete($this->options->getTableName(), array('id' => $job->getId()));
        } else {
            $message = isset($options['message']) ? $options['message'] : null;
            $trace   = isset($options['trace']) ? $options['trace'] : null;

            $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
                'SET status = ?, finished = ?, message = ?, trace = ? ' .
                'WHERE id = ? AND status = ?';

            $rows = $this->connection->executeUpdate(
                $update,
                array(
                    static::STATUS_BURIED,
                    new DateTime(null, new DateTimeZone(date_default_timezone_get())),
                    $message,
                    $trace,
                    $job->getId(),
                    static::STATUS_RUNNING
                ),
                array(Type::SMALLINT, Type::DATETIME, TYPE::STRING, TYPE::TEXT, TYPE::INTEGER, TYPE::SMALLINT)
            );

            if ($rows != 1) {
                throw new Exception\LogicException("Race-condition detected while updating item in queue.");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function recover($executionTime)
    {
        $executedLifetime = $this->parseOptionsToDateTime(array('delay' => - ($executionTime * 60)));

        $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
            'SET status = ? ' .
            'WHERE executed < ? AND status = ? AND queue = ? AND finished IS NULL';

        $rows = $this->connection->executeUpdate(
            $update,
            array(static::STATUS_PENDING, $executedLifetime, static::STATUS_RUNNING, $this->getName()),
            array(Type::SMALLINT, Type::DATETIME, Type::SMALLINT, Type::STRING)
        );

        return $rows;
    }

    /**
     * Create a concrete instance of a job from the queue
     *
     * @param  int          $id
     * @return JobInterface
     * @throws Exception\JobNotFoundException
     */
    public function peek($id)
    {
        $sql  = 'SELECT * FROM ' . $this->options->getTableName().' WHERE id = ?';
        $row  = $this->connection->fetchAssoc($sql, array($id), array(Type::SMALLINT));

        if (!$row) {
            throw new Exception\JobNotFoundException(sprintf("Job with id '%s' does not exists.", $id));
        }

        // Add job ID to meta data
        return $this->unserializeJob($row['data'], array('id' => $row['id']));
    }

    /**
     * Reschedules a specific running job
     *
     * Note : see DoctrineQueue::parseOptionsToDateTime for schedule and delay options
     *
     * @param  JobInterface             $job
     * @param  array                    $options
     * @throws Exception\LogicException
     */
    public function release(JobInterface $job, array $options = array())
    {
        $scheduled = $this->parseOptionsToDateTime($options);

        $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
            'SET status = ?, finished = ? , scheduled = ?, data = ? ' .
            'WHERE id = ? AND status = ?';

        $rows = $this->connection->executeUpdate(
            $update,
            array(
                static::STATUS_PENDING,
                new DateTime(null, new DateTimeZone(date_default_timezone_get())),
                $scheduled,
                $this->serializeJob($job),
                $job->getId(),
                static::STATUS_RUNNING
            ),
            array(Type::SMALLINT, Type::DATETIME, Type::DATETIME, Type::STRING, Type::INTEGER, Type::SMALLINT)
        );

        if ($rows != 1) {
            throw new Exception\LogicException("Race-condition detected while updating item in queue.");
        }
    }

    /**
     * Parses options to a datetime object
     *
     * valid options keys:
     *
     * scheduled: the time when the job will be scheduled to run next
     * - numeric string or integer - interpreted as a timestamp
     * - string parserable by the DateTime object
     * - DateTime instance
     * delay: the delay before a job become available to be popped (defaults to 0 - no delay -)
     * - numeric string or integer - interpreted as seconds
     * - string parserable (ISO 8601 duration) by DateTimeInterval::__construct
     * - string parserable (relative parts) by DateTimeInterval::createFromDateString
     * - DateTimeInterval instance
     *
     * @see http://en.wikipedia.org/wiki/Iso8601#Durations
     * @see http://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @param $options array
     * @return DateTime
     */
    protected function parseOptionsToDateTime($options)
    {
        $now       = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
        $scheduled = clone ($now);

        if (isset($options['scheduled'])) {
            switch (true) {
                case is_numeric($options['scheduled']):
                    $scheduled = new DateTime(
                        sprintf("@%d", (int) $options['scheduled']),
                        new DateTimeZone(date_default_timezone_get())
                    );
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

        return $scheduled;
    }

    /**
     * Cleans old jobs in the table according to the configured lifetime of successful and failed jobs.
     */
    protected function purge()
    {
        if ($this->options->getBuriedLifetime() > static::LIFETIME_UNLIMITED) {
            $options = array('delay' => - ($this->options->getBuriedLifetime() * 60));
            $buriedLifetime = $this->parseOptionsToDateTime($options);

            $delete = 'DELETE FROM ' . $this->options->getTableName(). ' ' .
                'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';

            $this->connection->executeUpdate(
                $delete,
                array($buriedLifetime, static::STATUS_BURIED, $this->getName()),
                array(Type::DATETIME, Type::INTEGER, Type::STRING)
            );
        }

        if ($this->options->getDeletedLifetime() > static::LIFETIME_UNLIMITED) {
            $options = array('delay' => - ($this->options->getDeletedLifetime() * 60));
            $deletedLifetime = $this->parseOptionsToDateTime($options);

            $delete = 'DELETE FROM ' . $this->options->getTableName(). ' ' .
                'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';

            $this->connection->executeUpdate(
                $delete,
                array($deletedLifetime, static::STATUS_DELETED, $this->getName()),
                array(Type::DATETIME, Type::INTEGER, Type::STRING)
            );
        }
    }
}
