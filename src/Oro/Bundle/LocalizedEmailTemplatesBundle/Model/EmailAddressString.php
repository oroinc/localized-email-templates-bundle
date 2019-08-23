<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Model;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

/**
 * Representation as an object of an email address
 */
class EmailAddressString implements EmailHolderInterface
{
    /** @var string */
    private $email;

    /**
     * @param string $email
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
