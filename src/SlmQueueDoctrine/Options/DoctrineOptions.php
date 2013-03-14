<?php

namespace SlmQueueDoctrine\Options;

use SlmQueueDoctrine\Queue\Table;
use Zend\Stdlib\AbstractOptions;

/**
 * DoctrineOptions
 */
class DoctrineOptions extends AbstractOptions
{
    /**
     * @var string
     */
    protected $connection;

    /**
     * Table name which should be used to store jobs
     *
     * @var string
     */
    protected $tableName;

    /**
     * how long to keep deleted (successful) jobs (in minutes)
     *
     * @var int
     */
    protected $deletedLifetime = Table::LIFETIME_DISABLED;

    /**
     * how long to keep buried (failed) jobs (in minutes)
     *
     * @var int
     */
    protected $buriedLifetime = Table::LIFETIME_DISABLED;


    /**
     * Set the name of the doctrine connection service
     *
     * @param  string $connection
     * @return void
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the connection service name
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param  int $buriedLifetime
     * @return void
     */
    public function setBuriedLifetime($buriedLifetime)
    {
        $this->buriedLifetime = (int) $buriedLifetime;
    }

    /**
     * @return int
     */
    public function getBuriedLifetime()
    {
        return $this->buriedLifetime;
    }

    /**
     * @param  int $deletedLifetime
     * @return void
     */
    public function setDeletedLifetime($deletedLifetime)
    {
        $this->deletedLifetime = (int) $deletedLifetime;
    }

    /**
     * @return int
     */
    public function getDeletedLifetime()
    {
        return $this->deletedLifetime;
    }

    /**
     * @param  string $tableName
     * @return void
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }
}
