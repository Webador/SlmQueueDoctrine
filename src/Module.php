<?php

namespace SlmQueueDoctrine;

/**
 * SlmQueueDoctrine
 */
class Module
{
    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
