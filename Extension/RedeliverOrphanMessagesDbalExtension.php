<?php

namespace Okvpn\Bundle\BetterOroBundle\Extension;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\DbalPidFileManager;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

/**
 * After the reboot of the server, the temporary file may be lost and message will not redeliver, so
 * message form a group can blocked
 */
class RedeliverOrphanMessagesDbalExtension extends AbstractExtension
{
    const CHECK_INTERVAL = 60; //60 sec

    /** @var DbalPidFileManager|null */
    protected $pidFileManager;

    /** @var int */
    protected static $lastCheck;

    /**
     * @param DbalPidFileManager $pidFileManager
     */
    public function __construct(DbalPidFileManager $pidFileManager = null)
    {
        $this->pidFileManager = $pidFileManager;
        if (self::$lastCheck === null) {
            self::$lastCheck = time();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        self::$lastCheck = null;
        $this->doRedeliver($context);
    }

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context)
    {
        $this->doRedeliver($context);
    }

    /**
     * @param Context $context
     */
    public function onIdle(Context $context)
    {
        $this->doRedeliver($context);
    }

    protected function doRedeliver(Context $context)
    {
        if ($this->pidFileManager === null || time() - self::$lastCheck < self::CHECK_INTERVAL) {
            return;
        }
        /** @var DbalSession $session */
        $session = $context->getSession();
        if (!$session instanceof DbalSession) {
            return;
        }

        $connection = $session->getConnection();
        $dbal = $connection->getDBALConnection();
        self::$lastCheck = time();
        $consumersId = array_column($this->pidFileManager->getListOfPidsFileInfo(), 'consumerId');

        $sql = sprintf(
            'UPDATE %s SET consumer_id=NULL, redelivered=:isRedelivered '.
            'WHERE consumer_id NOT IN (:consumerIds) AND consumer_id IS NOT NULL',
            $connection->getTableName()
        );

        $rows = $dbal->executeUpdate(
            $sql,
            [
                'isRedelivered' => true,
                'consumerIds' => $consumersId,
            ],
            [
                'isRedelivered' => Type::BOOLEAN,
                'consumerIds' => Connection::PARAM_STR_ARRAY,
            ]
        );

        $context->getLogger()->info(
            sprintf('Run redeliver message, received: %s. Running consumers: %s', $rows, implode(', ', $consumersId))
        );
    }
}
