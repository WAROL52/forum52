<?php

namespace App\Tests\Integration\Service;

use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailServiceTest extends KernelTestCase
{
    private MailService $mailService;
    private array $sentEmails = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Create a mock mailer that captures sent emails
        $mockMailer = $this->createMock(MailerInterface::class);
        $mockMailer->method('send')
            ->willReturnCallback(function ($email) {
                $this->sentEmails[] = $email;
            });

        // Create MailService with mock mailer
        $this->mailService = new MailService(
            $mockMailer,
            'noreply@example.com',
            'Test App'
        );
    }

    protected function tearDown(): void
    {
        $this->sentEmails = [];
        parent::tearDown();
    }

    public function testSendEmailWithSingleRecipient(): void
    {
        $to = 'user@example.com';
        $subject = 'Test Subject';
        $template = 'emails/test.html.twig';
        $context = ['name' => 'John'];

        $this->mailService->send($to, $subject, $template, $context);

        $this->assertCount(1, $this->sentEmails);

        $email = $this->sentEmails[0];
        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals($subject, $email->getSubject());
    }

    public function testSendEmailWithMultipleRecipients(): void
    {
        $to = ['user1@example.com', 'user2@example.com'];
        $subject = 'Test Subject';
        $template = 'emails/test.html.twig';

        $this->mailService->send($to, $subject, $template);

        $this->assertCount(1, $this->sentEmails);
    }

    public function testSendEmailWithContext(): void
    {
        $context = [
            'username' => 'testuser',
            'code' => '123456',
            'expirationMinutes' => 15,
        ];

        $this->mailService->send(
            'user@example.com',
            'Verification Code',
            'emails/verification.html.twig',
            $context
        );

        $this->assertCount(1, $this->sentEmails);
    }

    public function testEmailFromAddressIsCorrect(): void
    {
        $this->mailService->send(
            'user@example.com',
            'Test',
            'emails/test.html.twig'
        );

        $email = $this->sentEmails[0];
        $from = $email->getFrom();

        $this->assertCount(1, $from);
        $this->assertEquals('noreply@example.com', $from[0]->getAddress());
        $this->assertEquals('Test App', $from[0]->getName());
    }

    public function testEmailTemplateIsSet(): void
    {
        $template = 'emails/verification.html.twig';

        $this->mailService->send(
            'user@example.com',
            'Test',
            $template
        );

        $this->assertCount(1, $this->sentEmails);

        // The email should be a TemplatedEmail instance
        $email = $this->sentEmails[0];
        $this->assertInstanceOf(\Symfony\Bridge\Twig\Mime\TemplatedEmail::class, $email);
    }
}
