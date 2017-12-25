<?php

namespace Okvpn\Bundle\BetterOroBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class IndexMigrationQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Add index oro_message_queue (priority DESC, id ASC)';
    }

    /**
     * Executes a query
     *
     * @param LoggerInterface $logger A logger which can be used to log details of an execution process
     */
    public function execute(LoggerInterface $logger)
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQL92Platform) {
            $updateSql = 'CREATE INDEX ik_oro_message_queue_pi on oro_message_queue (priority DESC, id ASC)';
            $this->logQuery($logger, $updateSql);
            $this->connection->executeUpdate($updateSql);
        }
    }
}
