<?php

namespace SlmQueueDoctrineTest\Queue;

use DateTime;
use DateTimeZone;
use DateInterval;
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

    public function tearDown()
    {
        $this->dropDb();
    }

    public function testBuriedLifetimeOption()
    {
        // defaults disabled
        $this->assertEquals(Table::LIFETIME_DISABLED, $this->tableQueue->getBuriedLifetime());

        $this->tableQueue->setBuriedLifetime(10);
        $this->assertEquals(10, $this->tableQueue->getBuriedLifetime());
    }

    public function testDeletedLifetimeOption()
    {
        // defaults disabled
        $this->assertEquals(Table::LIFETIME_DISABLED, $this->tableQueue->getDeletedLifetime());

        $this->tableQueue->setDeletedLifetime(10);
        $this->assertEquals(10, $this->tableQueue->getDeletedLifetime());
    }

    public function testJobCanBePushed()
    {
        $job = new SimpleJob();

        $this->tableQueue->push($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(1, $result['count']);
    }

    public function testJobCanBePushedMoreThenOnce()
    {
        $job = new SimpleJob();

        $this->tableQueue->push($job);
        $this->tableQueue->push($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(2, $result['count']);
    }

    public function testPushDefaults()
    {
        $job = new SimpleJob();
        $this->assertNull($job->getId(), "Upon job instantiation its id should be null");

        $this->tableQueue->push($job);
        $this->assertTrue(is_numeric($job->getId()), "After a job has been pushed its id should should be an id");

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals('some-queue-name', $result['queue'], "The queue-name is expected to be stored.");
        $this->assertEquals(Table::STATUS_PENDING, $result['status'], "The status of a new job should be pending.");

        $this->assertEquals($result['created'], $result['scheduled'],
            "By default a job should be scheduled the same time it was created");
    }

    public function dataProvider_PushScheduledOptions() {
        $now = new DateTime('1970-01-01 00:01:40');

        return array(
            array(array('scheduled'=>100), '1970-01-01 00:01:40'),
            array(array('scheduled'=>100, 'delay'=>10), '1970-01-01 00:01:50'), // delay is added to scheduled
            array(array('scheduled'=>'100'), '1970-01-01 00:01:40'),
            array(array('scheduled'=>'1970-01-01 00:01:40'), '1970-01-01 00:01:40'),
            array(array('scheduled'=>'1970-01-01 00:01:40+03:00'), '1970-01-01 00:01:40'),
            array(array('scheduled'=>$now), $now->format('Y-m-d H:i:s')),
        );
    }

    /**
     * @dataProvider dataProvider_PushScheduledOptions
     */
    public function testPushOptions_Scheduled($testOptions, $expectedResult)
    {
        $this->tableQueue->push(new SimpleJob, $testOptions);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals($expectedResult, $result['scheduled'],
            "The job has not been scheduled correctly");
    }

    public function dataProvider_PushDelayOptions() {
        return array(
            array(array('delay'=>100), 100),
            array(array('delay'=>"100"), 100),
            array(array('delay'=>"PT100S"), 100),
            array(array('delay'=>"PT2H"), 7200),
            array(array('delay'=>"2 weeks"), 1209600),
            array(array('delay'=>new DateInterval("PT100S")), 100),
        );
    }


    /**
     * @dataProvider dataProvider_PushDelayOptions
     */
    public function testPushOptions_Delay($testOptions, $expectedResult)
    {
        $this->tableQueue->push(new SimpleJob, $testOptions);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $created = new DateTime($result['created']);
        $scheduled = new DateTime($result['scheduled']);

        $this->assertEquals($expectedResult, $scheduled->getTimestamp() - $created->getTimestamp(),
            "The job has not been scheduled correctly");
    }

    public function testPopBecomesPending()
    {

        $job = new SimpleJob();

        $this->tableQueue->push($job);

        $returnedJob = $this->tableQueue->pop();


        $this->assertNotNull($returnedJob, "A job should have been returned.");

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(Table::STATUS_RUNNING, $result['status'], "The status of a popped should be running.");
        $this->assertTrue((bool) preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result['executed']),
            "The executed field of a popped job should be set to a datetime");
    }

    public function testPopsCorrectlyScheduled()
    {
        $job = new SimpleJob();
        $returnedCount = 0;

        $now = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
        $this->tableQueue->push($job, array('scheduled' =>  time() + $now->getOffset() + 10));
        $this->assertNull($this->tableQueue->pop(), "Job is not due yet.");

        $this->tableQueue->push($job, array('scheduled' => time() + $now->getOffset() + 10)); // must not be returned
        $this->tableQueue->push($job, array('scheduled' => time() + $now->getOffset() - 10));$returnedCount++;
        $this->tableQueue->push($job, array('scheduled' => time() + $now->getOffset() - 100));$returnedCount++;
        $firstJobId = $job->getId();
        $this->tableQueue->push($job, array('scheduled' => time() + $now->getOffset()  - 50));$returnedCount++;
        $this->tableQueue->push($job, array('scheduled' => time() + $now->getOffset()  - 30));$returnedCount++;
        $this->tableQueue->push($job, array('delay' => 100)); // must not be returned
        $this->tableQueue->push($job, array('delay' => -90)); $returnedCount++;


        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC')->fetchAll();
        print_r($result);
        $jobs = array();
        while ($job = $this->tableQueue->pop()) {
            $jobs[] = $job;
        }

        $this->assertEquals($firstJobId, $jobs[0]->getId(), "Job with the oldest scheduled date is expected to be popped first.");
        $this->assertEquals($returnedCount, count($jobs), "The number of popped jobs is incorrect.");
    }

    public function testDelete_WithZeroLifeTimeShouldBeInstant()
    {
        $job = new SimpleJob();

        $this->tableQueue->setDeletedLifetime(Table::LIFETIME_DISABLED);
        $this->tableQueue->push($job);

        $this->tableQueue->delete($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(0, $result['count']);
    }

    public function testDelete_WithLifeTimeShouldMarked()
    {
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

    public function testDelete_RaceCondition()
    {
        $job = new SimpleJob();

        $this->tableQueue->setDeletedLifetime(10);
        $this->tableQueue->push($job);

        $this->tableQueue->pop(); // why must the job be running?

        $this->tableQueue->delete($job);

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->tableQueue->delete($job);
    }

    public function testBury_WithZeroLifeTimeShouldBeInstant()
    {
        $job = new SimpleJob();

        $this->tableQueue->setBuriedLifetime(Table::LIFETIME_DISABLED);
        $this->tableQueue->push($job);

        $this->tableQueue->bury($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(0, $result['count']);
    }

    public function testBury_Options()
    {
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

    public function testBury_WithLifeTimeShouldMarked()
    {
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
        $this->assertTrue((bool) preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result['finished']),
            "The finished field of a buried job should be set to a datetime");

    }

    public function testBury_RaceCondition()
    {
        $job = new SimpleJob();

        $this->tableQueue->setBuriedLifetime(10);
        $this->tableQueue->push($job);

        $this->tableQueue->pop(); // why must the job be running?

        $this->tableQueue->bury($job);

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->tableQueue->bury($job);
    }

    public function testPeek()
    {
        $job = new SimpleJob();
        $this->tableQueue->push($job);

        $peekedJob = $this->tableQueue->peek($job->getId());

        $this->assertEquals($job, $peekedJob);
    }

    public function testPeek_NonExistent()
    {
        // Should peek return a more specialized exception for non existent jobs id's?
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');

        $this->tableQueue->peek(1);
    }

    public function testRelease()
    {
        $job = new SimpleJob();
        $this->tableQueue->push($job);

        $job = $this->tableQueue->pop();

        $this->tableQueue->release($job);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(Table::STATUS_PENDING, $result['status'], "The status of a released job should be 'pending'.");

        $this->assertTrue((bool) preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result['finished']),
            "The finished field of a released job should be set to a datetime");
    }

    public function testRelease_RaceCondition()
    {
        $job = new SimpleJob();

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->tableQueue->release($job);
    }
}
