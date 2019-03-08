<?php

declare(strict_types=1);

namespace Okvpn\Bundle\BetterOroBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class OptimiseDataauditIndexQuery implements MigrationQuery, ConnectionAwareInterface
{
    /** @var Connection */
    protected $connection;

    protected $notUsedIndexes = [
        'idx_5fba427c26f87db8', // impersonation_id
        'idx_oro_audit_owner_descr',
        'idx_5fba427ca76ed395', //user_id
        'idx_oro_audit_object_class',
        'idx_oro_audit_organization_id',
        'idx_oro_audit_type'
    ];

    protected $excludeFromRemove;

    public function __construct(array $excludeFromRemove = [])
    {
        $this->excludeFromRemove = $excludeFromRemove;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove not used index from "oro_audit" table';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $notUsedIndexes = $this->notUsedIndexes;
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSqlPlatform) {
            // PostgreSQL automatically creates a index when a unique constraint is defined for a table.
            // Index idx_oro_audit_obj_by_type and idx_oro_audit_version is the same.
            $notUsedIndexes[] = 'idx_oro_audit_obj_by_type';
        }

        $notUsedIndexes = array_diff($notUsedIndexes, $this->excludeFromRemove);
        foreach ($notUsedIndexes as $index) {
            $this->dropIndex($index);
        }
    }

    private function dropIndex(string $index)
    {
        $sql = "DROP INDEX IF EXISTS " . $index;
        $this->connection->executeQuery($sql);
    }
}
