<?php

namespace SlmQueueDoctrineTest\Controller;

use PHPUnit_Framework_TestCase as TestCase;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;

class WorkerControllerTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;


    public function setUp()
    {
        parent::setUp();
        $this->serviceManager = ServiceManagerFactory::getServiceManager();
    }

    public function testThrowExceptionIfQueueIsUnknown()
    {
        $controller = $this->serviceManager->get('ControllerLoader')->get('SlmQueueDoctrine\Controller\Worker');
        $routeMatch = new RouteMatch(array('queueName' => 'unknownQueue'));
        $controller->getEvent()->setRouteMatch($routeMatch);

        $result = $controller->processAction();

        $this->assertContains('An error occurred', $result);
    }
}
