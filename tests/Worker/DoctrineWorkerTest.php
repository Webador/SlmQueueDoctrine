<?php

namespace SlmQueueDoctrineTest\Worker;

use PHPUnit_Framework_TestCase as TestCase;
use SlmQueue\Strategy\MaxRunsStrategy;
use SlmQueueDoctrine\Queue\DoctrineQueueInterface;
use SlmQueueDoctrineTest\Asset;
use SlmQueueDoctrine\Worker\DoctrineWorker;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\EventManager\EventManager;
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
    protected $queue;

    /**
     * @var DoctrineWorker
     */
    protected $worker;

    public function setUp()
    {
        $this->worker  = new DoctrineWorker(new EventManager());
        $this->queue   = $this->getMock(DoctrineQueueInterface::class);
        $this->job     = new Asset\SimpleJob();

        // set max runs so our tests won't run forever
        $this->maxRuns = new MaxRunsStrategy();
        $this->maxRuns->setMaxRuns(1);

        $this->maxRuns->attach($this->worker->getEventManager());
    }

    public function testAssertJobIsDeletedIfNoExceptionIsThrown()
    {
        $job = new Asset\SimpleJob();

        $this->queue->expects($this->once())
            ->method('delete')
            ->will($this->returnCallback(function () use ($job) {
                    $job->setContent('deleted');
                })
            );

        $this->worker->processJob($job, $this->queue);

        static::assertEquals('deleted', $job->getContent());
    }

    public function testAssertJobIsBuriedIfAnyExceptionIsThrown()
    {
        $job = new Asset\ExceptionJob();

        $this->queue->expects($this->once())
            ->method('bury')
            ->will($this->returnCallback(function () use ($job) {
                    $job->setContent('buried');
                })
            );

        $this->worker->processJob($job, $this->queue);

        static::assertEquals('buried', $job->getContent());
    }
}
