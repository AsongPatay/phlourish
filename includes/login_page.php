<?php
require_once __DIR__ . '/auth.php';

function render_login_page(string $title, array $roles, string $heading, string $cardClass = 'card-mentor'): void
{
    $error = '';
    $notice = '';
    $unverified = false;
    if (!empty($_GET['timeout'])) {
        $error = 'Your session expired. Please log in again.';
    }
    if (!empty($_GET['verified'])) {
        $notice = 'Email confirmed. You can log in now.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_verify()) {
            $error = 'Please try again.';
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $mfa = trim($_POST['mfa_code'] ?? '');
            try {
                if (!empty($_SESSION['mfa_pending_id']) && in_array('super_admin', $roles, true)) {
                    $stmt = db()->prepare('SELECT * FROM student WHERE id = ? AND role = \'super_admin\' LIMIT 1');
                    $stmt->execute([(int) $_SESSION['mfa_pending_id']]);
                    $row = $stmt->fetch();
                    if (!$row || empty($row['mfa_code_hash']) || !password_verify($mfa, $row['mfa_code_hash'])) {
                        $error = 'That security code is incorrect.';
                    } else {
                        unset($_SESSION['mfa_pending_id']);
                        ph_login_user($row);
                        audit_log('login_mfa', 'user', (int) $row['id']);
                        redirect(home_for_role($row['role']));
                    }
                } else {
                    $result = ph_authenticate($email, $password, $roles);
                    if (!$result['ok']) {
                        $error = $result['error'];
                        if (!empty($result['unverified'])) {
                            $unverified = true;
                            $_SESSION['verify_email'] = strtolower(trim($email));
                        }
                    } elseif ($result['user']['role'] === 'super_admin' && !empty($result['user']['mfa_code_hash'])) {
                        session_regenerate_id(true);
                        $_SESSION['mfa_pending_id'] = (int) $result['user']['id'];
                    } else {
                        ph_login_user($result['user']);
                        audit_log('login', 'user', (int) $result['user']['id']);
                        redirect(home_for_role($result['user']['role']));
                    }
                }
            } catch (Throwable $e) {
                // Installation gate removed: the app now uses the configured PostgreSQL connection directly.
                // $error = 'Database is not ready. Open install.php first (MySQL must be running).';
            }
        }
    }

    $mfaPending = !empty($_SESSION['mfa_pending_id']) && in_array('super_admin', $roles, true);
    $pageTitle = $title;
    $activeNav = 'login';
    $showCall = false;
    $showGame = false;
    require __DIR__ . '/head.php';
    require __DIR__ . '/header.php';
    ?>
<main class="page<?= in_array($cardClass, ['card-split', 'card-mentor', 'card-wide'], true) ? ' page--teal' : '' ?>">
    <form class="<?= e($cardClass === 'card-split' ? 'card-mentor' : $cardClass) ?>" method="post" autocomplete="on">
      <h1><?= e($heading) ?></h1>
      <?= csrf_field() ?>
      <?php if ($notice): ?>
        <p class="form-footer form-success" style="margin-top:0;margin-bottom:1rem;"><?= e($notice) ?></p>
      <?php endif; ?>
      <?php if ($error): ?>
        <p class="form-footer" style="margin-top:0;margin-bottom:1rem;color:<?= $cardClass === 'card-admin' ? '#fff' : '#c0392b' ?>;"><?= e($error) ?></p>
        <?php if ($unverified): ?>
          <p class="form-footer" style="margin-top:0;margin-bottom:1rem;"><a href="check-email.php">Resend verification email</a></p>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($mfaPending): ?>
        <p class="form-lead">Enter the Super Admin security code.</p>
        <div class="field">
          <label for="mfa_code">Security code</label>
          <input id="mfa_code" name="mfa_code" inputmode="numeric" autocomplete="one-time-code" required>
        </div>
      <?php else: ?>
        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
      <?php endif; ?>
      <button class="btn <?= $cardClass === 'card-admin' ? 'btn--white' : 'btn--teal' ?>" type="submit"><?= $mfaPending ? 'Verify' : 'Login' ?></button>
      <p class="form-footer">
        <?php if (in_array('mentee', $roles, true) && !$mfaPending): ?>
          New here? <a href="signup.php">Sign up with email</a><br>
        <?php endif; ?>
        <a href="login.php">Mentee</a> ·
        <a href="mentor-login.php">Mentor</a> ·
        <a href="admin-login.php">Admin</a> ·
        <a href="super-admin-login.php">Super admin</a>
      </p>
    </form>
</main>
    <?php
    require __DIR__ . '/footer.php';
}
