<?php

namespace AppBundle\Event;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ManagerRetrievedEvent
 * @package AppBundle\Event
 */
class ManagerRetrievedEvent extends Event
{
    const RETRIEVAL_STATUS_SUCCESS = 1;
    const RETRIEVAL_STATUS_FAILED_LOCKED = 2;
    const RETRIEVAL_STATUS_FAILED = 3;

    /**
     * @var int
     */
    protected $status = 0;

    /**
     * Class constructor
     *
     * @param int $status See ManagerRetrievedEvent constants for more information.
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
