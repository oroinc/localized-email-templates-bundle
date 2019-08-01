<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Manager;

use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Manager\TemplateEmailManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\LocalizationAwareEmailTemplateContentProvider;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\PreferredLocalizationProvider\PreferredLocalizationProviderInterface;

/**
 * Responsible for sending email templates in preferred the recipients localizations when recipient entities given
 * or in a specific localization to a set of email addresses.
 */
class LocalizationAwareEmailTemplateManager extends TemplateEmailManager
{
    /** @var \Swift_Mailer */
    private $mailer;

    /** @var PreferredLocalizationProviderInterface */
    private $localizationProvider;

    /** @var Processor */
    private $mailerProcessor;

    /** @var LocalizationAwareEmailTemplateContentProvider */
    private $emailTemplateContentProvider;

    /**
     * @param \Swift_Mailer $mailer
     * @param PreferredLocalizationProviderInterface $localizationProvider
     * @param Processor $mailerProcessor
     * @param LocalizationAwareEmailTemplateContentProvider $emailTemplateContentProvider
     */
    public function __construct(
        \Swift_Mailer $mailer,
        PreferredLocalizationProviderInterface $localizationProvider,
        Processor $mailerProcessor,
        LocalizationAwareEmailTemplateContentProvider $emailTemplateContentProvider
    ) {
        $this->mailer = $mailer;
        $this->localizationProvider = $localizationProvider;
        $this->mailerProcessor = $mailerProcessor;
        $this->emailTemplateContentProvider = $emailTemplateContentProvider;

        // Not calling parent constructor!
        // The parent is only needed so that this manager has the valid class for the type hint
    }

    /**
     * @param From $sender
     * @param iterable $recipients
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
        $aggregation = $this->aggregateRecipientEmailsByLocalization($recipients);

        $sent = 0;
        foreach ($aggregation as ['localization' => $localization, 'emails' => $emails]) {
            $emailTemplateModel = $this->emailTemplateContentProvider->getTemplateContent(
                $criteria,
                $localization,
                $templateParams
            );

            $message = \Swift_Message::newInstance()
                ->setSubject($emailTemplateModel->getSubject())
                ->setBody($emailTemplateModel->getContent())
                ->setContentType($emailTemplateModel->getType());

            $sender->populate($message);

            $this->mailerProcessor->processEmbeddedImages($message);

            foreach ($emails as $email) {
                $messageToSend = clone $message;
                $messageToSend->setTo($email);

                $sent += $this->mailer->send($messageToSend, $failedRecipients);
            }
        }

        return $sent;
    }

    /**
     * @param iterable $recipients
     * @return array
     */
    private function aggregateRecipientEmailsByLocalization(iterable $recipients): array
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
                    'emails' => [],
                ];
            }

            $aggregation[$localization->getId()]['recipients'][] = $recipient->getEmail();
        }

        return  $aggregation;
    }
}
