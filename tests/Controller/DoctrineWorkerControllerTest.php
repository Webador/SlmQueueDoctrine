<?php

namespace SlmQueueDoctrineTest\Controller;

use PHPUnit\Framework\TestCase;
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

    public function setUp(): void
    {
        parent::setUp();
        $this->serviceManager = ServiceManagerFactory::getServiceManager();
    }

    public function testThrowExceptionIfQueueIsUnknown()
    {
        $controller = $this->serviceManager->get('ControllerLoader')->get(DoctrineWorkerController::class);
        $routeMatch = new RouteMatch(['queue' => 'unknownQueue']);
        $controller->getEvent()->setRouteMatch($routeMatch);

        $this->expectException(ServiceNotFoundException::class);

        $controller->processAction();
    }
}
