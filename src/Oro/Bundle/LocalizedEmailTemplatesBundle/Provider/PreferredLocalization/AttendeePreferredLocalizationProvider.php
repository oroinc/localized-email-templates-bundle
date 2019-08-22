<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalization;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProviderInterface;

/**
 * Determines localization for Attendee entity based on related user.
 */
class AttendeePreferredLocalizationProvider extends BasePreferredLocalizationProvider
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
        return $entity instanceof Attendee;
    }

    /**
     * @param Attendee $entity
     * @return Localization|null
     */
    protected function getPreferredLocalizationForEntity($entity): ?Localization
    {
        return $this->innerLocalizationProvider->getPreferredLocalization($entity->getUser());
    }
}
