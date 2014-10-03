<?php

namespace SlmQueueDoctrineTest\Asset;

use Doctrine\Common\Persistence\ObjectManager;
use DoctrineModule\Persistence\ObjectManagerAwareInterface;
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
    }

    /**
     * {@inheritDoc}
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }
}
