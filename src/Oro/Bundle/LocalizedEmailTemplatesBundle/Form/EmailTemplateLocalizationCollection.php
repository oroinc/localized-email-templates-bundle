<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for collection of EmailTemplateLocalization
 */
class EmailTemplateLocalizationCollection extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('default', EmailTemplateLocalizationType::class, [
            'default_localization' => $options['default_localization'],
            'wysiwyg_enabled' => $options['wysiwyg_enabled'],
            'wysiwyg_options' => $options['wysiwyg_options'],
            'block_name' => 'template',
        ]);

        foreach ($options['localizations'] as $localization) {
            $builder->add($localization->getId(), EmailTemplateLocalizationType::class, [
                'localization' => $localization,
                'default_localization' => $options['default_localization'],
                'wysiwyg_enabled' => $options['wysiwyg_enabled'],
                'wysiwyg_options' => $options['wysiwyg_options'],
                'block_name' => 'template',
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'localizations' => [],
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        $resolver
            ->setRequired('default_localization')
            ->addAllowedTypes('default_localization', ['string']);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_email_emailtemplate_localizations';
    }
}
