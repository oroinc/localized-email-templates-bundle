<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LocalizedEmailTemplatesBundle\DependencyInjection\OroLocalizedEmailTemplatesExtension;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroLocalizedEmailTemplatesExtensionTest extends ExtensionTestCase
{
    /** @var OroLocalizedEmailTemplatesExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroLocalizedEmailTemplatesExtension();
    }

    public function testGetAlias(): void
    {
        $this->assertEquals('oro_localized_email_templates', $this->extension->getAlias());
    }

    public function testLoad(): void
    {
        $this->loadExtension($this->extension);

        $this->assertPublicServices([
            'oro_localized_email_templates.manager.localization_aware_email_notification',
            'oro_localized_email_templates.manager.localization_aware_email_template',
            PreferredLocalizationProviderInterface::class
        ]);
    }
}
