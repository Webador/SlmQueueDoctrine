<?php

namespace SlmQueueDoctrineTest\Options;

use PHPUnit_Framework_TestCase as TestCase;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\ServiceManager\ServiceManager;

class DoctrineOptionsTest extends TestCase
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

    public function testCreateDoctrineOptions()
    {
        /** @var $doctrineOptions \SlmQueueDoctrine\Options\DoctrineOptions */
        $doctrineOptions = $this->serviceManager->get('SlmQueueDoctrine\Options\Options');
        $this->assertInstanceOf('SlmQueueDoctrine\Options\DoctrineOptions', $doctrineOptions);
    }
}
