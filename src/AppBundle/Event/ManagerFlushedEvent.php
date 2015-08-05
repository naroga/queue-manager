<?php

namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ManagerFlushedEvent
 * @package AppBundle\Event
 */
class ManagerFlushedEvent extends Event
{
    const STATUS_FLUSH_SUCCESS = 1;
    const STATUS_FLUSH_FAILED_NOLOCK = 2;
    const STATUS_FLUSH_FAILED = 3;

    /**
     * @var int
     */
    protected $status;

    /**
     * Class constructor
     *
     * @param int $status
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
