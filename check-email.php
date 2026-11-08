<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail.php';

$message = '';
$error = '';
$email = strtolower(trim($_SESSION['verify_email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Please try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? $email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter the email you used to sign up.';
        } else {
            $last = (int) ($_SESSION['verify_resend_at'] ?? 0);
            if (time() - $last < 30) {
                $error = 'Please wait a few seconds before requesting another email.';
            } else {
                try {
                    $stmt = db()->prepare('SELECT * FROM student WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $row = $stmt->fetch();
                    if ($row && empty($row['email_verified_at'])) {
                        ph_issue_email_verification((int) $row['id'], $email, $row['full_name']);
                        $_SESSION['verify_resend_at'] = time();
                    } else {
                        $_SESSION['verify_email'] = $email;
                        $_SESSION['verify_link'] = null;
                    }
                    $message = 'If that email still needs confirmation, we sent a new link.';
                } catch (Throwable $e) {
                    // Installation gate removed: the app now connects directly to the configured PostgreSQL database.
                    // $error = 'Database is not ready. Open install.php first (MySQL must be running).';
                }
            }
        }
    }
}

$verifyLink = $_SESSION['verify_link'] ?? null;
$pageTitle = 'Confirm email';
$activeNav = 'signup';
$showCall = false;
$showGame = false;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal">
  <form class="card-mentor" method="post">
    <h1>Confirm your email</h1>
    <?= csrf_field() ?>
    <p class="form-lead">We sent a confirmation link to finish sign-up. Check your inbox, then log in.</p>
    <?php if ($error): ?>
      <p class="form-footer" style="margin-top:0;margin-bottom:1rem;color:#c0392b;"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
      <p class="form-footer form-success" style="margin-top:0;margin-bottom:1rem;"><?= e($message) ?></p>
    <?php endif; ?>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required value="<?= e($email) ?>">
    </div>
    <?php if ($verifyLink): ?>
      <p class="form-lead verify-fallback">XAMPP often cannot send mail. Use this confirmation link for local testing: <a href="<?= e($verifyLink) ?>">Confirm email</a></p>
    <?php endif; ?>
    <button class="btn btn--teal" type="submit">Resend email</button>
    <p class="form-footer"><a href="login.php">Back to login</a></p>
  </form>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
