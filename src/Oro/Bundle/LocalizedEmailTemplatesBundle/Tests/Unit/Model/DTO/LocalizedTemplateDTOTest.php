<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Model\EmailAddressString;

class LocalizedTemplateDTOTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailTemplate */
    private $emailTemplate;

    /** @var LocalizedTemplateDTO */
    private $dto;

    protected function setUp()
    {
        $this->emailTemplate = new EmailTemplate();

        $this->dto = new LocalizedTemplateDTO($this->emailTemplate);
    }

    public function testGetEmailTemplate(): void
    {
        $this->assertSame($this->emailTemplate, $this->dto->getEmailTemplate());
    }

    public function testRecipientsAndEmails(): void
    {
        $rcpt1 = new EmailAddressString('test1@example.com');
        $rcpt2 = new EmailAddressString('test2@example.com');
        $rcpt3 = new EmailAddressString('test3@example.com');

        $this->dto->addRecipient($rcpt1);
        $this->dto->addRecipient($rcpt2);
        $this->dto->addRecipient($rcpt3);

        $this->assertSame([$rcpt1, $rcpt2, $rcpt3], $this->dto->getRecipients());
        $this->assertSame([$rcpt1->getEmail(), $rcpt2->getEmail(), $rcpt3->getEmail()], $this->dto->getEmails());
    }
}
