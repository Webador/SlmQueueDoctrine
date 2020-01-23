<?php

namespace SlmQueueDoctrineTest;

use SlmQueueDoctrine\Module;
use PHPUnit\Framework\TestCase as TestCase;

class ConfigProviderTest extends TestCase
{
    public function testConfigProviderGetConfig()
    {
        $configProvider = new \SlmQueueDoctrine\ConfigProvider();
        $config         = $configProvider();

        static::assertNotEmpty($config);
    }

    public function testConfigEqualsToModuleConfig()
    {
        $module         = new Module();
        $moduleConfig   = $module->getConfig();
        $configProvider = new \SlmQueueDoctrine\ConfigProvider();
        $config         = $configProvider();

        static::assertEquals($moduleConfig['service_manager'], $config['dependencies']);
        static::assertEquals($moduleConfig['slm_queue'], $config['slm_queue']);
    }
}
