<?php
require __DIR__ . '/includes/auth.php';
$user = require_login(['mentee', 'mentor']);
$cfg = ph_config();

if (isset($_GET['ajax'])) {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $callId = (int) ($_POST['call_id'] ?? $_GET['call_id'] ?? 0);

    if ($action === 'open_calls' && $user['role'] === 'mentor') {
        $stmt = db()->prepare(
            'SELECT c.id, c.language, c.started_at, u.full_name
             FROM calls c JOIN users u ON u.id = c.user_id
             WHERE c.mentor_id = ? AND c.status <> \'ended\'
             ORDER BY c.created_at DESC'
        );
        $stmt->execute([$user['id']]);
        json_response(['ok' => true, 'calls' => $stmt->fetchAll()]);
    }

    if ($action === 'start' && $user['role'] === 'mentee') {
        if (!csrf_verify()) {
            json_response(['ok' => false, 'error' => 'Invalid session.'], 400);
        }
        $mentorId = (int) ($user['mentor_id'] ?? 0);
        if ($mentorId < 1) {
            json_response(['ok' => false, 'error' => 'No mentor is assigned yet.'], 400);
        }
        $lang = $_POST['language'] ?? 'english';
        if (!in_array($lang, ['english', 'filipino', 'taglish'], true)) {
            $lang = 'english';
        }
        $mhm = current_mhm((int) $user['id']);
        $stmt = db()->prepare(
            'INSERT INTO calls (user_id, mentor_id, status, language, started_at, mhm_at_start)
             VALUES (?, ?, \'active\', ?, NOW(), ?)'
        );
        $stmt->execute([$user['id'], $mentorId, $lang, $mhm]);
        json_response([
            'ok' => true,
            'call_id' => (int) db()->lastInsertId(),
            'language' => $lang,
        ]);
    }

    if ($action === 'join' && $user['role'] === 'mentor') {
        if (!csrf_verify()) {
            json_response(['ok' => false, 'error' => 'Invalid session.'], 400);
        }
        $call = call_for_user($callId, $user);
        if (!$call || $call['status'] === 'ended') {
            json_response(['ok' => false, 'error' => 'Call not found.'], 404);
        }
        db()->prepare(
            'UPDATE calls SET mentor_joined_at = NOW(),
             response_seconds = EXTRACT(EPOCH FROM (NOW() - created_at))::int
             WHERE id = ? AND mentor_id = ?'
        )->execute([$callId, $user['id']]);
        json_response(['ok' => true, 'language' => $call['language']]);
    }

    if ($action === 'line' && $callId) {
        if (!csrf_verify()) {
            json_response(['ok' => false], 400);
        }
        $call = call_for_user($callId, $user);
        if (!$call || $call['status'] === 'ended') {
            json_response(['ok' => false], 404);
        }
        $text = trim($_POST['text'] ?? '');
        if ($text === '') {
            json_response(['ok' => false]);
        }
        $role = $user['role'] === 'mentor' ? 'mentor' : 'mentee';
        db()->prepare(
            'INSERT INTO call_lines (call_id, speaker_id, speaker_role, line_text) VALUES (?, ?, ?, ?)'
        )->execute([$callId, $user['id'], $role, $text]);
        json_response(['ok' => true]);
    }

    if ($action === 'poll' && $callId) {
        $call = call_for_user($callId, $user);
        if (!$call) {
            json_response(['ok' => false], 404);
        }
        $after = (int) ($_GET['after'] ?? 0);
        $stmt = db()->prepare(
            'SELECT l.id, l.speaker_role, l.line_text, u.full_name
             FROM call_lines l JOIN users u ON u.id = l.speaker_id
             WHERE l.call_id = ? AND l.id > ? ORDER BY l.id ASC'
        );
        $stmt->execute([$callId, $after]);
        json_response(['ok' => true, 'status' => $call['status'], 'lines' => $stmt->fetchAll()]);
    }

    if ($action === 'end' && $callId) {
        if (!csrf_verify()) {
            json_response(['ok' => false], 400);
        }
        $call = call_for_user($callId, $user);
        if (!$call || $call['status'] === 'ended') {
            json_response(['ok' => false], 404);
        }
        $lines = db()->prepare(
            'SELECT speaker_role, line_text FROM call_lines WHERE call_id = ? ORDER BY id'
        );
        $lines->execute([$callId]);
        $text = '';
        foreach ($lines as $line) {
            $label = $line['speaker_role'] === 'mentor' ? 'Mentor' : 'Mentee';
            $text .= $label . ': ' . $line['line_text'] . "\n";
        }
        $note = trim($_POST['note'] ?? '');
        if ($note !== '') {
            $text .= ($text === '' ? '' : "\n") . 'Note: ' . $note;
        }
        $clientTranscript = trim($_POST['transcript'] ?? '');
        if ($text === '' && $clientTranscript !== '') {
            $text = $clientTranscript;
        }
        if (trim($text) === '') {
            $text = '(No speech was captured. Transcription may be unavailable.)';
        }
        $mhmEnd = current_mhm((int) $call['user_id']);
        $flagged = $mhmEnd < (int) $cfg['mhm_flag_threshold'] ? 1 : 0;
        db()->prepare(
            'UPDATE calls SET status = \'ended\', ended_at = NOW(),
             duration_seconds = EXTRACT(EPOCH FROM (NOW() - started_at))::int,
             mhm_at_end = ?, flagged = ? WHERE id = ?'
        )->execute([$mhmEnd, $flagged, $callId]);
        $exists = db()->prepare('SELECT id FROM call_transcriptions WHERE call_id = ?');
        $exists->execute([$callId]);
        if (!$exists->fetch()) {
            db()->prepare(
                'INSERT INTO call_transcriptions (call_id, user_id, mentor_id, transcript_text, language)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$callId, $call['user_id'], $call['mentor_id'], $text, $call['language']]);
        }
        json_response(['ok' => true, 'flagged' => (bool) $flagged]);
    }

    json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
}

$mentor = $user['role'] === 'mentee' && $user['mentor_id'] ? assigned_mentor((int) $user['mentor_id']) : null;
$openCalls = [];
if ($user['role'] === 'mentor') {
    $stmt = db()->prepare(
        'SELECT c.id, c.language, c.started_at, u.full_name
         FROM calls c JOIN users u ON u.id = c.user_id
         WHERE c.mentor_id = ? AND c.status <> \'ended\'
         ORDER BY c.created_at DESC'
    );
    $stmt->execute([$user['id']]);
    $openCalls = $stmt->fetchAll();
}

$isMentor = $user['role'] === 'mentor';
$peerLabel = $isMentor ? 'Mentee' : ($mentor['full_name'] ?? 'Mentor');
$selfInitials = initials_from_name($user['full_name'] ?? 'U');
$peerInitials = initials_from_name($peerLabel);
$roleClass = $isMentor ? 'call-stage--mentor' : 'call-stage--mentee';
$modelEn = is_file(__DIR__ . '/assets/models/vosk-model-en-us-0.22-lgraph.tar.gz');
$modelFil = is_file(__DIR__ . '/assets/models/vosk-model-tl-ph-generic-0.6.tar.gz');

$pageTitle = $isMentor ? 'Mentor desk' : 'Call mentor';
$activeNav = 'call';
$showCall = true;
$showGame = $user['role'] === 'mentee';
$extraCss = ['assets/css/call.css'];
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="page page--teal call-page <?= $isMentor ? 'call-page--mentor' : 'call-page--mentee' ?>">
  <div class="call-shell <?= $isMentor ? 'call-shell--mentor' : 'call-shell--mentee' ?>">
    <div class="call-stage <?= e($roleClass) ?>" id="call-root"
         data-role="<?= e($user['role']) ?>"
         data-name="<?= e($user['full_name']) ?>"
         data-csrf="<?= e(csrf_token()) ?>"
         data-signaling="<?= e($cfg['signaling_ws_url'] ?? 'ws://localhost:8080') ?>"
         data-mentor="<?= e($mentor['full_name'] ?? 'Your mentor') ?>"
         data-peer-label="<?= e($peerLabel) ?>">

      <?php if (!$isMentor): ?>
        <!-- MENTEE: soft focus on mentor + start CTA -->
        <div class="call-mentee">
          <header class="call-mentee__hero">
            <p class="call-mentee__eyebrow">Support call</p>
            <h1 class="call-mentee__title">Talk with your mentor</h1>
            <p class="call-mentee__lead" id="call-status">
              <?= $mentor
                ? 'Reach out to ' . e($mentor['full_name']) . ' for a private audio check-in. Captions stay on your device.'
                : 'A mentor must be assigned before you can call.' ?>
            </p>
          </header>

          <div class="call-mentee__stage">
            <div class="call-avatar call-avatar--mentor-focus" id="call-peer-avatar" data-state="idle">
              <span class="call-avatar__ring" aria-hidden="true"></span>
              <span class="call-avatar__face" id="call-peer-initials"><?= e($peerInitials) ?></span>
              <span class="call-avatar__role">Mentor</span>
              <span class="call-avatar__label" id="call-peer-name"><?= e($peerLabel) ?></span>
            </div>
            <div class="call-avatar call-avatar--self-chip" id="call-self-avatar" data-state="idle">
              <span class="call-avatar__ring" aria-hidden="true"></span>
              <span class="call-avatar__face"><?= e($selfInitials) ?></span>
              <span class="call-avatar__label" id="call-self-label">You</span>
            </div>
          </div>

          <div class="call-mentee__meta">
            <span class="call-badge" id="call-conn-badge">Ready</span>
            <span class="call-timer" id="call-timer" hidden>00:00</span>
          </div>

          <section class="call-captions-panel call-captions-panel--mentee" aria-label="Live captions">
            <header class="call-captions-panel__head">
              <h2>Live captions</h2>
              <span id="call-caption-hint">On-device only</span>
            </header>
            <div class="call-captions" id="call-captions" aria-live="polite">
              <p class="call-captions__empty" id="call-captions-empty">When you start, captions will show here.</p>
            </div>
          </section>

          <audio id="remote-audio" autoplay playsinline></audio>

          <div class="call-bar call-bar--mentee">
            <?php if ($mentor): ?>
              <label class="call-lang">
                <span class="call-lang__label">Language</span>
                <select id="call-language" aria-label="Caption language">
                  <option value="english">English</option>
                  <option value="filipino">Filipino</option>
                  <option value="taglish">Taglish</option>
                </select>
              </label>
              <button type="button" class="call-ctrl call-ctrl--primary call-ctrl--wide" id="btn-start">Start call</button>
            <?php endif; ?>
            <button type="button" class="call-ctrl" id="btn-mute" hidden aria-pressed="false">Mute</button>
            <button type="button" class="call-ctrl" id="btn-download" hidden disabled>Save .txt</button>
            <button type="button" class="call-ctrl call-ctrl--end" id="btn-end" hidden>End</button>
          </div>
        </div>

      <?php else: ?>
        <!-- MENTOR: desk / inbox console -->
        <div class="call-mentor">
          <aside class="call-inbox" aria-label="Incoming calls">
            <header class="call-inbox__head">
              <div>
                <p class="call-inbox__eyebrow">Mentor desk</p>
                <h1 class="call-inbox__title">Incoming</h1>
              </div>
              <span class="call-inbox__count" id="call-inbox-count"><?= count($openCalls) ?></span>
            </header>
            <p class="call-inbox__hint" id="call-waiting" <?= $openCalls ? 'hidden' : '' ?>>
              No mentees waiting. This list updates automatically.
            </p>
            <ul class="call-inbox__list" id="call-inbox-list" <?= $openCalls ? '' : 'hidden' ?>>
              <?php foreach ($openCalls as $i => $row): ?>
                <li>
                  <button type="button"
                          class="call-inbox__item<?= $i === 0 ? ' is-selected' : '' ?>"
                          data-call-id="<?= (int) $row['id'] ?>"
                          data-language="<?= e($row['language']) ?>"
                          data-name="<?= e($row['full_name']) ?>">
                    <span class="call-inbox__avatar"><?= e(initials_from_name($row['full_name'])) ?></span>
                    <span class="call-inbox__meta">
                      <span class="call-inbox__name"><?= e($row['full_name']) ?></span>
                      <span class="call-inbox__sub"><?= e(ucfirst($row['language'])) ?> · waiting</span>
                    </span>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
            <div class="call-inbox__actions" id="call-join-wrap" <?= $openCalls ? '' : 'hidden' ?>>
              <select id="call-pick" class="visually-hidden" aria-hidden="true" tabindex="-1">
                <?php foreach ($openCalls as $row): ?>
                  <option value="<?= (int) $row['id'] ?>" data-language="<?= e($row['language']) ?>">
                    <?= e($row['full_name'] . ' · ' . $row['language']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="call-ctrl call-ctrl--primary call-ctrl--wide" id="btn-join">Join selected</button>
            </div>
          </aside>

          <section class="call-desk">
            <header class="call-desk__head">
              <div class="call-desk__who">
                <div class="call-avatar call-avatar--desk-peer" id="call-peer-avatar" data-state="idle">
                  <span class="call-avatar__ring" aria-hidden="true"></span>
                  <span class="call-avatar__face" id="call-peer-initials"><?= e($openCalls ? initials_from_name($openCalls[0]['full_name']) : '–') ?></span>
                </div>
                <div>
                  <p class="call-desk__role">Mentee</p>
                  <h2 class="call-desk__name" id="call-peer-name"><?= e($openCalls[0]['full_name'] ?? 'Waiting for mentee') ?></h2>
                </div>
              </div>
              <div class="call-desk__status">
                <span class="call-badge" id="call-conn-badge">Standby</span>
                <span class="call-timer" id="call-timer" hidden>00:00</span>
              </div>
            </header>

            <p class="call-desk__status-text" id="call-status">
              Select a waiting mentee from the inbox, then join to connect with live audio and captions.
            </p>

            <div class="call-desk__self" id="call-self-avatar" data-state="idle">
              <span class="call-avatar__face call-avatar__face--sm"><?= e($selfInitials) ?></span>
              <span id="call-self-label">You · mentor</span>
            </div>

            <section class="call-captions-panel call-captions-panel--mentor" aria-label="Session transcript">
              <header class="call-captions-panel__head">
                <h2>Session transcript</h2>
                <span id="call-caption-hint">Live captions for notes</span>
              </header>
              <div class="call-captions" id="call-captions" aria-live="polite">
                <p class="call-captions__empty" id="call-captions-empty">Join a call to see live captions here.</p>
              </div>
            </section>

            <audio id="remote-audio" autoplay playsinline></audio>

            <div class="call-bar call-bar--mentor">
              <button type="button" class="call-ctrl" id="btn-mute" hidden aria-pressed="false">Mute</button>
              <button type="button" class="call-ctrl" id="btn-download" hidden disabled>Save .txt</button>
              <button type="button" class="call-ctrl call-ctrl--end" id="btn-end" hidden>End session</button>
            </div>
          </section>
        </div>
      <?php endif; ?>
    </div>

    <p class="call-credits">
      Speech recognition by <a href="https://alphacephei.com/vosk/" target="_blank" rel="noopener">Vosk</a>
      via <a href="https://github.com/ccoreilly/vosk-browser" target="_blank" rel="noopener">vosk-browser</a>.
      Filipino model by feddybear (Federico Ang), CC-BY-NC-SA 4.0.
      Keep the signaling server running — double-click <code>signaling/start.bat</code> (or <code>cd signaling && node server.js</code>).
      <?php if (!$modelEn || !$modelFil): ?>
        <br>Caption models are missing from <code>assets/models/</code>
        (<?= $modelEn ? 'English found' : 'English missing' ?>;
        <?= $modelFil ? 'Filipino found' : 'Filipino missing' ?>).
      <?php else: ?>
        First captions in a language download the on-device model (~128MB English / ~320MB Filipino); later calls reuse the browser cache.
      <?php endif; ?>
    </p>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/vosk-browser@0.0.8/dist/vosk.js"></script>
<script src="assets/js/call.js?v=2"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
