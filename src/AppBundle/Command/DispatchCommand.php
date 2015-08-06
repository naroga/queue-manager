<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DispatchCommand
 * @package AppBundle\Command
 */
class DispatchCommand extends ContainerAwareCommand
{
    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        sleep(rand(1, 3));
        if (rand(1, 20) == 20) {
            sleep(50);
        }

    }
}
