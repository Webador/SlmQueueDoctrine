<?php

namespace SlmQueueDoctrineTest\Controller;

use PHPUnit_Framework_TestCase as TestCase;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;

class DoctrineWorkerControllerTest extends TestCase
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
        $routeMatch = new RouteMatch(array('queue' => 'unknownQueue'));
        $controller->getEvent()->setRouteMatch($routeMatch);

           $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $controller->processAction();
    }
}
