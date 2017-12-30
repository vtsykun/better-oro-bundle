<?php

namespace Okvpn\Bundle\BetterOroBundle\Job;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculator as TurtleRootJobStatusCalculator;

class RootJobStatusCalculator extends TurtleRootJobStatusCalculator
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param JobStorage $jobStorage
     * @param ManagerRegistry $registry
     */
    public function __construct(JobStorage $jobStorage, ManagerRegistry $registry)
    {
        $this->jobStorage = $jobStorage;
        $this->registry = $registry;
        parent::__construct($jobStorage);
    }

    /**
     * @param Job $job
     *
     * @return bool true if root job was stopped
     */
    public function calculate(Job $job)
    {
        $rootJob = $job->isRoot() ? $job : $job->getRootJob();
        $rootJob->setLastActiveAt(new \DateTime());
        $stopStatuses = [Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED, Job::STATUS_STALE];

        if (in_array($rootJob->getStatus(), $stopStatuses, true)) {
            return false;
        }

        $rootStopped = false;
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use ($stopStatuses, &$rootStopped) {
            if (in_array($rootJob->getStatus(), $stopStatuses, true)) {
                return;
            }

            $status = $this->calculateRootJobStatusUseQueryBuilder($rootJob);

            $rootJob->setStatus($status);

            if (in_array($status, $stopStatuses, true)) {
                $rootStopped = true;
                if (! $rootJob->getStoppedAt()) {
                    $rootJob->setStoppedAt(new \DateTime());
                }
            }
        });

        return $rootStopped;
    }

    /**
     * @param Job $job
     * @return string
     */
    protected function calculateRootJobStatusUseQueryBuilder(Job $job)
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->registry->getManagerForClass('OroMessageQueueBundle:Job');
        $qb = $manager->createQueryBuilder();
        $qb
            ->select(
                [
                    sprintf('SUM(CASE WHEN j.status = \'%s\' THEN 1 ELSE 0 END) as STATUS_NEW', Job::STATUS_NEW),
                    sprintf('SUM(CASE WHEN j.status = \'%s\' THEN 1 ELSE 0 END) as STATUS_RUNNING', Job::STATUS_RUNNING),
                    sprintf('SUM(CASE WHEN j.status = \'%s\' THEN 1 ELSE 0 END) as STATUS_CANCELLED', Job::STATUS_CANCELLED),
                    sprintf('SUM(CASE WHEN j.status = \'%s\' THEN 1 ELSE 0 END) as STATUS_FAILED', Job::STATUS_FAILED),
                    sprintf('SUM(CASE WHEN j.status = \'%s\' THEN 1 ELSE 0 END) as STATUS_FAILED_REDELIVERED', Job::STATUS_FAILED_REDELIVERED),
                    sprintf('SUM(CASE WHEN j.status = \'%s\' THEN 1 ELSE 0 END) as STATUS_SUCCESS', Job::STATUS_SUCCESS),
                ]
            )
            ->from('OroMessageQueueBundle:Job', 'j')
            ->where($qb->expr()->eq('j.rootJob', $job->getId()));

        $result = $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY) ?? [];

        list($new, $running, $cancelled, $failed, $failedRedelivery, $success) = [
            $result['STATUS_NEW'] ?? 0,
            $result['STATUS_RUNNING'] ?? 0,
            $result['STATUS_CANCELLED'] ?? 0,
            $result['STATUS_FAILED'] ?? 0,
            $result['STATUS_FAILED_REDELIVERED'] ?? 0,
            $result['STATUS_SUCCESS'] ?? 0,
        ];

        return $this->getRootJobStatus($new, $running, $cancelled, $failed, $success, $failedRedelivery);
    }
}
