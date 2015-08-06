<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class DispatchCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('naroga:queue:dispatch')
            ->setDescription('Dispatches a HTTP request.')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'The process identifier (name).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        sleep(rand(1, 45));
    }
}
