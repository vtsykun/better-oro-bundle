<?php

namespace Okvpn\Bundle\BetterOroBundle\Job;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\RootJobProgressCalculator as TurtleRootJobProgressCalculator;

class RootJobProgressCalculator extends TurtleRootJobProgressCalculator
{
    private $jobStorage;
    private $registry;

    public function __construct(JobStorage $jobStorage, ManagerRegistry $registry)
    {
        $this->jobStorage = $jobStorage;
        $this->registry = $registry;
        parent::__construct($jobStorage);
    }

    /**
     * @param Job $job
     */
    public function calculate(Job $job)
    {
        $rootJob = $job->isRoot() ? $job : $job->getRootJob();
        $rootJob->setLastActiveAt(new \DateTime());
        /** @var EntityManagerInterface $manager */
        $manager = $this->registry->getManagerForClass('OroMessageQueueBundle:Job');
        $qb = $manager->createQueryBuilder();

        $qb->select('COUNT(1)')
            ->from('OroMessageQueueBundle:Job', 'j')
            ->where($qb->expr()->eq('j.rootJob', $rootJob->getId()));

        $numberOfChildren = (clone $qb)
            ->getQuery()
            ->getSingleScalarResult();
        $processed = (clone $qb)
            ->andWhere($qb->expr()->in('j.status', self::$stopStatuses))
            ->getQuery()
            ->getSingleScalarResult();

        $progress = round($processed / $numberOfChildren, 4);
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use ($progress) {
            if ($progress !== $rootJob->getJobProgress()) {
                $rootJob->setJobProgress($progress);
            }
        });
    }
}
