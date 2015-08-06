<?php

namespace AppBundle\Command;

use AppBundle\Process\Process;
use AppBundle\QueueManager\QueueManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StubCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('naroga:queue:stub')
            ->setDescription('Stubs/Mocks requests to test the queue.')
            ->addArgument(
                'number-of-jobs',
                InputArgument::REQUIRED,
                'Amount of jobs to stub/mock.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QueueManagerInterface $queueManager */
        $queueManager = $this->getContainer()->get('naroga.queue.accessor');

        for ($i = 0; $i < $input->getArgument('number-of-jobs'); $i++) {
            $queueManager->addProcess(new Process());
        }
    }
}
