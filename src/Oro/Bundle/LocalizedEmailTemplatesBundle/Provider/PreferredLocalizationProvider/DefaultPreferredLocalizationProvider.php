<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProvider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

/**
 * Default localization provider is used as a fallback for entities which are not supported by other providers.
 * Should be added with the lowest priority to be executed last.
 */
class DefaultPreferredLocalizationProvider implements PreferredLocalizationProviderInterface
{
    /** @var LocalizationManager */
    private $localizationManager;

    /**
     * @param LocalizationManager $localizationManager
     */
    public function __construct(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($entity): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getPreferredLocalization($entity): ?Localization
    {
        return $this->localizationManager->getDefaultLocalization();
    }
}
