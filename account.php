<?php
require __DIR__ . '/includes/auth.php';
$user = require_login();

$section = $_GET['section'] ?? 'details';
$allowed = ['details', 'progress', 'transcriptions', 'mentees'];
if (!in_array($section, $allowed, true)) {
    $section = 'details';
}
if ($section === 'mentees' && $user['role'] !== 'mentor' && $user['role'] !== 'super_admin') {
    $section = 'details';
}

$message = '';
$messageType = '';
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'email') {
        $next = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($next, FILTER_VALIDATE_EMAIL)) {
            $message = 'Enter a valid email.';
            $messageType = 'error';
        } else {
            $check = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $check->execute([$next, $user['id']]);
            if ($check->fetch()) {
                $message = 'That email is already in use.';
                $messageType = 'error';
            } else {
                db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$next, $user['id']]);
                audit_log('update_email', 'user', (int) $user['id']);
                $email = $next;
                $message = 'Email updated.';
                $messageType = 'ok';
                ph_refresh_user();
                $user = ph_user();
            }
        }
    } elseif ($action === 'password') {
        $next = $_POST['password'] ?? '';
        if (strlen($next) < 8) {
            $message = 'Password must be at least 8 characters.';
            $messageType = 'error';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($next, PASSWORD_DEFAULT), $user['id']]);
            audit_log('update_password', 'user', (int) $user['id']);
            $message = 'Password updated.';
            $messageType = 'ok';
        }
    } elseif ($action === 'delete') {
        $id = (int) $user['id'];
        ph_logout();
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        redirect('login.php');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Please try again.';
    $messageType = 'error';
}

$mentor = $user['mentor_id'] ? assigned_mentor((int) $user['mentor_id']) : null;
$progress = null;
if ($user['role'] === 'mentee') {
    $stmt = db()->prepare('SELECT * FROM game_progress WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $progress = $stmt->fetch() ?: null;
}
$pre = assessment_for((int) $user['id'], 'pre');
$post = assessment_for((int) $user['id'], 'post');

$transcripts = [];
if ($section === 'transcriptions') {
    $sql = 'SELECT t.*, c.started_at, c.language, c.duration_seconds
            FROM call_transcriptions t
            JOIN calls c ON c.id = t.call_id
            WHERE t.user_id = ? OR t.mentor_id = ?
            ORDER BY t.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$user['id'], $user['id']]);
    foreach ($stmt as $row) {
        if (can_view_transcript($user, $row)) {
            $transcripts[] = $row;
        }
    }
}

$mentees = [];
if ($section === 'mentees') {
    $stmt = db()->prepare(
        'SELECT u.id, u.full_name, u.status, u.last_login_at, g.mhm,
                (SELECT MAX(ended_at) FROM calls WHERE user_id = u.id) AS last_call
         FROM users u
         LEFT JOIN game_progress g ON g.user_id = u.id
         WHERE u.role = \'mentee\' AND u.mentor_id = ?
         ORDER BY u.full_name'
    );
    $stmt->execute([$user['id']]);
    $mentees = $stmt->fetchAll();
}

$pageTitle = 'Account';
$activeNav = 'account';
$showCall = in_array($user['role'], ['mentee', 'mentor'], true);
$showGame = $user['role'] === 'mentee';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';

$iconPerson = '<svg class="acc-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5 19.2c.8-3.2 3.4-5 7-5s6.2 1.8 7 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
$iconGender = '<svg class="acc-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="14" r="5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M14.2 9.8 19 5m0 0h-4.2M19 5v4.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$iconCal = '<svg class="acc-ico" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="6" width="16" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 4v4M16 4v4M4 10h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
$iconAt = '<svg class="acc-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7.2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M15 12v1.6a2 2 0 0 0 3.6 1.2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
$iconKey = '<svg class="acc-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="14" r="3.2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M11 14h9l-2 2 2 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$iconPencil = '<svg class="acc-ico acc-ico--sm" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m13 7 4 4" stroke="currentColor" stroke-width="1.8"/></svg>';
$iconBan = '<svg class="acc-ico acc-ico--danger" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M7 17 17 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
?>

<main class="page page--account">
  <div class="account-shell">
    <aside class="account-nav" aria-label="Account">
      <a href="account.php?section=details" class="<?= $section === 'details' ? 'is-active' : '' ?>"><span class="chev" aria-hidden="true">›</span> Account Details</a>
      <?php if ($user['role'] === 'mentee'): ?>
        <a href="account.php?section=progress" class="<?= $section === 'progress' ? 'is-active' : '' ?>"><span class="chev" aria-hidden="true">›</span> Progress</a>
      <?php endif; ?>
      <?php if (in_array($user['role'], ['mentee', 'mentor', 'super_admin'], true)): ?>
        <a href="account.php?section=transcriptions" class="<?= $section === 'transcriptions' ? 'is-active' : '' ?>"><span class="chev" aria-hidden="true">›</span> Transcriptions</a>
      <?php endif; ?>
      <?php if ($user['role'] === 'mentor' || $user['role'] === 'super_admin'): ?>
        <a href="account.php?section=mentees" class="<?= $section === 'mentees' ? 'is-active' : '' ?>"><span class="chev" aria-hidden="true">›</span> Mentees</a>
      <?php endif; ?>
      <a href="logout.php"><span class="chev" aria-hidden="true">›</span> Logout</a>
    </aside>

    <section class="account-panel">
      <?php if ($section === 'details'): ?>
        <h1>Account Details</h1>
        <?php if ($message): ?>
          <p class="account-flash account-flash--<?= $messageType === 'ok' ? 'ok' : 'error' ?>"><?= e($message) ?></p>
        <?php endif; ?>

        <div class="account-row">
          <div class="account-row__main"><?= $iconPerson ?><p><strong>Name:</strong> <?= e($user['full_name']) ?></p></div>
        </div>
        <div class="account-row account-row--split">
          <div class="account-row__main"><?= $iconGender ?><p><strong>Gender:</strong> <?= e($user['gender'] ?: '—') ?></p></div>
          <div class="account-row__main"><?= $iconCal ?><p><strong>Birthdate:</strong> <?= e($user['birthdate'] ? date('F j Y', strtotime($user['birthdate'])) : '—') ?></p></div>
        </div>
        <div class="account-row">
          <div class="account-row__main"><?= $iconAt ?><p><strong>Email:</strong> <?= e($user['email']) ?></p></div>
          <button class="icon-edit" type="button" aria-label="Edit email" data-edit="email"><?= $iconPencil ?></button>
        </div>
        <form class="account-edit" id="edit-email" method="post" hidden>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="email">
          <label class="visually-hidden" for="email">New email</label>
          <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
          <button class="btn btn--teal btn--inline" type="submit">Save</button>
        </form>
        <div class="account-row">
          <div class="account-row__main"><?= $iconKey ?><p><strong>Password:</strong> <?= str_repeat('•', 28) ?></p></div>
          <button class="icon-edit" type="button" aria-label="Edit password" data-edit="password"><?= $iconPencil ?></button>
        </div>
        <form class="account-edit" id="edit-password" method="post" hidden>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <label class="visually-hidden" for="password">New password</label>
          <input type="password" id="password" name="password" minlength="8" required>
          <button class="btn btn--teal btn--inline" type="submit">Save</button>
        </form>
        <?php if ($mentor): ?>
          <p class="account-empty" style="margin-top:1rem;">Assigned mentor: <?= e($mentor['full_name']) ?></p>
        <?php endif; ?>
        <form class="account-delete" method="post" onsubmit="return confirm('Delete this account and its records?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <button type="submit"><?= $iconBan ?> Delete Account</button>
        </form>

      <?php elseif ($section === 'progress'): ?>
        <h1>Progress</h1>
        <p>Mental Health Meter: <strong><?= (int) ($progress['mhm'] ?? 50) ?></strong> / 100</p>
        <div class="mhm-bar" aria-hidden="true"><span style="width:<?= (int) ($progress['mhm'] ?? 50) ?>%"></span></div>
        <p class="account-empty">Quest save slot: <?= e($progress['quest_id'] ?? 'not started') ?>. The meter updates while you play <a href="game.php">Mask</a>.</p>
        <p>Pre-test mean: <?= $pre ? e((string) $pre['mean_score']) : 'missing' ?></p>
        <p>Post-test: <?= $post ? e((string) $post['mean_score']) : 'not submitted' ?></p>
        <?php if (!empty($progress['completed']) && !$post): ?>
          <p><a href="post-test.php">Take the PHLOURISH post-test</a></p>
        <?php endif; ?>

      <?php elseif ($section === 'transcriptions'): ?>
        <h1>Transcriptions</h1>
        <p class="account-empty">Only you and the mentor on that call can read these. English, Filipino, or Taglish.</p>
        <?php if (!$transcripts): ?>
          <p class="account-empty">No call transcripts yet.</p>
        <?php endif; ?>
        <?php foreach ($transcripts as $row): ?>
          <article class="note-card">
            <h2><?= e(date('M j Y · g:i A', strtotime($row['created_at']))) ?> · <?= e($row['language']) ?></h2>
            <p><?= nl2br(e($row['transcript_text'])) ?></p>
          </article>
        <?php endforeach; ?>

      <?php else: ?>
        <h1>Mentees</h1>
        <?php if (!$mentees): ?>
          <p class="account-empty">No mentees assigned yet.</p>
        <?php endif; ?>
        <ul class="mentee-list">
          <?php foreach ($mentees as $row): ?>
            <li>
              <strong><?= e($row['full_name']) ?></strong>
              <span>MHM <?= (int) ($row['mhm'] ?? 50) ?> · <?= e($row['status']) ?><?= $row['last_call'] ? ' · last call ' . e(date('M j', strtotime($row['last_call']))) : '' ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</main>
<script>
  document.querySelectorAll('[data-edit]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var form = document.getElementById('edit-' + btn.getAttribute('data-edit'));
      if (form) form.hidden = !form.hidden;
    });
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
