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
     * Class constructor
     *
     * @param string $id The process ID
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
