<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Model\DTO;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;

/**
 * DTO model for relate template model to recipients
 */
class LocalizedTemplateDTO
{
    /** @var EmailTemplate */
    private $emailTemplate;

    /** @var EmailHolderInterface[] */
    private $recipients;

    /**
     * @param EmailTemplate $emailTemplate
     */
    public function __construct(EmailTemplate $emailTemplate)
    {
        $this->emailTemplate = $emailTemplate;
    }

    /**
     * @return EmailTemplate
     */
    public function getEmailTemplate(): EmailTemplate
    {
        return $this->emailTemplate;
    }

    /**
     * @return EmailHolderInterface[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @param EmailHolderInterface $recipient
     * @return LocalizedTemplateDTO
     */
    public function addRecipient(EmailHolderInterface $recipient): self
    {
        $this->recipients[] = $recipient;
        return $this;
    }
}