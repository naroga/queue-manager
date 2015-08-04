<?php

namespace AppBundle\Command\Util;
use Symfony\Component\Process\Process;

/**
 * Class ProcessChecker
 * @package AppBundle\Command\Util
 */
trait ProcessChecker
{
    /**
     * Checks is the Queue Manager is running, by a PID.
     *
     * @param $pid Process ID. Usually read from queue.lock.
     * @return bool If the queue manager is running.
     */
    protected function isQueueRunning($pid)
    {
        $processList = new Process("ps " . $pid);
        $processList->run();
        $data = explode("\n", $processList->getOutput());
        return ($data[1] && strpos($data[1], 'naroga:queue:start') !== false);
    }
}
