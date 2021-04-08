<?php

namespace SlmQueueDoctrine\Persistence;

use Doctrine\Persistence\ObjectManager;

interface ObjectManagerAwareInterface
{
    /**
     * Set the object manager
     */
    public function setObjectManager(ObjectManager $objectManager): void;

    /**
     * Get the object manager
     */
    public function getObjectManager(): ObjectManager;
}
