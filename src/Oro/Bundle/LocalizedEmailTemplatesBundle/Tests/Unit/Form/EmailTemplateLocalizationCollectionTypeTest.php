<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\OroRichTextTypeStub;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\EmailTemplateLocalizationCollectionType;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\EmailTemplateLocalizationType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

class EmailTemplateLocalizationCollectionTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        /** @var TranslatorInterface $translator */
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        return [
            new PreloadedExtension(
                [
                    EmailTemplateLocalizationType::class => new EmailTemplateLocalizationType(
                        $this->translator,
                        $this->localizationManager
                    ),
                    OroRichTextType::class => new OroRichTextTypeStub(),
                ],
                []
            ),
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(EmailTemplateLocalizationCollectionType::class, null, [
            'localizations' => [
                $this->getEntity(Localization::class, ['id' => 42]),
                $this->getEntity(Localization::class, ['id' => 54]),
                $this->getEntity(Localization::class, ['id' => 88]),
            ],
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => ['any-key' => 'any-val'],
        ]);

        $this->assertTrue($form->has('default'));
        $this->assertTrue($form->has(42));
        $this->assertTrue($form->has(54));
        $this->assertTrue($form->has(88));
    }

    public function testSubmit(): void
    {
        $localizationExist = $this->getEntity(Localization::class, ['id' => 42]);
        $localizationNew = $this->getEntity(Localization::class, ['id' => 54]);

        $form = $this->factory->create(EmailTemplateLocalizationCollectionType::class, null, [
            'localizations' => [
                $localizationExist,
                $localizationNew,
            ],
        ]);

        $data = new ArrayCollection([
            'default' => (new EmailTemplateLocalization())
                ->setSubject('Default subject')
                ->setSubjectFallback(false)
                ->setContent('Default content')
                ->setContentFallback(false),

            42 => (new EmailTemplateLocalization())
                ->setLocalization($localizationExist)
                ->setSubject('Old subject')
                ->setSubjectFallback(false)
                ->setContent('Old content')
                ->setContentFallback(false),
        ]);
        $form->setData($data);

        $submittedData = [
            'default' => [
                'subject' => 'New default subject',
                'subjectFallback' => '1',
                'content' => 'New default content',
                'contentFallback' => '1',
            ],
            42 => [
                'subject' => 'Test subject 42',
                'contentFallback' => '1',
            ],
            54 => [
                'subjectFallback' => '1',
                'content' => 'Test content 54',
            ],
        ];

        $form->submit($submittedData);

        $this->assertEquals(new ArrayCollection([
            'default' => (new EmailTemplateLocalization())
                ->setSubject('New default subject')
                ->setSubjectFallback(false)
                ->setContent('New default content')
                ->setContentFallback(false),

            42 => (new EmailTemplateLocalization())
                ->setLocalization($localizationExist)
                ->setSubject('Test subject 42')
                ->setSubjectFallback(false)
                ->setContent(null)
                ->setContentFallback(true),

            54 => (new EmailTemplateLocalization())
                ->setLocalization($localizationNew)
                ->setSubject(null)
                ->setSubjectFallback(true)
                ->setContent('Test content 54')
                ->setContentFallback(false),
        ]), $form->getData());
    }
}
