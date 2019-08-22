<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class EmailTemplateLocalizationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        self::assertPropertyAccessors(new EmailTemplateLocalization(), [
            ['id', 1],
            ['localization', new Localization()],
            ['subject', 'Test subject'],
            ['subjectFallback', false],
            ['content', 'Test content'],
            ['contentFallback', false],
        ]);
    }
}
