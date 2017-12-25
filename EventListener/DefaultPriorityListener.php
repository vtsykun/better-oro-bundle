<?php

namespace Okvpn\Bundle\BetterOroBundle\EventListener;

use Okvpn\Bundle\BetterOroBundle\Event\SendEvent;
use Oro\Bundle\CronBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;

class DefaultPriorityListener
{
    /** @var array */
    protected $mapping = [];

    /**
     * @param string $topicName
     * @param string $priority
     */
    public function addPriorityTopicMapping($topicName, $priority)
    {
        $this->mapping[$topicName] = $priority;
    }

    /**
     * @param array $mapping
     */
    public function setPriorityTopicMapping(array $mapping)
    {
        $this->mapping = array_merge($this->mapping, $mapping);
    }

    public function onBeforeSend(SendEvent $event)
    {
        $message = $event->getMessage();
        foreach ($this->getMatchTopics($event) as $topic) {
            if (array_key_exists($topic, $this->mapping)) {
                $message->setPriority($this->mapping[$topic]);
                break;
            }
        }

        $event->setMessage($message);
    }

    /**
     * @param SendEvent $event
     * @return \Generator
     */
    protected function getMatchTopics(SendEvent $event)
    {
        $message = $event->getMessage();
        $body = $message->getBody();

        // process priority for cron commands
        if (
            in_array($event->getTopic(), [Topics::RUN_COMMAND, Topics::RUN_COMMAND_DELAYED])
            && is_array($body) && isset($body['command'])
        ) {
            if ($body['command'] === HandleProcessTriggerCommand::NAME && isset($body['arguments']['--name'])) {
                yield $body['arguments']['--name'];
            }

            yield $body['command'];
        }

        // process priority for process triggers
        if ($event->getTopic() === 'oro.workflow.execute_process_job' && is_array($body) && isset($body['definition_name'])) {
            yield $body['definition_name'];
        }

        yield $event->getTopic();
    }
}
