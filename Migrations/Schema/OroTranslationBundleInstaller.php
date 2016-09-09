<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTranslationBundleInstaller implements Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroLanguageTable($schema);
        $this->createOroTranslationTable($schema);

        /** Foreign keys generation **/
        $this->addOroLanguageForeignKeys($schema);
        $this->addOroTranslationForeignKeys($schema);
    }

    /**
     * Create oro_language table
     *
     * @param Schema $schema
     */
    protected function createOroLanguageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_language');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('code', 'string', ['length' => 16]);
        $table->addColumn('enabled', 'boolean', ['default' => false]);
        $table->addColumn('installed_build_date', 'datetime', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code']);
    }

    /**
     * Create oro_translation table
     *
     * @param Schema $schema
     */
    protected function createOroTranslationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('language_id', 'integer', ['notnull' => false]);
        $table->addColumn('key', 'string', ['length' => 255]);
        $table->addColumn('value', 'text', ['notnull' => false]);
        $table->addColumn('domain', 'string', ['length' => 255]);
        $table->addColumn('scope', 'smallint', []);
        $table->addIndex(['key'], 'message_idx', []);
        $table->addIndex(['language_id']);
        $table->addIndex(['language_id', 'domain'], 'messages_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_language foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroLanguageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_language');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_translation foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroTranslationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_translation');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_language'),
            ['language_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
