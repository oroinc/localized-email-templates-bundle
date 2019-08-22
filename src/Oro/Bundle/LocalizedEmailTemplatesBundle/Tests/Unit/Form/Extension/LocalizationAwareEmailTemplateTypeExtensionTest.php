<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\DataMapper\LocalizationAwareEmailTemplateDataMapper;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\EmailTemplateLocalizationCollectionType;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Form\Extension\LocalizationAwareEmailTemplateTypeExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;

class LocalizationAwareEmailTemplateTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var LocalizationAwareEmailTemplateTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->extension = new LocalizationAwareEmailTemplateTypeExtension($this->localizationManager);
    }

    public function testGetExtendedType(): void
    {
        $this->assertSame(EmailTemplateType::class, $this->extension->getExtendedType());
    }

    public function testBuildForm(): void
    {
        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->willReturn([42 => $localization]);

        $originalTranslations = $this->createMock(FormBuilderInterface::class);
        $originalTranslations->expects($this->once())
            ->method('getOption')
            ->with('fields')
            ->willReturn([
                'content' => [
                    'attr' => [
                        'data-wysiwyg-enabled' => true,
                    ],
                    'wysiwyg_options' => [
                        'any-key' => 'any-val',
                    ],
                ],
            ]);

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('get')
            ->with('translations')
            ->willReturn($originalTranslations);

        $builder->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                ['translations'],
                ['translation']
            )
            ->willReturnSelf();

        /** @var DataMapperInterface|\PHPUnit\Framework\MockObject\MockObject $dataMapper */
        $dataMapper = $this->createMock(DataMapperInterface::class);
        $builder->expects($this->once())
            ->method('getDataMapper')
            ->willReturn($dataMapper);

        $builder->expects($this->once())
            ->method('setDataMapper')
            ->with($this->equalTo(new LocalizationAwareEmailTemplateDataMapper($dataMapper)))
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'localizations',
                EmailTemplateLocalizationCollectionType::class,
                [
                    'localizations' => [
                        42 => $localization,
                    ],
                    'wysiwyg_enabled' => true,
                    'wysiwyg_options' => [
                        'any-key' => 'any-val',
                    ],
                ]
            )
            ->willReturnSelf();

        $this->extension->buildForm($builder, []);
    }
}
