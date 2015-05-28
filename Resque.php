<?php

namespace Terramar\Bundle\ResqueBundle;

class Resque
{
    /**
     * @var array
     */
    private $kernelOptions;

    /**
     * @var array
     */
    private $redisConfiguration;

    /**
     * Constructor
     *
     * @param array $kernelOptions
     */
    public function __construct(array $kernelOptions)
    {
        $this->kernelOptions = $kernelOptions;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        \Resque_Redis::prefix($prefix);
    }

    /**
     * Set the Redis configuration
     *
     * @param string $host
     * @param string $port
     * @param string $database
     */
    public function setRedisConfiguration($host, $port, $database)
    {
        $this->redisConfiguration = array(
            'host'     => $host,
            'port'     => $port,
            'database' => $database,
        );

        if (strpos($host, 'unix:') === false) {
            $host .= ':' . $port;
        }

        \Resque::setBackend($host, $database);
    }

    /**
     * @return array
     */
    public function getRedisConfiguration()
    {
        return $this->redisConfiguration;
    }

    /**
     * Enqueue a job with Resque
     *
     * @param Job  $job
     * @param bool $trackStatus
     *
     * @return \Resque_Job_Status
     */
    public function enqueue(Job $job, $trackStatus = false)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        $result = \Resque::enqueue($job->queue, \get_class($job), $job->args, $trackStatus);

        return new \Resque_Job_Status($result);
    }

    /**
     * Returns true if the given Job is already queued
     *
     * @param Job  $job
     * @param bool $strict If true, check that arguments are identical
     *
     * @return bool
     */
    public function isQueued(Job $job, $strict = false)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        $queue = new Queue($job->queue);
        $jobs  = $queue->getJobs();

        $jobName = $job->getName();

        foreach ($jobs as $otherJob) {
            if ($otherJob->getName() !== $jobName) {
                continue;
            }

            if ((!$strict && count(array_intersect($otherJob->args, $job->args)) === count($job->args))
                || $otherJob->args === $job->args
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue a job only if it is not already in the queue
     *
     * @param Job  $job
     * @param bool $trackStatus
     * @param bool $strict If true, check that arguments are identical
     *
     * @return false|\Resque_Job_Status False if the Job is already queued
     */
    public function enqueueOnce(Job $job, $trackStatus = false, $strict = false)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        if ($this->isQueued($job, $strict)) {
            return false;
        }

        return $this->enqueue($job, $trackStatus);
    }

    /**
     * Enqueue a job for later processing
     *
     * @param int $at Timestamp of when to run the Job
     * @param Job $job
     *
     * @return null
     */
    public function enqueueAt($at, Job $job)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        \ResqueScheduler::enqueueAt($at, $job->queue, \get_class($job), $job->args);

        return null;
    }

    /**
     * Enqueue a job for processing in the given number of seconds
     *
     * @param int $in Delay, in seconds, of when to run the Job
     * @param Job $job
     *
     * @return null
     */
    public function enqueueIn($in, Job $job)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        \ResqueScheduler::enqueueIn($in, $job->queue, \get_class($job), $job->args);

        return null;
    }

    /**
     * Remove a delayed job
     *
     * @param Job $job
     *
     * @return int
     */
    public function removedDelayed(Job $job)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        return \ResqueScheduler::removeDelayed($job->queue, \get_class($job), $job->args);
    }

    /**
     * Remove a delayed job at the given timestamp
     *
     * @param int $at
     * @param Job $job
     *
     * @return mixed
     */
    public function removeFromTimestamp($at, Job $job)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        return \ResqueScheduler::removeDelayedJobFromTimestamp($at, $job->queue, \get_class($job), $job->args);
    }

    /**
     * Get all existing queues
     *
     * @return array|Queue[]
     */
    public function getQueues()
    {
        return \array_map(function ($queue) {
            return new Queue($queue);
        }, \Resque::queues());
    }

    /**
     * Get the given queue
     *
     * @param string $queue The queue name
     *
     * @return Queue
     */
    public function getQueue($queue)
    {
        return new Queue($queue);
    }

    /**
     * Get all existing workers
     *
     * @return array|Worker[]
     */
    public function getWorkers()
    {
        return \array_map(function ($worker) {
            return new Worker($worker);
        }, \Resque_Worker::all());
    }

    /**
     * Get a worker with the given ID
     *
     * @param string $id
     *
     * @return null|Worker
     */
    public function getWorker($id)
    {
        $worker = \Resque_Worker::find($id);

        if (!$worker) {
            return null;
        }

        return new Worker($worker);
    }

    /**
     * Prune dead workers
     */
    public function pruneDeadWorkers()
    {
        // HACK, prune dead workers, just in case
        $worker = new \Resque_Worker('temp');
        $worker->pruneDeadWorkers();
    }

    /**
     * @return array
     */
    public function getDelayedJobTimestamps()
    {
        $timestamps = \Resque::redis()->zrange('delayed_queue_schedule', 0, -1);

        //TODO: find a more efficient way to do this
        $out = array();
        foreach ($timestamps as $timestamp) {
            $out[] = array($timestamp, \Resque::redis()->llen('delayed:' . $timestamp));
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getFirstDelayedJobTimestamp()
    {
        $timestamps = $this->getDelayedJobTimestamps();
        if (count($timestamps) > 0) {
            return $timestamps[0];
        }

        return array(null, 0);
    }

    /**
     * Get the number of delayed jobs
     *
     * @return int
     */
    public function getNumberOfDelayedJobs()
    {
        return \ResqueScheduler::getDelayedQueueScheduleSize();
    }

    /**
     * Get jobs
     *
     * @param $timestamp
     *
     * @return array
     */
    public function getJobsForTimestamp($timestamp)
    {
        $jobs = \Resque::redis()->lrange('delayed:' . $timestamp, 0, -1);
        $out  = array();
        foreach ($jobs as $job) {
            $out[] = json_decode($job, true);
        }

        return $out;
    }

    /**
     * Clear the given queue
     *
     * @param string $queue The name of the queue
     *
     * @return int
     */
    public function clearQueue($queue)
    {
        $length = \Resque::redis()->llen('queue:' . $queue);
        \Resque::redis()->del('queue:' . $queue);

        return $length;
    }

    /**
     * Get failed jobs
     *
     * @param int $start
     * @param int $count
     *
     * @return array|FailedJob[]
     */
    public function getFailedJobs($start = -100, $count = 100)
    {
        $jobs = \Resque::redis()->lrange('failed', $start, $count);

        $result = array();

        foreach ($jobs as $job) {
            $result[] = new FailedJob(json_decode($job, true));
        }

        return $result;
    }

    /**
     * Clear the list of failed jobs
     */
    public function clearFailedJobs()
    {
        \Resque::redis()->del('failed');
    }
}
