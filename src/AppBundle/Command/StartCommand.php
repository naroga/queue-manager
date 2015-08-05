<?php

namespace AppBundle\Command;

use AppBundle\Command\Util\ProcessChecker;
use AppBundle\Event\ProcessDequeuedEvent;
use AppBundle\Event\ProcessFinishedEvent;
use AppBundle\Event\ProcessQueuedEvent;
use AppBundle\QueueManager\Manager;
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
        /** @var Manager $manager */
        $manager = $this->getContainer()->get('naroga.queue.manager');
        $manager->setOutputInterface($output);

        $options = [
            'verbose' => $input->getOption('verbose'),
            'daemon' => $input->getOption('daemon'),
            'workers' => $this->getContainer()->getParameter('queue.workers'),
            'interval' => $this->getContainer()->getParameter('queue.interval'),
            'timeout' => $this->getContainer()->getParameter('queue.process.timeout')
        ];

        return $manager->start($options);

    }
}
