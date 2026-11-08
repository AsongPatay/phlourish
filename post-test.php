<?php
$pageTitle = 'Post-Test';
$activeNav = 'login';
$showCall = true;
$showGame = true;
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';

$flourishingQuestions = [
    'fq1' => [
        'text' => 'Sa pangkalahatan, gaano ka ka-kuntento sa iyong buhay sa mga araw na ito?',
        'left' => 'Lubos na HINDI kuntento',
        'right' => 'Lubos na kuntento',
    ],
    'fq2' => [
        'text' => 'Sa pangkalahatan, itinuturing ko ang aking sarili na isang masayang tao.',
        'left' => 'Lubos na HINDI sumasang-ayon',
        'right' => 'Lubos na sumasang-ayon',
    ],
];

$phq4Questions = [
    'p1' => 'Pakiramdam na kinakabahan, nababalisa, o nag-aalala.',
    'p2' => 'Hindi mapigilan o makontrol ang pag-aalala.',
    'p3' => 'Pakiramdam na malungkot, nalulumbay o nawawalan ng pag-asa.',
    'p4' => 'Kaunting interes o saya sa paggawa ng mga bagay.',
];

$phq4Labels = [
    0 => '0 (Hindi Kailanman)',
    1 => '1 (Mga ilang araw)',
    2 => '2 (Higit sa pitong araw)',
    3 => '3 (Halos araw-araw)',
];

$outcomeQuestions = [
    'o1' => [
        'text' => 'Komportable ka bang pag-usapan ang mental health?',
        'options' => [
            1 => '1 - Lubos na hindi kumportable',
            2 => '2 - Hindi kumportable',
            3 => '3 - Kumportable',
            4 => '4 - Lubos na kumportable',
        ],
    ],
    'o2' => [
        'text' => 'Madalas bang negatibo ang iyong mga iniisip tungkol sa iyong sarili?',
        'options' => [
            1 => '1 - Hindi nangyayari',
            2 => '2 - Hindi madalas',
            3 => '3 - Minsan',
            4 => '4 - Madalas',
            5 => '5 - Palagi',
        ],
    ],
    'o3' => [
        'text' => 'Naniniwala ka ba na ang lungkot, galit at inis, ay mahalaga at okay lang na iyong maramdaman?',
        'options' => [1 => 'Oo', 2 => 'Hindi'],
    ],
    'o4' => [
        'text' => 'Sa tingin mo ba ay kaya mong kontrolin ang mga matinding emosyon?',
        'options' => [
            1 => '1 - Hindi kaya',
            2 => '2 - Medyo kaya',
            3 => '3 - Kaya',
            4 => '4 - Kayang kaya',
        ],
    ],
    'o5' => [
        'text' => 'Kaya mo bang tukuyin kung ikaw ay may mabuting relasyon sa ibang tao?',
        'options' => [
            1 => '1 - Hindi kaya',
            2 => '2 - Medyo kaya',
            3 => '3 - Kaya',
            4 => '4 - Kayang kaya',
        ],
    ],
    'o6' => [
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
    'oe7' => 'Ano ang ibig sabihin ng mental health para sa\'yo?',
    'oe8' => 'Ano ang sinasabi mo sa iyong sarili kung ikaw ay nagkamali o may mahirap na pinagdadaanan?',
    'oe9' => 'Ano ang ginagawa mo kapag ikaw ay nakakaramdam ng mga mabigat o matinding emosyon?',
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

$chapter2Habits = [
    'Positive Self-talk', 'Thought replacement', 'Ehersisyo (pag-exercise)',
    'Nutrisyon (pagkain nang masustansya)', 'Pagtulog nang maayos', 'Kalinisan sa katawan',
    'Meditation', 'Mindfulness', 'Time Management', 'Gratitude', 'Wala sa nabanggit (None of the above)',
];

$chapter3Techniques = [
    'Box Breathing', 'Self-soothing Diary', 'Worry Time', 'Radical Acceptance', 'Grounding', 'Wala sa nabanggit (None of the above)',
];

$toolkitChapters = [
    'Chapter 1: Pagbubukas ng Isip',
    'Chapter 2: Ang Aking Katawan at Isipan',
    'Chapter 3: Ang Aking Kalooban',
    'Chapter 4: Ako at Ang Aking Kapwa',
    'Chapter 5: Ang Aking Kaagapay',
    'Hindi naaangkop (Not Applicable)',
];

$worksheetChapters = [
    'Chapter 1: Pagbubukas ng Isip',
    'Chapter 2: Ang Aking Katawan at Isipan',
    'Chapter 3: Ang Aking Kalooban',
    'Chapter 4: Ako at Ang Aking Kapwa',
    'Chapter 5: Ang Aking Kaagapay',
    'Hindi naaangkop (hindi nakapagsagot)',
];

$toolkitProgramQuestions = [
    't1' => 'Magagamit ko at ng aking mga kaedad ang impormasyong natutunan ko mula sa toolkit.',
    't2' => 'Babalikan ko ang toolkit kung kailangan ko ng suporta para sa aking mental health.',
    't3' => 'Madaling gamitin ang toolkit.',
    't4' => 'Kapag kausap ko ang aking mentor, nararamdaman kong pinahahalagahan ako.',
    't5' => 'Hindi ako kumportable sa aking mentor kapag kami ay nag-uusap.',
    't6' => 'Nakatulong sa akin ang mga check-in kasama ng aking mentor.',
];

$checkinTiming = [
    1 => 'Hindi, kulang pa',
    2 => 'Oo, tama lang',
    3 => 'Oo, pero sobra na',
];

$toolkitChallenges = [
    'Busy sa school/academics',
    'May mga gawain o responsibilidad sa bahay',
    'Hindi kumportable sa pakikipag-usap',
    'Nawalan ng interes sa programa',
    'Wala sa nabanggit',
];
?>

<main class="page page--teal">
    <div class="pre-test-card">
        <form method="post" action="">

            <div class="form-step" data-step="1">
                <h1>BASIC INFORMATION</h1>

                <label for="first-name">Pangalan (First Name):</label>
                <input type="text" id="first-name" name="first-name" required>

                <label for="middle-name">Gitnang Pangalan (Middle Name):</label>
                <input type="text" id="middle-name" name="middle-name">

                <label for="surname">Apelyido (Surname):</label>
                <input type="text" id="surname" name="surname" required>

                <div class="step-actions">
                    <button class="btn btn--teal next-step" type="button">Next</button>
                </div>
            </div>

            <div class="form-step" data-step="2" hidden>
                <h1>FLOURISHING SCALE</h1>
                <p><strong>Panuto</strong>: Bilugan (O) ang naaangkop na numero/score na nais mong isagot. Mangyaring maging tapat sa iyong pagsagot. <strong>Isa lamang ang piliing sagot bawat item.</strong></p>

                <?php $n = 1; foreach ($flourishingQuestions as $qKey => $q): ?>
                    <div class="scale-question">
                        <label class="question"><?= $n ?>. <?= htmlspecialchars($q['text'], ENT_QUOTES, 'UTF-8') ?></label>
                        <div class="scale-row">
                            <?php for ($i = 0; $i <= 10; $i++): ?>
                                <label class="scale-option">
                                    <input type="radio" name="<?= $qKey ?>" value="<?= $i ?>" required>
                                    <span><?= $i ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div class="scale-endpoints">
                            <span><?= htmlspecialchars($q['left'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars($q['right'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                <?php $n++; endforeach; ?>

                <div class="step-actions">
                    <button class="btn btn--white prev-step" type="button">Back</button>
                    <button class="btn btn--teal next-step" type="button">Next</button>
                </div>
            </div>

            <div class="form-step" data-step="3" hidden>
                <h1>MENTAL HEALTH - PHQ-4</h1>
                <p><strong>Panuto</strong>: Bilugan (O) ang naaangkop na numero/score na nais mong isagot. Mangyaring maging tapat sa iyong pagsagot. <strong>Isa lamang ang piliing sagot bawat item.</strong></p>
                <p>Sa nakaraang dalawang linggo, gaano kadalas kang nababahala sa mga sumusunod na problema?</p>

                <table class="scale-table">
                    <thead>
                        <tr>
                            <th></th>
                            <?php foreach ($phq4Labels as $value => $label): ?>
                                <th><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n = 1; foreach ($phq4Questions as $qKey => $qText): ?>
                            <tr>
                                <td class="scale-table__question"><?= $n ?>. <?= htmlspecialchars($qText, ENT_QUOTES, 'UTF-8') ?></td>
                                <?php foreach ($phq4Labels as $value => $label): ?>
                                    <td class="scale-table__radio">
                                        <input type="radio" name="<?= $qKey ?>" value="<?= $value ?>" required>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php $n++; endforeach; ?>
                    </tbody>
                </table>

                <div class="step-actions">
                    <button class="btn btn--white prev-step" type="button">Back</button>
                    <button class="btn btn--teal next-step" type="button">Next</button>
                </div>
            </div>

            <div class="form-step" data-step="4" hidden>
                <h1>MENTAL HEALTH - OUTCOMES</h1>
                <p><strong>Panuto</strong>: Basahin nang mabuti ang mga sumusunod na pahayag. Lagyan ng check (✓) ang bilog na nais mong isagot. Mangyaring maging tapat sa iyong pagsagot. <strong>Isa lamang ang piliing sagot bawat item.</strong></p>

                <?php $n = 1; foreach ($outcomeQuestions as $qKey => $q): ?>
                    <div class="mh-question">
                        <div class="mh-question__text"><?= $n ?>. <?= htmlspecialchars($q['text'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="mh-question__options">
                            <?php foreach ($q['options'] as $value => $label): ?>
                                <label class="mh-option">
                                    <input type="radio" name="<?= $qKey ?>" value="<?= $value ?>" required>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $n++; endforeach; ?>

                <div class="step-actions">
                    <button class="btn btn--white prev-step" type="button">Back</button>
                    <button class="btn btn--teal next-step" type="button">Next</button>
                </div>
            </div>

            <div class="form-step" data-step="5" hidden>
                <?php $n = 7; foreach ($openEndedQuestions as $qKey => $qText): ?>
                    <div class="open-question">
                        <label class="question" for="<?= $qKey ?>"><?= $n ?>. <?= htmlspecialchars($qText, ENT_QUOTES, 'UTF-8') ?> <em>Isulat ang iyong sagot sa kahon sa ibaba.</em></label>
                        <textarea id="<?= $qKey ?>" name="<?= $qKey ?>" rows="4"></textarea>
                    </div>
                <?php $n++; endforeach; ?>

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
                            <input type="checkbox" name="support-people[]" value="Iba pa">
                            Iba pa:
                            <input type="text" name="support-people-other">
                        </label>
                    </div>
                </div>

                <div class="step-actions">
                    <button class="btn btn--white prev-step" type="button">Back</button>
                    <button class="btn btn--teal next-step" type="button">Next</button>
                </div>
            </div>

            <div class="form-step" data-step="6" hidden>
                <h1>OTHER QUESTIONS</h1>
                <p><strong>Panuto</strong>: Basahin nang maigi ang bawat item sa ibaba at lagyan ng check (✓) ang box na nais mong isagot. Mangyaring maging tapat sa iyong pagsagot.</p>

                <div class="open-question">
                    <label class="question">1. Sa Chapter 2, natutunan natin na mahalaga ang self-compassion at healthy habits. Anong mga habits mula sa Chapter 2 ang ginagawa mo pa rin? <em>Maaaring lagyan ng check (✓) ang higit sa isa.</em></label>
                    <div class="support-grid">
                        <?php foreach ($chapter2Habits as $habit): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="chapter2-habits[]" value="<?= htmlspecialchars($habit, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($habit, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                        <label class="mh-option support-other">
                            <input type="checkbox" name="chapter2-habits[]" value="Iba pa">
                            Iba pa:
                            <input type="text" name="chapter2-habits-other">
                        </label>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question">2. Sa Chapter 3, pinakilala sa atin ang iba't ibang emotional regulation techniques. Alin sa mga techniques na ito ang nagamit mong muli pagkatapos itong matutunan? <em>Maaaring lagyan ng check (✓) ang higit sa isa.</em></label>
                    <div class="support-grid">
                        <?php foreach ($chapter3Techniques as $technique): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="chapter3-techniques[]" value="<?= htmlspecialchars($technique, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($technique, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question">3. Sa kabuuan ng toolkit, anong mga lesson ang pinakatumatak sa iyo? <em>Pumili ng dalawa (2) lamang sa mga sumusunod na chapter ng toolkit.</em></label>
                    <div class="support-grid">
                        <?php foreach ($toolkitChapters as $chapter): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="toolkit-lessons[]" value="<?= htmlspecialchars($chapter, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($chapter, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question">5. Alin sa mga chapter ng toolkit ang nasubukan mong sagutan ang worksheets/exercises? <em>Piliin ang lahat ng naaangkop.</em></label>
                    <div class="support-grid">
                        <?php foreach ($worksheetChapters as $chapter): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="worksheet-chapters[]" value="<?= htmlspecialchars($chapter, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($chapter, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question" for="oe6">6. Ano ang pinakamahalagang natutunan mo mula sa programa? <em>Isulat ang iyong sagot sa kahon sa ibaba.</em></label>
                    <textarea id="oe6" name="oe6" rows="4"></textarea>
                </div>

                <div class="step-actions">
                    <button class="btn btn--white prev-step" type="button">Back</button>
                    <button class="btn btn--teal next-step" type="button">Next</button>
                </div>
            </div>

            <div class="form-step" data-step="7" hidden>
                <h1>TOOLKIT PROGRAM</h1>
                <p><strong>Panuto</strong>: Bilugan (O) ang naaangkop na numero/score na nais mong isagot. Mangyaring maging tapat sa iyong pagsagot. <strong>Isa lamang ang piliing sagot bawat item.</strong></p>

                <div class="na-legend">
                    <p>Ibig sabihin ng mga numero/score:</p>
                    <ul>
                        <li>1 - Lubos na hindi sumasang-ayon</li>
                        <li>2 - Hindi sumasang-ayon</li>
                        <li>3 - Hindi ko alam o wala akong opinyon</li>
                        <li>4 - Sumasang-ayon</li>
                        <li>5 - Lubos na sumasang-ayon</li>
                        <li>NA - Hindi naaangkop</li>
                    </ul>
                </div>

                <table class="scale-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>NA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $n = 1; foreach ($toolkitProgramQuestions as $qKey => $qText): ?>
                            <tr>
                                <td class="scale-table__question"><?= $n ?>. <?= htmlspecialchars($qText, ENT_QUOTES, 'UTF-8') ?></td>
                                <?php foreach ([1, 2, 3, 4, 5, 'NA'] as $value): ?>
                                    <td class="scale-table__radio">
                                        <input type="radio" name="<?= $qKey ?>" value="<?= $value ?>" required>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php $n++; endforeach; ?>
                    </tbody>
                </table>

                <div class="open-question">
                    <label class="question" for="oe7">7. Anong masasabi mo tungkol sa iyong mentor? <em>Isulat ang iyong sagot sa kahon sa ibaba.</em></label>
                    <textarea id="oe7" name="oe7" rows="4"></textarea>
                </div>

                <div class="step-actions">
                    <button class="btn btn--white prev-step" type="button">Back</button>
                    <button class="btn btn--teal next-step" type="button">Next</button>
                </div>
            </div>

            <div class="form-step" data-step="8" hidden>
                <div class="open-question">
                    <label class="question" for="oe8">8. Anong masasabi mo tungkol sa programa? <em>Isulat ang iyong sagot sa kahon sa ibaba.</em></label>
                    <textarea id="oe8" name="oe8" rows="4"></textarea>
                </div>

                <div class="open-question">
                    <label class="question">9. Sa tingin mo, sapat na ba ang oras ng check-in mo kasama ang mentor bawat linggo? <em>Lagyan ng check (✓) ang isang (1) sagot lamang.</em></label>
                    <div class="checkbox-group">
                        <?php foreach ($checkinTiming as $value => $label): ?>
                            <label><input type="radio" name="checkin-timing" value="<?= $value ?>" required> <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question">10. Ano ang mga challenges na naranasan mo, kung mayroon, sa pagbabasa ng toolkit o pagsali sa mga check-ins? <em>Maaaring lagyan ng check (✓) ang higit sa isa.</em></label>
                    <div class="support-grid">
                        <?php foreach ($toolkitChallenges as $challenge): ?>
                            <label class="mh-option">
                                <input type="checkbox" name="toolkit-challenges[]" value="<?= htmlspecialchars($challenge, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($challenge, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                        <label class="mh-option support-other">
                            <input type="checkbox" name="toolkit-challenges[]" value="Iba pa">
                            Iba pa:
                            <input type="text" name="toolkit-challenges-other">
                        </label>
                    </div>
                </div>

                <div class="open-question">
                    <label class="question">11. Mula 1-10, 10 bilang pinakamataas o pinakamaganda, ano ang final rating mo para sa toolkit at program? <em>Bilugan (O) ang naaangkop na numero/score na nais mong isagot.</em></label>
                    <div class="lowest-highest-scale">
                        <span>Lowest</span>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <label class="scale-option">
                                <input type="radio" name="final-rating" value="<?= $i ?>" required>
                                <span><?= $i ?></span>
                            </label>
                        <?php endfor; ?>
                        <span>Highest</span>
                    </div>
                </div>

                <div class="step-actions">
                    <button class="btn btn--white prev-step" type="button">Back</button>
                    <button class="btn btn--teal" type="submit">Submit</button>
                </div>
            </div>

        </form>
    </div>
</main>

<script src="assets/js/pre-test-form.js"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>