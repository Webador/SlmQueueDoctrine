<?php

namespace SlmQueueDoctrineTest\Queue;

use SlmQueueDoctrine\Queue\Table;
use SlmQueueDoctrineTest\Asset\SimpleJob;
use SlmQueueDoctrineTest\Framework\TestCase;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\ServiceManager\ServiceManager;

class DoctrineTableTest extends TestCase
{
    /**
     * @var \SlmQueueDoctrine\Queue\Table
     */
    protected $tableQueue;

    public function setUp()
    {
        parent::setUp();

        $this->createDb();

        $this->tableQueue = new Table($this->getEntityManager()->getConnection(), 'queue_default', 'main',
            ServiceManagerFactory::getServiceManager()->get('SlmQueue\Job\JobPluginManager'));
    }

    public function tearDown() {
        $this->dropDb();
    }

    public function testBuriedLifetimeOption() {
        // defaults disabled
        $this->assertEquals(Table::LIFETIME_DISABLED, $this->tableQueue->getBuriedLifetime());

        $this->tableQueue->setBuriedLifetime(10);
        $this->assertEquals(10, $this->tableQueue->getBuriedLifetime());
    }

    public function testDeletedLifetimeOption() {
        // defaults disabled
        $this->assertEquals(Table::LIFETIME_DISABLED, $this->tableQueue->getDeletedLifetime());

        $this->tableQueue->setDeletedLifetime(10);
        $this->assertEquals(10, $this->tableQueue->getDeletedLifetime());
    }

    public function testJobIsPushed() {
        /** @var \Doctrine\DBAL\Driver\Statement $statement */
        $statement = $this->getEntityManager()->getConnection()->query('SELECT count(*) as count FROM queue_default');
        $countBefore = $statement->fetch()['count'];

        $job = new SimpleJob();
        $this->tableQueue->push($job);

        $statement = $this->getEntityManager()->getConnection()->query('SELECT count(*) as count FROM queue_default');
        $countAfter = $statement->fetch()['count'];

        $this->assertLessThan($countAfter, $countBefore);
    }

    // more to come
}
