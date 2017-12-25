<?php

namespace Okvpn\Bundle\BetterOroBundle\Logger;

use Oro\Component\MessageQueue\Job\Job;

/**
 * Jobs stack that controls the lifecycle of jobs
 */
class JobsStack
{
    /** @var \SplStack */
    private $stackJobs;

    public function __construct()
    {
        $this->stackJobs = new \SplStack();
    }

    /**
     * @return null|Job
     */
    public function getCurrentRootJob()
    {
        $currentJob = $this->getCurrentJob();
        if (null !== $currentJob) {
            return $currentJob->isRoot() ? $currentJob : $currentJob->getRootJob();
        }

        return null;
    }

    /**
     * @return Job|null
     */
    public function getCurrentJob()
    {
        if ($this->stackJobs->isEmpty()) {
            return null;
        }

        return $this->stackJobs->top();
    }

    /**
     * @param Job $job
     */
    public function push(Job $job)
    {
        $this->stackJobs->push($job);
    }

    public function pop()
    {
        return $this->stackJobs->pop();
    }
}
