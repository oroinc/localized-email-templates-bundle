<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Model\ExtendEmailTemplateLocalization;

/**
 * Represents localizations for email templates.
 * This entity exists only for the case when an application is updated
 * from an older version with OroLocalizedEmailTemplatesBundle.
 *
 * @ORM\Entity()
 * @ORM\Table(name="oro_email_template_localized")
 * @Config()
 */
class EmailTemplateLocalization extends ExtendEmailTemplateLocalization
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Localization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization")
     * @ORM\JoinColumn(name="localization_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $localization;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    private $subject;

    /**
     * @var bool
     *
     * @ORM\Column(name="subject_fallback", type="boolean", options={"default"=true})
     */
    private $subjectFallback = true;

    /**
     * @var string|null
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var bool
     *
     * @ORM\Column(name="content_fallback", type="boolean", options={"default"=true})
     */
    private $contentFallback = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocalization(): ?Localization
    {
        return $this->localization;
    }

    public function setLocalization(?Localization $localization): EmailTemplateLocalization
    {
        $this->localization = $localization;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): EmailTemplateLocalization
    {
        $this->subject = $subject;
        return $this;
    }

    public function isSubjectFallback(): bool
    {
        return $this->subjectFallback;
    }

    public function setSubjectFallback(bool $subjectFallback): EmailTemplateLocalization
    {
        $this->subjectFallback = $subjectFallback;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): EmailTemplateLocalization
    {
        $this->content = $content;
        return $this;
    }

    public function isContentFallback(): bool
    {
        return $this->contentFallback;
    }

    public function setContentFallback(bool $contentFallback): EmailTemplateLocalization
    {
        $this->contentFallback = $contentFallback;
        return $this;
    }
}
