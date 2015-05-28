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

    /**
     * @return string
     */
    public function getName()
    {
        return \get_class($this);
    }

    /**
     * @return null|string
     */
    public function getId()
    {
        return isset($this->job->payload['id']) ? $this->job->payload['id'] : null;
    }

    /**
     * Called before a job is performed
     */
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

    /**
     * Perform the work
     *
     * @param array $args
     *
     * @return void
     */
    abstract public function run($args);

    /**
     * Called after a job is performed
     */
    public function tearDown()
    {
    }
}
