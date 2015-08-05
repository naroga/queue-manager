<?php

namespace AppBundle\QueueManager;

use AppBundle\Command\Util\ProcessChecker;
use AppBundle\Event\ManagerFlushedEvent;
use AppBundle\Event\ManagerRetrievedEvent;
use AppBundle\Event\ProcessFinishedEvent;
use AppBundle\Event\ProcessQueuedEvent;
use AppBundle\Process\ProcessData;
use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
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
     * @param QueueManagerInterface $manager
     */
    public function __construct(
        TraceableEventDispatcher $eventDispatcher,
        AntiDogPileMemcache $memcache,
        QueueManagerInterface $manager
    ) {
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
     * @return void
     */
    public function start(array $options)
    {

        $verbose = isset($options['verbose']) && $options['verbose'];

        if ($this->checkServer()) {
            $this->output->writeln("<error>Queue Manager is already running.</error>");
            return;
        }

        if ($verbose) {
            $this->output->writeln("<info>Queue Manager is starting.</info>");
        }

        $phpFinder = new PhpExecutableFinder();
        $phpPath = $phpFinder->find();

        if ($options['daemon']) {
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

        $this->registerListeners($verbose);

        if ($verbose) {
            $this->output->writeln('<info>Queue Manager started with PID = ' . $pid . '.</info>');
        }

        $this->resetServerConfig($options);
        $this->processQueue($options);

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

        while (true) {
            //Shuts down the server if there is a SIGTERM present in the server for this PID.
            if ($this->checkSigterm($options)) {
                $this->output->writeln("<info>SIGTERM Received. Exiting queue manager.</info>");
                $this->resetServerConfig($options);
                return true;
            }

            $this->clearIdleWorkers($workers);

            $queue = $queueManager->getQueue();

            while (count($workers) < $options['workers'] && count($queue) > 0) {
                /** @var ProcessData $newProcess */
                $newProcess = $queue->dequeue();
                $workers[$newProcess->getName()] = $newProcess->getProcess();
                $workers[$newProcess->getName()]->setTimeout($options['timeout']);
                $workers[$newProcess->getName()]->start();
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
                    (new Process('kill ' . $worker->getPid()))->run();
                    $this->eventDispatcher->dispatch(
                        'queue.process_failed',
                        new ProcessFinishedEvent($worker->getPid(), ProcessFinishedEvent::STATUS_TIMEOUT)
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
    protected function checkSigterm(array $options)
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
     * @TODO: This is not using the ->add() atomic operation, so it will fail the concurrency checks. Fix this.
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
            'queue.process_failed',
            function (ProcessFinishedEvent $event) use (&$output) {

                $status = [
                    2 => 'TIMEOUT',
                    3 => 'ERROR IN RESPONSE'
                ];

                $this->output->writeln(
                    '<error>The process named \'' . $event->getName() .
                    '\' was killed with an error.</error>'
                );
                $this->output->writeln('<error>Status: ' . $status[$event->getStatus()] . '</error>');
            }
        );

        $this->eventDispatcher->addListener(
            'queue.manager.retrieved',
            function (ManagerRetrievedEvent $event) use ($verbose, &$output) {

                $errors = [
                    ManagerRetrievedEvent::RETRIEVAL_STATUS_FAILED =>
                        'Failed to retrieve the manager. Key not found.'
                ];

                $warnings = [
                    ManagerRetrievedEvent::RETRIEVAL_STATUS_FAILED_LOCKED =>
                        'Attempted to retrieve the manager, but it was locked. Retrying in a few moments.'
                ];

                if (isset($errors[$event->getStatus()])) {
                    $this->output->writeln('<error>' . $errors[$event->getStatus()] .'</error>');
                }
                if (isset($warnings[$event->getStatus()]) && $verbose) {
                    $this->output->writeln('<bg=yellow>' . $warnings[$event->getStatus()] .'</bg>');
                }
            }
        );

        $this->eventDispatcher->addListener(
            'queue.manager.flushed',
            function (ManagerFlushedEvent $event) use ($verbose, &$output) {

                switch ($event->getStatus()) {
                    case ManagerFlushedEvent::STATUS_FLUSH_FAILED_NOLOCK:
                        $output->writeln(
                            '<error>Could not flush the queue to the queue server. ' .
                            'Reason: the queue manager is locked.</error>'
                        );
                        break;
                    case ManagerFlushedEvent::STATUS_FLUSH_FAILED:
                        $output->writeln(
                            '<error>Could not flush the queue to the queue server. ' .
                            'Reason: the server is unreachable.'
                        );
                        break;
                    case ManagerRetrievedEvent::RETRIEVAL_STATUS_SUCCESS:
                        if ($verbose) {
                            $output->writeln('<info>The queue manager has been flushed.</info>');
                        }
                        break;
                }

            }
        );

    }

}
