<?php

namespace SlmQueueDoctrineTest\Controller;

use PHPUnit_Framework_TestCase as TestCase;
use SlmQueueDoctrine\Controller\DoctrineWorkerController;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;

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
        $controller = $this->serviceManager->get('ControllerLoader')->get(DoctrineWorkerController::class);
        $routeMatch = new RouteMatch(['queue' => 'unknownQueue']);
        $controller->getEvent()->setRouteMatch($routeMatch);

        $this->setExpectedException(ServiceNotFoundException::class);
        $controller->processAction();
    }
}
