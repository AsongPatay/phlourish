<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';

function ph_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function ph_refresh_user(): void
{
    $user = ph_user();
    if (!$user) {
        return;
    }
    $stmt = db()->prepare('SELECT id, email, role, full_name, gender, birthdate, mentor_id, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $user['id']]);
    $row = $stmt->fetch();
    if (!$row || $row['status'] !== 'active') {
        ph_logout();
        return;
    }
    $_SESSION['user'] = $row;
}

function ph_enforce_session_timeout(): void
{
    $user = ph_user();
    if (!$user) {
        return;
    }
    $cfg = ph_config();
    $idle = $user['role'] === 'super_admin'
        ? (int) $cfg['super_admin_idle_seconds']
        : (int) $cfg['session_idle_seconds'];
    $last = $_SESSION['last_activity'] ?? time();
    if (time() - (int) $last > $idle) {
        ph_logout();
        redirect('login.php?timeout=1');
    }
    $_SESSION['last_activity'] = time();
}

function ph_login_user(array $row): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $row['id'],
        'email' => $row['email'],
        'role' => $row['role'],
        'full_name' => $row['full_name'],
        'gender' => $row['gender'],
        'birthdate' => $row['birthdate'],
        'mentor_id' => $row['mentor_id'] ? (int) $row['mentor_id'] : null,
        'status' => $row['status'],
    ];
    $_SESSION['last_activity'] = time();
    $stmt = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $stmt->execute([(int) $row['id']]);
}

function ph_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function require_login(array $roles = []): array
{
    $user = ph_user();
    if (!$user) {
        redirect('login.php');
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        redirect('account.php');
    }
    return $user;
}

function home_for_role(string $role): string
{
    if ($role === 'admin') {
        return 'admin.php';
    }
    if ($role === 'super_admin') {
        return 'super-admin.php';
    }
    if ($role === 'mentor') {
        return 'account.php?section=mentees';
    }
    return 'account.php';
}

function ph_authenticate(string $email, string $password, array $roles): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return ['ok' => false, 'error' => 'Email or password is incorrect.'];
    }
    if ($row['status'] !== 'active') {
        return ['ok' => false, 'error' => 'This account is paused. Contact PHLOURISH.'];
    }
    if ($roles && !in_array($row['role'], $roles, true)) {
        return ['ok' => false, 'error' => 'Use the login page that matches your role.'];
    }
    return ['ok' => true, 'user' => $row];
}

ph_enforce_session_timeout();
if (ph_user()) {
    try {
        ph_refresh_user();
    } catch (Throwable $e) {
        // Database may not be installed yet.
    }
}
