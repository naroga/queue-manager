<?php

namespace AppBundle\QueueManager;
use AppBundle\Process\Process;

/**
 * Interface QueueManagerInterface
 * @package AppBundle
 */
interface QueueManagerInterface
{
    /**
     * Adds a Process to the queue.
     *
     * @param Process $process Process to be added.
     * @param string $name The process name
     * @param boolean $flush Auto flush the queue to the server.
     */
    public function addProcess(Process $process, $name = null, $flush = true);

    /**
     * Flushes all queue changes to the server.
     *
     * @param \SplQueue $queue The queue object
     */
    public function flush(\SplQueue $queue);

    /**
     * Gets the queue from the manager.
     *
     * @param boolean $exclusive If set to true, this will lock all processes from querying the queue.
     * @return \SplQueue
     */
    public function getQueue($exclusive = false);
}
