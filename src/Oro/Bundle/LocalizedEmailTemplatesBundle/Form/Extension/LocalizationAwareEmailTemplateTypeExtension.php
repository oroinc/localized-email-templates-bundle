<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Form\Extension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\DataMapper\LocalizationAwareEmailTemplateDataMapper;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\EmailTemplateLocalizationCollection;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Decorating email template form for create/update localized templates
 */
class LocalizationAwareEmailTemplateTypeExtension extends AbstractTypeExtension
{
    /** @var ConfigManager */
    private $userConfig;

    /** @var LocaleSettings */
    private $localeSettings;

    /** @var LocalizationManager */
    private $localizationManager;

    /**
     * @param ConfigManager $userConfig
     * @param LocaleSettings $localeSettings
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        ConfigManager $userConfig,
        LocaleSettings $localeSettings,
        LocalizationManager $localizationManager
    ) {
        $this->userConfig = $userConfig;
        $this->localeSettings = $localeSettings;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return EmailTemplateType::class;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Removing original translation form types
        $builder->remove('translations');
        $builder->remove('translation');

        // Adding form type for localized template
        $isWysiwygEnabled = $this->userConfig->get('oro_form.wysiwyg_enabled');

        $builder->add(
            'localizations',
            EmailTemplateLocalizationCollection::class,
            [
                'localizations' => $this->getLocalizations(),
                "wysiwyg_enabled" => $isWysiwygEnabled,
                'wysiwyg_options' => $isWysiwygEnabled ? $this->getWysiwygOptions() : [],
            ]
        );

        $builder->setDataMapper(new LocalizationAwareEmailTemplateDataMapper($builder->getDataMapper()));
    }

    /**
     * @return Localization[]
     */
    private function getLocalizations(): array
    {
        $result = [];
        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $result[$localization->getId()] = $localization;
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getWysiwygOptions(): array
    {
        if ($this->userConfig->get('oro_email.sanitize_html')) {
            return [];
        }

        return [
            'height' => '250px',
            'valid_elements' => null, // all elements are valid
            'plugins' => array_merge(OroRichTextType::$defaultPlugins, ['fullpage']),
            'relative_urls' => true,
        ];
    }
}
