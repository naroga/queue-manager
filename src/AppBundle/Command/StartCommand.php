<?php

namespace AppBundle\Command;

use AppBundle\Command\Util\ProcessChecker;
use AppBundle\Event\ProcessDequeuedEvent;
use AppBundle\Event\ProcessQueuedEvent;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
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

        $server = $this->getContainer()->get('memcache.default');

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

        $appContainer = $this->getContainer();
        $interval = $appContainer->getParameter('queue.interval');

        while (true) {
            if ($server->get('queue.sigterm')) {
                $pid = $server->get('queue.sigterm');

                if (getmypid() == $pid) {
                    $output->writeln("<info>SIGTERM Received. Exiting queue manager.</info>");
                    $server->delete('queue.lock');
                    $server->delete('queue.sigterm');
                    return;
                }
            }

            usleep($interval * 1000);
        }
    }
}
