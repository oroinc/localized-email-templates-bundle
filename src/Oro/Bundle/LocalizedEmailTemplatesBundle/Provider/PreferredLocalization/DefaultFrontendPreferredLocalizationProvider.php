<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Default frontend localization provider is used as a fallback for entities which are not supported by other providers.
 * Should be added with the priority to be after main provider and before default one.
 */
class DefaultFrontendPreferredLocalizationProvider extends BasePreferredLocalizationProvider
{
    /**
     * @var UserLocalizationManager|null
     */
    private $userLocalizationManager;

    /**
     * @var FrontendHelper|null
     */
    private $frontendHelper;

    /**
     * @param UserLocalizationManager $userLocalizationManager
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(?UserLocalizationManager $userLocalizationManager, ?FrontendHelper $frontendHelper)
    {
        $this->userLocalizationManager = $userLocalizationManager;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $this->userLocalizationManager && $this->frontendHelper
            && $this->frontendHelper->isFrontendRequest();
    }

    /**
     * @param $entity
     * @return Localization|null
     */
    protected function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->userLocalizationManager->getCurrentLocalization();
    }
}
