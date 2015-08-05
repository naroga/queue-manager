<?php

namespace AppBundle\Event;

use AppBundle\Process\Process;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProcessQueuedEvent
 * @package AppBundle\Event
 */
class ProcessQueuedEvent extends Event
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var Process
     */
    protected $process;

    /**
     * Class constructor
     *
     * @param string $id The process ID
     * @param Process $process The Process
     */
    public function __construct($id, Process $process)
    {
        $this->id = $id;
        $this->process = $process;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
