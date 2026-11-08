<?php
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function pretest_questions(): array
{
    return [
        'q1' => 'I often feel stressed due to responsibilities.',
        'q2' => 'I am aware of my mental health state.',
        'q3' => 'I find it difficult to manage my mental health.',
        'q4' => 'I know where to get help or resources.',
        'q5' => 'I talk to people about my mental health.',
        'q6' => 'I am familiar with mental health interventions.',
    ];
}

function age_from_birthdate(string $birthdate): ?int
{
    try {
        $born = new DateTime($birthdate);
        return (int) $born->diff(new DateTime('today'))->y;
    } catch (Exception $e) {
        return null;
    }
}

function least_loaded_mentor_id(): ?int
{
    $sql = "SELECT u.id, COUNT(m.id) AS caseload
            FROM users u
            LEFT JOIN users m ON m.mentor_id = u.id AND m.role = 'mentee'
            WHERE u.role = 'mentor' AND u.status = 'active'
            GROUP BY u.id
            ORDER BY caseload ASC, u.id ASC
            LIMIT 1";
    $row = db()->query($sql)->fetch();
    return $row ? (int) $row['id'] : null;
}

function current_mhm(int $userId): int
{
    $stmt = db()->prepare('SELECT mhm FROM game_progress WHERE user_id = ?');
    $stmt->execute([$userId]);
    $value = $stmt->fetchColumn();
    return $value === false ? 50 : (int) $value;
}

function audit_log(string $action, ?string $targetType = null, ?int $targetId = null, ?string $reason = null): void
{
    $user = ph_user();
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (actor_id, action, target_type, target_id, reason, ip)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $user['id'] ?? null,
        $action,
        $targetType,
        $targetId,
        $reason,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function can_view_transcript(array $user, array $row): bool
{
    if ($user['role'] === 'super_admin') {
        return true;
    }
    if ((int) $row['user_id'] === (int) $user['id'] || (int) $row['mentor_id'] === (int) $user['id']) {
        return true;
    }
    return false;
}

function assigned_mentor(int $mentorId): ?array
{
    if ($mentorId < 1) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, full_name, email FROM users WHERE id = ? AND role = \'mentor\' LIMIT 1');
    $stmt->execute([$mentorId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function assessment_for(int $userId, string $type): ?array
{
    $stmt = db()->prepare('SELECT * FROM assessments WHERE user_id = ? AND type = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId, $type]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function call_for_user(int $callId, array $user): ?array
{
    $stmt = db()->prepare('SELECT * FROM calls WHERE id = ? LIMIT 1');
    $stmt->execute([$callId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    if (!can_view_transcript($user, $row)) {
        return null;
    }
    return $row;
}

function json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function initials_from_name(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $ch = function_exists('mb_substr')
            ? mb_strtoupper(mb_substr($part, 0, 1))
            : strtoupper(substr($part, 0, 1));
        $letters .= $ch;
    }
    return $letters !== '' ? $letters : '??';
}
