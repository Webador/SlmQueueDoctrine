<?php

namespace SlmQueueDoctrineTest\Worker;

use PHPUnit_Framework_TestCase as TestCase;
use SlmQueueDoctrineTest\Asset;
use SlmQueueDoctrine\Worker\DoctrineWorker;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\ServiceManager\ServiceManager;

class DoctrineWorkerTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var \SlmQueueDoctrine\Queue\DoctrineQueueInterface
     */
    protected $queueMock;

    /**
     * @var DoctrineWorker
     */
    protected $worker;


    public function setUp()
    {
        parent::setUp();
        $this->serviceManager = ServiceManagerFactory::getServiceManager();

        $this->queueMock  = $this->getMock('SlmQueueDoctrine\Queue\DoctrineQueueInterface');
        $queueManagerMock = $this->getMock('SlmQueue\Queue\QueuePluginManager');
        $workerOptions    = $this->serviceManager->get('SlmQueue\Options\WorkerOptions');

        $this->worker = new DoctrineWorker($queueManagerMock, $workerOptions);
    }

    public function testAssertJobIsDeletedIfNoExceptionIsThrown()
    {
        $job = new Asset\SimpleJob();

        $this->queueMock->expects($this->once())
            ->method('delete')
            ->will($this->returnCallback(function() use ($job) {
                    $job->setContent('deleted');
                })
            );

        $this->worker->processJob($job, $this->queueMock);

        $this->assertEquals('deleted', $job->getContent());
    }

    public function testAssertJobIsReleasedIfReleasableExceptionIsThrown()
    {
        $job = new Asset\ReleasableJob();

        $this->queueMock->expects($this->once())
            ->method('release')
            ->will($this->returnCallback(function() use ($job) {
                    $job->setContent('released');
                })
            );

        $this->worker->processJob($job, $this->queueMock);

        $this->assertEquals('released', $job->getContent());
    }

    public function testAssertJobIsBuriedIfBuryableExceptionIsThrown()
    {
        $job = new Asset\BuryableJob();

        $this->queueMock->expects($this->once())
            ->method('bury')
            ->will($this->returnCallback(function() use ($job) {
                    $job->setContent('buried');
                })
            );

        $this->worker->processJob($job, $this->queueMock);

        $this->assertEquals('buried', $job->getContent());
    }

    public function testAssertJobIsBuriedIfAnyExceptionIsThrown()
    {
        $job = new Asset\ExceptionJob();

        $this->queueMock->expects($this->once())
            ->method('bury')
            ->will($this->returnCallback(function() use ($job) {
                    $job->setContent('buried');
                })
            );

        $this->worker->processJob($job, $this->queueMock);

        $this->assertEquals('buried', $job->getContent());
    }
}
