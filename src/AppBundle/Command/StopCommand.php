<?php

namespace AppBundle\Command;

use AppBundle\Command\Util\ProcessChecker;
use Lsw\MemcacheBundle\Cache\AntiDogPileMemcache;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class StopCommand
 * @package AppBundle\Command
 */
class StopCommand extends ContainerAwareCommand
{

    use ProcessChecker;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('naroga:queue:stop')
            ->setDescription(
                "Sends a SIGTERM to the Queue Manager. " .
                "It may only processed after the configured <info>queue.interval</info> " .
                "(see <info>app/config/services.yml</info>)."
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var AntiDogPileMemcache $server */
        $server = $this->getContainer()->get('memcache.default');

        if (!$server->get('queue.lock')) {
            $output->writeln('<error>Error: unable to find a queue.lock file.</error>');
            $output->writeln('This probably means the queue manager is not running.');
            $output->writeln(
                'If you are absolutely sure the queue manager is running, ' .
                'try running the following command:</error> ' .
                '<info>sudo killall naroga:queue:start</info>'
            );
            $output->writeln(
                "Be aware this will result in a total shutdown. " .
                "Dispatched processes will be dispatched again on restart. " .
                "The result of this action may be unpredictable."
            );
            return;
        }

        $pid = $server->get('queue.lock');
        if (!$this->isQueueRunning($pid)) {
            $output->writeln('<error>The queue manager is not running.</error>');
            $output->writeln(
                'There is a <info>queue.lock</info> file, but the informed PID (' . $pid . ') ' .
                'does not exist or isn\'t a manager process.'
            );
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Delete <info>queue.lock</info>? [Y/n] ');
            if ($helper->ask($input, $output, $question)) {
                $server->delete('queue.lock');
            }
            return;
        }

        $interval = $this->getContainer()->getParameter('queue.interval');

        if ($server->get('queue.sigterm')) {
            $server->replace('queue.sigterm', $pid);
        } else {
            $server->set('queue.sigterm', $server->get('queue.lock'));
        }
        $output->writeln(
            '<error>SIGTERM</error> successfully sent. ' .
            'The manager might still be running, but will shutdown after <info>' . $interval .
            ' seconds</info>, at most.'
        );
        $output->writeln(
            'You can change this configuration in <info>app/config/services.yml</info> ' .
            '(<info>queue.interval</info>).'
        );
    }
}
