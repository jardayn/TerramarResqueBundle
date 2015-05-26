<?php

namespace Terramar\Bundle\ResqueBundle;

abstract class Job
{
    /**
     * @var \Resque_Job
     */
    public $job;

    /**
     * @var string The queue name
     */
    public $queue = 'default';

    /**
     * @var array The job args
     */
    public $args = array();

    /**
     * @var \Exception
     */
    protected $failure;

    public function getName()
    {
        return \get_class($this);
    }

    public function setUp()
    {
    }

    /**
     * Overrides the default perform with an exception catch all
     *
     * This is so that tearDown is still performed even during exceptional circumstances.
     */
    public function perform()
    {
        try {
            $this->run($this->args);
        } catch (\Exception $e) {
            $this->failure = $e;
            $this->job->fail($e);
        }
    }

    abstract public function run($args);

    public function tearDown()
    {
    }
}
