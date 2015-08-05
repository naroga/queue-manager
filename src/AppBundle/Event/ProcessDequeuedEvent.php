<?php

namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ProcessDequeuedEvent
 * @package AppBundle\Event
 */
class ProcessDequeuedEvent extends Event
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @param string $id
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
