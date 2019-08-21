<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;

use Oro\Bundle\CustomerProBundle\Model\CustomerUserWithWebsiteModel;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Returns preferred localization for CustomerUser based on current Website defined in CustomerUserWithWebsiteModel.
 */
class CustomerUserWithWebsitePreferredLocalizationProvider extends BasePreferredLocalizationProvider
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
     * Returns true if entity is supported by provider.
     *
     * @param object|null $entity
     * @return bool
     */
    public function supports($entity): bool
    {
        return $this->userLocalizationManager
            && class_exists(CustomerUserWithWebsiteModel::class)
            && $entity instanceof CustomerUserWithWebsiteModel
            && !$entity->getCustomerUser()->isGuest();
    }

    /**
     * @param CustomerUserWithWebsiteModel $entity
     * @return Localization|null
     */
    protected function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->getLocalizationByCurrentWebsite($entity) ?? $this->getLocalizationByPrimaryWebsite($entity);
    }


    /**
     * @param CustomerUserWithWebsiteModel $model
     * @return null|Localization
     */
    protected function getLocalizationByCurrentWebsite(CustomerUserWithWebsiteModel $model): ?Localization
    {
        return $this->userLocalizationManager->getCurrentLocalizationByCustomerUser(
            $model->getCustomerUser(),
            $model->getWebsite()
        );
    }

    /**
     * @param CustomerUserWithWebsiteModel $model
     * @return null|Localization
     */
    protected function getLocalizationByPrimaryWebsite(CustomerUserWithWebsiteModel $model): ?Localization
    {
        $customerUser = $model->getCustomerUser();
        if (!$customerUser->getWebsite()) {
            return null;
        }

        return $this->userLocalizationManager->getCurrentLocalizationByCustomerUser(
            $customerUser,
            $customerUser->getWebsite()
        );
    }
}
