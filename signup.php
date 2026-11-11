<?php
require __DIR__ . '/includes/auth.php';

$error = '';
$questions = pretest_questions();
$cfg = ph_config();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $gender = trim($_POST['gender'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $age = age_from_birthdate($birthdate);
        $answers = [];
        foreach (array_keys($questions) as $key) {
            $answers[$key] = (int) ($_POST[$key] ?? 0);
        }

        if ($fullName === '' || $email === '' || $password === '' || $confirm === '' || $birthdate === '') {
            $error = 'Please complete registration and the pre-test.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($age === null || $age < $cfg['mentee_min_age'] || $age > $cfg['mentee_max_age']) {
            $error = 'Registration is for Filipino young adults aged 18–24.';
        } elseif (in_array(0, $answers, true)) {
            $error = 'Please answer every pre-test item.';
        } else {
            try {
                $pdo = db();
                $exists = db()->prepare('SELECT * FROM users WHERE email = ?');
                $exists->execute([$email]);
                $existing = $exists->fetch();
                if ($existing && !empty($existing['email_verified_at'])) {
                    $error = 'That email is already registered.';
                } elseif ($existing && empty($existing['email_verified_at'])) {
                    db()->prepare(
                        'UPDATE users
                         SET password_hash = ?, full_name = ?, gender = ?, birthdate = ?,
                             email_verified_at = COALESCE(email_verified_at, NOW()),
                             email_verify_hash = NULL, email_verify_expires = NULL
                         WHERE id = ?'
                    )->execute([
                        password_hash($password, PASSWORD_DEFAULT),
                        $fullName,
                        $gender,
                        $birthdate,
                        (int) $existing['id'],
                    ]);
                    audit_log('register', 'user', (int) $existing['id']);
                    redirect('login.php?registered=1');
                } else {
                    $mentorId = least_loaded_mentor_id();
                    db()->beginTransaction();
                    $ins = db()->prepare(
                        'INSERT INTO users (
                            email, password_hash, role, full_name, gender, birthdate, mentor_id, email_verified_at
                         ) VALUES (?, ?, \'mentee\', ?, ?, ?, ?, NOW()) RETURNING id'
                    );
                    $ins->execute([$email, password_hash($password, PASSWORD_DEFAULT), $fullName, $gender, $birthdate, $mentorId]);
                    $userId = (int) $ins->fetchColumn();
                    db()->prepare('INSERT INTO game_progress (user_id, mhm) VALUES (?, 50)')->execute([$userId]);
                    $mean = array_sum($answers) / count($answers);
                    db()->prepare(
                        'INSERT INTO assessments (user_id, type, answers, mean_score) VALUES (?, \'pre\', ?, ?)'
                    )->execute([$userId, json_encode($answers), $mean]);
                    db()->commit();

                    audit_log('register', 'user', $userId);
                    redirect('login.php?registered=1');
                }
            } catch (Throwable $e) {
                if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Signup failed: ' . $e->getMessage());
                $error = 'We could not complete registration right now. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Sign Up';
$activeNav = 'signup';
$showCall = false;
$showGame = false;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal">
  <form class="card-wide" method="post">
    <h1>Register with PHLOURISH</h1>
    <p class="form-lead">Create a mentee account with your email and log in right away. Registration is for ages 18–24.</p>
    <?= csrf_field() ?>
    <?php if ($error): ?>
      <p class="form-footer" style="margin-top:0;margin-bottom:1rem;color:#c0392b;"><?= e($error) ?></p>
    <?php endif; ?>

    <div class="field">
      <label for="full_name">Full name</label>
      <input id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
    </div>
    <div class="field-row">
      <div class="field">
        <label for="birthdate">Birthdate</label>
        <input id="birthdate" name="birthdate" type="date" required value="<?= e($_POST['birthdate'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
          <?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?>
            <option value="<?= e($option) ?>" <?= (($_POST['gender'] ?? '') === $option) ? 'selected' : '' ?>><?= e($option) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    </div>
    <div class="field-row">
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="8" required>
      </div>
      <div class="field">
        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
      </div>
    </div>

    <h2 class="pretest-title">Pre-test</h2>
    <p class="form-lead">1 = Never / Strongly disagree · 5 = Always / Strongly agree</p>
    <?php foreach ($questions as $key => $label): ?>
      <fieldset class="likert">
        <legend><?= e($label) ?></legend>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <label>
            <input type="radio" name="<?= e($key) ?>" value="<?= $i ?>" <?= (string) ($_POST[$key] ?? '') === (string) $i ? 'checked' : '' ?> required>
            <?= $i ?>
          </label>
        <?php endfor; ?>
      </fieldset>
    <?php endforeach; ?>

    <button class="btn btn--teal" type="submit">Create account</button>
    <p class="form-footer">Have an account? <a href="login.php">Login</a>.</p>
  </form>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
