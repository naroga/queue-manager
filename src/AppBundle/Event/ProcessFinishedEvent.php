<?php

namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProcessFinishedEvent
 * @package AppBundle\Event
 */
class ProcessFinishedEvent extends Event
{
    const STATUS_SUCCESS = 1;
    const STATUS_TIMEOUT = 2;
    const STATUS_ERROR = 3;

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
}
