<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Provider\PreferredLocalization;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserProBundle\Model\UserWithOrganizationModel;

class UserPrimaryOrganizationPreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationConfigManager;

    /** @var PreferredLocalization\UserPrimaryOrganizationPreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->organizationConfigManager = $this->createMock(ConfigManager::class);

        $this->provider = new PreferredLocalization\UserPrimaryOrganizationPreferredLocalizationProvider(
            $this->innerProvider,
            $this->organizationConfigManager
        );
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param object $entity
     * @param int $scopeId
     * @param bool $isSupported
     */
    public function testSupports($entity, int $scopeId, bool $isSupported): void
    {
        $this->organizationConfigManager->expects($this->atLeastOnce())
            ->method('getScopeId')
            ->willReturn($scopeId);

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
                'entity' => new User(),
                'scopeId' => 0,
                'isSupported' => true,
            ],
            'not supported by scope' => [
                'entity' => new User(),
                'scopeId' => 1,
                'isSupported' => false,
            ],
            'not supported by entity' => [
                'entity' => new \stdClass(),
                'scopeId' => 0,
                'isSupported' => false,
            ],
            'not supported both' => [
                'entity' => new \stdClass(),
                'scopeId' => 1,
                'isSupported' => false,
            ],
        ];
    }

    public function testGetPreferredLocalization(): void
    {
        $this->organizationConfigManager->expects($this->atLeastOnce())
            ->method('getScopeId')
            ->willReturn(0);

        $organization = new Organization();
        $entity = (new User())->setOrganization($organization);

        $localization = new Localization();
        $this->innerProvider->expects($this->once())
            ->method('getPreferredLocalization')
            ->with(new UserWithOrganizationModel($entity, $organization))
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
