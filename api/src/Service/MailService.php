<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    /**
     * Send a templated email
     *
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $htmlTemplate Path to HTML template (e.g., 'emails/verification.html.twig')
     * @param array $context Template variables
     * @param string|null $textTemplate Optional path to text template
     */
    public function send(
        string|array $to,
        string $subject,
        string $htmlTemplate,
        array $context = []
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->subject($subject)
            ->htmlTemplate($htmlTemplate)
            ->context($context);

        // Add recipients
        if (is_array($to)) {
            foreach ($to as $recipient) {
                $email->addTo($recipient);
            }
        } else {
            $email->to($to);
        }

        $this->mailer->send($email);
    }
}
