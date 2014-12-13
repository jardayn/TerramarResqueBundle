<?php

namespace Terramar\Bundle\ResqueBundle\Command\Scheduler;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Terramar\Bundle\ResqueBundle\Resque;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

if (!defined('SIGTERM')) define('SIGTERM', 15);

class StopSchedulerCommand extends AbstractSchedulerCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:scheduler:stop')
            ->setDescription('Stop the resque scheduler');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pidFile = $this->getPidFilename();
        if (!file_exists($pidFile)) {
            $output->writeln('No PID file found');

            return -1;
        }

        $pid = file_get_contents($pidFile);
        \posix_kill($pid, SIGTERM);

        unlink($pidFile);

        if (!$input->getOption('quiet')) {
            $hostname = function_exists('gethostname') ? gethostname() : php_uname('n');

            $output->writeln(\sprintf('<info>Scheduler stopped</info> %s:%s', $hostname, $pid));
        }

        return 0;
    }
}
