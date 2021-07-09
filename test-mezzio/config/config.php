<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    \SlmQueue\ConfigProvider::class,
    \SlmQueueDoctrine\ConfigProvider::class,

    \DoctrineModule\ConfigProvider::class,
    \DoctrineORMModule\ConfigProvider::class,

    \Laminas\Validator\ConfigProvider::class,
    \Mezzio\ConfigProvider::class,
    \Laminas\Diactoros\ConfigProvider::class,

    // Default App module config
    App\ConfigProvider::class,
    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),
]);

return $aggregator->getMergedConfig();

