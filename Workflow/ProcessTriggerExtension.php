<?php

namespace Okvpn\Bundle\BetterOroBundle\Workflow;

use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessPriority;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\ProcessTriggerExtension as BaseProcessTriggerExtension;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Add additional info into message body {"definition_name": "some_def", "entity_id": 1, "process_job_id":1}
 */
class ProcessTriggerExtension extends BaseProcessTriggerExtension
{
    /**
     * @param array $queuedJobs [time shift => [priority => [process job, ...], ...], ...]
     * {@inheritdoc}
     */
    protected function createJobs(array $queuedJobs)
    {
        foreach ($queuedJobs as $timeShift => $processJobBatch) {
            foreach ($processJobBatch as $priority => $processJobs) {
                /** @var ProcessJob $processJob */
                foreach ($processJobs as $processJob) {
                    $message = new Message();
                    $message->setBody([
                        'process_job_id' => $processJob->getId(),
                        'definition_name' => $processJob->getProcessTrigger()->getDefinition()->getName(),
                        'entity_id' => $this->getEntity($processJob)
                    ]);
                    $message->setPriority(ProcessPriority::convertToMessageQueuePriority($priority));

                    if ($timeShift) {
                        $message->setDelay($timeShift);
                    }

                    $this->getMessageProducer()->send(Topics::EXECUTE_PROCESS_JOB, $message);
                    $this->logger->debug('Process queued', $processJob->getProcessTrigger(), $processJob->getData());
                }
            }
        }
    }

    /**
     * @return MessageProducerInterface
     */
    protected function getMessageProducer()
    {

        $closure = \Closure::bind(
            function (BaseProcessTriggerExtension $extension) {
                return $extension->{'messageProducer'};
            },
            null,
            BaseProcessTriggerExtension::class
        );

        return $closure($this);
    }

    /**
     * @param ProcessJob $processJob
     * @return null|int
     */
    protected function getEntity(ProcessJob $processJob)
    {
        $data = $processJob->getData();
        $entity = $data->getEntity();

        return is_object($entity) && method_exists($entity, 'getId') ? $entity->getId() : null;
    }
}
