<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Form\DataMapper;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Adding default localization for fields from the EmailTemplate entity
 */
class LocalizationAwareEmailTemplateDataMapper implements DataMapperInterface
{
    /** @var DataMapperInterface|null */
    private $inner;

    /**
     * @param DataMapperInterface|null $inner Original data mapper
     */
    public function __construct(DataMapperInterface $inner = null)
    {
        $this->inner = $inner;
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($viewData, $forms): void
    {
        if ($viewData === null) {
            return;
        }

        $this->assertViewDataType($viewData);

        $innerMapperForms = [];
        foreach ($forms as $form) {
            if ($form->getName() === 'localizations') {
                $entity = new EmailTemplateLocalization();
                $entity->setSubject($viewData->getSubject())
                    ->setContent($viewData->getContent());

                $data = ['default' => $entity];

                /** @var EmailTemplateLocalization $templateLocalization */
                foreach ($viewData->getLocalizations() as $templateLocalization) {
                    $data[$templateLocalization->getLocalization()->getId()] = $templateLocalization;
                }

                $form->setData($data);
            } else {
                $innerMapperForms[] = $form;
            }
        }

        // Fallback to inner data mapper with not mapped fields
        if ($this->inner) {
            $this->inner->mapDataToForms($viewData, new \ArrayIterator($innerMapperForms));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$viewData): void
    {
        $this->assertViewDataType($viewData);

        $innerMapperForms = [];
        foreach ($forms as $form) {
            if ($form->getName() === 'localizations') {
                // Process default template localization
                /** @var EmailTemplateLocalization[] $data */
                $data = $form->getData();
                $viewData
                    ->setSubject($data['default'] ? $data['default']->getSubject() : null)
                    ->setContent($data['default'] ? $data['default']->getContent() : null);
                unset($data['default']);

                // Process existing localizations
                /** @var EmailTemplateLocalization $templateLocalization */
                foreach ($viewData->getLocalizations() as $templateLocalization) {
                    $localizationId = $templateLocalization->getLocalization()->getId();
                    if (!isset($data[$localizationId])) {
                        $templateLocalization
                            ->setSubject(null)
                            ->setSubjectFallback(true)
                            ->setContent(null)
                            ->setContentFallback(true);
                    } else {
                        $templateLocalization
                            ->setSubject($data[$localizationId]->getSubject())
                            ->setSubjectFallback($data[$localizationId]->isSubjectFallback())
                            ->setContent($data[$localizationId]->getContent())
                            ->setContentFallback($data[$localizationId]->isContentFallback());

                        unset($data[$localizationId]);
                    }
                }

                // Process new localizations
                foreach ($data as $newTemplateLocalization) {
                    $viewData->addLocalization($newTemplateLocalization);
                }
            } else {
                $innerMapperForms[] = $form;
            }
        }

        // Fallback to inner data mapper with not mapped fields
        if ($this->inner) {
            $this->inner->mapFormsToData(new \ArrayIterator($innerMapperForms), $viewData);
        }
    }

    /**
     * @param $viewData
     * @throws UnexpectedTypeException
     */
    private function assertViewDataType($viewData): void
    {
        if (!$viewData instanceof EmailTemplate) {
            throw new UnexpectedTypeException($viewData, EmailTemplate::class);
        }
    }
}
