<?php

namespace SlmQueueDoctrineTest\Controller;

use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use SlmQueueDoctrine\Command\DoctrineWorkerCommand;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;

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

    public function testThrowExceptionIfQueueIsUnknown(): void
    {
        $controller = $this->serviceManager->get('ControllerManager')->get(DoctrineWorkerCommand::class);
        $routeMatch = new RouteMatch(['queue' => 'unknownQueue']);
        $controller->getEvent()->setRouteMatch($routeMatch);

        $this->expectException(ServiceNotFoundException::class);

        $controller->processAction();
    }
}
