<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Manager;

use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\LocalizedTemplateProvider;

/**
 * Responsible for sending email templates in preferred the recipients localizations when recipient entities given
 * or in a specific localization to a set of email addresses.
 */
class LocalizationAwareEmailTemplateManager extends TemplateEmailManager
{
    /** @var \Swift_Mailer */
    private $mailer;

    /** @var Processor */
    private $mailerProcessor;

    /** @var LocalizedTemplateProvider */
    private $localizedTemplateProvider;

    /**
     * @param \Swift_Mailer $mailer
     * @param Processor $mailerProcessor
     * @param LocalizedTemplateProvider $localizedTemplateProvider
     */
    public function __construct(
        \Swift_Mailer $mailer,
        Processor $mailerProcessor,
        LocalizedTemplateProvider $localizedTemplateProvider
    ) {
        $this->mailer = $mailer;
        $this->mailerProcessor = $mailerProcessor;
        $this->localizedTemplateProvider = $localizedTemplateProvider;

        // Not calling parent constructor!
        // The parent is only needed so that this manager has the valid class for the type hint
    }

    /**
     * @param From $sender
     * @param iterable|EmailHolderInterface[] $recipients
     * @param EmailTemplateCriteria $criteria
     * @param array $templateParams
     * @param null|array $failedRecipients
     * @return int
     */
    public function sendTemplateEmail(
        From $sender,
        iterable $recipients,
        EmailTemplateCriteria $criteria,
        array $templateParams = [],
        &$failedRecipients = null
    ): int {
        $sent = 0;
        $templateCollection = $this->localizedTemplateProvider->getAggregated($recipients, $criteria, $templateParams);

        foreach ($templateCollection as $localizedTemplateDTO) {
            $emailTemplate = $localizedTemplateDTO->getEmailTemplate();
            $message = \Swift_Message::newInstance()
                ->setSubject($emailTemplate->getSubject())
                ->setBody($emailTemplate->getContent())
                ->setContentType($emailTemplate->getType());

            $sender->populate($message);

            $this->mailerProcessor->processEmbeddedImages($message);

            foreach ($localizedTemplateDTO->getRecipients() as $recipient) {
                $messageToSend = clone $message;
                $messageToSend->setTo($recipient->getEmail());

                $sent += $this->mailer->send($messageToSend, $failedRecipients);
            }
        }

        return $sent;
    }
}
