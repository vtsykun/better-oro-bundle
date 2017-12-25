<?php

namespace Okvpn\Bundle\BetterOroBundle\Client;

use Okvpn\Bundle\BetterOroBundle\Event\SendEvent;
use Okvpn\Bundle\BetterOroBundle\Event\SendEvents;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessageProducerEventDecorator implements MessageProducerInterface
{
    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param MessageProducerInterface $messageProducer
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(MessageProducerInterface $messageProducer, EventDispatcherInterface $eventDispatcher)
    {
        $this->messageProducer = $messageProducer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $message = $message instanceof Message ? $message : (new Message())->setBody($message);
        $event = new SendEvent($message, $topic);

        $this->eventDispatcher->dispatch(SendEvents::BEFORE_SEND, $event);
        if ($event->isDrop() === false) {
            $this->messageProducer->send($event->getTopic(), $event->getMessage());
            $this->eventDispatcher->dispatch(SendEvents::AFTER_SEND, $event);
        }
    }
}
