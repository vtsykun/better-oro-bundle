<?php

namespace Okvpn\Bundle\BetterOroBundle\Event;

final class SendEvents
{
    /**
     * Dispatch before send message to producer.
     * Used for modify properties, headers, body or decline sending to producer
     */
    const BEFORE_SEND = 'message_queue.before_send';

    /**
     * Dispatch after send message to producer.
     */
    const AFTER_SEND = 'message_queue.after_send';
}
