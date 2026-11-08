<?php
require __DIR__ . '/includes/auth.php';
$user = require_login(['super_admin']);

$message = '';
$messageType = '';
$viewTranscript = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_mentor') {
        $name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            $message = 'Name, valid email, and an 8+ character password are required.';
            $messageType = 'error';
        } else {
            $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
            $exists->execute([$email]);
            if ($exists->fetch()) {
                $message = 'That email is already registered.';
                $messageType = 'error';
            } else {
                db()->prepare(
                    'INSERT INTO users (email, password_hash, role, full_name, status)
                     VALUES (?, ?, \'mentor\', ?, \'active\')'
                )->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name]);
                audit_log('create_mentor', 'user', (int) db()->lastInsertId());
                $message = 'Mentor account created.';
                $messageType = 'ok';
            }
        }
    } elseif ($action === 'toggle_mentor') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $status = ($_POST['status'] ?? '') === 'paused' ? 'active' : 'paused';
        db()->prepare('UPDATE users SET status = ? WHERE id = ? AND role = \'mentor\'')->execute([$status, $id]);
        audit_log('toggle_mentor', 'user', $id, $status);
        $message = 'Mentor status updated.';
        $messageType = 'ok';
    } elseif ($action === 'view_transcript') {
        $id = (int) ($_POST['transcript_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            $message = 'A reason is required to open a transcript.';
            $messageType = 'error';
        } else {
            $stmt = db()->prepare('SELECT * FROM call_transcriptions WHERE id = ?');
            $stmt->execute([$id]);
            $viewTranscript = $stmt->fetch();
            if ($viewTranscript) {
                audit_log('view_transcript', 'call_transcription', $id, $reason);
            }
        }
    }
}

$mentors = db()->query(
    "SELECT u.id, u.full_name, u.email, u.status, u.last_login_at,
            COUNT(m.id) AS caseload,
            COUNT(c.id) AS call_count,
            ROUND(AVG(c.duration_seconds), 0) AS avg_duration,
            ROUND(AVG(c.response_seconds), 0) AS avg_response
     FROM users u
     LEFT JOIN users m ON m.mentor_id = u.id AND m.role = 'mentee'
     LEFT JOIN calls c ON c.mentor_id = u.id
     WHERE u.role = 'mentor'
     GROUP BY u.id
     ORDER BY u.full_name"
)->fetchAll();

$admins = db()->query(
    "SELECT a.created_at, a.action, a.target_type, a.target_id, a.reason, u.full_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.actor_id
     ORDER BY a.id DESC
     LIMIT 40"
)->fetchAll();

$transcripts = db()->query(
    'SELECT id, created_at, language FROM call_transcriptions ORDER BY id DESC LIMIT 20'
)->fetchAll();

$pageTitle = 'Super Admin';
$activeNav = 'admin';
$showCall = false;
$showGame = false;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal">
  <div class="admin-shell admin-shell--wide">
    <aside class="account-nav" aria-label="Super admin">
      <a href="super-admin.php" class="is-active"><span class="chev" aria-hidden="true">›</span> Mentors</a>
      <a href="admin.php"><span class="chev" aria-hidden="true">›</span> MHM monitor</a>
      <a href="logout.php"><span class="chev" aria-hidden="true">›</span> Logout</a>
    </aside>
    <section class="account-panel">
      <h1>Mentor performance</h1>
      <?php if ($message): ?>
        <p class="account-flash account-flash--<?= $messageType === 'ok' ? 'ok' : 'error' ?>"><?= e($message) ?></p>
      <?php endif; ?>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Mentor</th><th>Email</th><th>Caseload</th><th>Calls</th>
              <th>Avg duration</th><th>Avg response</th><th>Status</th><th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($mentors as $row): ?>
              <tr>
                <td><?= e($row['full_name']) ?></td>
                <td><?= e($row['email']) ?></td>
                <td><?= (int) $row['caseload'] ?></td>
                <td><?= (int) $row['call_count'] ?></td>
                <td><?= e((string) ($row['avg_duration'] ?? '0')) ?>s</td>
                <td><?= e((string) ($row['avg_response'] ?? '0')) ?>s</td>
                <td><?= e($row['status']) ?></td>
                <td>
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_mentor">
                    <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="status" value="<?= e($row['status']) ?>">
                    <button class="btn btn--teal btn--inline" type="submit"><?= $row['status'] === 'active' ? 'Pause' : 'Activate' ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h2 class="admin-subhead">Create mentor</h2>
      <form method="post" class="account-edit" style="padding-left:0;display:flex;flex-wrap:wrap;gap:0.6rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_mentor">
        <input name="full_name" placeholder="Full name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" minlength="8" required>
        <button class="btn btn--teal btn--inline" type="submit">Add mentor</button>
      </form>

      <h2 class="admin-subhead">Admin activity</h2>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Target</th><th>Reason</th></tr></thead>
          <tbody>
            <?php foreach ($admins as $row): ?>
              <tr>
                <td><?= e($row['created_at']) ?></td>
                <td><?= e($row['full_name'] ?: 'system') ?></td>
                <td><?= e($row['action']) ?></td>
                <td><?= e(trim($row['target_type'] . ' ' . $row['target_id'])) ?></td>
                <td><?= e($row['reason'] ?: '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h2 class="admin-subhead">Open a transcript (audited)</h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="view_transcript">
        <div class="field-row">
          <div class="field">
            <label for="transcript_id">Transcript</label>
            <select id="transcript_id" name="transcript_id" required>
              <?php foreach ($transcripts as $row): ?>
                <option value="<?= (int) $row['id'] ?>">#<?= (int) $row['id'] ?> · <?= e($row['language']) ?> · <?= e($row['created_at']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="reason">Reason / ticket</label>
            <input id="reason" name="reason" required placeholder="Support ticket or compliance reason">
          </div>
        </div>
        <button class="btn btn--teal btn--inline" type="submit">View with audit log</button>
      </form>
      <?php if ($viewTranscript): ?>
        <article class="note-card">
          <h2>Transcript #<?= (int) $viewTranscript['id'] ?></h2>
          <p><?= nl2br(e($viewTranscript['transcript_text'])) ?></p>
        </article>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
