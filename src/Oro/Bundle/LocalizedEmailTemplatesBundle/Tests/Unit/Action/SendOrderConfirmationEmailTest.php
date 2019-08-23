<?php

namespace Oro\Bundle\LocalizedEmailTemplatesBundle\Tests\Unit\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Action\SendOrderConfirmationEmail;
use Oro\Bundle\LocalizedEmailTemplatesBundle\Provider\LocalizedTemplateProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendOrderConfirmationEmailTest extends \PHPUnit\Framework\TestCase
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

    /** @var SendOrderConfirmationEmail */
    private $action;

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

        $this->action = new SendOrderConfirmationEmail(
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
    }

    public function testExecuteIgnoresTwigExceptions(): void
    {
        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->willThrowException(new \Twig_Error_Runtime('Twig_Error_Runtime'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Twig exception in @send_order_confirmation_email action');

        $this->action->initialize(
            [
                'from' => 'test@test.com',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
                'workflow' => 'test'
            ]
        );
        $this->action->execute([]);
    }

    public function testExecuteIgnoresMissingEmailTemplate(): void
    {
        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->willThrowException(new EntityNotFoundException());

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Cannot find the specified email template in @send_order_confirmation_email action');

        $this->action->initialize(
            [
                'from' => 'test@test.com',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
                'workflow' => 'test'
            ]
        );
        $this->action->execute([]);
    }
}
