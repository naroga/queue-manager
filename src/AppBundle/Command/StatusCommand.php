<?php

namespace AppBundle\Command;

use AppBundle\Command\Util\ProcessChecker;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class StatusCommand
 * @package AppBundle\Command
 */
class StatusCommand extends ContainerAwareCommand
{

    use ProcessChecker;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('naroga:queue:status')
            ->setDescription('Checks the Queue Manager status.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = $this->getContainer()->get('memcache.default');

        $running = false;
        if ($server->get('queue.lock')) {
            $pid = $server->get('queue.lock');
            if ($this->isQueueRunning($pid)) {
                $running = true;
            }
        }

        $output->writeln(
            'Status: ' .
            ($running ? '<info>RUNNING</info>' : '<error>NOT RUNNING</error>')
        );

        if (!$running) {
            return;
        }

        $output->writeln('Process ID: <info>' . $server->get('queue.lock') . '</info>');


    }
}
