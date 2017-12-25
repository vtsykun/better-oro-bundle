<?php

namespace Okvpn\Bundle\BetterOroBundle\Logger;

use Doctrine\Common\Persistence\ManagerRegistry;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Okvpn\Bundle\BetterOroBundle\Entity\JobLog;

class JobLogHandler extends AbstractProcessingHandler
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var array */
    protected $errors = [];

    /** @var JobsStack */
    protected $jobsStack;

    /**
     * {@inheritdoc}
     */
    public function __construct(JobsStack $jobsStack, $level = Logger::DEBUG, $bubble = true)
    {
        $this->jobsStack = $jobsStack;
        parent::__construct($level, $bubble);
    }

    public function clear()
    {
        $this->errors = [];
    }

    public function getLastError()
    {
        if (empty($this->errors)) {
            return null;
        }

        return reset($this->errors);
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (!$job = $this->jobsStack->getCurrentJob()) {
            return;
        }

        $this->collectError($record);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        try {
            $this->registry
                ->getConnection('joblog')
                ->insert($this->getJobLogTable(), [
                    'level' => strtolower($record['level_name']),
                    'job_id' => $job->getId(),
                    'log' => $record['message'] . "\n" . $this->getExceptionMessage($record),
                    'created_at' => $now->format('Y-m-d H:i:s')
                ]);
        } catch (\Exception $e) {
            // skip
        }
    }

    /**
     * @param array $record
     * @return string
     */
    protected function getExceptionMessage(array $record)
    {
        $context = $record['context'] ?? [];
        foreach ($context as $e) {
            if (is_object($e) && $e instanceof \Throwable) {
                return sprintf(
                    "[%s] %s in %s:%s.\nTrace:\n%s",
                    get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
                );
            }
        }

        return '';
    }

    protected function collectError(array $record)
    {
        $context = $record['context'] ?? [];
        foreach ($context as $e) {
            if (is_object($e) && $e instanceof \Throwable) {
                $this->errors[] = $e;
            }
        }
    }

    /**
     * @return string
     */
    protected function getJobLogTable()
    {
        return $this->registry
            ->getManagerForClass(JobLog::class)
            ->getClassMetadata(JobLog::class)
            ->table['name'];
    }
}
