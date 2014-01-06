<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueueDoctrine\Options\DoctrineOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * DoctrineOptionsFactory
 */
class DoctrineOptionsFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        return new DoctrineOptions($config['slm_queue']['doctrine']);
    }
}
