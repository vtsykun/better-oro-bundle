<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\Cron;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Symfony\Component\EventDispatcher\Event;

class CronLoadEvent extends Event
{
    const NAME = 'okvpn.onCronLoad';

    private $scheduler;

    public function __construct(DeferredScheduler $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * @return DeferredScheduler
     */
    public function getScheduler(): DeferredScheduler
    {
        return $this->scheduler;
    }
}
