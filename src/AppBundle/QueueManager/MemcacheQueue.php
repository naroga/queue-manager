<?php

namespace AppBundle\QueueManager;

use AppBundle\Event\ProcessDequeuedEvent;
use AppBundle\Event\ProcessQueuedEvent;
use AppBundle\Exception\InvalidProcessException;
use AppBundle\Process\Process as ProcessData;
use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class MemcacheQueue
 * @package AppBundle\QueueManager
 */
class MemcacheQueue implements QueueManagerInterface
{
    /**
     * @var array
     */
    protected $queue = [];

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var AntiDogPileMemcache
     */
    protected $server;

    /**
     * Class constructor
     *
     * @param TraceableEventDispatcher $eventDispatcher The Event Dispatcher.
     */
    public function __construct(TraceableEventDispatcher $eventDispatcher, AntiDogPileMemcache $server)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->server = $server;
    }

    /**
     * Adds a Process to the queue.
     *
     * @param ProcessData $process Process to be added.
     * @param string $name The process name
     * @throws InvalidProcessException
     */
    public function addProcess(ProcessData $process, $name = null)
    {
        if (isset($name)) {
            if (array_key_exists($name, $this->queue)) {
                throw new InvalidProcessException('The specified process name (' . $name . ') is already in use.');
            }
        } else {
            $name = substr(str_shuffle(md5(microtime())), 0, 10);
        }

        $this->queue[$name] = $process;
        $this->eventDispatcher->dispatch('queue.process_queued', new ProcessQueuedEvent($name, $process));
    }

    /**
     * Removes a Process from the queue.
     *
     * @param string|ProcessData $process Either a process ID or a Process object.
     */
    public function removeProcess($process)
    {
        if (is_string($process)) {
            unset($this->queue[$process]);
            $id = $process;
        } else {
            $id = array_search($process, $this->queue);
            if ($id) {
                unset($this->queue[$id]);
            }
        }
        $this->eventDispatcher->dispatch('queue.process_dequeued', new ProcessDequeuedEvent($id));
    }

    /**
     * Gets $limit processes from the top of the queue. If $limit = 0, it will fetch all processes.
     *
     * @param int $limit Max number of processes to be returned.
     * @return ProcessData[] The processes.
     */
    public function getProcesses($limit = 0)
    {
        if ($limit > 0) {
            return array_slice($this->queue, 0, $limit);
        } else {
            return $this->queue;
        }
    }

    /**
     * Finds a process by ID.
     *
     * @param string $id The ID to be searched.
     * @return ProcessData|null The process or null, if not found.
     */
    public function findById($id)
    {
        if (isset($this->queue[$id])) {
            return $this->queue[$id];
        } else {
            return null;
        }
    }


}
