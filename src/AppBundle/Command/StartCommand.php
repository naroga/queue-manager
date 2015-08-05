<?php

namespace AppBundle\Command;

use AppBundle\Command\Util\ProcessChecker;
use AppBundle\Event\ProcessDequeuedEvent;
use AppBundle\Event\ProcessFinishedEvent;
use AppBundle\Event\ProcessQueuedEvent;
use AppBundle\QueueManager\QueueManagerInterface;
use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Class StartCommand
 * @package AppBundle\Command
 */
class StartCommand extends ContainerAwareCommand
{

    use ProcessChecker;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('naroga:queue:start')
            ->setDescription('Starts the Queue Manager.')
            ->addOption(
                'daemon',
                '-d',
                InputOption::VALUE_NONE,
                'Runs in the background.'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $appContainer = $this->getContainer();

        /** @var AntiDogPileMemcache $server */
        $server = $appContainer->get('memcache.default');

        $verbose = $input->getOption('verbose');

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');

        $eventDispatcher->addListener(
            'queue.process_queued',
            function (ProcessQueuedEvent $event) use ($verbose, &$output) {
                $output->writeln('<info>Process \'' . $event->getId() . '\' was added to the queue.');
                if ($verbose) {
                    var_dump($event->getProcess());
                }
            }
        );

        $eventDispatcher->addListener(
            'queue.process_dequeued',
            function (ProcessDequeuedEvent $event) use (&$output) {
                $output->writeln('Process \'' . $event->getId() . '\' was removed from the queue.');
            }
        );

        $phpFinder = new PhpExecutableFinder();
        $phpPath = $phpFinder->find();

        $lock = $server->get('queue.lock');
        if ($lock) {
            $pid = $lock;
            if ($this->isQueueRunning($pid)) {
                $output->writeln("<error>Queue Manager is already running.</error>");
                return;
            }
        }

        if ($verbose) {
            $output->writeln("<info>Queue Manager is starting.</info>");
        }

        if ($input->getOption('daemon')) {
            $command = $phpPath . ' app/console naroga:queue:start ' . ($verbose ? '-v' : '') . ' &';
            $app = new Process($command);
            $app->setTimeout(0);
            $app->start();
            $pid = $app->getPid();
            $server->set('queue.lock', $pid);
            if ($verbose) {
                $output->writeln('<info>Queue Manager started with PID = ' . ($pid + 1) . '.</info>');
            }
            return;
        } else {
            $pid = getmypid();
            $server->set('queue.lock', $pid);
        }

        if ($verbose) {
            $output->writeln('<info>Queue Manager started with PID = ' . $pid . '.</info>');
        }


        $interval = $appContainer->getParameter('queue.interval');

        $server->delete('queue.sigterm');
        $server->delete('queue.manager');
        $server->delete('queue.manager.lock');
        $server->delete('queue.process.interval');

        $server->add('queue.process.interval', $interval);

        /** @var QueueManagerInterface $queueManager */
        $queueManager = $appContainer->get('naroga.queue.manager');

        /** @var Process[] $workers */
        $workers = [];
        $limitWorkers = $appContainer->getParameter('queue.workers');

        while (true) {
            //Shuts down the server if there is a SIGTERM present in the server for this PID.
            $sigterm = $server->get('queue.sigterm');
            if ($sigterm) {
                if (getmypid() == $sigterm) {
                    $output->writeln("<info>SIGTERM Received. Exiting queue manager.</info>");
                    $server->delete('queue.lock');
                    $server->delete('queue.sigterm');
                    return;
                }
            }

            //Clears the current
            foreach ($workers as &$worker) {
                if (!$worker->isRunning()) {
                    $worker = null;
                } else {
                    try {
                        $worker->checkTimeout();
                    } catch (ProcessTimedOutException $e) {
                        $eventDispatcher->dispatch(
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

            if (count($workers) < $limitWorkers) {
                //TODO: add new worker.
            }

            usleep($interval * 1000 * 1000);
        }
    }
}
