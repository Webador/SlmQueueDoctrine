<?php

namespace SlmQueueDoctrineTest\Queue;

use DateTime;
use DateTimeZone;
use DateInterval;
use SlmQueueDoctrine\Queue\DoctrineQueue;
use SlmQueueDoctrineTest\Asset\SimpleJob;
use SlmQueueDoctrineTest\Framework\TestCase;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\ServiceManager\ServiceManager;

class DoctrineQueueTest extends TestCase
{
    /**
     * @var \SlmQueueDoctrine\Queue\DoctrineQueue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();

        $this->createDb();

        $this->queue = new DoctrineQueue($this->getEntityManager()->getConnection(), 'queue_default', 'some-queue-name',
            ServiceManagerFactory::getServiceManager()->get('SlmQueue\Job\JobPluginManager'));

        // we want tests to run as fast as possible
        $this->queue->setSleepWhenIdle(0);
    }

    public function tearDown()
    {
        $this->dropDb();
    }

    public function testBuriedLifetimeOption()
    {
        // defaults disabled
        $this->assertEquals(DoctrineQueue::LIFETIME_DISABLED, $this->queue->getBuriedLifetime());

        $this->queue->setBuriedLifetime(10);
        $this->assertEquals(10, $this->queue->getBuriedLifetime());
    }

    public function testDeletedLifetimeOption()
    {
        // defaults disabled
        $this->assertEquals(DoctrineQueue::LIFETIME_DISABLED, $this->queue->getDeletedLifetime());

        $this->queue->setDeletedLifetime(10);
        $this->assertEquals(10, $this->queue->getDeletedLifetime());
    }

    public function testSleepWhenIdleOption()
    {
        // recreate queue with real defaults
        $this->queue = new DoctrineQueue($this->getEntityManager()->getConnection(), 'queue_default', 'some-queue-name',
            ServiceManagerFactory::getServiceManager()->get('SlmQueue\Job\JobPluginManager'));

        // default
        $this->assertEquals(1, $this->queue->getSleepWhenIdle());

        $this->queue->setSleepWhenIdle(2);
        $this->assertEquals(2, $this->queue->getSleepWhenIdle());

        $this->queue->setSleepWhenIdle(1);
        $start = microtime(true);
        $this->queue->pop();
        $this->queue->pop();
        $this->queue->pop();

        $this->assertTrue((microtime(true) - $start) >= ($this->queue->getSleepWhenIdle() * 3),
            "When no job is returned pop should sleep for a while");


        $job = new SimpleJob();
        $this->queue->push($job);
        $this->queue->push($job);
        $this->queue->push($job);
        $this->queue->push($job);
        $this->queue->push($job);

        $start = microtime(true);
        $this->queue->pop();
        $this->queue->pop();
        $this->queue->pop();
        $this->queue->pop();
        $this->queue->pop();

        $this->assertTrue(microtime(true) - $start < $this->queue->getSleepWhenIdle(),
            "When jobs are returned this should be as quick as possible");
    }

    public function testJobCanBePushed()
    {
        $job = new SimpleJob();

        $this->queue->push($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(1, $result['count']);
    }

    public function testJobCanBePushedMoreThenOnce()
    {
        $job = new SimpleJob();

        $this->queue->push($job);
        $this->queue->push($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(2, $result['count']);
    }

    public function testPushDefaults()
    {
        $job = new SimpleJob();
        $this->assertNull($job->getId(), "Upon job instantiation its id should be null");

        $this->queue->push($job);
        $this->assertTrue(is_numeric($job->getId()), "After a job has been pushed its id should should be an id");

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals('some-queue-name', $result['queue'], "The queue-name is expected to be stored.");
        $this->assertEquals(DoctrineQueue::STATUS_PENDING, $result['status'], "The status of a new job should be pending.");

        $this->assertEquals($result['created'], $result['scheduled'],
            "By default a job should be scheduled the same time it was created");
    }

    public function dataProvider_PushScheduledOptions()
    {
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
        $this->queue->push(new SimpleJob, $testOptions);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals($expectedResult, $result['scheduled'],
            "The job has not been scheduled correctly");
    }

    public function dataProvider_PushDelayOptions()
    {
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
        $this->queue->push(new SimpleJob, $testOptions);

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

        $this->queue->push($job);

        $returnedJob = $this->queue->pop();


        $this->assertNotNull($returnedJob, "A job should have been returned.");

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(DoctrineQueue::STATUS_RUNNING, $result['status'], "The status of a popped should be running.");
        $this->assertTrue((bool) preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result['executed']),
            "The executed field of a popped job should be set to a datetime");
    }

    public function testPopsCorrectlyScheduled()
    {
        $job = new SimpleJob();
        $returnedCount = 0;

        $now = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
        $this->queue->push($job, array('scheduled' =>  time() + $now->getOffset() + 10));
        $this->assertNull($this->queue->pop(), "Job is not due yet.");

        $this->queue->push($job, array('scheduled' => time() + $now->getOffset() + 10)); // must not be returned
        $this->queue->push($job, array('scheduled' => time() + $now->getOffset() - 10));$returnedCount++;
        $this->queue->push($job, array('scheduled' => time() + $now->getOffset() - 100));$returnedCount++;

        $firstJobId = $job->getId();
        $this->queue->push($job, array('scheduled' => time() + $now->getOffset()  - 50));$returnedCount++;
        $this->queue->push($job, array('scheduled' => time() + $now->getOffset()  - 30));$returnedCount++;
        $this->queue->push($job, array('delay' => 100)); // must not be returned
        $this->queue->push($job, array('delay' => -90)); $returnedCount++;


        $jobs = array();
        while ($job = $this->queue->pop()) {
            $jobs[] = $job;
        }

        $this->assertEquals($firstJobId, $jobs[0]->getId(), "Job with the oldest scheduled date is expected to be popped first.");
        $this->assertEquals($returnedCount, count($jobs), "The number of popped jobs is incorrect.");
    }

    public function testDelete_WithZeroLifeTimeShouldBeInstant()
    {
        $job = new SimpleJob();

        $this->queue->setDeletedLifetime(DoctrineQueue::LIFETIME_DISABLED);
        $this->queue->push($job);

        $this->queue->delete($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(0, $result['count']);
    }

    public function testDelete_WithLifeTimeShouldMarked()
    {
        $job = new SimpleJob();

        $this->queue->setDeletedLifetime(10);
        $this->queue->push($job);

        $this->queue->pop(); // why must the job be running?

        $this->queue->delete($job);

        // count
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(1, $result['count']);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(DoctrineQueue::STATUS_DELETED, $result['status'], "The status of this job should be 'deleted'.");
    }

    public function testDelete_RaceCondition()
    {
        $job = new SimpleJob();

        $this->queue->setDeletedLifetime(10);
        $this->queue->push($job);

        $this->queue->pop(); // why must the job be running?

        $this->queue->delete($job);

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->queue->delete($job);
    }

    public function testBury_WithZeroLifeTimeShouldBeInstant()
    {
        $job = new SimpleJob();

        $this->queue->setBuriedLifetime(DoctrineQueue::LIFETIME_DISABLED);
        $this->queue->push($job);

        $this->queue->bury($job);

        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(0, $result['count']);
    }

    public function testBury_Options()
    {
        $job = new SimpleJob();

        $this->queue->setBuriedLifetime(10);

        $this->queue->push($job);
        $this->queue->pop(); // why must the job be running?
        $this->queue->bury($job);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertNull($result['message'], "The message of this job should be 'null'.");
        $this->assertNull($result['trace'], "The message of this job should be 'null'.");


        $this->queue->push($job);
        $this->queue->pop(); // why must the job be running?
        $this->queue->bury($job, array('message'=>'hi', 'trace'=>'because'));

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertContains('hi', $result['message']);
        $this->assertNotNull('because', $result['trace']);

    }

    public function testBury_WithLifeTimeShouldMarked()
    {
        $job = new SimpleJob();

        $this->queue->setBuriedLifetime(10);
        $this->queue->push($job);

        $this->queue->pop(); // why must the job be running?

        $this->queue->bury($job);

        // count
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT count(*) as count FROM queue_default')->fetch();

        $this->assertEquals(1, $result['count']);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(DoctrineQueue::STATUS_BURIED, $result['status'], "The status of this job should be 'buried'.");
        $this->assertTrue((bool) preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result['finished']),
            "The finished field of a buried job should be set to a datetime");

    }

    public function testBury_RaceCondition()
    {
        $job = new SimpleJob();

        $this->queue->setBuriedLifetime(10);
        $this->queue->push($job);

        $this->queue->pop(); // why must the job be running?

        $this->queue->bury($job);

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->queue->bury($job);
    }

    public function testPeek()
    {
        $job = new SimpleJob();
        $this->queue->push($job);

        $peekedJob = $this->queue->peek($job->getId());

        $this->assertEquals($job, $peekedJob);
    }

    public function testPeek_NonExistent()
    {
        $this->setExpectedException('SlmQueueDoctrine\Exception\JobNotFoundException');

        $this->queue->peek(1);
    }

    public function testRelease()
    {
        $job = new SimpleJob();
        $this->queue->push($job);

        $job = $this->queue->pop();

        $this->queue->release($job);

        // fetch last added job
        $result = $this->getEntityManager()->getConnection()
            ->query('SELECT * FROM queue_default ORDER BY id DESC LIMIT 1')->fetch();

        $this->assertEquals(DoctrineQueue::STATUS_PENDING, $result['status'], "The status of a released job should be 'pending'.");

        $this->assertTrue((bool) preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result['finished']),
            "The finished field of a released job should be set to a datetime");
    }

    public function testRelease_RaceCondition()
    {
        $job = new SimpleJob();

        $this->setExpectedException('SlmQueueDoctrine\Exception\LogicException', 'Race-condition detected');
        $this->queue->release($job);
    }
}
