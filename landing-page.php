<?php
require __DIR__ . '/includes/auth.php';
$user = ph_user();
$callReady = $user && in_array($user['role'], ['mentee', 'mentor'], true);
$callCtaHref = $callReady ? 'call.php' : 'signup.php';
$callCtaLabel = $callReady ? 'Open call' : 'Get started';
$pageTitle = 'Home';
$extraCss = ['assets/css/landingpage-style.css'];
require __DIR__ . '/includes/head.php';
?>

<div class="navbar">
  <div class="navbar-container">
    <div class="org-logo">
      <a class="phlourish-logo" href="landing-page.php">
        PHL<img class="logo-icon" src="assets/img/logo.svg" alt="" width="22" height="22">URISH
      </a>
    </div>
    <div class="nav-buttons">
      <nav>
        <ul>
          <li><a href="landing-page.php" class="is-active">Home</a></li>
          <li><a href="game.php">Game</a></li>
          <li><a href="call.php">Call</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="signup.php">Sign Up</a></li>
        </ul>
      </nav>
    </div>
  </div>
</div>

<div class="hero">
  <div class="hero-container">
    <div class="hero-container-image">
      <div class="hero-container-info">
        <div class="hero-container-title"><h1>Meet the cast. Play your story.</h1></div>
        <div class="hero-container-text">
          <p>PHLOURISH is a story-driven game about campus life, quiet pressure, and the people who pass through your day. Browse the roster below — no spoilers, just first impressions — then jump in when you are ready.</p>
        </div>
        <div class="hero-container-button">
          <a class="joinnow-button" href="signup.php">Join Now</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="info" id="about">
  <div class="info-white-container">
    <div class="left-character-list">
      <ul>
        <li data-index="0"></li>
        <li data-index="1"></li>
        <li data-index="2"></li>
        <li data-index="3"></li>
        <li data-index="4"></li>
        <li data-index="5"></li>
        <li data-index="6"></li>
      </ul>
    </div>
    <div class="right-character-information-container">
      <div class="character-image"></div>
      <div class="character-name"></div>
      <div class="character-bio"></div>
    </div>
  </div>
</div>

<div class="call-section-container" id="call">
  <div class="call-section-information-container">
    <div class="call-information">
      <h1>Discuss with your mentor today!</h1>
      <a class="joinnow-button joinnow-button--on-teal" href="<?= e($callCtaHref) ?>"><?= e($callCtaLabel) ?></a>
    </div>
  </div>
</div>

<footer class="landing-footer">
  <p>PHLOURISH Mental Health Initiative Inc.</p>
</footer>

<script src="assets/js/landing-master-detail.js?v=5"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
