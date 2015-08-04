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
        $filesystem = new Filesystem();
        $running = false;
        if ($filesystem->exists('app/cache/queue.lock')) {
            $pid = file_get_contents('app/cache/queue.lock');
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

        $output->writeln('Process ID: <info>' . file_get_contents('app/cache/queue.lock') . '</info>');


    }
}
