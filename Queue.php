<?php

namespace Terramar\Bundle\ResqueBundle;

class Queue
{
    /**
     * @var string
     */
    private $name;

    /**
     * Constructor
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return \Resque::size($this->name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $start
     * @param int $stop
     *
     * @return array|Job[]
     * @throws \Resque_Exception
     */
    public function getJobs($start=0, $stop=-1)
    {
        $jobs = \Resque::redis()->lrange('queue:' . $this->name, $start, $stop);

        $result = array();
        foreach ($jobs as $job) {
            $job = new \Resque_Job($this->name, \json_decode($job, true));
            $result[] = $job->getInstance();
        }

        return $result;
    }
}
