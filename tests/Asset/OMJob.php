<?php

namespace SlmQueueDoctrineTest\Asset;

use SlmQueueDoctrine\Persistence\ObjectManagerAwareInterface;
use Doctrine\Persistence\ObjectManager;
use SlmQueue\Job\AbstractJob;

class OMJob extends AbstractJob implements ObjectManagerAwareInterface
{

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function setObjectManager(ObjectManager $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectManager(): ObjectManager
    {
        return $this->objectManager;
    }
}
