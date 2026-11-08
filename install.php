<?php
require __DIR__ . '/includes/auth.php';

$cfg = ph_config();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Please try again.';
    } else {
    try {
        $pdo = new PDO(
            'pgsql:host=' . $cfg['db_host'] . ';port=' . ($cfg['db_port'] ?? '5432') . ';dbname=' . $cfg['db_name'],
            $cfg['db_user'],
            $cfg['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        foreach (['audit_logs', 'call_lines', 'call_transcriptions', 'calls', 'assessments', 'user_abilities', 'abilities', 'game_progress', 'users'] as $table) {
            $pdo->exec('DROP TABLE IF EXISTS "' . str_replace('"', '""', $table) . '" CASCADE');
        }

        $raw = file_get_contents(__DIR__ . '/sql/schema.sql');
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $raw)), fn($s) => $s !== '') as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement . ';');
            }
        }

        $abilities = [
            ['Grounding breath', 'A short breathing exercise from the Phlourish toolkit.'],
            ['Name the feeling', 'Pause and label the emotion without judging it.'],
            ['Reach out', 'Ask one trusted person or a mentor for a short check-in.'],
        ];
        $pdo->exec('DELETE FROM user_abilities');
        $pdo->exec('DELETE FROM abilities');
        $insAbility = $pdo->prepare('INSERT INTO abilities (name, description) VALUES (?, ?)');
        foreach ($abilities as $ability) {
            $insAbility->execute($ability);
        }

        $hash = password_hash('Password123!', PASSWORD_DEFAULT);
        $mfa = password_hash('246810', PASSWORD_DEFAULT);
        $users = [
            ['mike.santos@phlourish.test', $hash, 'mentor', 'Mike Santos', 'Male', '1999-08-15', null, null],
            ['aya.reyes@phlourish.test', $hash, 'mentor', 'Aya Reyes', 'Female', '1998-03-02', null, null],
            ['admin@phlourish.test', $hash, 'admin', 'Pat Admin', 'Prefer not to say', '1996-01-12', null, null],
            ['super@phlourish.test', $hash, 'super_admin', 'Lee Super Admin', 'Prefer not to say', '1995-06-20', null, $mfa],
            ['jordan.cruz@phlourish.test', $hash, 'mentee', 'Jordan Cruz', 'Male', '2004-04-09', 1, null],
            ['sam.villanueva@phlourish.test', $hash, 'mentee', 'Sam Villanueva', 'Female', '2005-11-18', 1, null],
        ];

        $pdo->exec('TRUNCATE TABLE audit_logs RESTART IDENTITY CASCADE');
        $pdo->exec('TRUNCATE TABLE call_lines RESTART IDENTITY CASCADE');
        $pdo->exec('TRUNCATE TABLE call_transcriptions RESTART IDENTITY CASCADE');
        $pdo->exec('TRUNCATE TABLE calls RESTART IDENTITY CASCADE');
        $pdo->exec('TRUNCATE TABLE assessments RESTART IDENTITY CASCADE');
        $pdo->exec('TRUNCATE TABLE game_progress RESTART IDENTITY CASCADE');
        $pdo->exec('TRUNCATE TABLE users RESTART IDENTITY CASCADE');

        $insUser = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role, full_name, gender, birthdate, mentor_id, mfa_code_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($users as $user) {
            $insUser->execute($user);
        }

        $pdo->prepare('INSERT INTO game_progress (user_id, mhm, quest_id, completed) VALUES (5, 62, \'after-class\', 1)')->execute();
        $pdo->prepare('INSERT INTO game_progress (user_id, mhm, quest_id, completed) VALUES (6, 28, \'hallway\', 0)')->execute();

        $pre = json_encode(['q1' => 5, 'q2' => 4, 'q3' => 3, 'q4' => 4, 'q5' => 2, 'q6' => 3]);
        $pdo->prepare('INSERT INTO assessments (user_id, type, answers, mean_score) VALUES (5, \'pre\', ?, 3.50)')->execute([$pre]);
        $pdo->prepare('INSERT INTO assessments (user_id, type, answers, mean_score) VALUES (6, \'pre\', ?, 3.17)')->execute([$pre]);

        $pdo->prepare(
            'INSERT INTO calls (user_id, mentor_id, status, language, started_at, ended_at, duration_seconds, mhm_at_start, mhm_at_end, flagged)
             VALUES (6, 1, \'ended\', \'english\', NOW() - INTERVAL \'2 days\', NOW() - INTERVAL \'2 days\' + INTERVAL \'18 minutes\', 1080, 34, 28, 1)'
        )->execute();
        $pdo->prepare(
            'INSERT INTO call_transcriptions (call_id, user_id, mentor_id, transcript_text, language)
             VALUES (1, 6, 1, ?, \'english\')'
        )->execute(['Mentor: Kamusta. We can go slow.\nMentee: Group work still feels heavy. I kept saying I\'m fine.']);

        $pdo->exec('UPDATE users SET email_verified_at = NOW()');

        $pdo->prepare(
            'INSERT INTO audit_logs (actor_id, action, target_type, target_id, reason, ip)
             VALUES (4, \'install_seed\', \'system\', NULL, \'Initial schema and demo accounts\', ?)'
        )->execute([$_SERVER['REMOTE_ADDR'] ?? 'cli']);

        $message = 'Database ready. Demo password for all seeded accounts: Password123! Super admin extra code: 246810';
    } catch (Throwable $e) {
        $error = 'Install failed: ' . $e->getMessage();
    }
    }
}

$pageTitle = 'Install';
$activeNav = 'home';
$showCall = false;
$showGame = false;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal">
  <form class="card-mentor" method="post">
    <h1>Set up PHLOURISH</h1>
    <?= csrf_field() ?>
    <?php if ($error): ?>
      <p class="form-footer" style="margin-top:0;margin-bottom:1rem;color:#c0392b;"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
      <p class="form-footer" style="margin-top:0;margin-bottom:1rem;color:var(--teal);"><?= e($message) ?></p>
      <p class="form-footer">Mentee: jordan.cruz@phlourish.test<br>Mentor: mike.santos@phlourish.test<br>Admin: admin@phlourish.test<br>Super admin: super@phlourish.test · code 246810</p>
    <?php else: ?>
      <p class="form-footer" style="margin-top:0;text-align:left;">Creates the PostgreSQL database schema and demo accounts for your PHLOURISH instance.</p>
      <button class="btn btn--teal" type="submit">Install database</button>
    <?php endif; ?>
  </form>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
