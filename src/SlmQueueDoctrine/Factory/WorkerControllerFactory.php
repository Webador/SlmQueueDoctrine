<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueueDoctrine\Controller\WorkerController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * WorkerFactory
 */
class WorkerControllerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $worker = $serviceLocator->getServiceLocator()
                                 ->get('SlmQueueDoctrine\Worker\Worker');

        return new WorkerController($worker);
    }
}
