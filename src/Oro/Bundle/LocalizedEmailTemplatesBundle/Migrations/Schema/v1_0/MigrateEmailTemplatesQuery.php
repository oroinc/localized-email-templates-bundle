<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Copy email templates translations to templates localizations
 */
class MigrateEmailTemplatesQuery extends ParametrizedMigrationQuery
{
    /** @var string Check content on wysiwyg empty formatting */
    private const EMPTY_REGEX = '#^(\r*\n*)*'
        . '\<!DOCTYPE html\>(\r*\n*)*'
        . '\<html\>(\r*\n*)*'
        . '\<head\>(\r*\n*)*\</head\>(\r*\n*)*'
        . '\<body\>(\r*\n*)*\</body\>(\r*\n*)*'
        . '\</html\>(\r*\n*)*$#';

    private const BATCH_SIZE = 500;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Copy email templates translations to templates localizations';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $qb = $this->connection->createQueryBuilder()
            ->select(
                'loc.id as localization_id',
                'trans.object_id as template_id'
            )
            ->from('oro_email_template_translation', 'trans')
            ->innerJoin('trans', 'oro_language', 'lang', 'lang.code = trans.locale')
            ->innerJoin('lang', 'oro_localization', 'loc', 'loc.language_id = lang.id')
            ->groupBy('trans.object_id, trans.locale, loc.id')
            ->setFirstResult(0)
            ->setMaxResults(self::BATCH_SIZE);

        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof MySqlPlatform) {
            $qb->addSelect(
                "GROUP_CONCAT(CASE WHEN trans.field = 'subject' THEN content ELSE null END) as subject",
                "GROUP_CONCAT(CASE WHEN trans.field = 'content' THEN content ELSE null END) as content"
            );
        } elseif ($platform instanceof PostgreSqlPlatform) {
            $qb->addSelect(
                "STRING_AGG(CASE WHEN trans.field = 'subject' THEN content ELSE null END, ',') as subject",
                "STRING_AGG(CASE WHEN trans.field = 'content' THEN content ELSE null END, ',') as content"
            );
        } else {
            $logger->critical('Not allowed database platform for migrate email templates');
            return;
        }

        do {
            $stm = $qb->execute();

            foreach ($stm->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                if (preg_match(self::EMPTY_REGEX, $row['content'])) {
                    $row['content'] = null;
                }

                $this->connection->insert(
                    'oro_email_template_localized',
                    [
                        'localization_id' => $row['localization_id'],
                        'template_id' => $row['template_id'],
                        'subject' => $row['subject'],
                        'subject_fallback' => $row['subject'] === null,
                        'content' => $row['content'],
                        'content_fallback' => $row['content'] === null
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

            $qb->setFirstResult($qb->getFirstResult() + $qb->getMaxResults());
        } while ($stm->rowCount() > 0);
    }
}
