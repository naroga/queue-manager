<?php

namespace AppBundle\QueueManager;

use AppBundle\Command\Util\ProcessChecker;
use AppBundle\Event\ProcessFinishedEvent;
use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Class Manager
 * @package AppBundle\QueueManager
 */
class Manager
{

    use ProcessChecker;

    /**
     * @var TraceableEventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var AntiDogPileMemcache
     */
    protected $memcache;

    /**
     * @var QueueManagerInterface
     */
    protected $manager;

    /**
     * Class constructor
     *
     * @param TraceableEventDispatcher $eventDispatcher
     * @param AntiDogPileMemcache $memcache
     */
    public function __construct(TraceableEventDispatcher $eventDispatcher, AntiDogPileMemcache $memcache, QueueManagerInterface $manager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->memcache = $memcache;
        $this->manager = $manager;
    }

    /**
     * Sets the output interface.
     *
     * @param OutputInterface $output
     */
    public function setOutputInterface(OutputInterface &$output)
    {
        $this->output = $output;
    }

    /**
     * Starts the server
     *
     * @param array $options
     */
    public function start(array $options)
    {

        $verbose = isset($options['verbose']) && $options['verbose'];
        $interval = $options['interval'];

        if ($this->checkServer()) {
            $this->output->writeln("<error>Queue Manager is already running.</error>");
            return;
        }

        if ($verbose) {
            $this->output->writeln("<info>Queue Manager is starting.</info>");
        }

        $this->registerListeners($verbose);

        $phpFinder = new PhpExecutableFinder();
        $phpPath = $phpFinder->find();

        if ($this->input->getOption('daemon')) {
            $command = $phpPath . ' app/console naroga:queue:start ' . ($verbose ? '-v' : '') . ' &';
            $app = new Process($command);
            $app->setTimeout(0);
            $app->start();
            $pid = $app->getPid();
            $this->memcache->set('queue.lock', $pid);
            if ($verbose) {
                $this->output->writeln('<info>Queue Manager started with PID = ' . ($pid + 1) . '.</info>');
            }
            return;
        } else {
            $pid = getmypid();
            $this->memcache->set('queue.lock', $pid);
        }

        if ($verbose) {
            $this->output->writeln('<info>Queue Manager started with PID = ' . $pid . '.</info>');
        }

        $this->resetServerConfig($options);

        return $this->processQueue($options);

    }

    /**
     * Processes the queue.
     *
     * @param array $options
     * @return bool|void
     */
    protected function processQueue(array $options)
    {
        /** @var QueueManagerInterface $queueManager */
        $queueManager = $this->manager;

        /** @var Process[] $workers */
        $workers = [];
        $limitWorkers = $options['workers'];

        while (true) {
            //Shuts down the server if there is a SIGTERM present in the server for this PID.
            if ($this->verifySigterm($options)) {
                $this->output->writeln("<info>SIGTERM Received. Exiting queue manager.</info>");
                $this->resetServerConfig($options);
                return;
            }

            $this->clearIdleWorkers($workers);

            if (count($workers) < $limitWorkers) {
                //TODO: add new worker.
            }

            usleep($options['interval'] * 1000 * 1000);
        }

        return true;
    }

    /**
     * Removes Idle workers (finished and timed out).
     *
     * @param Process[] $workers
     */
    public function clearIdleWorkers(array &$workers)
    {
        //Clears the current
        foreach ($workers as &$worker) {
            if (!$worker->isRunning()) {
                $worker = null;
            } else {
                try {
                    $worker->checkTimeout();
                } catch (ProcessTimedOutException $e) {
                    $this->eventDispatcher->dispatch(
                        'queue.process_failed',
                        new ProcessFinishedEvent(ProcessFinishedEvent::STATUS_TIMEOUT)
                    );
                    $worker = null;
                }
            }
        }

        $workers = array_filter(
            $workers,
            function ($item) {
                return $item !== null;
            }
        );
    }

    /**
     * Checks if there is a SIGTERM message pending in the server.
     *
     * @param array $options
     * @return bool
     */
    protected function verifySigterm(array $options)
    {
        //
        $sigterm = $this->memcache->get('queue.sigterm');
        if ($sigterm) {
            if (getmypid() == $sigterm) {
                return true;
            }
        }
        return false;
    }

    /**
     * Resets the server configuration. Needed before starting the server.
     *
     * @param array $options
     */
    protected function resetServerConfig(array $options)
    {
        $this->memcache->delete('queue.sigterm');
        $this->memcache->delete('queue.manager');
        $this->memcache->delete('queue.manager.lock');
        $this->memcache->delete('queue.process.interval');
        $this->memcache->add('queue.process.interval', $options['interval']);
    }

    /**
     * Checks if the Queue Manager is already running in a different process.
     *
     * @return bool If the manager is running in another process.
     */
    public function checkServer()
    {
        $lock = $this->memcache->get('queue.lock');
        if ($lock) {
            $pid = $lock;
            if ($this->isQueueRunning($pid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Registers all event listeners related to the manager.
     *
     * @param bool $verbose
     */
    public function registerListeners($verbose = false)
    {

        $output = $this->output;

        $this->eventDispatcher->addListener(
            'queue.process_queued',
            function (ProcessQueuedEvent $event) use ($verbose, &$output) {
                $output->writeln('<info>Process \'' . $event->getId() . '\' was added to the queue.');
                if ($verbose) {
                    var_dump($event->getProcess());
                }
            }
        );

        $this->eventDispatcher->addListener(
            'queue.process_dequeued',
            function (ProcessDequeuedEvent $event) use (&$output) {
                $output->writeln('Process \'' . $event->getId() . '\' was removed from the queue.');
            }
        );
    }

}
