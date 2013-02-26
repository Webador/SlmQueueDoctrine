<?php

namespace GoalioQueueDoctrine;

use Zend\Loader;
use Zend\Console\Adapter\AdapterInterface;
use Zend\ModuleManager\Feature;

/**
 * GoalioQueueDoctrine
 */
class Module implements
    Feature\AutoloaderProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\ConsoleBannerProviderInterface,
    Feature\ConsoleUsageProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            Loader\AutoloaderFactory::STANDARD_AUTOLOADER => array(
                Loader\StandardAutoloader::LOAD_NS => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * {@inheritDoc}
     */
    public function getConsoleBanner(AdapterInterface $console)
    {
        return "\n----------------------------------------------------------------------\n" .
            "GoalioQueueDoctrine | Doctrine 2 based Queuing Zend Framework 2 module\n" .
            "----------------------------------------------------------------------\n";
    }

    /**
     * {@inheritDoc}
     */
    public function getConsoleUsage(AdapterInterface $console)
    {
        return array(
            'queue doctrine <queueName> --start' => 'Process the jobs',
            'queue doctrine <queueName> --recover [--executiontime=]' => 'Recover long running jobs',

            array('<queueName>', 'Queue\'s name to process'),
            array('<executiontime>', 'Time (in minutes) after which the job gets recovered'),
        );
    }
}
