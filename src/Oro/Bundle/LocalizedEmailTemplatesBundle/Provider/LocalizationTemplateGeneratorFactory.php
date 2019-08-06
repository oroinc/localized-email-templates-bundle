<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider;

use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProvider\PreferredLocalizationProviderInterface;

/**
 * Provide templates aggregated by recipient preferred localizations
 */
class LocalizationTemplateGeneratorFactory
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
     * @param iterable|EmailHolderInterface[] $recipients
     * @param EmailTemplateCriteria $criteria
     * @param array $params
     * @return \Generator
     */
    public function createTemplateGenerator(
        iterable $recipients,
        EmailTemplateCriteria $criteria,
        array $params = []
    ): \Generator {
        $aggregation = $this->aggregateRecipientByLocalization($recipients);

        foreach ($aggregation as ['localization' => $localization, 'recipients' => $recipients]) {
            yield $this->templateProvider->getTemplateContent($criteria, $localization, $params) => $recipients;
        }
    }

    /**
     * @param iterable $recipients
     * @return array
     */
    private function aggregateRecipientByLocalization(iterable $recipients): array
    {
        $aggregation = [];
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof EmailHolderInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'recipients should be array of EmailHolderInterface values, "%s" type in array given.',
                        \is_object($recipient) ? \get_class($recipient) : gettype($recipient)
                    )
                );
            }

            $localization = $this->localizationProvider->getPreferredLocalization($recipient);

            if (!isset($aggregation[$localization->getId()])) {
                $aggregation[$localization->getId()] = [
                    'localization' => $localization,
                    'recipients' => [],
                ];
            }

            $aggregation[$localization->getId()]['recipients'][] = $recipient;
        }

        return  $aggregation;
    }
}
