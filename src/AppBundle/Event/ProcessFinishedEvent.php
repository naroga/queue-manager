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
     * @var string
     */
    protected $name;

    /**
     * Class constructor
     *
     * @param int $status
     * @param string $name
     */
    public function __construct($name, $status)
    {
        $this->name = $name;
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


}
