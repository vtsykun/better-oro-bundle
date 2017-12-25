<?php

namespace Okvpn\Bundle\BetterOroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\MessageQueueBundle\Entity\Job;

/**
 * Class JobLog
 *
 * @ORM\Table(name="oro_message_queue_job_log")
 * @ORM\Entity()
 */
class JobLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="level", type="string")
     */
    private $level;

    /**
     * @var string
     *
     * ORM\ManyToOne(targetEntity="Oro\Bundle\MessageQueueBundle\Entity\Job")
     * ORM\JoinColumn(name="job_id", referencedColumnName="id")
     * @ORM\Column(name="job_id", type="integer")
     */
    private $job;

    /**
     * @var string
     *
     * @ORM\Column(name="log", type="text")
     */
    private $log;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param string $level
     * @return JobLog
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param Job $job
     * @return JobLog
     */
    public function setJob(Job $job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param string $log
     * @return JobLog
     */
    public function setLog($log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return JobLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
