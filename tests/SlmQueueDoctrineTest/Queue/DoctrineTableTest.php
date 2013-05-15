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

        $this->tableQueue = new Table($this->getEntityManager()->getConnection(), 'queue_default', 'some-queue-name',
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

    public function testJobCanBePushed() {
        $job = new SimpleJob();

        $this->tableQueue->push($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(1, $result['count']);
    }

    public function testJobCanBePushedMoreThenOnce() {
        $job = new SimpleJob();

        $this->tableQueue->push($job);
        $this->tableQueue->push($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(2, $result['count']);
    }

    public function testPushDefaults() {
        $job = new SimpleJob();
        $this->assertNull($job->getId(), "Upon job instantiation its id's should be null");

        $this->tableQueue->push($job);
        $this->assertTrue(is_numeric($job->getId()), "After a job has been pushed its id's should should be an id");

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals('some-queue-name', $result['queue'], "The queue-name is expected to be stored.");
        $this->assertEquals(Table::STATUS_PENDING, $result['status'], "The status of a new job should be pending.");

        $this->assertEquals($result['created'], $result['scheduled'],
            "By default a job should be sceduled the same time it was created");
    }

    public function testPushOptions_Delay() {
        $job = new SimpleJob();

        $testOptions = array('delay'=>100);
        $this->tableQueue->push($job, $testOptions);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $created = new \DateTime($result['created']);
        $scheduled = new \DateTime($result['scheduled']);

        $this->assertEquals($testOptions['delay'], $scheduled->getTimestamp() - $created->getTimestamp(),
            "The job has not been scheduled with the correct delay");
    }

    public function testPushOptions_Scheduled() {
        $job = new SimpleJob();

        $someDate = new \DateTime('2000-01-01 0:0:00');
        $testOptions = array('scheduled'=>$someDate->getTimestamp());

        $this->tableQueue->push($job, $testOptions);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $scheduled = new \DateTime($result['scheduled']);

        $this->assertEquals($testOptions['scheduled'], $scheduled->getTimestamp(),
            "The job has not been scheduled at the specified datetime");

        // add delay option
        $testOptions['delay'] = 100;
        $this->tableQueue->push($job, $testOptions);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $created = new \DateTime($result['created']);
        $scheduled = new \DateTime($result['scheduled']);

        $this->assertEquals($testOptions['scheduled'], $scheduled->getTimestamp(),
            "The delay option should be ignored when a scheduled time has been specified");
    }

}
