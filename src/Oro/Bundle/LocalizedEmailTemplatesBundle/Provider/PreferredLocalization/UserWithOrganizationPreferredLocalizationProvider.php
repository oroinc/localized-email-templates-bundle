<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\UserProBundle\Model\UserWithOrganizationModel;

/**
 * Falls back to organization defined in UserWithOrganizationModel.
 */
class UserWithOrganizationPreferredLocalizationProvider extends BasePreferredLocalizationProvider
{
    /** @var PreferredLocalizationProviderInterface */
    private $innerLocalizationProvider;

    /** @var ConfigManager|null */
    private $organizationConfigManager;

    /** @var ConfigManager */
    private $userOrganizationConfigManager;

    /**
     * @param PreferredLocalizationProviderInterface $innerLocalizationProvider
     * @param ConfigManager|null $organizationConfigManager
     * @param ConfigManager $userOrganizationConfigManager
     */
    public function __construct(
        PreferredLocalizationProviderInterface $innerLocalizationProvider,
        ?ConfigManager $organizationConfigManager,
        ConfigManager $userOrganizationConfigManager
    ) {
        $this->innerLocalizationProvider = $innerLocalizationProvider;
        $this->organizationConfigManager = $organizationConfigManager;
        $this->userOrganizationConfigManager = $userOrganizationConfigManager;
    }

    /**
     * Returns true if entity is supported by provider.
     *
     * @param object|null $entity
     * @return bool
     */
    public function supports($entity): bool
    {
        return $this->organizationConfigManager
            && class_exists(UserWithOrganizationModel::class)
            && $entity instanceof UserWithOrganizationModel
            && $this->innerLocalizationProvider->supports($entity->getUser());
    }

    /**
     * @param UserWithOrganizationModel $entity
     * @return Localization|null
     */
    protected function getPreferredLocalizationForEntity($entity): ?Localization
    {
        $organizationScopeId = $this->organizationConfigManager->getScopeId();
        $userOrganizationScopeId = $this->userOrganizationConfigManager->getScopeId();

        // Note that if there is token in current context the scope will be redefined by decorated provider
        // which is ok, because organizations in token and in model should be equal.
        $this->userOrganizationConfigManager->setScopeIdFromEntity($entity);
        $this->organizationConfigManager->setScopeIdFromEntity($entity->getOrganization());

        $localization = $this->innerLocalizationProvider->getPreferredLocalization($entity->getUser());

        $this->organizationConfigManager->setScopeId($organizationScopeId);
        $this->userOrganizationConfigManager->setScopeId($userOrganizationScopeId);

        return $localization;
    }
}
