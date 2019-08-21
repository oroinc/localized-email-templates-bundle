<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;

/**
 * Determines localization for EmailAddressWithContext entity
 * by initiating localization determination process for the context
 */
class EmailAddressWithContextPreferredLocalizationProvider extends BasePreferredLocalizationProvider
{
    /** @var PreferredLocalizationProviderInterface */
    private $innerLocalizationProvider;

    /**
     * @param PreferredLocalizationProviderInterface $innerLocalizationProvider
     */
    public function __construct(PreferredLocalizationProviderInterface $innerLocalizationProvider)
    {
        $this->innerLocalizationProvider = $innerLocalizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof EmailAddressWithContext;
    }

    /**
     * @param EmailAddressWithContext $entity
     * @return Localization|null
     */
    public function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->innerLocalizationProvider->getPreferredLocalization($entity->getContext());
    }
}
