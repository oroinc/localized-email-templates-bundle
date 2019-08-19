<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Form;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateRichTextType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Form type for EmailTemplateLocalization entity
 */
class EmailTemplateLocalizationType extends AbstractType
{
    /** @var string Check content on wysiwyg empty formatting */
    private const EMPTY_REGEX = '#^(\r*\n*)*'
    . '\<!DOCTYPE html\>(\r*\n*)*'
    . '\<html\>(\r*\n*)*'
    . '\<head\>(\r*\n*)*\</head\>(\r*\n*)*'
    . '\<body\>(\r*\n*)*\</body\>(\r*\n*)*'
    . '\</html\>(\r*\n*)*$#';

    /** @var TranslatorInterface */
    private $translator;

    /** @var LocalizationManager */
    private $localizationManager;

    /**
     * @param TranslatorInterface $translator
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        TranslatorInterface $translator,
        LocalizationManager $localizationManager
    ) {
        $this->translator = $translator;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'attr' => [
                    'maxlength' => 255,
                ],
            ])
            ->add('content', EmailTemplateRichTextType::class, [
                'attr' => [
                    'class' => 'template-editor',
                    'data-wysiwyg-enabled' => $options['wysiwyg_enabled'],
                ],
                'wysiwyg_options' => $options['wysiwyg_options'],
            ]);

        if ($options['localization']) {
            $fallbackLabel = $options['localization']->getParentLocalization()
                ? $this->translator->trans(
                    'oro.localizedemailtemplates.emailtemplatelocalization.use_parent_localization',
                    [
                        '%name%' => $options['localization']->getParentLocalization()->getTitle(
                            $this->localizationManager->getDefaultLocalization()
                        ),
                    ]
                )
                : $this->translator->trans(
                    'oro.localizedemailtemplates.emailtemplatelocalization.use_default_localization'
                );

            $builder
                ->add('subjectFallback', CheckboxType::class, [
                    'label' => $fallbackLabel,
                    'required' => false,
                    'block_name' => 'fallback_checkbox',
                ])
                ->add('contentFallback', CheckboxType::class, [
                    'label' => $fallbackLabel,
                    'required' => false,
                    'block_name' => 'fallback_checkbox',
                ]);
        }

        $builder->addViewTransformer(
            new CallbackTransformer(
                function ($data) use ($options) {
                    // Create localized template for localization
                    if (!$data) {
                        $data = new EmailTemplateLocalization();
                        $data->setLocalization($options['localization']);
                    }

                    return $data;
                },
                function ($data) {
                    // Clear empty input
                    if ($data instanceof EmailTemplateLocalization) {
                        if (!trim($data->getSubject())) {
                            $data->setSubject(null);
                        }

                        if (preg_match(self::EMPTY_REGEX, trim($data->getContent()))) {
                            $data->setContent(null);
                        }
                    }

                    return $data;
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['localization_title'] = $options['localization']
            ? $options['localization']->getTitle(
                $this->localizationManager->getDefaultLocalization()
            )
            : null;

        if (isset($view->children['subject'], $view->children['subjectFallback'])) {
            $this->processFallbackView($view->children['subject'], $view->children['subjectFallback']);
        }

        if (isset($view->children['content'], $view->children['contentFallback'])) {
            $this->processFallbackView($view->children['content'], $view->children['contentFallback']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplateLocalization::class,
            'localization' => null,
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        $resolver->setAllowedTypes('localization', ['null', Localization::class]);
        $resolver->setAllowedTypes('wysiwyg_enabled', ['bool']);
        $resolver->setAllowedTypes('wysiwyg_options', ['array']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_email_emailtemplate_localization';
    }

    /**
     * @param FormView $field
     * @param FormView $fallback
     */
    private function processFallbackView(FormView $field, FormView $fallback): void
    {
        $field->vars['disabled'] = (bool)$fallback->vars['data'];
        $fallback->vars['target_id'] = $field->vars['id'];
    }
}
