<?php
require_once __DIR__ . '/helpers.php';

function ph_base_url(): string
{
    $cfg = ph_config();
    if (!empty($cfg['app_url'])) {
        return rtrim((string) $cfg['app_url'], '/');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim(dirname($script), '/');
    return $scheme . '://' . $host . $dir;
}

function ph_send_mail(string $to, string $subject, string $body): bool
{
    $cfg = ph_config();
    $from = (string) ($cfg['mail_from'] ?? 'noreply@phlourish.test');
    $fromName = (string) ($cfg['mail_from_name'] ?? 'PHLOURISH');
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . sprintf('"%s" <%s>', addslashes($fromName), $from),
        'Reply-To: ' . $from,
        'X-Mailer: PHLOURISH',
    ]);
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

    if (getenv('APP_ENV') !== 'production') {
        $logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @file_put_contents(
            $logDir . DIRECTORY_SEPARATOR . 'mail.log',
            date('c') . "\t" . $to . "\t" . $subject . "\n" . $body . "\n\n",
            FILE_APPEND | LOCK_EX
        );
    }

    return (bool) $ok;
}

function ph_issue_email_verification(int $userId, string $email, string $name): array
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    db()->prepare(
        "UPDATE users SET email_verify_hash = ?, email_verify_expires = CURRENT_TIMESTAMP + INTERVAL '24 hours' WHERE id = ?"
    )->execute([$hash, $userId]);

    $url = ph_base_url() . '/verify-email.php?token=' . urlencode($token);
    $body = "Hi {$name},\n\n"
        . "Thanks for signing up for PHLOURISH. Confirm this email to finish creating your account:\n\n"
        . $url . "\n\n"
        . "This link expires in 24 hours. If you did not create an account, you can ignore this message.\n\n"
        . "— PHLOURISH Mental Health Initiative Inc.\n";

    $sent = ph_send_mail($email, 'Confirm your PHLOURISH email', $body);
    $_SESSION['verify_email'] = $email;
    $_SESSION['verify_link'] = $sent ? null : $url;

    return ['sent' => $sent, 'url' => $url];
}
