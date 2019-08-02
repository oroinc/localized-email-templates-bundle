<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Provides compiled email template information ready to be sent via email.
 */
class LocalizationAwareEmailTemplateContentProvider
{
    /** @var RegistryInterface */
    private $doctrine;

    /** @var EmailRenderer */
    private $emailRenderer;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param RegistryInterface $doctrine
     * @param EmailRenderer $emailRenderer
     * @param LoggerInterface $logger
     */
    public function __construct(
        RegistryInterface $doctrine,
        EmailRenderer $emailRenderer,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->emailRenderer = $emailRenderer;
        $this->logger = $logger;
    }

    /**
     * Get localization aware email template
     *
     * @param EmailTemplateCriteria $criteria
     * @param Localization $localization
     * @param array $templateParams
     * @return EmailTemplateModel
     */
    public function getTemplateContent(
        EmailTemplateCriteria $criteria,
        Localization $localization,
        array $templateParams
    ): EmailTemplateModel {
        $repository = $this->doctrine->getRepository(EmailTemplate::class);

        try {
            /** @var EmailTemplate $emailTemplate */
            $emailTemplateEntity =  $repository->findSingleByEmailTemplateCriteria($criteria);
        } catch (NonUniqueResultException | NoResultException $exception) {
            $this->logger->error(
                'Could not find unique email template for the given criteria',
                ['exception' => $exception, 'criteria' => $criteria]
            );

            throw new EmailTemplateNotFoundException($criteria);
        }

        $emailTemplateModel = new EmailTemplateModel();
        $this->populateModel($emailTemplateModel, $emailTemplateEntity, $localization);

        try {
            [$subject, $content] = $this->emailRenderer->compileMessage($emailTemplateModel, $templateParams);
            $emailTemplateModel
                ->setSubject($subject)
                ->setContent($content);
        } catch (\Twig_Error $exception) {
            $this->logger->error(
                sprintf(
                    'Rendering of email template "%s" failed. %s',
                    $emailTemplateModel->getSubject(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );

            throw new EmailTemplateCompilationException($criteria);
        }


        return $emailTemplateModel;
    }

    /**
     * @param EmailTemplateModel $model
     * @param EmailTemplate $entity
     * @param Localization $localization
     */
    private function populateModel(
        EmailTemplateModel $model,
        EmailTemplate $entity,
        Localization $localization
    ): void {
        $templateIndex = [];

        /** @var EmailTemplateLocalization $templateLocalization */
        foreach ($entity->getLocalizations() as $templateLocalization) {
            $templateIndex[$templateLocalization->getLocalization()->getId()] = $templateLocalization;
        }

        $this->populateAttribute($templateIndex, $localization, $model, $entity, 'subject');
        $this->populateAttribute($templateIndex, $localization, $model, $entity, 'content');

        $model->setType(
            $entity->getType() === EmailTemplate::TYPE_HTML
                ? EmailTemplateModel::CONTENT_TYPE_HTML
                : EmailTemplateModel::CONTENT_TYPE_TEXT
        );
    }

    /**
     * Localize model attribute
     *
     * @param array $templateIndex
     * @param Localization $localization
     * @param EmailTemplateModel $model
     * @param EmailTemplate $entity
     * @param string $attribute
     */
    private function populateAttribute(
        array $templateIndex,
        Localization $localization,
        EmailTemplateModel $model,
        EmailTemplate $entity,
        string $attribute
    ): void {
        $attribute = ucfirst($attribute);
        $getter = 'get' . $attribute;
        $setter = 'set' . $attribute;
        $fallbackAllowed = 'is' . $attribute . 'Fallback';

        $currentTemplate = $this->findTemplate($templateIndex, $localization);
        $value = call_user_func([$model, $getter]);
        while (!$value) {
            // Template not found for localization tree
            if (!$currentTemplate) {
                call_user_func([$model, $setter], call_user_func([$entity, $getter]));
                return;
            }

            // Localized template found end not empty
            $currentValue = call_user_func([$currentTemplate, $getter]);
            if ($currentValue) {
                call_user_func([$model, $setter], $currentValue);
                return;
            }

            // For current attribute not allowed fallback to parent(-s) localizations
            if (!call_user_func([$currentTemplate, $fallbackAllowed])) {
                call_user_func([$model, $setter], call_user_func([$entity, $getter]));
                return;
            }

            // Find next available localized template by localization tree
            $currentTemplate = $this->findTemplate(
                $templateIndex,
                $currentTemplate->getLocalization()->getParentLocalization()
            );
        }
    }

    /**
     * @param array $templateIndex
     * @param Localization $localization
     * @return EmailTemplateLocalization|null
     */
    private function findTemplate(array $templateIndex, Localization $localization): ?EmailTemplateLocalization
    {
        while ($localization) {
            if (isset($templateIndex[$localization->getId()])) {
                return $templateIndex[$localization->getId()];
            }

            $localization = $localization->getParentLocalization();
        }

        return null;
    }
}
