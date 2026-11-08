<?php
/**
 * Shared site header.
 *
 * @var string $activeNav  Current nav key: home|game|call|login|account|admin
 * @var bool   $showCall   Whether to show the Call link (default true)
 * @var bool   $showGame   Whether to show the Game link (default false)
 * @var string $authNav    Last nav item: login|account
 */
require_once __DIR__ . '/auth.php';
$user = ph_user();
$activeNav = $activeNav ?? 'login';
$showCall = $showCall ?? true;
$showGame = $showGame ?? false;
$authNav = $authNav ?? 'login';

if ($user) {
    $authNav = 'account';
    if ($user['role'] === 'mentee') {
        $showGame = true;
        $showCall = true;
    } elseif ($user['role'] === 'mentor') {
        $showCall = true;
    } elseif ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
        $showGame = false;
        $showCall = false;
    }
}
?>
<header class="site-header">
  <a class="logo" href="landing-page.php" aria-label="PHLOURISH home">
    <span class="logo-wordmark">
      PHL<img class="logo-icon" src="assets/img/logo.svg" alt="" width="22" height="22">URISH
    </span>
    <span class="logo-tagline">Mental Health Initiative Inc.</span>
  </a>
  <nav class="nav" aria-label="Primary">
    <a href="landing-page.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Home</a>
    <?php if ($showGame): ?>
      <a href="game.php" class="<?= $activeNav === 'game' ? 'active' : '' ?>">Game</a>
    <?php endif; ?>
    <?php if ($showCall): ?>
      <a href="call.php" class="<?= $activeNav === 'call' ? 'active' : '' ?>">Call</a>
    <?php endif; ?>
    <?php if ($user && $user['role'] === 'admin'): ?>
      <a href="admin.php" class="<?= $activeNav === 'admin' ? 'active' : '' ?>">Admin</a>
    <?php endif; ?>
    <?php if ($user && $user['role'] === 'super_admin'): ?>
      <a href="super-admin.php" class="<?= $activeNav === 'admin' ? 'active' : '' ?>">Super admin</a>
    <?php endif; ?>
    <?php if ($authNav === 'account'): ?>
      <a href="account.php" class="<?= $activeNav === 'account' ? 'active' : '' ?>">Account</a>
    <?php else: ?>
      <a href="signup.php" class="<?= $activeNav === 'signup' ? 'active' : '' ?>">Sign up</a>
      <a href="login.php" class="<?= $activeNav === 'login' ? 'active' : '' ?>">Login</a>
    <?php endif; ?>
  </nav>
</header>
