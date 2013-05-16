<?php

namespace SlmQueueDoctrineTest\Queue;

use SlmQueueDoctrine\Queue\Table;
use SlmQueueDoctrine\Queue\Timestamp;
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

        $testOptions = array('scheduled'=> (string) new Timestamp(time() - 10));

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

    public function testPopBecomesPending() {
        $job = new SimpleJob();

        $this->tableQueue->push($job);

        $returnedJob = $this->tableQueue->pop();


        $this->assertNotNull($returnedJob, "A job should have been returned.");

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(Table::STATUS_RUNNING, $result['status'], "The status of a popped should be running.");
        $this->assertNotNull($result['executed'], "The executed field of a popped job should be set to current time.");
    }

    public function testPopsCorrectlyScheduled() {
        $this->markTestSkipped('until fixed');
        $job = new SimpleJob();
        $returnedCount = 0;

        $this->tableQueue->push($job, array('scheduled' => (string) new Timestamp(time() + 10))); // must not be returned
        $this->tableQueue->push($job, array('scheduled' => (string) new Timestamp(time() - 10)));$returnedCount++;
        $this->tableQueue->push($job, array('scheduled' => (string) new Timestamp(time() - 100)));$returnedCount++;
        $firstJobId = $job->getId();
        $this->tableQueue->push($job, array('scheduled' => (string) new Timestamp(time() - 50)));$returnedCount++;
        $this->tableQueue->push($job, array('scheduled' => (string) new Timestamp(time() - 30)));$returnedCount++;
        $this->tableQueue->push($job, array('delay' => 100)); // must not be returned
        $this->tableQueue->push($job, array('delay' => -90)); $returnedCount++;


        $jobs = array();
        while ($job = $this->tableQueue->pop()) {
            $jobs[] = $job;
        }

        $this->assertEquals($firstJobId, $jobs[0]->getId(), "Job with the oldest scheduled date is expected to be popped first.");
        $this->assertEquals($returnedCount, count($jobs), "The number of popped jobs is incorrect.");
    }

    public function testDelete_WithZeroLifeTimeShouldBeInstant() {
        $job = new SimpleJob();

        $this->tableQueue->setDeletedLifetime(Table::LIFETIME_DISABLED);
        $this->tableQueue->push($job);

        $this->tableQueue->delete($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(0, $result['count']);
    }

    public function testDelete_WithLifeTimeShouldMarked() {
        $job = new SimpleJob();

        $this->tableQueue->setDeletedLifetime(10);
        $this->tableQueue->push($job);

        $this->tableQueue->pop(); // why must the job be running?

        $this->tableQueue->delete($job);

        // count
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(1, $result['count']);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(Table::STATUS_DELETED, $result['status'], "The status of this job should be 'deleted'.");
    }

    public function testDelete_RaceCondition() {
        $job = new SimpleJob();

        $this->tableQueue->setDeletedLifetime(10);
        $this->tableQueue->push($job);

        $this->tableQueue->pop(); // why must the job be running?

        $this->tableQueue->delete($job);

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->tableQueue->delete($job);
    }

    public function testBury_WithZeroLifeTimeShouldBeInstant() {
        $job = new SimpleJob();

        $this->tableQueue->setBuriedLifetime(Table::LIFETIME_DISABLED);
        $this->tableQueue->push($job);

        $this->tableQueue->bury($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(0, $result['count']);
    }

    public function testBury_Options() {
        $job = new SimpleJob();

        $this->tableQueue->setBuriedLifetime(10);

        $this->tableQueue->push($job);
        $this->tableQueue->pop(); // why must the job be running?
        $this->tableQueue->bury($job);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertNull($result['message'], "The message of this job should be 'null'.");
        $this->assertNull($result['trace'], "The message of this job should be 'null'.");


        $this->tableQueue->push($job);
        $this->tableQueue->pop(); // why must the job be running?
        $this->tableQueue->bury($job, array('message'=>'hi', 'trace'=>'because'));

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertContains('hi', $result['message']);
        $this->assertNotNull('because', $result['trace']);

    }

    public function testBury_WithLifeTimeShouldMarked() {
        $job = new SimpleJob();

        $this->tableQueue->setBuriedLifetime(10);
        $this->tableQueue->push($job);

        $this->tableQueue->pop(); // why must the job be running?

        $this->tableQueue->bury($job);

        // count
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(1, $result['count']);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(Table::STATUS_BURIED, $result['status'], "The status of this job should be 'buried'.");
    }

    public function testBury_RaceCondition() {
        $job = new SimpleJob();

        $this->tableQueue->setBuriedLifetime(10);
        $this->tableQueue->push($job);

        $this->tableQueue->pop(); // why must the job be running?

        $this->tableQueue->bury($job);

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->tableQueue->bury($job);
    }
}
