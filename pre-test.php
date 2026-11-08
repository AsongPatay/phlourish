<?php
$pageTitle = 'Pre-Test';
$activeNav = 'login';
$showCall = true;
$showGame = true;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';

$flourishingQuestions = [
    'q1' => 'Sa pangkalahatan, gaano ka ka-kuntento sa iyong buhay sa mga araw na ito?',
    'q2' => 'Sa pangkalahatan, itinuturing ko ang aking sarili na isang masayang tao.',
];

$anxietyQuestions = [
    'a1' => 'Pakiramdam na kinakabahan, nababalisa, o nag-aalala.',
    'a2' => 'Hindi mapigilan o makontrol ang pag-aalala.',
    'a3' => 'Pakiramdam na malungkot, nalulumbay o nawawalan ng pag-asa.',
    'a4' => 'Kaunting interes o saya sa paggawa ng mga bagay.',
];

$scaleLabels = [
    0 => '0 (Hindi Kailanman)',
    1 => '1 (Mga ilang araw)',
    2 => '2 (Higit sa pitong araw)',
    3 => '3 (Halos araw-araw)',
];

$mentalHealthQuestions = [
    'm1' => [
        'text' => 'Komportable ka bang pag-usapan ang mental health?',
        'options' => [
            1 => '1 - Lubos na hindi kumportable',
            2 => '2 - Hindi kumportable',
            3 => '3 - Kumportable',
            4 => '4 - Lubos na kumportable',
        ],
    ],
    'm2' => [
        'text' => 'Madalas bang negatibo ang iyong mga iniisip tungkol sa iyong sarili?',
        'options' => [
            1 => '1 - Hindi nangyayari',
            2 => '2 - Hindi madalas',
            3 => '3 - Minsan',
            4 => '4 - Madalas',
            5 => '5 - Palagi',
        ],
    ],
    'm3' => [
        'text' => 'Naniniwala ka ba na ang lungkot, galit at inis, ay mahalaga at okay lang na iyong maramdaman?',
        'options' => [
            1 => 'Oo',
            2 => 'Hindi',
        ],
    ],
    'm4' => [
        'text' => 'Sa tingin mo ba ay kaya mong kontrolin ang mga matinding emosyon?',
        'options' => [
            1 => '1 - Hindi kaya',
            2 => '2 - Medyo kaya',
            3 => '3 - Kaya',
            4 => '4 - Kayang kaya',
        ],
    ],
    'm5' => [
        'text' => 'Kaya mo bang tukuyin kung ikaw ay may mabuting relasyon sa ibang tao?',
        'options' => [
            1 => '1 - Hindi kaya',
            2 => '2 - Medyo kaya',
            3 => '3 - Kaya',
            4 => '4 - Kayang kaya',
        ],
    ],
    'm6' => [
        'text' => 'Humihingi ka ba ng tulong sa mga taong pinagkakatiwalaan mo kapag ikaw ay nakararanas ng personal o emosyonal na problema?',
        'options' => [
            1 => '1 - Hindi humihingi ng tulong',
            2 => '2 - Hindi madalas humingi ng tulong',
            3 => '3 - Minsan lang humingi ng tulong',
            4 => '4 - Madalas humingi ng tulong',
            5 => '5 - Laging humihingi ng tulong',
        ],
    ],
];

$openEndedQuestions = [
    'oe1' => 'Ano ang ibig sabihin ng mental health para sa\'yo?',
    'oe2' => 'Ano ang sinasabi mo sa iyong sarili kung ikaw ay nagkamali o may mahirap na pinagdadaanan?',
    'oe3' => 'Ano ang ginagawa mo kapag ikaw ay nakakaramdam ng mga mabigat o matinding emosyon?',
];

$supportPeople = [
    'Parent/Guardian (Magulang/Tagapangalaga)',
    'Sibling (Kapatid)',
    'Friend (Kaibigan)',
    'Teacher (Guro)',
    'Guidance Counselor',
    'Social Worker/Houseparent',
    'Priest/Pastor/Religious Leader (Pari/Pastor)',
    'Other Relative (Iba pang kamag-anak)',
    'Doctor (Doktor)',
    'None (Walang lalapitan)',
];

$mhServiceOptions = [
    'Wala',
    'Guidance and Counseling',
    'Psychological Assessment and Therapy',
    'Peer Group Support',
    'Mental Health App or Website',
    'Self-help Program',
    'Psychiatry',
];

$programReasons = [
    'Inirekomenda ng LGU/Health Office/Organisasyon/Paaralan',
    'Hinikayat ng mga kaibigan',
    'Hinikayat ng mga magulang',
    'Gustong matuto tungkol sa mental health at paano ito aalagaan',
    'Gustong mas makilala ang sarili',
    'Gustong maintindihan at ma-manage ang mga emosyon',
    'Gustong mas mapabuti ang aking mga relasyon',
];


?>

<main class="page page--teal">
    <div class="pre-test-card">
        <form method="post" action="">

            <div class="form-step" data-step="1">
                <h1>Basic Information</h1>

                <label for="first-name">Pangalan (First Name):</label>
                <input type="text" id="first-name" name="first-name" required>

                <label for="middle-name">Gitnang Pangalan (Middle Name):</label>
                <input type="text" id="middle-name" name="middle-name">

                <label for="surname">Apelyido (Surname):</label>
                <input type="text" id="surname" name="surname" required>

                <label for="school">Paaralan:</label>
                <input type="text" id="school" name="school">

                <label for="grade-level">Grade Level:</label>
                <input type="text" id="grade-level" name="grade-level">

                <label for="contact-number">Contact Number:</label>
                <input type="text" id="contact-number" name="contact-number" placeholder="0 XXX-XXX-XXXX">

                <label for="facebook-name">Facebook Account Name:</label>
                <input type="text" id="facebook-name" name="facebook-name">

                <label>Mentor Preference:</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="mentor-pref[]" value="Babae"> Babae</label>
                    <label><input type="checkbox" name="mentor-pref[]" value="Lalaki"> Lalaki</label>
                    <label><input type="checkbox" name="mentor-pref[]" value="Kahit ano"> Kahit ano</label>
                </div>

                <label>Language Preference:</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="lang-pref[]" value="Tagalog"> Tagalog</label>
                    <label><input type="checkbox" name="lang-pref[]" value="Bisaya"> Bisaya</label>
                    <label for="lang-other">Iba pa:</label>
                    <input type="text" id="lang-other" name="lang-other">
                </div>

                <label for="date-time-pref">Tuwing kailan ka available?</label>
                <input type="text" id="date-time-pref" name="date-time-pref" placeholder="Sabado, 2:00 PM">

                <label for="guardian-name">Pangalan ng Guardian</label>
                <input type="text" id="guardian-name" name="guardian-name">

                <label for="guardian-contact-number">Contact Number ng Guardian:</label>
                <input type="text" id="guardian-contact-number" name="guardian-contact-number" placeholder="0 XXX-XXX-XXXX">

<div class="step-actions">
    <button class="btn btn--white prev-step" type="button">Back</button>
    <button class="btn btn--teal next-step" type="button">Next</button>
</div>            </div>

            <div class="form-step" data-step="2" hidden>
                <h1>Additional Information</h1>

                <label>Nagpapatingin ka ba sa isang doktor (psychiatrist) o psychologist ngayon?</label>
                <div class="checkbox-group">
                    <label><input type="radio" name="seeing-doctor" value="Oo"> Oo</label>
                    <label><input type="radio" name="seeing-doctor" value="Hindi"> Hindi</label>
                </div>

                <label>Kung oo ang sagot mo sa itaas, may gamot bang nireseta sayo?</label>
                <div class="checkbox-group">
                    <label><input type="radio" name="has-medication" value="Oo"> Oo</label>
                    <label><input type="radio" name="has-medication" value="Hindi"> Hindi</label>
                </div>

                <label for="medication-name">Kung oo, anong gamot ito?</label>
                <input type="text" id="medication-name" name="medication-name">

<div class="step-actions">
    <button class="btn btn--white prev-step" type="button">Back</button>
    <button class="btn btn--teal next-step" type="button">Next</button>
</div>
            </div>

            <div class="form-step" data-step="3" hidden>
                <h1>Demographics</h1>

                <label for="age">Edad (Age):</label>
                <input type="text" id="age" name="age">

                <label>Kasarian (Biological Sex):</label>
                <div class="checkbox-group">
                    <label><input type="radio" name="bio-sex" value="Babae"> Babae</label>
                    <label><input type="radio" name="bio-sex" value="Lalake"> Lalake</label>
                </div>

                <label>Gender Identity</label>
                <div class="checkbox-group">
                    <label><input type="radio" name="gender-identity" value="Woman"> Woman</label>
                    <label><input type="radio" name="gender-identity" value="Man"> Man</label>
                    <label><input type="radio" name="gender-identity" value="Transgender"> Transgender</label>
                    <label><input type="radio" name="gender-identity" value="Non-binary"> Non-binary</label>
                    <label><input type="radio" name="gender-identity" value="Hindi ko nais sumagot"> Hindi ko nais sumagot</label>
                    <label for="gender-other">Iba pa:</label>
                    <input type="text" id="gender-other" name="gender-other">
                </div>

<div class="step-actions">
    <button class="btn btn--white prev-step" type="button">Back</button>
    <button class="btn btn--teal next-step" type="button">Next</button>
</div>
            </div>

            <div class="form-step" data-step="4" hidden>
                <h1>FLOURISHING SCALE</h1>
                <p>Panuto: Piliin ang naaangkop na numero/score na nais mong isagot. Mangyaring maging tapat sa iyong pagsagot. Isa lamang ang piliing sagot bawat item.</p>

                <?php foreach ($flourishingQuestions as $qKey => $qText): ?>
                    <div class="scale-question">
                        <label class="question"><?= htmlspecialchars($qText, ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="scale-row">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <label class="scale-option">
                                    <input type="radio" name="<?= $qKey ?>" value="<?= $i ?>" required>
                                    <span><?= $i ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div class="scale-endpoints">
                            <span>Hindi Sang-ayon</span>
                            <span>Lubos na Sang-ayon</span>
                        </div>
                    </div>
                <?php endforeach; ?>

<div class="step-actions">
    <button class="btn btn--white prev-step" type="button">Back</button>
    <button class="btn btn--teal next-step" type="button">Next</button>
</div>
            </div>

            <div class="form-step" data-step="5" hidden>
                <p><strong>Panuto</strong>: Basahin nang mabuti ang mga sumusunod na pahayag. Lagyan ng check (✓) ang bilog na nais mong isagot. Mangyaring maging tapat sa iyong pagsagot. <strong>Isa lamang ang piliing sagot bawat item.</strong></p>

                <?php $n = 1;
                foreach ($mentalHealthQuestions as $qKey => $q): ?>
                    <div class="mh-question">
                        <div class="mh-question__text">
                            <?= $n ?>. <?= htmlspecialchars($q['text'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="mh-question__options">
                            <?php foreach ($q['options'] as $value => $label): ?>
                                <label class="mh-option">
                                    <input type="radio" name="<?= $qKey ?>" value="<?= $value ?>" required>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $n++;
                endforeach; ?>
                <?php $n = 7;
                foreach ($openEndedQuestions as $qKey => $qText): ?>
                    <div class="open-question">
                        <label class="question" for="<?= $qKey ?>"><?= $n ?>. <?= htmlspecialchars($qText, ENT_QUOTES, 'UTF-8') ?> <em>Isulat ang iyong sagot sa kahon sa ibaba.</em></label>
                        <textarea id="<?= $qKey ?>" name="<?= $qKey ?>" rows="4"></textarea>
                    </div>
                <?php $n++;
                endforeach; ?>

                <div class="open-question">
                    <label class="question">10. Sino-sino ang iyong lalapitan kung sa tingin mo ay mayroon kang mental health condition o kailangan mo ng ibang pangangalaga? <em>Maaaring lagyan ng check (✓) ang higit sa isa.</em></label>
                    <div class="support-grid">
                        <?php foreach ($supportPeople as $person): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="support-people[]" value="<?= htmlspecialchars($person, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($person, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                        <label class="mh-option support-other">
                            <input type="checkbox" name="support-people[]" value="Iba pa" id="support-other-check">
                            Iba pa:
                            <input type="text" name="support-people-other" id="support-other-text">
                        </label>
                    </div>
                </div>
<div class="step-actions">
    <button class="btn btn--white prev-step" type="button">Back</button>
    <button class="btn btn--teal next-step" type="button">Next</button>
</div>
            </div>

            <div class="form-step" data-step="6" hidden>
                <h1>MENTAL HEALTH SERVICES</h1>
                <p><strong>Panuto</strong>: Lagyan ng check (✓) ang box na nais mong isagot. Mangyaring maging tapat.</p>

                <div class="open-question">
                    <label class="question">1. Ikaw ba ay nakatanggap o nakasali na sa ibang mental health program?</label>
                    <div class="checkbox-group">
                        <label class="mh-option"><input type="radio" name="prev-program" value="Oo"> Oo</label>
                        <label class="mh-option"><input type="radio" name="prev-program" value="Hindi"> Hindi</label>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question">2. May alam ka bang mga mental health services na maaari mong lapitan kung kailangan? <em>Maaaring mag-check (✓) ng isa o higit pa.</em></label>
                    <div class="support-grid">
                        <?php foreach ($mhServiceOptions as $option): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="mh-services[]" value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                        <label class="mh-option support-other">
                            <input type="checkbox" name="mh-services[]" value="Iba pa">
                            Iba pa:
                            <input type="text" name="mh-services-other">
                        </label>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question">3. Bakit ka naging interesadong sumama sa aming programa? <em>Maaaring mag-check (✓) ng isa o higit pa.</em></label>
                    <div class="support-grid">
                        <?php foreach ($programReasons as $reason): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="program-reasons[]" value="<?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                        <label class="mh-option support-other">
                            <input type="checkbox" name="program-reasons[]" value="Iba pa">
                            Iba pa:
                            <input type="text" name="program-reasons-other">
                        </label>
                    </div>
                </div>

<div class="step-actions">
    <button class="btn btn--white prev-step" type="button">Back</button>
    <button class="btn btn--teal next-step" type="submit">Submit</button>
</div>
            </div>

        </form>
    </div>
</main>

<script src="assets/js/pre-test-form.js"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>