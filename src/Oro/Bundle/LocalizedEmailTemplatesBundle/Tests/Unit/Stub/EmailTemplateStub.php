<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;

/**
 * Stub with extended entity relation to EmailTemplateLocalization
 */
class EmailTemplateStub extends EmailTemplate
{
    /** @var Collection|EmailTemplateLocalization[] */
    private $localizations;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = '', $content = '', $type = 'html', $isSystem = false)
    {
        parent::__construct($name, $content, $type, $isSystem);
        $this->localizations = new ArrayCollection();
    }

    /**
     * @return Collection|EmailTemplateLocalization[]
     */
    public function getLocalizations(): Collection
    {
        return $this->localizations;
    }

    /**
     * @param Collection|EmailTemplateLocalization[] $localizations
     * @return EmailTemplateStub
     */
    public function setLocalizations(array $localizations): self
    {
        $this->localizations = $localizations;
        return $this;
    }

    /**
     * @param EmailTemplateLocalization $localization
     * @return EmailTemplateStub
     */
    public function addLocalization(EmailTemplateLocalization $localization): self
    {
        $this->localizations->add($localization);
        return $this;
    }

    /**
     * @param EmailTemplateLocalization $localization
     * @return EmailTemplateStub
     */
    public function removeLocalization(EmailTemplateLocalization $localization): self
    {
        $this->localizations->removeElement($localization);
        return $this;
    }
}
