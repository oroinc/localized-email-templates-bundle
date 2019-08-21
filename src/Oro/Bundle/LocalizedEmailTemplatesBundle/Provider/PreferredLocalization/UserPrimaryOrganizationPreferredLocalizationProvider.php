<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserProBundle\Model\UserWithOrganizationModel;

/**
 * Fallback to primary user organization if there is no organization in current context.
 *
 * We need to be able to determine localization by primary organization in order to send localized emails
 * from consumer context (e.g. cli commands like oro:cron:password:notify-expiring).
 */
class UserPrimaryOrganizationPreferredLocalizationProvider extends BasePreferredLocalizationProvider
{
    /** @var PreferredLocalizationProviderInterface */
    private $innerProvider;

    /** @var ConfigManager|null */
    private $organizationConfigManager;

    /**
     * @param PreferredLocalizationProviderInterface $innerProvider
     * @param ConfigManager|null $organizationConfigManager
     */
    public function __construct(
        PreferredLocalizationProviderInterface $innerProvider,
        ?ConfigManager $organizationConfigManager
    ) {
        $this->organizationConfigManager = $organizationConfigManager;
        $this->innerProvider = $innerProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $this->organizationConfigManager
            && $this->organizationConfigManager->getScopeId() === 0
            && class_exists(UserWithOrganizationModel::class)
            && $entity instanceof User;
    }

    /**
     * @param User $entity
     * @return Localization|null
     */
    protected function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->innerProvider->getPreferredLocalization(
            new UserWithOrganizationModel($entity, $entity->getOrganization())
        );
    }
}
