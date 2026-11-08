<?php
require __DIR__ . '/includes/auth.php';

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    redirect('check-email.php');
}

try {
    $hash = hash('sha256', $token);
    $stmt = db()->prepare(
        'SELECT id FROM student
         WHERE email_verify_hash = ? AND email_verify_expires >= NOW() AND email_verified_at IS NULL
         LIMIT 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) {
        $_SESSION['verify_link'] = null;
        redirect('check-email.php');
    }

    db()->prepare(
        'UPDATE student SET email_verified_at = NOW(), email_verify_hash = NULL, email_verify_expires = NULL WHERE id = ?'
    )->execute([(int) $row['id']]);
    unset($_SESSION['verify_email'], $_SESSION['verify_link']);
    audit_log('verify_email', 'user', (int) $row['id']);
    redirect('login.php?verified=1');
} catch (Throwable $e) {
    redirect('check-email.php');
}
