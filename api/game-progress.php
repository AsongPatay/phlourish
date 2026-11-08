<?php
require __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$user = ph_user();
if (!$user || $user['role'] !== 'mentee') {
    json_response(['ok' => false, 'error' => 'Please log in as a mentee.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'POST required.'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    $data = $_POST;
}

$token = (string) ($data['csrf_token'] ?? '');
if ($token === '' || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    json_response(['ok' => false, 'error' => 'Please refresh and try again.'], 400);
}

$mhm = isset($data['mhm']) ? (int) $data['mhm'] : null;
if ($mhm === null || $mhm < 0 || $mhm > 100) {
    json_response(['ok' => false, 'error' => 'Invalid meter value.'], 400);
}

$quest = trim((string) ($data['quest_id'] ?? ''));
if (strlen($quest) > 80) {
    $quest = substr($quest, 0, 80);
}
if ($quest === '') {
    $quest = null;
}

$completed = !empty($data['completed']) ? 1 : 0;

$stmt = db()->prepare(
    'INSERT INTO game_progress (user_id, mhm, quest_id, completed)
     VALUES (?, ?, ?, ?)
     ON CONFLICT (user_id) DO UPDATE SET
       mhm = EXCLUDED.mhm,
       quest_id = EXCLUDED.quest_id,
       completed = GREATEST(game_progress.completed, EXCLUDED.completed)'
);
$stmt->execute([(int) $user['id'], $mhm, $quest, $completed]);

json_response([
    'ok' => true,
    'mhm' => $mhm,
    'quest_id' => $quest,
    'completed' => $completed,
]);
