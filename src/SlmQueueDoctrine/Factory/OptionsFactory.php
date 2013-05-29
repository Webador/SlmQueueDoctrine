<?php

namespace SlmQueueDoctrine\Factory;

use SlmQueueDoctrine\Options\Options;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * DoctrineOptionsFactory
 */
class OptionsFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        return new Options($config['slm_queue']['doctrine']);
    }
}
