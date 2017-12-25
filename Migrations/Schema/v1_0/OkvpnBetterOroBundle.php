<?php

namespace Okvpn\Bundle\BetterOroBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Doctrine\DBAL\Types\Type;

class OkvpnBetterOroBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_message_queue_job_log');
        $table->addColumn('id', Type::INTEGER, ['autoincrement' => true]);
        $table->addColumn('level', Type::STRING, ['length' => 32]);
        $table->addColumn('job_id', Type::INTEGER);
        $table->addColumn('log', Type::TEXT);
        $table->addColumn('created_at', Type::DATETIME);
        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            'oro_message_queue_job',
            ['job_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $queries->addPostQuery(new IndexMigrationQuery());
    }
}
