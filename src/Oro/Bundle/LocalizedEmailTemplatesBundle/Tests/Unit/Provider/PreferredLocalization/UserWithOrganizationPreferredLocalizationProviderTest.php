<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Provider\PreferredLocalization;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserProBundle\Model\UserWithOrganizationModel;

class UserWithOrganizationPreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationConfigManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userOrganizationConfigManager;

    /** @var PreferredLocalization\UserWithOrganizationPreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->organizationConfigManager = $this->createMock(ConfigManager::class);
        $this->userOrganizationConfigManager = $this->createMock(ConfigManager::class);

        $this->provider = new PreferredLocalization\UserWithOrganizationPreferredLocalizationProvider(
            $this->innerProvider,
            $this->organizationConfigManager,
            $this->userOrganizationConfigManager
        );
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param object $entity
     * @param bool $isInnerSupport
     * @param bool $isSupported
     */
    public function testSupports($entity, bool $isInnerSupport, bool $isSupported): void
    {
        $this->innerProvider->expects($isSupported ? $this->atLeastOnce() : $this->any())
            ->method('supports')
            ->willReturn($isInnerSupport);

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
                'entity' => new UserWithOrganizationModel(new User(), new Organization()),
                'isInnerSupport' => true,
                'isSupported' => true,
            ],
            'not supported by inner' => [
                'entity' => new UserWithOrganizationModel(new User(), new Organization()),
                'isInnerSupport' => false,
                'isSupported' => false,
            ],
            'not supported by entity' => [
                'entity' => new \stdClass(),
                'isInnerSupport' => false,
                'isSupported' => false,
            ],
            'not supported both' => [
                'entity' => new \stdClass(),
                'isInnerSupport' => true,
                'isSupported' => false,
            ],
        ];
    }

    public function testGetPreferredLocalization(): void
    {
        $organization = new Organization();
        $user = new User();
        $entity = new UserWithOrganizationModel($user, $organization);

        $this->innerProvider->expects($this->once())
            ->method('supports')
            ->with($this->identicalTo($user))
            ->willReturn(true);

        $this->organizationConfigManager->expects($this->atLeastOnce())
            ->method('getScopeId')
            ->willReturn(20);

        $this->userOrganizationConfigManager->expects($this->once())
            ->method('getScopeId')
            ->willReturn(30);

        $this->organizationConfigManager->expects($this->once())
            ->method('setScopeIdFromEntity')
            ->with($this->identicalTo($organization));

        $this->userOrganizationConfigManager->expects($this->once())
            ->method('setScopeIdFromEntity')
            ->with($this->identicalTo($entity));

        $localization = new Localization();
        $this->innerProvider->expects($this->once())
            ->method('getPreferredLocalization')
            ->with($this->identicalTo($user))
            ->willReturn($localization);

        $this->organizationConfigManager->expects($this->once())
            ->method('setScopeId')
            ->with(20);

        $this->userOrganizationConfigManager->expects($this->once())
            ->method('setScopeId')
            ->with(30);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
