<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\LocalizationTemplateGeneratorFactory;
use Oro\Bundle\NotificationBundle\Exception\NotificationSendException;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\TemplateMassNotification;
use Psr\Log\LoggerInterface;

/**
 * Manager that processes notifications and make them to be processed using message queue.
 * Email templates render in preferred recipients localizations.
 */
class LocalizationAwareEmailNotificationManager extends EmailNotificationManager
{
    /** @var EmailNotificationSender */
    private $emailNotificationSender;

    /** @var LoggerInterface */
    private $logger;

    /** @var LocalizationTemplateGeneratorFactory */
    private $templateGeneratorFactory;

    /**
     * @param EmailNotificationSender $emailNotificationSender
     * @param LoggerInterface $logger
     * @param LocalizationTemplateGeneratorFactory $templateGeneratorFactory
     */
    public function __construct(
        EmailNotificationSender $emailNotificationSender,
        LoggerInterface $logger,
        LocalizationTemplateGeneratorFactory $templateGeneratorFactory
    ) {
        $this->emailNotificationSender = $emailNotificationSender;
        $this->logger = $logger;
        $this->templateGeneratorFactory = $templateGeneratorFactory;

        // Not calling parent constructor!
        // The parent is only needed so that this manager has the valid class for the type hint
    }

    /**
     * @param TemplateEmailNotificationInterface $notification
     * @param array $params
     * @param LoggerInterface|null $logger
     * @throws NotificationSendException
     */
    public function processSingle(
        TemplateEmailNotificationInterface $notification,
        array $params = [],
        LoggerInterface $logger = null
    ): void {
        try {
            $sender = $notification instanceof SenderAwareInterface
                ? $notification->getSender()
                : null;

            $generator = $this->templateGeneratorFactory->createTemplateGenerator(
                $notification->getRecipients(),
                $notification->getTemplateCriteria(),
                ['entity' => $notification->getEntity()] + $params
            );
            /**
             * @var EmailTemplate $emailTemplateModel
             * @var EmailHolderInterface[] $groupedRecipients
             */
            foreach ($generator as $emailTemplateModel => $groupedRecipients) {
                $languageNotification = new TemplateEmailNotification(
                    $notification->getTemplateCriteria(),
                    $groupedRecipients,
                    $notification->getEntity(),
                    $sender
                );

                if ($notification instanceof TemplateMassNotification) {
                    if ($notification->getSubject()) {
                        $emailTemplateModel->setSubject($notification->getSubject());
                    }

                    $this->emailNotificationSender->sendMass($languageNotification, $emailTemplateModel);
                } else {
                    $this->emailNotificationSender->send($languageNotification, $emailTemplateModel);
                }
            }
        } catch (\Exception $exception) {
            $logger = $logger ?? $this->logger;
            $logger->error('An error occurred while processing notification', ['exception' => $exception]);

            throw new NotificationSendException($notification);
        }
    }
}
