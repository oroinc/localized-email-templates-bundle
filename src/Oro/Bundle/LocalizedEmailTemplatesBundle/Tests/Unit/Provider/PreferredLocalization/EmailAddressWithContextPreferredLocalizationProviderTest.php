<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Provider\PreferredLocalization;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\UserBundle\Entity\User;

class EmailAddressWithContextPreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var PreferredLocalization\EmailAddressWithContextPreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->provider = new PreferredLocalization\EmailAddressWithContextPreferredLocalizationProvider(
            $this->innerProvider
        );
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param object $entity
     * @param bool $isSupported
     */
    public function testSupports($entity, bool $isSupported): void
    {
        $this->assertSame($isSupported, $this->provider->supports($entity));

        if (!$isSupported) {
            $this->expectException(\LogicException::class);
            $this->provider->getPreferredLocalization($entity);
        }
    }

    /**
     * @return array
     */
    public function supportsDataProvider(): array
    {
        return [
            'supported' => [
                'entity' => new EmailAddressWithContext('to@example.com'),
                'isSupported' => true,
            ],
            'not supported' => [
                'entity' => new \stdClass(),
                'isSupported' => false,
            ],
        ];
    }

    public function testGetPreferredLocalization(): void
    {
        $context = new User();
        $entity = new EmailAddressWithContext('to@example.com', $context);

        $localization = new Localization();
        $this->innerProvider->expects($this->once())
            ->method('getPreferredLocalization')
            ->with($this->identicalTo($context))
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
