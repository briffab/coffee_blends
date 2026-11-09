<?php

declare(strict_types=1);

/**
 * Sends a plain-text email using PHP's built-in mail(). Most shared hosts have
 * this working out of the box for mail sent "from" an address on the same
 * domain (set mail.from_email in config.php to one). If your host blocks
 * mail(), swap this for PHPMailer configured with your SMTP provider.
 *
 * Callers must pass an already-validated $toEmail / $replyTo address.
 */
function send_mail(array $mailCfg, string $toEmail, string $subject, string $body, ?string $replyTo = null): bool
{
    $fromEmail = $mailCfg['from_email'] ?? 'no-reply@example.com';
    $fromName = $mailCfg['from_name'] ?? 'Coffee Blends';

    $headers = [
        'From' => sprintf('%s <%s>', $fromName, $fromEmail),
        'Content-Type' => 'text/plain; charset=utf-8',
    ];
    if ($replyTo !== null) {
        $headers['Reply-To'] = $replyTo;
    }

    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "$key: $value\r\n";
    }

    return @mail($toEmail, $subject, $body, $headerString);
}

function send_password_reset_email(array $mailCfg, string $toEmail, string $resetUrl): bool
{
    $body = "We received a request to reset your Coffee Blends password.\n\n"
        . "Click the link below to choose a new password. This link expires in 1 hour.\n\n"
        . $resetUrl . "\n\n"
        . "If you didn't request this, you can safely ignore this email.\n";

    return send_mail($mailCfg, $toEmail, 'Reset your Coffee Blends password', $body);
}

/** Emails a new family member their login details and first-time password. */
function send_welcome_email(array $config, array $newUser, string $password, array $admin): bool
{
    $loginUrl = site_base_url($config) . '/login.php';
    $homeUrl = rtrim((string) ($config['app']['home_url'] ?? 'https://thebriffas.co.uk'), '/');
    $adminName = display_name($admin);

    $body = 'Hi ' . display_name($newUser) . ",\n\n"
        . $adminName . " has set you up with an account on Coffee Blends, where we record our home-roasted\n"
        . "coffee blends: the beans, the roast and what it tastes like.\n\n"
        . "Your login details\n"
        . "------------------\n"
        . "Website:             " . $loginUrl . "\n"
        . "Email (username):    " . $newUser['email'] . "\n"
        . "First-time password: " . $password . "\n\n"
        . "Please sign in and change that password straight away: click your name at the top right,\n"
        . "then \"Change password\".\n\n"
        . "While you're at it, have a look around The Briffas: " . $homeUrl . "\n\n"
        . "Happy roasting!\n";

    $replyTo = filter_var($admin['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $admin['email'] : null;

    return send_mail($config['mail'] ?? [], $newUser['email'], 'Your Coffee Blends login', $body, $replyTo);
}
