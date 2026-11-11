<?php
require_once __DIR__ . '/helpers.php';

function ph_mailer(): PHPMailer\PHPMailer\PHPMailer
{
    static $loaded = false;
    if (!$loaded) {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Email delivery is unavailable because the mail transport is not installed.');
        }
        require_once $autoload;
        $loaded = true;
    }

    $cfg = ph_config();
    $host = trim((string) ($cfg['mail_host'] ?? ''));
    $username = trim((string) ($cfg['mail_username'] ?? ''));
    $password = (string) ($cfg['mail_password'] ?? '');
    $port = (int) ($cfg['mail_port'] ?? 587);
    $encryption = (string) ($cfg['mail_encryption'] ?? 'tls');
    $from = trim((string) ($cfg['mail_from'] ?? ''));
    $fromName = trim((string) ($cfg['mail_from_name'] ?? 'PHLOURISH'));

    if (
        $host === ''
        || $username === ''
        || $password === ''
        || $from === ''
        || !filter_var($from, FILTER_VALIDATE_EMAIL)
        || $port < 1
        || $port > 65535
        || !in_array($encryption, ['tls', 'ssl'], true)
    ) {
        throw new RuntimeException('Email delivery is not configured. Set MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, and MAIL_FROM.');
    }

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $host;
    $mailer->SMTPAuth = true;
    $mailer->Username = $username;
    $mailer->Password = $password;
    $mailer->Port = $port;
    $mailer->SMTPSecure = $encryption === 'ssl'
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($from, $fromName);

    return $mailer;
}

function ph_base_url(): string
{
    $cfg = ph_config();
    if (!empty($cfg['app_url'])) {
        return rtrim((string) $cfg['app_url'], '/');
    }
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim(dirname($script), '/');
    return $scheme . '://' . $host . $dir;
}

function ph_send_mail(string $to, string $subject, string $body): bool
{
    try {
        $mailer = ph_mailer();
        $mailer->addAddress($to);
        $mailer->Subject = $subject;
        $mailer->Body = $body;
        $mailer->send();
        return true;
    } catch (Throwable $e) {
        error_log('Email delivery failed: ' . $e->getMessage());
        return false;
    }
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
