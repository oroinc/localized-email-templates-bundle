<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Model\EmailAddressString;

class EmailAddressStringTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEmail(): void
    {
        $email = 'test@example.com';

        $obj = new EmailAddressString($email);

        $this->assertInstanceOf(EmailHolderInterface::class, $obj);
        $this->assertSame($email, $obj->getEmail());
    }
}
