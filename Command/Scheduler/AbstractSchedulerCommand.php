<?php

namespace Terramar\Bundle\ResqueBundle\Command\Scheduler;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class AbstractSchedulerCommand extends ContainerAwareCommand
{
    protected function getPidFilename()
    {
        return $this->getContainer()->get('kernel')->getCacheDir() . '/terramar_scheduler.pid';
    }
}