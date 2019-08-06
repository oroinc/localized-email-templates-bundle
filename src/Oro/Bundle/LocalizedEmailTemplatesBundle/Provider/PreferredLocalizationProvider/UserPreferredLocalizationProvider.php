<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Returns preferred localization for User entity based on his or her language chosen in system configuration settings.
 */
class UserPreferredLocalizationProvider implements PreferredLocalizationProviderInterface
{
    /**
     * @var ConfigManager
     */
    private $userConfigManager;

    /**
     * @var LocaleSettings
     */
    private $localizationManager;

    /**
     * @param ConfigManager $userConfigManager
     * @param LocalizationManager $localizationManager
     */
    public function __construct(ConfigManager $userConfigManager, LocalizationManager $localizationManager)
    {
        $this->userConfigManager = $userConfigManager;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreferredLocalization($entity): Localization
    {
        $originalScopeId = $this->userConfigManager->getScopeId();
        $this->userConfigManager->setScopeIdFromEntity($entity);

        $localization = $this->localizationManager->getLocalization(
            $this->userConfigManager->get(
                Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION)
            )
        );

        $this->userConfigManager->setScopeId($originalScopeId);

        return $localization;
    }
}
