<?php

namespace SlmQueueDoctrineTest\Options;

use PHPUnit_Framework_TestCase as TestCase;
use SlmQueueDoctrineTest\Util\ServiceManagerFactory;
use Zend\ServiceManager\ServiceManager;

class OptionsTest extends TestCase
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
        /** @var $doctrineOptions \SlmQueueDoctrine\Options\Options */
        $doctrineOptions = $this->serviceManager->get('SlmQueueDoctrine\Options\Options');
        $this->assertInstanceOf('SlmQueueDoctrine\Options\Options', $doctrineOptions);
    }
}
