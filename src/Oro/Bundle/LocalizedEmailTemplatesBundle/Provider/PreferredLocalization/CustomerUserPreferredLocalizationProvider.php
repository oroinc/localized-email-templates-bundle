<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Returns preferred localization for CustomerUser entity based on customer user settings on frontend.
 */
class CustomerUserPreferredLocalizationProvider extends BasePreferredLocalizationProvider
{
    /**
     * @var UserLocalizationManager|null
     */
    private $userLocalizationManager;

    /**
     * @param UserLocalizationManager|null $userLocalizationManager
     */
    public function __construct(?UserLocalizationManager $userLocalizationManager)
    {
        $this->userLocalizationManager = $userLocalizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $this->userLocalizationManager
            && class_exists(CustomerUser::class)
            && $entity instanceof CustomerUser
            && !$entity->isGuest();
    }

    /**
     * @param CustomerUser $entity
     * @return Localization|null
     */
    protected function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->getLocalizationByCurrentWebsite($entity) ?? $this->getLocalizationByPrimaryWebsite($entity);
    }

    /**
     * @param CustomerUser $entity
     * @return Localization|null
     */
    private function getLocalizationByCurrentWebsite(CustomerUser $entity): ?Localization
    {
        return $this->userLocalizationManager->getCurrentLocalizationByCustomerUser($entity);
    }

    /**
     * @param CustomerUser $customerUser
     * @return Localization|null
     */
    private function getLocalizationByPrimaryWebsite(CustomerUser $customerUser): ?Localization
    {
        if (!$customerUser->getWebsite()) {
            return null;
        }

        return $this->userLocalizationManager->getCurrentLocalizationByCustomerUser(
            $customerUser,
            $customerUser->getWebsite()
        );
    }
}
