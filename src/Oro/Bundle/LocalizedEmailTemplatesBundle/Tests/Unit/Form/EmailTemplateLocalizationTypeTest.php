<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Form;

use Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub\OroRichTextTypeStub;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\EmailTemplateLocalizationType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

class EmailTemplateLocalizationTypeTest extends FormIntegrationTestCase
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

    public function testBuildFormWithLocalization(): void
    {
        $form = $this->factory->create(EmailTemplateLocalizationType::class, null, [
            'localization' => $this->getEntity(Localization::class, ['id' => 42]),
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => ['any-key' => 'any-val'],
        ]);

        $this->assertTrue($form->has('subject'));
        $this->assertTrue($form->has('content'));
        $this->assertSame(
            ['any-key' => 'any-val'],
            $form->get('content')->getConfig()->getOption('wysiwyg_options')
        );
        $this->assertArraySubset(
            ['data-wysiwyg-enabled' => true],
            $form->get('content')->getConfig()->getOption('attr')
        );

        $this->assertTrue($form->has('subjectFallback'));
        $this->assertTrue($form->has('contentFallback'));
    }

    public function testBuildFormWithoutLocalization(): void
    {
        $form = $this->factory->create(EmailTemplateLocalizationType::class, null, [
            'localization' => null,
            'wysiwyg_enabled' => true,
            'wysiwyg_options' => ['any-key' => 'any-val'],
        ]);

        $this->assertTrue($form->has('subject'));
        $this->assertTrue($form->has('content'));
        $this->assertSame(
            ['any-key' => 'any-val'],
            $form->get('content')->getConfig()->getOption('wysiwyg_options')
        );
        $this->assertArraySubset(
            ['data-wysiwyg-enabled' => true],
            $form->get('content')->getConfig()->getOption('attr')
        );

        $this->assertFalse($form->has('subjectFallback'));
        $this->assertFalse($form->has('contentFallback'));
    }

    public function testSubmit(): void
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42]);

        $form = $this->factory->create(EmailTemplateLocalizationType::class, null, [
            'localization' => $localization,
        ]);

        $data = (new EmailTemplateLocalization())
            ->setLocalization($localization)
            ->setSubject('Old subject')
            ->setSubjectFallback(false)
            ->setContent('Old content')
            ->setContentFallback(false);

        $form->setData($data);

        $submittedData = [
            'subject' => 'Test subject',
            'subjectFallback' => '1',
            'content' => 'Test content',
            'contentFallback' => '1',
        ];

        $form->submit($submittedData);

        $this->assertEquals(
            (new EmailTemplateLocalization())
                ->setLocalization($localization)
                ->setSubject('Test subject')
                ->setSubjectFallback(true)
                ->setContent('Test content')
                ->setContentFallback(true),
            $form->getData()
        );
    }
}
