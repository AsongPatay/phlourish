<?php
require __DIR__ . '/includes/auth.php';
$user = require_login(['mentee']);

$stmt = db()->prepare('SELECT * FROM game_progress WHERE user_id = ?');
$stmt->execute([$user['id']]);
$progress = $stmt->fetch() ?: ['mhm' => 50, 'quest_id' => null, 'completed' => 0];
$post = assessment_for((int) $user['id'], 'post');

$pageTitle = 'Game';
$activeNav = 'game';
$showGame = true;
$extraCss = ['assets/css/game.css'];
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal page--game">
  <div class="game-shell">
    <div class="game-meta">
      <div>
        <h1>Mask</h1>
        <p class="form-lead">WASD to move · E to talk · Esc for the in-game menu. Click the window first so the keys reach the game.</p>
      </div>
      <div class="game-meta__meter">
        <p>Mental Health Meter: <strong id="game-mhm-value"><?= (int) $progress['mhm'] ?></strong> / 100</p>
        <div class="mhm-bar" aria-hidden="true"><span id="game-mhm-bar" style="width:<?= (int) $progress['mhm'] ?>%"></span></div>
        <p class="game-meta__slot">Save: <span id="game-quest-label"><?= e($progress['quest_id'] ?: 'not started') ?></span></p>
      </div>
    </div>

    <div class="game-fit">
      <div class="game-window">
        <iframe
          id="game-frame"
          class="game-frame"
          src="game/index.html?v=20261111"
          title="PHLOURISH RPG"
          allow="autoplay; fullscreen; gamepad"
          tabindex="0"
        ></iframe>
      </div>
    </div>
    <p class="game-credits">
      SLEEP | Scott Buckley<br>
      SIMPLICITY | Scott Buckley<br>
      INTERVENTION | Scott Buckley<br>
      Colorless Aura | Kevin MacLeod<br>
      STATIC | Scott Buckley
    </p>

    <div class="game-footer-links" id="game-posttest" <?= (int) $progress['completed'] && !$post ? '' : 'hidden' ?>>
      <?php if (!$post): ?>
        <p><a class="btn btn--teal" href="post-test.php">Take post-test</a></p>
      <?php endif; ?>
    </div>
    <?php if ($post): ?>
      <p class="form-lead game-footer-note">Post-test submitted. Mean <?= e((string) $post['mean_score']) ?>.</p>
    <?php endif; ?>
    <p class="form-footer"><a href="call.php">Optional mentor call</a> · <a href="account.php?section=progress">Account progress</a></p>
  </div>
</main>
<script>
  window.PHLOURISH_GAME = {
    csrf: <?= json_encode(csrf_token()) ?>,
    hasPost: <?= $post ? 'true' : 'false' ?>
  };
</script>
<script src="assets/js/game.js?v=5"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
