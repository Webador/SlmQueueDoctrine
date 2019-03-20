<?php

namespace SlmQueueDoctrineTest\Asset;

use Doctrine\Common\Persistence\ObjectManager;
use SlmQueue\Job\AbstractJob;

class OMJob extends AbstractJob
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
