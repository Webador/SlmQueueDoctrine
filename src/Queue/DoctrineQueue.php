<?php

namespace SlmQueueDoctrine\Queue;

use SlmQueueDoctrine\Exception\LogicException;
use SlmQueueDoctrine\Exception\RuntimeException;
use SlmQueueDoctrine\Exception\JobNotFoundException;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Types;
use SlmQueue\Job\JobInterface;
use SlmQueue\Job\JobPluginManager;
use SlmQueue\Queue\AbstractQueue;
use SlmQueueDoctrine\Options\DoctrineOptions;
use SlmQueueDoctrine\Worker\DoctrineWorker;

class DoctrineQueue extends AbstractQueue implements DoctrineQueueInterface
{
    protected static $defaultWorkerName = DoctrineWorker::class;

    public const STATUS_PENDING = 1;
    public const STATUS_RUNNING = 2;
    public const STATUS_DELETED = 3;
    public const STATUS_BURIED  = 4;

    public const LIFETIME_DISABLED  = 0;
    public const LIFETIME_UNLIMITED = -1;

    private const DATABASE_PLATFORM_POSTGRES = 'postgresql';

    public const DEFAULT_PRIORITY = 1024;

    /**
     * Options for this queue
     */
    protected DoctrineOptions $options;

    /**
     * Used to synchronize time calculations
     */
    private DateTimeInterface $now;

    private Connection $connection;

    public function __construct(
        Connection $connection,
        DoctrineOptions $options,
        string $name,
        JobPluginManager $jobPluginManager
    ) {
        $this->connection = $connection;
        $this->options    = clone $options;

        parent::__construct($name, $jobPluginManager);
    }

    public function getOptions(): DoctrineOptions
    {
        return $this->options;
    }

    /**
     * Valid options are:
     *      - priority: the lower the priority is, the sooner the job get popped from the queue (default to 1024)
     *
     * Note : see DoctrineQueue::parseOptionsToDateTime for schedule and delay options
     */
    public function push(JobInterface $job, array $options = []): void
    {
        $time      = microtime(true);
        $micro     = sprintf("%06d", ($time - floor($time)) * 1000000);
        $this->now = new DateTime(
            date('Y-m-d H:i:s.' . $micro, (int)$time),
            new DateTimeZone(date_default_timezone_get())
        );
        $scheduled = $this->parseOptionsToDateTime($options);

        $this->connection->insert($this->options->getTableName(), [
            'queue'     => $this->getName(),
            'status'    => self::STATUS_PENDING,
            'created'   => $this->now->format('Y-m-d H:i:s.u'),
            'data'      => $this->serializeJob($job),
            'scheduled' => $scheduled->format('Y-m-d H:i:s.u'),
            'priority'  => isset($options['priority']) ? $options['priority'] : self::DEFAULT_PRIORITY,
        ], [
            Types::STRING,
            Types::SMALLINT,
            Types::STRING,
            Types::TEXT,
            Types::STRING,
            Types::INTEGER,
        ]);

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
    public function pop(array $options = []): ?JobInterface
    {
        // First run garbage collection
        $this->purge();

        $conn = $this->connection;
        $conn->beginTransaction();

        $time      = microtime(true);
        $micro     = sprintf("%06d", ($time - floor($time)) * 1000000);
        $this->now = new DateTime(
            date('Y-m-d H:i:s.' . $micro, (int)$time),
            new DateTimeZone(date_default_timezone_get())
        );

        try {
            $platform = $conn->getDatabasePlatform();

            $queryBuilder = $conn->createQueryBuilder();

            $queryBuilder
                ->select('*')
                ->from($platform->appendLockHint($this->options->getTableName(), LockMode::PESSIMISTIC_WRITE))
                ->where('status = ?')
                ->andWhere('queue = ?')
                ->andWhere('scheduled <= ?')
                ->addOrderBy('priority', 'ASC')
                ->addOrderBy('scheduled', 'ASC')
                ->setParameter(0, static::STATUS_PENDING)
                ->setParameter(1, $this->getName())
                ->setParameter(2, $this->now->format('Y-m-d H:i:s.u'))
                ->setMaxResults(1);

            // Modify the query so it supports row locking (if applicable for database provider)
            // @see https://github.com/doctrine/doctrine2/blob/5f3afa4c4ffb8cb49870d794cc7daf6a49406966/lib/Doctrine/ORM/Query/SqlWalker.php#L556-L558
            $sql = $queryBuilder->getSQL() . ' ' . $platform->getWriteLockSQL();

            $query = $this->connection->executeQuery(
                $sql,
                $queryBuilder->getParameters(),
                $queryBuilder->getParameterTypes()
            );

            if ($row = $query->fetch()) {
                $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
                    'SET status = ?, executed = ? ' .
                    'WHERE id = ? AND status = ?';

                $rows = $conn->executeUpdate(
                    $update,
                    [
                        static::STATUS_RUNNING,
                        $this->now->format('Y-m-d H:i:s.u'),
                        $row['id'],
                        static::STATUS_PENDING
                    ],
                    [Types::SMALLINT, Types::STRING, Types::INTEGER, Types::SMALLINT]
                );

                if ($rows !== 1) {
                    throw new LogicException("Race-condition detected while updating item in queue.");
                }
            }

            $conn->commit();
        } catch (DBALException $e) {
            $conn->rollback();
            $conn->close();
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if ($row === false) {
            return null;
        }

        // Add job ID to meta data
        return $this->unserializeJob($row['data'], ['__id__' => $row['id']]);
    }

    /**
     * {@inheritDoc}
     *
     * Note: When $deletedLifetime === 0 the job will be deleted immediately
     */
    public function delete(JobInterface $job, array $options = []): void
    {
        if ($this->options->getDeletedLifetime() === static::LIFETIME_DISABLED) {
            $this->connection->delete($this->options->getTableName(), ['id' => $job->getId()]);
        } else {
            $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
                'SET status = ?, finished = ? ' .
                'WHERE id = ? AND status = ?';

            $time      = microtime(true);
            $micro     = sprintf("%06d", ($time - floor($time)) * 1000000);
            $this->now = new DateTime(
                date('Y-m-d H:i:s.' . $micro, (int)$time),
                new DateTimeZone(date_default_timezone_get())
            );

            $rows = $this->connection->executeUpdate(
                $update,
                [
                    static::STATUS_DELETED,
                    $this->now->format('Y-m-d H:i:s.u'),
                    $job->getId(),
                    static::STATUS_RUNNING
                ],
                [Types::SMALLINT, Types::STRING, Types::INTEGER, Types::SMALLINT]
            );

            if ($rows !== 1) {
                throw new LogicException("Race-condition detected while updating item in queue.");
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * Note: When $buriedLifetime === 0 the job will be deleted immediately
     */
    public function bury(JobInterface $job, array $options = []): void
    {
        if ($this->options->getBuriedLifetime() === static::LIFETIME_DISABLED) {
            $this->connection->delete($this->options->getTableName(), ['id' => $job->getId()]);
        } else {
            $message = isset($options['message']) ? $options['message'] : null;
            $trace   = isset($options['trace']) ? $options['trace'] : null;

            $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
                'SET status = ?, finished = ?, message = ?, trace = ? ' .
                'WHERE id = ? AND status = ?';

            $time      = microtime(true);
            $micro     = sprintf("%06d", ($time - floor($time)) * 1000000);
            $this->now = new DateTime(
                date('Y-m-d H:i:s.' . $micro, (int)$time),
                new DateTimeZone(date_default_timezone_get())
            );

            $rows = $this->connection->executeUpdate(
                $update,
                [
                    static::STATUS_BURIED,
                    $this->now->format('Y-m-d H:i:s.u'),
                    $message,
                    $trace,
                    $job->getId(),
                    static::STATUS_RUNNING
                ],
                [Types::SMALLINT, Types::STRING, Types::STRING, Types::TEXT, Types::INTEGER, Types::SMALLINT]
            );

            if ($rows !== 1) {
                throw new LogicException("Race-condition detected while updating item in queue.");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function recover(int $executionTime): int
    {
        $executedLifetime = $this->parseOptionsToDateTime(['delay' => - ($executionTime * 60)]);

        $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
            'SET status = ? ' .
            'WHERE executed < ? AND status = ? AND queue = ? AND finished IS NULL';

        return $this->connection->executeUpdate(
            $update,
            [
                static::STATUS_PENDING,
                $executedLifetime->format('Y-m-d H:i:s.u'),
                static::STATUS_RUNNING,
                $this->getName()
            ],
            [Types::SMALLINT, Types::STRING, Types::SMALLINT, Types::STRING]
        );
    }

    /**
     * Create a concrete instance of a job from the queue
     */
    public function peek(int $id): JobInterface
    {
        $sql  = 'SELECT * FROM ' . $this->options->getTableName() . ' WHERE id = ?';
        $row  = $this->connection->fetchAssociative($sql, [$id], [Types::SMALLINT]);

        if (!$row) {
            throw new JobNotFoundException(sprintf("Job with id '%s' does not exists.", $id));
        }

        // Add job ID to meta data
        return $this->unserializeJob($row['data'], ['__id__' => $row['id']]);
    }

    /**
     * Reschedules a specific running job
     *
     * Note : see DoctrineQueue::parseOptionsToDateTime for schedule and delay options
     */
    public function release(JobInterface $job, array $options = []): void
    {
        $scheduled = $this->parseOptionsToDateTime($options);

        $update = 'UPDATE ' . $this->options->getTableName() . ' ' .
            'SET status = ?, finished = ? , scheduled = ?, data = ? ' .
            'WHERE id = ? AND status = ?';

        $time      = microtime(true);
        $micro     = sprintf("%06d", ($time - floor($time)) * 1000000);

        $rows = $this->connection->executeUpdate(
            $update,
            [
                static::STATUS_PENDING,
                $this->now->format('Y-m-d H:i:s.u'),
                $scheduled->format('Y-m-d H:i:s.u'),
                $this->serializeJob($job),
                $job->getId(),
                static::STATUS_RUNNING,
            ],
            [Types::SMALLINT, Types::STRING, Types::STRING, Types::STRING, Types::INTEGER, Types::SMALLINT]
        );

        if ($rows !== 1) {
            throw new LogicException("Race-condition detected while updating item in queue.");
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
     */
    protected function parseOptionsToDateTime(array $options): DateTime
    {
        $time      = microtime(true);
        $micro     = sprintf("%06d", ($time - floor($time)) * 1000000);
        $this->now = new DateTime(
            date('Y-m-d H:i:s.' . $micro, (int)$time),
            new DateTimeZone(date_default_timezone_get())
        );
        $scheduled = clone ($this->now);

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
    protected function purge(): void
    {
        if ($this->options->getBuriedLifetime() > static::LIFETIME_UNLIMITED) {
            $options = ['delay' => - ($this->options->getBuriedLifetime() * 60)];
            $buriedLifetime = $this->parseOptionsToDateTime($options);

            $delete = 'DELETE FROM ' . $this->options->getTableName() . ' ' .
                'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';

            $this->connection->executeUpdate(
                $delete,
                [$buriedLifetime->format('Y-m-d H:i:s.u'), static::STATUS_BURIED, $this->getName()],
                [Types::STRING, Types::INTEGER, Types::STRING]
            );
        }

        if ($this->options->getDeletedLifetime() > static::LIFETIME_UNLIMITED) {
            $options = ['delay' => - ($this->options->getDeletedLifetime() * 60)];
            $deletedLifetime = $this->parseOptionsToDateTime($options);

            $delete = 'DELETE FROM ' . $this->options->getTableName() . ' ' .
                'WHERE finished < ? AND status = ? AND queue = ? AND finished IS NOT NULL';

            $this->connection->executeUpdate(
                $delete,
                [$deletedLifetime->format('Y-m-d H:i:s.u'), static::STATUS_DELETED, $this->getName()],
                [Types::STRING, Types::INTEGER, Types::STRING]
            );
        }
    }
}
