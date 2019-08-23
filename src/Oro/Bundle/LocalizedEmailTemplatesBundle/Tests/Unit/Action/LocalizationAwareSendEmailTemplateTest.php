<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Action\LocalizationAwareSendEmailTemplate;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Model\EmailAddressString;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LocalizationAwareSendEmailTemplateTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $emailProcessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var LocalizedTemplateProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localizedTemplateProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var LocalizationAwareSendEmailTemplate */
    private $action;

    /** @var EmailTemplate|\PHPUnit\Framework\MockObject\MockObject */
    private $emailTemplate;

    protected function setUp()
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->emailProcessor = $this->createMock(Processor::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->any())
            ->method('getName')
            ->willReturn(\stdClass::class);

        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($classMetadata);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->objectManager);

        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->localizedTemplateProvider = $this->createMock(LocalizedTemplateProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new LocalizationAwareSendEmailTemplate(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $registry,
            $this->validator,
            $this->localizedTemplateProvider
        );

        $this->action->setLogger($this->logger);
        $this->action->setDispatcher($this->dispatcher);

        $this->emailTemplate = $this->createMock(EmailTemplate::class);
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage): void
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider(): array
    {
        return [
            'no from' => [
                'options' => ['to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'From parameter is required'
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'from' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no to or recipients' => [
                'options' => ['from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Need to specify "to" or "recipients" parameters'
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'recipients in not an array' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'recipients' => 'some@recipient.com'
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Recipients parameter must be an array',
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['test@test.com', ['name' => 'Test']]
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no template' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Template parameter is required'
            ],
            'no entity' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'template' => 'test'],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Entity parameter is required'
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testInitialize($options, $expected): void
    {
        $this->assertSame($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($expected, 'options', $this->action);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function optionsDataProvider(): array
    {
        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
            ],
            'simple with name' => [
                [
                    'from' => 'Test <test@test.com>',
                    'to' => 'Test <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => 'Test <test@test.com>',
                    'to' => ['Test <test@test.com>'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
            ],
            'extended' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ]
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
            ],
            'multiple to' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ],
                        'test@test.com',
                        'Test <test@test.com>'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ],
                        'test@test.com',
                        'Test <test@test.com>'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
            ],
            'with recipients' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test2@test.com',
                    'recipients' => [new EmailHolderStub()],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test2@test.com'],
                    'recipients' => [new EmailHolderStub()],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
            ],
        ];
    }

    /**
     * Test with expected \Doctrine\ORM\EntityNotFoundException for the case, when template does not found
     *
     * @expectedException \Doctrine\ORM\EntityNotFoundException
     */
    public function testExecuteWithoutTemplateEntity()
    {
        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->willThrowException(new EntityNotFoundException());

        $this->emailProcessor->expects($this->never())
            ->method('process');

        $this->action->initialize(
            [
                'from' => 'test@test.com',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
            ]
        );
        $this->action->execute([]);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ValidatorException
     * @expectedExceptionMessage test
     */
    public function testExecuteWithInvalidEmail()
    {
        $violationListInterface = $this->createMock('Symfony\Component\Validator\ConstraintViolationInterface');
        $violationListInterface->expects($this->once())
            ->method('getMessage')
            ->willReturn('test');

        $violationList = $this->createMock('Symfony\Component\Validator\ConstraintViolationList');
        $violationList->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $violationList->expects($this->once())
            ->method('get')
            ->willReturn($violationListInterface);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violationList);

        $this->localizedTemplateProvider->expects($this->never())
            ->method($this->anything());

        $this->emailProcessor->expects($this->never())
            ->method($this->anything());

        $this->action->initialize(
            [
                'from' => 'invalidemailaddress',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
            ]
        );
        $this->action->execute([]);
    }

    public function testExecuteWithProcessException()
    {
        $rcpt = new EmailAddressString('test@test.com');

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient($rcpt);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with([$rcpt], new EmailTemplateCriteria('test', \stdClass::class), ['entity' => new \stdClass()])
            ->willReturn([$dto]);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('txt');

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email'))
            ->willThrowException(new \Swift_SwiftException('The email was not delivered.'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Workflow send email template action.');

        $this->action->initialize(
            [
                'from' => 'test@test.com',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
            ]
        );
        $this->action->execute([]);
    }

    /**
     * @dataProvider executeOptionsDataProvider
     *
     * @param array $options
     * @param string|object $recipient
     * @param array $expected
     */
    public function testExecute(array $options, $recipient, array $expected)
    {
        $context = [];

        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(
                function () {
                    return '_Formatted';
                }
            );

        if (!$recipient instanceof EmailHolderInterface) {
            $recipient = new EmailAddressString($recipient);
        }

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient(is_object($recipient) ? $recipient : new EmailAddressString($recipient));

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with([$recipient], new EmailTemplateCriteria('test', \stdClass::class), ['entity' => new \stdClass()])
            ->willReturn([$dto]);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('txt');
        $this->emailTemplate->expects($this->once())
            ->method('getSubject')
            ->willReturn($expected['subject']);
        $this->emailTemplate->expects($this->once())
            ->method('getContent')
            ->willReturn($expected['body']);

        $emailEntity = $this->createMock('\Oro\Bundle\EmailBundle\Entity\Email');

        $emailUserEntity = $this->getMockBuilder('\Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();
        $emailUserEntity->expects($this->any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email'))
            ->willReturnCallback(
                function (Email $model) use ($emailUserEntity, $expected) {
                    $this->assertEquals($expected['body'], $model->getBody());
                    $this->assertEquals($expected['subject'], $model->getSubject());
                    $this->assertEquals($expected['from'], $model->getFrom());
                    $this->assertEquals($expected['to'], $model->getTo());
                    $this->assertEquals('txt', $model->getType());

                    return $emailUserEntity;
                }
            );

        if (array_key_exists('attribute', $options)) {
            $this->contextAccessor->expects($this->once())
                ->method('setValue')
                ->with($context, $options['attribute'], $emailEntity);
        }

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function executeOptionsDataProvider()
    {
        $nameMock = $this->createMock('Oro\Bundle\LocaleBundle\Model\FirstNameInterface');
        $nameMock->expects($this->any())
            ->method('getFirstName')
            ->willReturn('NAME');
        $recipient = new EmailHolderStub('recipient@test.com');

        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                'test@test.com',
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'simple with name' => [
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"Test" <test@test.com>',
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'extended' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"Test" <test@test.com>',
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'extended with name formatting' => [
                [
                    'from' => [
                        'name' => $nameMock,
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        'name' => $nameMock,
                        'email' => 'test@test.com'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"_Formatted" <test@test.com>',
                [
                    'from' => '"_Formatted" <test@test.com>',
                    'to' => ['"_Formatted" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'with recipients' => [
                [
                    'from' => 'test@test.com',
                    'recipients' => [$recipient],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                $recipient,
                [
                    'from' => 'test@test.com',
                    'to' => ['recipient@test.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de'
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithMultipleRecipients(): void
    {
        $toEmail1 = 'to1@test.com';
        $toEmail2 = 'to2@test.com';

        $recipientEmail1 = 'recipient1@test.com';
        $recipient1 = new EmailHolderStub($recipientEmail1);

        $recipientEmail2 = 'recipient2@test.com';
        $recipient2 = new EmailHolderStub($recipientEmail2);

        $enTemplate = new EmailTemplate();
        $enTemplate->setSubject('subject_en');
        $enTemplate->setContent('body_en');
        $enTemplate->setType('txt');

        $deTemplate = new EmailTemplate();
        $deTemplate->setSubject('subject_de');
        $deTemplate->setContent('body_de');
        $deTemplate->setType('txt');

        $options = [
            'from' => 'from@test.com',
            'to' => [
                $toEmail1,
                $toEmail2,
                ' '
            ],
            'template' => 'test',
            'entity' => new \stdClass(),
            'attribute' => 'attr',
            'recipients' => [
                $recipient1,
                $recipient2,
            ]
        ];

        $messages = [
            'en' => ['subject_en', 'body_en'],
            'de' => ['subject_de', 'body_de'],
        ];

        $rcpt1 = new EmailAddressString($toEmail1);
        $rcpt2 = new EmailAddressString($toEmail2);

        $dto1 = new LocalizedTemplateDTO($enTemplate);
        $dto1->addRecipient($rcpt1);
        $dto1->addRecipient($recipient1);

        $dto2 = new LocalizedTemplateDTO($deTemplate);
        $dto2->addRecipient($rcpt2);
        $dto2->addRecipient($recipient2);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                [$rcpt1, $rcpt2, $recipient1, $recipient2],
                new EmailTemplateCriteria('test', \stdClass::class),
                ['entity' => new \stdClass()]
            )
            ->willReturn([$dto1, $dto2]);

        $email = new EmailEntity();

        $this->emailProcessor->expects($this->at(1))
            ->method('process')
            ->willReturnCallback(
                function (Email $model) use ($enTemplate, $toEmail1, $recipientEmail1, $email) {
                    $this->assertEquals($enTemplate->getSubject(), $model->getSubject());
                    $this->assertEquals($enTemplate->getContent(), $model->getBody());
                    $this->assertEquals([$toEmail1, $recipientEmail1], $model->getTo());
                    $this->assertEquals('txt', $model->getType());

                    $emailUser = new EmailUser();
                    $emailUser->setEmail($email);

                    return $emailUser;
                }
            );
        $this->emailProcessor->expects($this->at(3))
            ->method('process')
            ->willReturnCallback(
                function (Email $model) use ($deTemplate, $toEmail2, $recipientEmail2, $email) {
                    $this->assertEquals($deTemplate->getSubject(), $model->getSubject());
                    $this->assertEquals($deTemplate->getContent(), $model->getBody());
                    $this->assertEquals([$toEmail2, $recipientEmail2], $model->getTo());
                    $this->assertEquals('txt', $model->getType());

                    $emailUser = new EmailUser();
                    $emailUser->setEmail($email);

                    return $emailUser;
                }
            );

        $context = [];

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($context, $options['attribute'], $email);

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
