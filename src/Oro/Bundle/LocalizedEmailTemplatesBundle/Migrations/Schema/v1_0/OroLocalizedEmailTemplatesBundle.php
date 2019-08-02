<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * The LocalizedEmailTemplate bundle class
 */
class OroLocalizedEmailTemplatesBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_email_template_trans');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('localization_id', 'integer', ['notnull' => true]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('subject_fallback', 'boolean', ['notnull' => true, 'default' => true]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('content_fallback', 'boolean', ['notnull' => true, 'default' => true]);
        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'template',
            'oro_email_template',
            'id',
            ['extend' => [
                'is_extend' => true,
                'owner' => ExtendScope::OWNER_CUSTOM,
                'without_default' => true,
                'on_delete' => 'CASCADE',
            ]]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $table,
            'template',
            'oro_email_template',
            'localizations',
            ['id'],
            ['id'],
            ['id'],
            [
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'cascade' => ['persist'],
                    'on_delete' => 'CASCADE',
                    'fetch' => ClassMetadataInfo::FETCH_EXTRA_LAZY
                ],
                'datagrid' => ['is_visible' => false],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false]
            ]
        );
    }
}
