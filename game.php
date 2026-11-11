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
      <div class="game-meta__title">
        <h1>Mask</h1>
      </div>
      <div class="game-meta__meter">
        <p>Mental Health Meter: <strong id="game-mhm-value"><?= (int) $progress['mhm'] ?></strong> / 100</p>
        <div class="mhm-bar" aria-hidden="true"><span id="game-mhm-bar" style="width:<?= (int) $progress['mhm'] ?>%"></span></div>
        <p class="game-meta__slot">Save: <span id="game-quest-label"><?= e($progress['quest_id'] ?: 'not started') ?></span></p>
      </div>
      <section class="game-howto" aria-labelledby="howto-heading">
        <h2 id="howto-heading">How to play</h2>
        <ul class="game-keys">
          <li><kbd>W A S D</kbd> <span>Move around</span></li>
          <li><kbd>E</kbd> <span>Talk / interact</span></li>
          <li><kbd>Esc</kbd> <span>In-game menu</span></li>
        </ul>
        <p class="game-howto__hint">Click the game window first so your keys reach the game.</p>
      </section>
    </div>

    <div class="game-fit">
      <div class="game-window">
        <iframe
          id="game-frame"
          class="game-frame"
          src="game/index.html?v=6669f1c"
          title="PHLOURISH RPG"
          allow="autoplay; fullscreen; gamepad"
          tabindex="0"
        ></iframe>
      </div>
    </div>

    <section class="game-about" aria-label="Game description and credits">
      <div class="game-about__block">
        <h2>Description</h2>
        <p class="game-about__quote">"Just act normal."</p>
        <p>To the rest of the campus, Alex is doing fine. He attends lectures, smiles at the right moments, and answers every "How are you?" with a practiced, seamless ease. But behind the perfect exterior, his life is a delicate house of cards.</p>
        <p>Masked is an intimate, story-driven game about the heavy cost of trying to fit in. As Alex navigates the overwhelming pressures of college academics, social anxiety, and a fractured sense of identity, you must choose how he copes. Every interaction requires a choice: do you play the part the world expects, or do you take the risk to let the mask slip?</p>
        <p class="game-about__subhead">Key features</p>
        <ul class="game-about__features">
          <li><strong>Relatable branching narrative:</strong> Navigate realistic college dilemmas, from crushing academic deadlines to complex roommate dynamics and late-night panic attacks.</li>
          <li><strong>A journey of unmasking:</strong> Multiple endings dictate Alex's fate. Will he collapse under the weight of his own performance, or find a safe space to show who he truly is?</li>
        </ul>
      </div>
      <div class="game-about__block">
        <h2>Music credits</h2>
        <ul class="game-about__credits">
          <li>SLEEP | Scott Buckley</li>
          <li>SIMPLICITY | Scott Buckley</li>
          <li>INTERVENTION | Scott Buckley</li>
          <li>Colorless Aura | Kevin MacLeod</li>
          <li>STATIC | Scott Buckley</li>
        </ul>
      </div>
      <div class="game-about__block">
        <h2>Spritework credits</h2>
        <ul class="game-about__credits">
          <li>LimeZu. (2023a). Modern Interiors - RPG Tileset [16x16] [Art asset pack]. itch.io.</li>
          <li>LimeZu. (2023b). Modern User Interface - RPG Asset Pack [16x16] [Art asset pack]. itch.io.</li>
          <li>LimeZu. (2026). Modern Exteriors - RPG Tileset [16x16] [Art asset pack]. itch.io.</li>
          <li>ErisEsra. (2026). Character Template Asset Pack [Art asset pack]. itch.io.</li>
        </ul>
      </div>
    </section>

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
<script src="assets/js/game.js?v=9"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
