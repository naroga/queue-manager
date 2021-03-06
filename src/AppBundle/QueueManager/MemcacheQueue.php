<?php

namespace AppBundle\QueueManager;

use AppBundle\Event\ManagerFlushedEvent;
use AppBundle\Event\ManagerRetrievedEvent;
use AppBundle\Event\ProcessQueuedEvent;
use AppBundle\Process\Process;
use AppBundle\Process\ProcessData;
use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class MemcacheQueue
 * @package AppBundle\QueueManager
 */
class MemcacheQueue implements QueueManagerInterface
{

    /**
     * @var TraceableEventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var AntiDogPileMemcache
     */
    protected $memcache;

    /**
     * @var \SplQueue
     */
    protected $queue;

    /**
     * @var bool
     */
    protected $locked = false;

    /**
     * @var string
     */
    protected $phpPath = 'php';

    /**
     * Class constructor
     *
     * @param TraceableEventDispatcher $dispatcher
     * @param AntiDogPileMemcache $memcache
     */
    public function __construct(TraceableEventDispatcher $dispatcher, AntiDogPileMemcache $memcache)
    {
        $this->eventDispatcher = $dispatcher;
        $this->memcache = $memcache;
        $phpFinder = new PhpExecutableFinder();
        $this->phpPath = $phpFinder->find();
    }

    /**
     * Gets the current queue from the Memcache server.
     *
     * @param boolean $exclusive If set to true, this will lock all processes from querying the queue.
     * @return \SplQueue
     */
    public function getQueue($exclusive = false)
    {
        if ($this->locked) {
            return $this->queue;
        }

        //If the 'add' operation fails, it means the lock is already active.
        if ($this->memcache->add('queue.manager.lock', true)) {
            $queue = $this->memcache->get('queue.manager');
            if ($queue) {
                $this->queue = unserialize($queue);
            }
            if (!$queue) {
                $this->queue = new \SplQueue;
            }
            $this->locked = true;
        } else {
            $interval = $this->memcache->get('queue.process.interval');

            $this->eventDispatcher->dispatch(
                'queue.manager.retrieved',
                new ManagerRetrievedEvent(ManagerRetrievedEvent::RETRIEVAL_STATUS_FAILED_LOCKED)
            );

            //Wait for the lock to be released.
            while ($this->memcache->get('queue.manager.lock')) {
                usleep($interval * 1000 * 1000);
            }

            $this->queue = unserialize($this->memcache->get('queue.manager'));
            $this->locked = true;
        }

        if (!$exclusive) {
            $this->memcache->delete('queue.manager.lock');
            $this->locked = false;
        }

        return $this->queue;

    }

    /**
     * Adds a Process to the queue.
     *
     * @param Process $process Process to be added.
     * @param string $name The process name
     */
    public function addProcess(Process $process, $name = null, $flush = true)
    {
        /** @var \SplQueue $queue */
        $queue = $this->getQueue(true);

        if (!$name) {
            $name = substr(str_shuffle(md5(microtime())), 0, 10);
        }

        $this->memcache->add('queue.process.processlist.' . $name, $process);

        $service = new \Symfony\Component\Process\Process(
            $this->phpPath . ' app/console naroga:queue:dispatch ' .
            $name
        );

        $processData = new ProcessData($service, $name);
        $queue->enqueue($processData);
        if ($flush) {
            $this->flush($queue);
        }

        $this->eventDispatcher->dispatch(
            'queue.process_queued',
            new ProcessQueuedEvent($processData->getName(), $process)
        );

    }

    /**
     * Flushes all queue changes to the server.
     *
     * @param \SplQueue $queue
     */
    public function flush(\SplQueue $queue)
    {
        if ($this->locked) {
            $this->memcache->delete('queue.manager');
            $this->memcache->set('queue.manager', serialize($queue));
            $this->locked = false;
            $this->memcache->delete('queue.manager.lock');
        } else {
            $this->eventDispatcher->dispatch(
                'queue.manager.flushed',
                new ManagerFlushedEvent(ManagerFlushedEvent::STATUS_FLUSH_FAILED_NOLOCK)
            );
        }
    }


}
