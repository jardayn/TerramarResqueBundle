<?php

namespace Terramar\Bundle\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Terramar\Bundle\ResqueBundle\Resque;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

if (!defined('SIGTERM')) define('SIGTERM', 15);

class StopScheduledWorkerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:scheduledworker-stop')
            ->setDescription('Stop a bcc resque scheduled worker')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pidFile=$this->getContainer()->get('kernel')->getCacheDir().'/terramar_scheduledworker.pid';
        if (!file_exists($pidFile)) {
            $output->writeln('No PID file found');

            return -1;
        }

        $pid=file_get_contents($pidFile);

        $output->writeln('Killing process '.$pid);

        \posix_kill($pid,SIGTERM);

        unlink($pidFile);

        return 0;
    }
}
