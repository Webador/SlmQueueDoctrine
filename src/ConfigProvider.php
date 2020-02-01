<?php

namespace SlmQueueDoctrine;

class ConfigProvider
{
    public function __invoke(): array
    {
        $module = new Module();
        $config = $module->getConfig();

        return [
            'dependencies'  => $config['service_manager'],
            'slm_queue'     => $config['slm_queue'],
        ];
    }
}
