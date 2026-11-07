<?php
$existingAnswered = [];
foreach ($existingAnswers as $qid => $a) {
    $hasAnswer = !empty($a['selected_option_ids']) || (isset($a['answer_text']) && trim((string)$a['answer_text']) !== '');
    if ($hasAnswer) {
        $existingAnswered[] = (int)$qid;
    }
}
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — ExamCore</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="exam-mode">
<header class="exam-topbar">
    <div class="exam-topbar-title">
        <strong><?= e($attempt['exam_title']) ?></strong>
        <span class="muted"><?= e($attempt['subject_name']) ?></span>
    </div>
    <div class="exam-timer" id="exam-timer">--:--</div>
    <div class="exam-topbar-actions">
        <button type="button" class="btn btn-primary" id="submit-btn">Submit Exam</button>
    </div>
</header>

<main class="exam-body">
    <nav class="question-nav" id="question-nav">
        <?php foreach ($questions as $i => $q): ?>
            <button type="button" class="nav-dot <?= in_array($q['id'], $existingAnswered, true) ? 'answered' : '' ?>"
                    data-index="<?= $i ?>" data-qid="<?= (int)$q['id'] ?>"><?= $i + 1 ?></button>
        <?php endforeach; ?>
    </nav>

    <div class="question-panel">
        <?php foreach ($questions as $i => $q): ?>
            <?php
            $existing = $existingAnswers[$q['id']] ?? null;
            $selected = $existing['selected_option_ids'] ?? '';
            $selectedIds = $selected !== '' && $selected !== null ? explode(',', (string)$selected) : [];
            ?>
            <section class="q-card" data-qid="<?= (int)$q['id'] ?>" data-type="<?= e($q['type']) ?>" <?= $i === 0 ? '' : 'hidden' ?>>
                <p class="q-meta">Question <?= $i + 1 ?> of <?= count($questions) ?> &middot; <?= e((string)$q['marks']) ?> marks</p>
                <h2 class="q-text"><?= e($q['question_text']) ?></h2>

                <?php if ($q['type'] === 'single' || $q['type'] === 'true_false'): ?>
                    <div class="q-options">
                        <?php foreach ($optionsByQuestion[$q['id']] ?? [] as $opt): ?>
                            <label class="option-choice">
                                <input type="radio" name="q_<?= (int)$q['id'] ?>" value="<?= (int)$opt['id'] ?>"
                                       <?= in_array((string)$opt['id'], $selectedIds, true) ? 'checked' : '' ?>>
                                <?= e($opt['option_text']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($q['type'] === 'multi'): ?>
                    <div class="q-options">
                        <?php foreach ($optionsByQuestion[$q['id']] ?? [] as $opt): ?>
                            <label class="option-choice">
                                <input type="checkbox" name="q_<?= (int)$q['id'] ?>[]" value="<?= (int)$opt['id'] ?>"
                                       <?= in_array((string)$opt['id'], $selectedIds, true) ? 'checked' : '' ?>>
                                <?= e($opt['option_text']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <input type="text" class="fill-blank-input" name="q_<?= (int)$q['id'] ?>"
                           value="<?= e($existing['answer_text'] ?? '') ?>" placeholder="Type your answer" autocomplete="off">
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <div class="q-nav-buttons">
            <button type="button" class="btn btn-ghost" id="prev-btn">&larr; Previous</button>
            <button type="button" class="btn btn-ghost" id="next-btn">Next &rarr;</button>
        </div>
    </div>
</main>

<form id="submit-form" method="POST" action="/index.php?route=student/exam/submit" class="hidden">
    <?= Csrf::field() ?>
    <input type="hidden" name="attempt_id" value="<?= (int)$attempt['id'] ?>">
</form>

<div id="violation-overlay" class="violation-overlay hidden">
    <div class="violation-box" id="violation-box">
        <h2 id="violation-title">Tab switch detected</h2>
        <p id="violation-message"></p>
        <button type="button" class="btn btn-primary" id="violation-dismiss">I Understand — Continue Exam</button>
    </div>
</div>

<script>
window.EXAM_DATA = {
    attemptId: <?= (int)$attempt['id'] ?>,
    remainingSeconds: <?= (int)$remaining ?>,
    csrfToken: <?= json_encode(Csrf::token()) ?>,
    autosaveUrl: '/index.php?route=student/exam/autosave',
    violationUrl: '/index.php?route=student/exam/violation'
};
</script>
<script src="/assets/js/exam.js"></script>
</body>
</html>
