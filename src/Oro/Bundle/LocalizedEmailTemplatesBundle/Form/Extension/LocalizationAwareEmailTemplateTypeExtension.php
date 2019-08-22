<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Form\Extension;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateType;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\DataMapper\LocalizationAwareEmailTemplateDataMapper;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\EmailTemplateLocalizationCollectionType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Decorating email template form for create/update localized templates
 */
class LocalizationAwareEmailTemplateTypeExtension extends AbstractTypeExtension
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
        $fieldOptions = $builder->get('translations')->getOption('fields');

        // Removing original translation form types
        $builder->remove('translations');
        $builder->remove('translation');

        $builder->add(
            'localizations',
            EmailTemplateLocalizationCollectionType::class,
            [
                'localizations' => $this->localizationManager->getLocalizations(),
                'wysiwyg_enabled' => $fieldOptions['content']['attr']['data-wysiwyg-enabled'] ?? false,
                'wysiwyg_options' => $fieldOptions['content']['wysiwyg_options'] ?? [],
            ]
        );

        $builder->setDataMapper(new LocalizationAwareEmailTemplateDataMapper($builder->getDataMapper()));
    }
}
