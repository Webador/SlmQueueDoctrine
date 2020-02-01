<?php

namespace SlmQueueDoctrine\Options;

use SlmQueueDoctrine\Queue\DoctrineQueue;
use Laminas\Stdlib\AbstractOptions;

/**
 * DoctrineOptions
 */
class DoctrineOptions extends AbstractOptions
{
    /**
     * Name of the registered doctrine connection service
     *
     * @var string
     */
    protected $connection = 'doctrine.connection.orm_default';

    /**
     * Table name which should be used to store jobs
     *
     * @var string
     */
    protected $tableName = 'queue_default';

    /**
     * how long to keep deleted (successful) jobs (in minutes)
     *
     * @var int
     */
    protected $deletedLifetime = DoctrineQueue::LIFETIME_DISABLED;

    /**
     * how long to keep buried (failed) jobs (in minutes)
     *
     * @var int
     */
    protected $buriedLifetime = DoctrineQueue::LIFETIME_DISABLED;

    /**
     * Set the name of the doctrine connection service
     *
     * @param  string $connection
     * @return void
     */
    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Get the connection service name
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    public function setBuriedLifetime(int $buriedLifetime): void
    {
        $this->buriedLifetime = (int) $buriedLifetime;
    }

    public function getBuriedLifetime(): int
    {
        return $this->buriedLifetime;
    }

    public function setDeletedLifetime(int $deletedLifetime): void
    {
        $this->deletedLifetime = (int) $deletedLifetime;
    }

    public function getDeletedLifetime(): int
    {
        return $this->deletedLifetime;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }
}
