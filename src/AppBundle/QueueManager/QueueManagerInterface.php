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
     */
    public function addProcess(Process $process, $name = null);

    /**
     * Removes a Process from the queue.
     *
     * @param string|Process $process Either a process ID or a Process object.
     */
    public function removeProcess($process);

    /**
     * Gets $limit processes from the top of the queue. If $limit = 0, it will fetch all processes.
     *
     * @param int $limit Max number of processes to be returned.
     * @return array The processes.
     */
    public function getProcesses($limit = 0);

    /**
     * Finds a process by ID.
     *
     * @param string $id The ID to be searched.
     * @return Process|null The process or null, if not found.
     */
    public function findById($id);
}
