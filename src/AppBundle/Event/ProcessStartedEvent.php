<?php

namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Process\Process;

/**
 * Class ProcessStartedEvent
 * @package AppBundle\Event
 */
class ProcessStartedEvent extends Event
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @param string $name
     * @param Process $process
     */
    public function __construct($name, Process $process)
    {
        $this->name = $name;
        $this->process = $process;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
