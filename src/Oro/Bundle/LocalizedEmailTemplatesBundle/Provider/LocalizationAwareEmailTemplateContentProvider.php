<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Provider;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\LocaleBundle\Entity\Localization;
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
            $emailTemplate =  $repository->findSingleByLocalized($criteria, $localization);
        } catch (NonUniqueResultException | NoResultException $exception) {
            $this->logger->error(
                'Could not find unique email template for the given criteria',
                ['exception' => $exception, 'criteria' => $criteria]
            );

            throw new EmailTemplateNotFoundException($criteria);
        }

        try {
            [$subject, $content] = $this->emailRenderer->compileMessage($emailTemplate, $templateParams);
        } catch (\Twig_Error $exception) {
            $this->logger->error(
                sprintf(
                    'Rendering of email template "%s" failed. %s',
                    $emailTemplate->getSubject(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );

            throw new EmailTemplateCompilationException($criteria);
        }

        $emailTemplateModel = new EmailTemplateModel();
        $emailTemplateModel
            ->setSubject($subject)
            ->setContent($content)
            ->setType($this->getTemplateContentType($emailTemplate));

        return $emailTemplateModel;
    }

    /**
     * @param EmailTemplateInterface $emailTemplate
     * @return string
     */
    private function getTemplateContentType(EmailTemplateInterface $emailTemplate): string
    {
        return $emailTemplate->getType() === EmailTemplate::TYPE_HTML
            ? EmailTemplateModel::CONTENT_TYPE_HTML
            : EmailTemplateModel::CONTENT_TYPE_TEXT;
    }
}
