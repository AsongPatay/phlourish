<?php
$pageTitle = 'Settings';
$activeNav = 'game';
$showCall = true;
$showGame = true;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal">
  <div class="settings-card">
    <a class="settings-close" href="game.php?state=pause" aria-label="Close settings">
      <svg viewBox="0 0 32 32" width="40" height="40" aria-hidden="true">
        <rect x="2" y="2" width="28" height="28" fill="#4a4a58" stroke="#2c2c36" stroke-width="2"/>
        <path d="M9 9l14 14M23 9L9 23" stroke="#d8d8e0" stroke-width="3" stroke-linecap="square"/>
      </svg>
    </a>

    <h1>Settings</h1>

    <div class="settings-block">
      <h2>Audio</h2>
      <div class="settings-row">
        <label for="music">Music</label>
        <input id="music" type="range" min="0" max="100" value="62" />
      </div>
      <div class="settings-row">
        <label for="sfx">Sound effects</label>
        <input id="sfx" type="range" min="0" max="100" value="62" />
      </div>
    </div>

    <div class="settings-block">
      <h2>Font</h2>
      <div class="settings-row">
        <span>Font Size</span>
        <div class="font-stepper" role="group" aria-label="Font size">
          <button type="button" id="font-prev" aria-label="Smaller">‹</button>
          <span id="font-label">Medium</span>
          <button type="button" id="font-next" aria-label="Larger">›</button>
        </div>
      </div>
    </div>

    <div class="settings-block">
      <h2>Controls</h2>
      <div class="controls-grid">
        <div class="controls-col">
          <p>Movement</p>
          <div class="wasd" aria-hidden="true">
            <span class="pixel-key pixel-key--w">W</span>
            <span class="pixel-key pixel-key--a">A</span>
            <span class="pixel-key pixel-key--s">S</span>
            <span class="pixel-key pixel-key--d">D</span>
          </div>
        </div>
        <div class="controls-col">
          <p>Interact</p>
          <span class="pixel-key">E</span>
        </div>
        <div class="controls-col">
          <p>Movement/Interact</p>
          <svg class="pixel-mouse" viewBox="0 0 64 80" width="52" height="64" aria-hidden="true">
            <rect x="10" y="8" width="44" height="64" rx="18" fill="#8b8b96" stroke="#3a3a46" stroke-width="3"/>
            <rect x="12" y="10" width="18" height="22" rx="8" fill="#c8c8d2"/>
            <rect x="32" y="10" width="18" height="22" rx="8" fill="#6f6f7c"/>
            <rect x="29" y="12" width="6" height="16" rx="2" fill="#3a3a46"/>
          </svg>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
  (function () {
    var sizes = ['Small', 'Medium', 'Large'];
    var index = 1;
    var label = document.getElementById('font-label');
    function render() {
      label.textContent = sizes[index];
    }
    document.getElementById('font-prev').addEventListener('click', function () {
      index = (index + sizes.length - 1) % sizes.length;
      render();
    });
    document.getElementById('font-next').addEventListener('click', function () {
      index = (index + 1) % sizes.length;
      render();
    });
  })();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
