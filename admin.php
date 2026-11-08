<?php
require __DIR__ . '/includes/auth.php';
$user = require_login(['admin', 'super_admin']);
$cfg = ph_config();
$threshold = (int) $cfg['mhm_flag_threshold'];

$statsStmt = db()->prepare(
    "SELECT
        COUNT(*) AS mentees,
        ROUND(AVG(g.mhm), 1) AS avg_mhm,
        SUM(CASE WHEN g.mhm < ? THEN 1 ELSE 0 END) AS below_threshold
     FROM users u
     JOIN game_progress g ON g.user_id = u.id
     WHERE u.role = 'mentee'"
);
$statsStmt->execute([$threshold]);
$stats = $statsStmt->fetch();

$trends = db()->query(
    "SELECT u.full_name, g.mhm, g.quest_id, g.updated_at
     FROM users u
     JOIN game_progress g ON g.user_id = u.id
     WHERE u.role = 'mentee'
     ORDER BY g.mhm ASC"
)->fetchAll();

$calls = db()->query(
    "SELECT c.id, c.language, c.status, c.duration_seconds, c.response_seconds,
            c.mhm_at_start, c.mhm_at_end, c.flagged, c.started_at, c.ended_at,
            mu.full_name AS mentee_name, me.full_name AS mentor_name
     FROM calls c
     JOIN users mu ON mu.id = c.user_id
     JOIN users me ON me.id = c.mentor_id
     ORDER BY c.created_at DESC
     LIMIT 50"
)->fetchAll();

$pageTitle = 'Admin';
$activeNav = 'admin';
$showCall = false;
$showGame = false;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal">
  <div class="admin-shell">
    <aside class="account-nav" aria-label="Admin">
      <a href="admin.php" class="is-active"><span class="chev" aria-hidden="true">›</span> MHM &amp; calls</a>
      <a href="logout.php"><span class="chev" aria-hidden="true">›</span> Logout</a>
    </aside>
    <section class="account-panel">
      <h1>Mental Health Meter</h1>
      <p class="account-empty">Aggregated mentee meters. Calls are flagged when MHM is below <?= $threshold ?>. Transcript text is not shown on this role.</p>
      <p>Mentees: <strong><?= (int) ($stats['mentees'] ?? 0) ?></strong> · Average MHM: <strong><?= e((string) ($stats['avg_mhm'] ?? '—')) ?></strong> · Below threshold: <strong><?= (int) ($stats['below_threshold'] ?? 0) ?></strong></p>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>Mentee</th><th>MHM</th><th>Quest save</th><th>Updated</th></tr></thead>
          <tbody>
            <?php foreach ($trends as $row): ?>
              <tr class="<?= (int) $row['mhm'] < $threshold ? 'is-flagged' : '' ?>">
                <td><?= e($row['full_name']) ?></td>
                <td><?= (int) $row['mhm'] ?></td>
                <td><?= e($row['quest_id'] ?: '—') ?></td>
                <td><?= e($row['updated_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <h2 id="reports" class="admin-subhead">Call metadata</h2>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>When</th><th>Mentee</th><th>Mentor</th><th>Lang</th>
              <th>Duration</th><th>Response</th><th>MHM</th><th>Flag</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($calls as $row): ?>
              <tr class="<?= $row['flagged'] ? 'is-flagged' : '' ?>">
                <td><?= e($row['started_at'] ?: '—') ?></td>
                <td><?= e($row['mentee_name']) ?></td>
                <td><?= e($row['mentor_name']) ?></td>
                <td><?= e($row['language']) ?></td>
                <td><?= (int) $row['duration_seconds'] ?>s</td>
                <td><?= (int) $row['response_seconds'] ?>s</td>
                <td><?= e((string) $row['mhm_at_start']) ?>→<?= e((string) ($row['mhm_at_end'] ?? '—')) ?></td>
                <td><?= $row['flagged'] ? 'Yes' : 'No' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
