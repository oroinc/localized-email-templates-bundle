<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Provider\PreferredLocalization;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerProBundle\Model\CustomerUserWithWebsiteModel;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerUserWithWebsitePreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserLocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userLocalizationManager;

    /** @var PreferredLocalization\CustomerUserWithWebsitePreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->userLocalizationManager = $this->createMock(UserLocalizationManager::class);
        $this->provider = new PreferredLocalization\CustomerUserWithWebsitePreferredLocalizationProvider(
            $this->userLocalizationManager
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
                'entity' => new CustomerUserWithWebsiteModel(
                    (new CustomerUser())->setIsGuest(false),
                    new Website()
                ),
                'isSupported' => true,
            ],
            'not supported guests' => [
                'entity' => new CustomerUserWithWebsiteModel(
                    (new CustomerUser())->setIsGuest(true),
                    new Website()
                ),
                'isSupported' => false,
            ],
            'not supported' => [
                'entity' => new \stdClass(),
                'isSupported' => false,
            ],
        ];
    }

    public function testGetPreferredLocalizationByCurrentWebsite(): void
    {
        $user = (new CustomerUser())->setIsGuest(false);
        $website = new Website();

        $entity = new CustomerUserWithWebsiteModel($user, $website);

        $localization = new Localization();
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalizationByCustomerUser')
            ->with($this->identicalTo($user), $this->identicalTo($website))
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }

    public function testGetPreferredLocalizationWithoutWebsite(): void
    {
        $user = (new CustomerUser())->setIsGuest(false);
        $website = new Website();

        $entity = new CustomerUserWithWebsiteModel($user, $website);

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalizationByCustomerUser')
            ->with($this->identicalTo($user), $this->identicalTo($website))
            ->willReturn(null);

        $this->assertNull($this->provider->getPreferredLocalization($entity));
    }

    public function testGetPreferredLocalizationByPrimaryWebsite(): void
    {
        $websiteFromUser = new Website();
        $user = (new CustomerUser())
            ->setIsGuest(false)
            ->setWebsite($websiteFromUser);

        $websiteFromEntity = new Website();

        $entity = new CustomerUserWithWebsiteModel($user, $websiteFromEntity);

        $localization = new Localization();
        $this->userLocalizationManager->expects($this->exactly(2))
            ->method('getCurrentLocalizationByCustomerUser')
            ->withConsecutive(
                [$this->identicalTo($user), $this->identicalTo($websiteFromEntity)],
                [$this->identicalTo($user), $this->identicalTo($websiteFromUser)]
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $localization
            );

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
