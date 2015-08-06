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

    /** @var string */
    protected $output;

    /**
     * Class constructor
     *
     * @param int $status
     * @param string $name
     */
    public function __construct($name, $output, $status)
    {
        $this->name = $name;
        $this->status = $status;
        $this->output = $output;
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

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }
}
