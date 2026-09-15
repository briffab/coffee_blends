<?php

declare(strict_types=1);

/**
 * Sends the password reset email using PHP's built-in mail().
 * Most shared hosts have this working out of the box for mail sent
 * "from" an address on the same domain. If your host blocks mail(),
 * swap this out for PHPMailer configured with your SMTP provider.
 */
function send_password_reset_email(array $mailCfg, string $toEmail, string $resetUrl): bool
{
    $subject = 'Reset your Coffee Blends password';
    $body = "We received a request to reset your Coffee Blends password.\n\n"
        . "Click the link below to choose a new password. This link expires in 1 hour.\n\n"
        . $resetUrl . "\n\n"
        . "If you didn't request this, you can safely ignore this email.\n";

    $fromEmail = $mailCfg['from_email'] ?? 'no-reply@example.com';
    $fromName = $mailCfg['from_name'] ?? 'Coffee Blends';

    $headers = [
        'From' => sprintf('%s <%s>', $fromName, $fromEmail),
        'Content-Type' => 'text/plain; charset=utf-8',
    ];

    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "$key: $value\r\n";
    }

    return mail($toEmail, $subject, $body, $headerString);
}
