<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Form;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateRichTextType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
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

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Localization|null $parentLocalization */
        $parentLocalization = $options['localization'] ? $options['localization']->getParentLocalization() : null;
        if ($parentLocalization) {
            $fallbackLabel = $this->translator->trans('oro.email.emailtemplate.use_parent_localization', [
                '%name%' => $parentLocalization->getName()
            ]);
        }

        $builder->add('subject', TextType::class, [
            'attr' => [
                'maxlength' => 255,
            ],
        ]);

        if ($parentLocalization) {
            $builder->add('subjectFallback', CheckboxType::class, [
                'label' => $fallbackLabel,
                'required' => false,
                'block_name' => 'fallback_checkbox',
            ]);
        }

        $builder->add('content', EmailTemplateRichTextType::class, [
            'attr' => [
                'class' => 'template-editor',
                "data-wysiwyg-enabled" => $options['wysiwyg_enabled'],
            ],
            'wysiwyg_options' => $options['wysiwyg_options'],
        ]);

        if ($parentLocalization) {
            $builder->add('contentFallback', CheckboxType::class, [
                'label' => $fallbackLabel,
                'required' => false,
                'block_name' => 'fallback_checkbox'
            ]);
        }

        $transformer = new CallbackTransformer(
            function ($data) use ($options) {
                // Create localized template for each localization
                return $data ?? (new EmailTemplateLocalization())->setLocalization($options['localization']);
            },
            function ($data) {
                // Clear empty input
                if ($data && $data instanceof EmailTemplateLocalization) {
                    if (!trim($data->getSubject())) {
                        $data->setSubject(null);
                    }

                    if (preg_match(self::EMPTY_REGEX, trim($data->getContent()))) {
                        $data->setContent(null);
                    }
                }

                return $data;
            }
        );

        $builder->addViewTransformer($transformer);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['localization'] = $options['localization'];
        $view->vars['localization_title'] = $options['localization']
            ? $options['localization']->getName()
            : $options['default_localization'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplateLocalization::class,
            'default_localization' => null,
            'localization' => null,
            'wysiwyg_enabled' => false,
            'wysiwyg_options' => [],
        ]);

        $resolver->setAllowedTypes('default_localization', ['string']);
        $resolver->setAllowedTypes('localization', ['null', Localization::class]);
        $resolver->setAllowedTypes('wysiwyg_enabled', ['bool']);
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
        return 'oro_email_emailtemplate_localization';
    }
}
