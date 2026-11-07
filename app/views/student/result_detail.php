<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub"><?= e($attempt['subject_name']) ?> &middot; <a href="/index.php?route=student/results">&larr; back to results</a></p>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= e($attempt['score']) ?> / <?= e($attempt['total_marks']) ?></span><span class="stat-label">Score</span></div>
        <div class="stat-tile"><span class="stat-value"><?= $attempt['total_marks'] > 0 ? number_format(($attempt['score'] / $attempt['total_marks']) * 100, 1) : 0 ?>%</span><span class="stat-label">Percentage</span></div>
        <div class="stat-tile"><span class="stat-value"><?= e(str_replace('_', ' ', $attempt['status'])) ?></span><span class="stat-label">Status</span></div>
    </div>

    <section class="card">
        <h2>Answer Breakdown</h2>
        <?php foreach ($questions as $i => $q): ?>
            <?php
            $answer = $answers[$q['id']] ?? null;
            $selectedIds = $answer && $answer['selected_option_ids'] ? explode(',', (string)$answer['selected_option_ids']) : [];
            $isCorrect = $answer['is_correct'] ?? null;
            $rowClass = $isCorrect === null ? 'unanswered' : ($isCorrect ? 'correct' : 'incorrect');
            ?>
            <div class="review-row review-<?= $rowClass ?>">
                <p class="q-meta">Question <?= $i + 1 ?> &middot; <?= e((string)$q['marks']) ?> marks &middot;
                    <?php if ($isCorrect === null): ?>Not answered<?php elseif ($isCorrect): ?>Correct (+<?= e((string)$answer['marks_awarded']) ?>)<?php else: ?>Incorrect (<?= e((string)$answer['marks_awarded']) ?>)<?php endif; ?>
                </p>
                <p class="q-text"><?= e($q['question_text']) ?></p>
                <?php if ($q['type'] === 'fill_blank'): ?>
                    <p>Your answer: <strong><?= e($answer['answer_text'] ?? '(blank)') ?></strong></p>
                    <p class="muted">Correct answer: <?= e($q['correct_answer_text']) ?></p>
                <?php else: ?>
                    <ul class="option-list">
                        <?php foreach ($optionsByQuestion[$q['id']] ?? [] as $opt): ?>
                            <?php
                            $wasSelected = in_array((string)$opt['id'], $selectedIds, true);
                            $classes = [];
                            if ($opt['is_correct']) $classes[] = 'correct-option';
                            if ($wasSelected && !$opt['is_correct']) $classes[] = 'wrong-selected';
                            if ($wasSelected) $classes[] = 'was-selected';
                            ?>
                            <li class="<?= e(implode(' ', $classes)) ?>"><?= e($opt['option_text']) ?><?= $wasSelected ? ' (your answer)' : '' ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
