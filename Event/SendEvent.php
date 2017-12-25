<?php

namespace Okvpn\Bundle\BetterOroBundle\Event;

use Oro\Component\MessageQueue\Client\Message;
use Symfony\Component\EventDispatcher\Event;

final class SendEvent extends Event
{
    /** @var string */
    private $topic;

    /** @var Message */
    private $message;

    /** @var bool */
    private $dropMessage = false;

    /**
     * @param string $topic
     * @param Message $message
     */
    public function __construct(Message $message, $topic)
    {
        $this->topic = $topic;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Message $message
     */
    public function setMessage(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param bool $dropMessage
     */
    public function setDropMessage($dropMessage = true)
    {
        $this->dropMessage = $dropMessage;
    }

    /**
     * @return bool
     */
    public function isDrop()
    {
        return $this->dropMessage;
    }
}
