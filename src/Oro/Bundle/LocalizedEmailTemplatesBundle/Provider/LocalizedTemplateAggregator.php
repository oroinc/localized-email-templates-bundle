<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider;

use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProvider\PreferredLocalizationProviderInterface;

/**
 * Provide templates aggregated by recipient preferred localizations
 */
class LocalizedTemplateAggregator
{
    /** @var PreferredLocalizationProviderInterface */
    private $localizationProvider;

    /** @var LocalizationAwareEmailTemplateContentProvider */
    private $templateProvider;

    /**
     * @param PreferredLocalizationProviderInterface $localizationProvider
     * @param LocalizationAwareEmailTemplateContentProvider $templateProvider
     */
    public function __construct(
        PreferredLocalizationProviderInterface $localizationProvider,
        LocalizationAwareEmailTemplateContentProvider $templateProvider
    ) {
        $this->localizationProvider = $localizationProvider;
        $this->templateProvider = $templateProvider;
    }

    /**
     * Aggregate templates by recipient preferred localization
     *
     * @param iterable|EmailHolderInterface[] $recipients
     * @param EmailTemplateCriteria $criteria
     * @param array $params
     * @return LocalizedTemplateDTO[]
     */
    public function aggregate(
        iterable $recipients,
        EmailTemplateCriteria $criteria,
        array $params = []
    ): array {
        /** @var LocalizedTemplateDTO[] $aggregation */
        $aggregation = [];
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof EmailHolderInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Recipients should be array of EmailHolderInterface values, "%s" type in array given.',
                        \is_object($recipient) ? \get_class($recipient) : \gettype($recipient)
                    )
                );
            }

            $localization = $this->localizationProvider->getPreferredLocalization($recipient);
            if (!$localization) {
                throw new \LogicException(sprintf(
                    'No preferred localization for the "%s" recipient class, check service dependencies',
                    \get_class($recipient)
                ));
            }

            if (!isset($aggregation[$localization->getId()])) {
                $aggregation[$localization->getId()] = new LocalizedTemplateDTO(
                    $this->templateProvider->getTemplateContent($criteria, $localization, $params)
                );
            }

            $aggregation[$localization->getId()]->addRecipient($recipient);
        }

        return $aggregation;
    }
}
