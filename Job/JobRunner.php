<?php

namespace Okvpn\Bundle\BetterOroBundle\Job;

use Okvpn\Bundle\BetterOroBundle\Logger\JobLogHandler;
use Okvpn\Bundle\BetterOroBundle\Logger\JobsStack;
use Oro\Bundle\MessageQueueBundle\Entity\Job as JobEntity;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner as OroJobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Psr\Log\LoggerInterface;

class JobRunner extends OroJobRunner
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /** @var JobLogHandler */
    private $logHandler;

    /**
     * @var JobProcessor
     */
    private $jobProcessor;

    /**
     * @var Job
     */
    private $rootJob;

    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var JobsStack
     */
    private $jobsStack;

    /**
     * {@inheritdoc}
     */
    public function __construct(JobProcessor $jobProcessor, Job $rootJob = null)
    {
        parent::__construct($jobProcessor, $rootJob);

        $this->jobProcessor = $jobProcessor;
        $this->rootJob = $rootJob;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setLogHandler(JobLogHandler $logHandler)
    {
        $this->logHandler = $logHandler;
    }

    public function setJobsStack(JobsStack $jobsStack)
    {
        $this->jobsStack = $jobsStack;
    }

    /**
     * @param JobStorage $jobStorage
     */
    public function setJobStorage(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function runUnique($ownerId, $name, \Closure $runCallback)
    {
        $rootJob = $this->jobProcessor->findOrCreateRootJob($ownerId, $name, true);

        return $this->runWrapperAndLog(function () use ($ownerId, $name, $runCallback) {
            return parent::runUnique($ownerId, $name, $runCallback);
        }, $rootJob);
    }

    /**
     * {@inheritdoc}
     */
    public function createDelayed($name, \Closure $startCallback)
    {
        return $this->runWrapperAndLog(function () use ($name, $startCallback) {
            return parent::createDelayed($name, $startCallback);
        }, $this->rootJob);
    }

    /**
     * {@inheritdoc}
     */
    public function runDelayed($jobId, \Closure $runCallback)
    {
        $job = $this->jobProcessor->findJobById($jobId);

        return $this->runWrapperAndLog(function () use ($jobId, $runCallback) {
            return parent::runDelayed($jobId, $runCallback);
        }, $job->getRootJob());
    }

    /**
     * @param callable $wrapper
     * @param JobEntity $job
     * @return mixed
     * @throws \Throwable
     */
    protected function runWrapperAndLog(callable $wrapper, JobEntity $job = null)
    {
        if ($job !== null && $this->jobsStack !== null) {
            $this->jobsStack->push($job);
        }

        if ($this->logHandler !== null) {
            $this->logHandler->clear();
        }

        ob_start();
        try {
            $result = call_user_func($wrapper);
        } catch (\Throwable $e) {
            $log = sprintf(
                "[%s] %s in %s:%s.\nTrace:\n%s",
                get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
            );

            $this->logger->critical($log);
            throw $e;
        }

        $this->logger->info(ob_get_contents());
        ob_end_clean();

        if ($job !== null && $this->jobsStack !== null) {
            $this->jobsStack->pop();
        }

        return $result;
    }
}
