<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Manager;

use Oro\Bundle\EmailBundle\Model\SenderAwareInterface;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\LocalizedTemplateAggregator;
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

    /** @var LocalizedTemplateAggregator */
    private $localizedTemplateAggregator;

    /**
     * @param EmailNotificationSender $emailNotificationSender
     * @param LoggerInterface $logger
     * @param LocalizedTemplateAggregator $localizedTemplateAggregator
     */
    public function __construct(
        EmailNotificationSender $emailNotificationSender,
        LoggerInterface $logger,
        LocalizedTemplateAggregator $localizedTemplateAggregator
    ) {
        $this->emailNotificationSender = $emailNotificationSender;
        $this->logger = $logger;
        $this->localizedTemplateAggregator = $localizedTemplateAggregator;

        // Not calling parent constructor!
        // The parent is only needed so that this manager has the valid class for the type hint
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $notifications, LoggerInterface $logger = null, array $params = []): void
    {
        foreach ($notifications as $notification) {
            try {
                $this->processSingle($notification, $params, $logger);
            } catch (NotificationSendException $exception) {
                $logger = $logger ?? $this->logger;
                $logger->error(
                    sprintf(
                        'An error occurred while sending "%s" notification with email template "%s" for "%s" entity',
                        \get_class($notification),
                        $notification->getTemplateCriteria()->getName(),
                        $notification->getTemplateCriteria()->getEntityName()
                    ),
                    ['exception' => $exception]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
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

            $templateCollection = $this->localizedTemplateAggregator->aggregate(
                $notification->getRecipients(),
                $notification->getTemplateCriteria(),
                ['entity' => $notification->getEntity()] + $params
            );

            foreach ($templateCollection as $localizedTemplateDTO) {
                $languageNotification = new TemplateEmailNotification(
                    $notification->getTemplateCriteria(),
                    $localizedTemplateDTO->getRecipients(),
                    $notification->getEntity(),
                    $sender
                );

                $emailTemplate = $localizedTemplateDTO->getEmailTemplate();

                if ($notification instanceof TemplateMassNotification) {
                    if ($notification->getSubject()) {
                        $emailTemplate->setSubject($notification->getSubject());
                    }

                    $this->emailNotificationSender->sendMass($languageNotification, $emailTemplate);
                } else {
                    $this->emailNotificationSender->send($languageNotification, $emailTemplate);
                }
            }
        } catch (\Exception $exception) {
            $logger = $logger ?? $this->logger;
            $logger->error('An error occurred while processing notification', ['exception' => $exception]);

            throw new NotificationSendException($notification);
        }
    }
}
