<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProvider;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Chain provider for preferred localization providers allows to extend it's behavior by adding preferred localization
 * providers from other bundles.
 * This class should be injected as a dependency in services where entity's preferred language is needed.
 */
class ChainPreferredLocalizationProvider implements PreferredLocalizationProviderInterface
{
    /**
     * @var PreferredLocalizationProviderInterface[]
     */
    private $providers;

    /**
     * ChainPreferredLocalizationProvider constructor.
     * @param PreferredLocalizationProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($entity): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getPreferredLocalization($entity): Localization
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($entity)) {
                return $provider->getPreferredLocalization($entity);
            }
        }

        throw new \LogicException(
            sprintf('No preferred localization provider for the "%s" entity class exists', \get_class($entity))
        );
    }
}
