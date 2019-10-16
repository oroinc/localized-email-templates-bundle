<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Migrations\Schema\v1_99;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveManyToOneRelationQuery;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveExtensionConfigurations implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('oro_email_template_localized')) {
            $queries->addPostQuery(new RemoveManyToOneRelationQuery(
                EmailTemplate::class,
                'localizations'
            ));
            $queries->addPostQuery(new RemoveManyToOneRelationQuery(
                EmailTemplateLocalization::class,
                'template'
            ));

            $queries->addPostQuery(new RemoveFieldQuery(EmailTemplate::class, 'localizations'));
            $queries->addPostQuery(new RemoveTableQuery(EmailTemplateLocalization::class));
        }
    }
}
