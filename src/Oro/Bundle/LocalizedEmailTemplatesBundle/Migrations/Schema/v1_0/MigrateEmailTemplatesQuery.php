<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Migrations\Schema\v1_0;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MigrateEmailTemplatesQuery extends ParametrizedMigrationQuery
{
    /** @var string Check content on wysiwyg empty formatting */
    public const EMPTY_REGEX = '#^(\r*\n*)*'
        . '\<!DOCTYPE html\>(\r*\n*)*'
        . '\<html\>(\r*\n*)*'
        . '\<head\>(\r*\n*)*\</head\>(\r*\n*)*'
        . '\<body\>(\r*\n*)*\</body\>(\r*\n*)*'
        . '\</html\>(\r*\n*)*$#';

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Copy email templates translations to localized templates';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $qb = $this->connection->createQueryBuilder()
            ->select(
                'loc.id as localization_id',
                'trans.object_id as template_id',
                'trans.field as field',
                'trans.content as content'
            )
            ->from('oro_email_template_translation', 'trans')
            ->innerJoin('trans', 'oro_language', 'lang', 'lang.code = trans.locale')
            ->innerJoin('trans', 'oro_localization', 'loc', 'loc.language_id = lang.id')
            ->where('trans.content IS NOT NULL');

        $stm = $qb->execute();

        $aggregation = [];
        foreach ($stm->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if (!preg_match(self::EMPTY_REGEX, trim($row['content']))) {
                $aggregation[$row['template_id']][$row['localization_id']][$row['field']] = $row['content'];
            }
        }

        foreach ($aggregation as $templateId => $localizations) {
            foreach ($localizations as $localizationId => $data) {
                $this->connection->insert(
                    'oro_email_template_trans',
                    [
                        'localization_id' => $localizationId,
                        'template_id' => $templateId,
                        'subject' => $data['subject'] ?? null,
                        'subject_fallback' => empty($data['subject']),
                        'content' => $data['content'] ?? null,
                        'content_fallback' => empty($data['content']),
                    ],
                    [
                        'integer',
                        'integer',
                        'string',
                        'boolean',
                        'string',
                        'boolean',
                    ]
                );
            }
        }
    }
}
