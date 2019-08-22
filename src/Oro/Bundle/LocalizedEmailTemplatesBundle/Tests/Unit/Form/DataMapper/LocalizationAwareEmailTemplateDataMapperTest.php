<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Form\DataMapper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Entity\EmailTemplateLocalization;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\DataMapper\LocalizationAwareEmailTemplateDataMapper;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Stub\EmailTemplateStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;

class LocalizationAwareEmailTemplateDataMapperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationForm;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $anotherForm;

    /** @var iterable */
    private $forms;

    /** @var DataMapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerDataMapper;

    /** @var LocalizationAwareEmailTemplateDataMapper */
    private $dataMapper;

    protected function setUp(): void
    {
        $this->localizationForm = $this->createMock(FormInterface::class);
        $this->localizationForm->expects($this->any())->method('getName')->willReturn('localizations');

        $this->anotherForm = $this->createMock(FormInterface::class);
        $this->anotherForm->expects($this->any())->method('getName')->willReturn('another_form');
        $this->anotherForm->expects($this->never())->method('setData');
        $this->anotherForm->expects($this->never())->method('getData');

        $this->forms = new \ArrayIterator([
            $this->anotherForm,
            $this->localizationForm,
        ]);

        $this->innerDataMapper = $this->createMock(DataMapperInterface::class);

        $this->dataMapper = new LocalizationAwareEmailTemplateDataMapper($this->innerDataMapper);
    }

    public function testMapDataToFormsWithNullData(): void
    {
        $this->localizationForm->expects($this->never())->method('getName');
        $this->anotherForm->expects($this->never())->method('getName');
        $this->innerDataMapper->expects($this->never())->method('mapFormsToData');

        $this->dataMapper->mapDataToForms(null, $this->forms);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Bundle\EmailBundle\Entity\EmailTemplate", "array" given
     */
    public function testMapDataToFormsWithIncorrectData(): void
    {
        $this->dataMapper->mapDataToForms([], $this->forms);
    }

    public function testMapDataToFormsWithValidData(): void
    {
        /** @var Localization $existLocalization */
        $existLocalization = $this->getEntity(Localization::class, ['id' => 42]);
        $existTemplateLocalization = $this->getEmailTemplateLocalization($existLocalization, 'Exist localization');

        $emailTemplate = (new EmailTemplateStub())
            ->setSubject('Default subject')
            ->setContent('Default content')
            ->addLocalization($existTemplateLocalization);

        $this->localizationForm->expects($this->once())
            ->method('setData')
            ->with([
                'default' => (new EmailTemplateLocalization())
                    ->setSubject('Default subject')
                    ->setContent('Default content'),
                $existLocalization->getId() => $existTemplateLocalization,
            ]);

        $this->innerDataMapper->expects($this->once())
            ->method('mapDataToForms')
            ->with($emailTemplate, new \ArrayIterator([$this->anotherForm]));

        $this->dataMapper->mapDataToForms($emailTemplate, $this->forms);
    }

    public function testMapFormsToDataWithNullData(): void
    {
        $this->localizationForm->expects($this->never())->method('getName');
        $this->anotherForm->expects($this->never())->method('getName');
        $this->innerDataMapper->expects($this->never())->method('mapFormsToData');

        $data = null;
        $this->dataMapper->mapFormsToData($this->forms, $data);
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Oro\Bundle\EmailBundle\Entity\EmailTemplate", "array" given
     */
    public function testMapFormsToDataWithIncorrectData(): void
    {
        $data = [];
        $this->dataMapper->mapFormsToData($this->forms, $data);
    }

    public function testMapFormsToDataWithValidData(): void
    {
        /** @var Localization $newLocalization */
        $newLocalization = $this->getEntity(Localization::class, ['id' => 28]);
        $newTemplateLocalizationData = $this->getEmailTemplateLocalization($newLocalization, 'New localization');

        /** @var Localization $existLocalization */
        $existLocalization = $this->getEntity(Localization::class, ['id' => 42]);
        $existTemplateLocalizationData = $this->getEmailTemplateLocalization(
            $existLocalization,
            'Exist localization updated'
        );

        $this->localizationForm->expects($this->once())
            ->method('getData')
            ->willReturn([
                'default' => (new EmailTemplateLocalization())
                    ->setSubject('Default subject')
                    ->setContent('Default content'),
                $newLocalization->getId() => $newTemplateLocalizationData,
                $existLocalization->getId() => $existTemplateLocalizationData
            ]);

        $existTemplateLocalization = $this->getEmailTemplateLocalization(
            $existLocalization,
            'Exist localization',
            true,
            true
        );

        $emailTemplate = new EmailTemplateStub();
        $emailTemplate->addLocalization($existTemplateLocalization);

        $this->innerDataMapper->expects($this->once())
            ->method('mapFormsToData')
            ->with(new \ArrayIterator([$this->anotherForm]), $emailTemplate);

        $this->dataMapper->mapFormsToData($this->forms, $emailTemplate);

        $this->assertEquals('Default subject', $emailTemplate->getSubject());
        $this->assertEquals('Default content', $emailTemplate->getContent());

        $this->assertTrue($emailTemplate->getLocalizations()->contains($newTemplateLocalizationData));
        $this->assertTrue($emailTemplate->getLocalizations()->contains($existTemplateLocalization));

        $this->assertEquals($existTemplateLocalizationData, $existTemplateLocalization);
    }

    /**
     * @param Localization $localization
     * @param string $data
     * @param bool $subjectFallback
     * @param bool $contentFallback
     * @return EmailTemplateLocalization
     */
    private function getEmailTemplateLocalization(
        Localization $localization,
        string $data,
        bool $subjectFallback = false,
        bool $contentFallback = false
    ): EmailTemplateLocalization {
        return (new EmailTemplateLocalization())
            ->setLocalization($localization)
            ->setSubject($data . ' subject')
            ->setSubjectFallback($subjectFallback)
            ->setContent($data . ' content')
            ->setSubjectFallback($contentFallback);
    }
}
