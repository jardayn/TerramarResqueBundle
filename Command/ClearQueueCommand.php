<?php

namespace Terramar\Bundle\ResqueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearQueueCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('resque:queue:clear')
            ->setDescription('Clear a resque queue')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queue name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resque = $this->getContainer()->get('terramar.resque');

        $queue = $input->getArgument('queue');
        $count=$resque->clearQueue($queue);

        $output->writeln('Cleared queue '.$queue.' - removed '.$count.' entries');

        return 0;
    }
}
