<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Provider\PreferredLocalization;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization\AttendeePreferredLocalizationProvider;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeePreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var AttendeePreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->provider = new AttendeePreferredLocalizationProvider($this->innerProvider);
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
                'entity' => new Attendee(),
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
        $user = new User();
        $entity = (new Attendee())->setUser($user);

        $localization = new Localization();
        $this->innerProvider->expects($this->once())
            ->method('getPreferredLocalization')
            ->with($this->identicalTo($user))
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
