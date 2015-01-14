<?php

namespace Terramar\Bundle\ResqueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render(
            'TerramarResqueBundle:Default:index.html.twig',
            array(
                'resque' => $this->getResque(),
            )
        );
    }

    public function showQueueAction($queue)
    {
        list($start, $count, $showingAll) = $this->getShowParameters();

        $queue = $this->getResque()->getQueue($queue);
        $jobs = $queue->getJobs($start, $count);

        if (!$showingAll) {
            $jobs = array_reverse($jobs);
        }

        return $this->render(
            'TerramarResqueBundle:Default:queue_show.html.twig',
            array(
                'resque' => $this->getResque(),
                'queue' => $queue,
                'jobs' => $jobs,
                'showingAll' => $showingAll
            )
        );
    }

    public function listFailedAction()
    {
        list($start, $count, $showingAll) = $this->getShowParameters();

        $jobs = $this->getResque()->getFailedJobs($start, $count);

        if (!$showingAll) {
            $jobs = array_reverse($jobs);
        }

        return $this->render(
            'TerramarResqueBundle:Default:failed_list.html.twig',
            array(
                'resque' => $this->getResque(),
                'jobs' => $jobs,
                'showingAll' => $showingAll,
            )
        );
    }

    public function clearFailedAction()
    {
        $this->getResque()->clearFailedJobs();

        return $this->redirect($this->generateUrl('TerramarResqueBundle_failed_list'));
    }

    public function listScheduledAction()
    {
        $jobs = array();
        foreach ($this->getResque()->getDelayedJobTimestamps() as $timestamp) {
            foreach ($this->getResque()->getJobsForTimestamp($timestamp[0]) as $job) {
                $job['schedule_date'] = $timestamp[0];
                
                $jobs[] = $job;
            }
        }
        
        return $this->render(
            'TerramarResqueBundle:Default:scheduled_list.html.twig',
            array(
                'resque' => $this->getResque(),
                'jobs' => $jobs
            )
        );
    }

    public function showTimestampAction($timestamp)
    {
        $jobs = array();

        // we don't want to enable the twig debug extension for this...
        foreach ($this->getResque()->getJobsForTimestamp($timestamp) as $job) {
            $jobs[] = print_r($job, true);
        }

        return $this->render(
            'TerramarResqueBundle:Default:scheduled_timestamp.html.twig',
            array(
                'resque' => $this->getResque(),
                'timestamp' => $timestamp,
                'jobs' => $jobs
            )
        );
    }

    /**
     * @return \Terramar\Bundle\ResqueBundle\Resque
     */
    protected function getResque()
    {
        return $this->get('terramar.resque');
    }

    /**
     * decide which parts of a job queue to show
     *
     * @return array
     */
    private function getShowParameters()
    {
        $showingAll = false;
        $start = -100;
        $count = -1;

        if ($this->getRequest()->query->has('all')) {
            $start = 0;
            $count = -1;
            $showingAll = true;
        }

        return array($start, $count, $showingAll);
    }
}
