<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mail.php';

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
                $exists = db()->prepare('SELECT * FROM student WHERE email = ?');
                $exists->execute([$email]);
                $existing = $exists->fetch();
                if ($existing && !empty($existing['email_verified_at'])) {
                    $error = 'That email is already registered.';
                } elseif ($existing && empty($existing['email_verified_at'])) {
                    db()->prepare(
                        'UPDATE student SET password_hash = ?, full_name = ?, gender = ?, birthdate = ? WHERE id = ?'
                    )->execute([
                        password_hash($password, PASSWORD_DEFAULT),
                        $fullName,
                        $gender,
                        $birthdate,
                        (int) $existing['id'],
                    ]);
                    ph_issue_email_verification((int) $existing['id'], $email, $fullName);
                    audit_log('resend_verify', 'user', (int) $existing['id']);
                    redirect('check-email.php');
                } else {
                    $mentorId = least_loaded_mentor_id();
                    db()->beginTransaction();
                    $ins = db()->prepare(
                        'INSERT INTO student (email, password_hash, role, full_name, gender, birthdate, mentor_id)
                         VALUES (?, ?, \'mentee\', ?, ?, ?, ?)'
                    );
                    $ins->execute([$email, password_hash($password, PASSWORD_DEFAULT), $fullName, $gender, $birthdate, $mentorId]);
                    $studentId = (int) db()->lastInsertId();
                    db()->prepare('INSERT INTO game_progress (student_id, mhm) VALUES (?, 50)')->execute([$studentId]);
                    $mean = array_sum($answers) / count($answers);
                    db()->prepare(
                        'INSERT INTO assessments (student_id, type, answers, mean_score) VALUES (?, \'pre\', ?, ?)'
                    )->execute([$studentId, json_encode($answers), $mean]);
                    db()->commit();

                    ph_issue_email_verification($studentId, $email, $fullName);
                    audit_log('register', 'student', $studentId);
                    redirect('check-email.php');
                }
            } catch (Throwable $e) {
                if (db()->inTransaction()) {
                    db()->rollBack();
                }

                $dbError = $e instanceof PDOException ? $e->getMessage() : $e->getMessage();
                error_log('SIGNUP DB ERROR: ' . $dbError);
                $debugLog = __DIR__ . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'signup-debug.log';
                @mkdir(dirname($debugLog), 0775, true);
                @file_put_contents($debugLog, date('c') . ' ' . $dbError . PHP_EOL, FILE_APPEND | LOCK_EX);
                $error = 'Database error: ' . $dbError;
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
    <p class="form-lead">Create a mentee account with your email. We will send a confirmation link, then you can log in. Registration is for ages 18–24.</p>
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
