<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub">
        <?= (int)count($questions) ?> question(s) &middot;
        <?= e((string)array_sum(array_column($questions, 'marks'))) ?> total marks &middot;
        <a href="/index.php?route=examiner/exams">&larr; back to exams</a>
    </p>

    <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>

    <?php if ($locked): ?>
        <div class="alert alert-error">A student has already started this exam — the question bank is locked so already-recorded results stay accurate.</div>
    <?php else: ?>
    <section class="card">
        <h2><?= $editing ? 'Edit Question' : 'Add Question' ?></h2>
        <form method="POST"
              action="/index.php?route=examiner/questions/<?= $editing ? 'update' : 'store' ?>"
              class="form-grid" id="question-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>">
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

            <label>Question Text
                <textarea name="question_text" rows="2" required><?= e($oldInput['question_text'] ?? $editing['question_text'] ?? '') ?></textarea>
            </label>

            <label>Marks
                <input type="number" name="marks" step="0.5" min="0.5" value="<?= e((string)($oldInput['marks'] ?? $editing['marks'] ?? '1')) ?>" required>
            </label>

            <?php if ($editing): ?>
                <input type="hidden" id="q-type" value="<?= e($editing['type']) ?>">
                <p>Type: <strong><?= e(str_replace('_', ' ', $editing['type'])) ?></strong> (fixed after creation &mdash; delete and re-add to change type)</p>
            <?php else: ?>
                <?php $oldType = $oldInput['type'] ?? ''; ?>
                <label>Type
                    <select name="type" id="q-type" required>
                        <option value="single" <?= $oldType === 'single' ? 'selected' : '' ?>>Single correct (MCQ)</option>
                        <option value="multi" <?= $oldType === 'multi' ? 'selected' : '' ?>>Multiple correct (MCQ)</option>
                        <option value="true_false" <?= $oldType === 'true_false' ? 'selected' : '' ?>>True / False</option>
                        <option value="fill_blank" <?= $oldType === 'fill_blank' ? 'selected' : '' ?>>Fill in the blank (exact match)</option>
                    </select>
                </label>
            <?php endif; ?>

            <div id="options-block">
                <p class="field-label">Options (check the correct one(s))</p>
                <div id="option-rows">
                    <?php
                    if (isset($oldInput['option_text'])) {
                        $oldCorrect = array_map('intval', $oldInput['option_correct'] ?? []);
                        $rows = [];
                        foreach ($oldInput['option_text'] as $i => $text) {
                            $rows[] = ['option_text' => $text, 'is_correct' => in_array((int)$i, $oldCorrect, true)];
                        }
                    } else {
                        $rows = $editingOptions;
                    }
                    $rows = $rows ?: [['option_text' => '', 'is_correct' => 0], ['option_text' => '', 'is_correct' => 0], ['option_text' => '', 'is_correct' => 0], ['option_text' => '', 'is_correct' => 0]];
                    foreach ($rows as $i => $opt): ?>
                        <div class="option-row">
                            <input type="checkbox" name="option_correct[]" value="<?= $i ?>" <?= !empty($opt['is_correct']) ? 'checked' : '' ?>>
                            <input type="text" name="option_text[]" value="<?= e($opt['option_text'] ?? '') ?>" placeholder="Option text">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" id="add-option-row">+ Add option</button>
            </div>

            <div id="tf-block" class="hidden">
                <p class="field-label">Correct answer</p>
                <?php
                if (isset($oldInput['tf_correct'])) {
                    $tfCorrect = $oldInput['tf_correct'];
                } else {
                    $tfCorrect = 'true';
                    foreach ($editingOptions as $opt) {
                        if ($opt['option_text'] === 'False' && $opt['is_correct']) { $tfCorrect = 'false'; }
                    }
                }
                ?>
                <label class="radio-label"><input type="radio" name="tf_correct" value="true" <?= $tfCorrect === 'true' ? 'checked' : '' ?>> True</label>
                <label class="radio-label"><input type="radio" name="tf_correct" value="false" <?= $tfCorrect === 'false' ? 'checked' : '' ?>> False</label>
            </div>

            <div id="fill-block" class="hidden">
                <label>Correct Answer (case-insensitive exact match)
                    <input type="text" name="correct_answer_text" value="<?= e($oldInput['correct_answer_text'] ?? $editing['correct_answer_text'] ?? '') ?>">
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Question' : 'Add Question' ?></button>
                <?php if ($editing): ?><a class="btn btn-ghost" href="/index.php?route=examiner/questions&exam_id=<?= (int)$exam['id'] ?>">Cancel</a><?php endif; ?>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <section class="card">
        <h2>Questions</h2>
        <?php if (empty($questions)): ?>
            <p class="empty-state">No questions yet — add the first one above.</p>
        <?php endif; ?>
        <?php foreach ($questions as $q): ?>
            <div class="question-row">
                <div class="question-row-main">
                    <span class="badge badge-type"><?= e(str_replace('_', ' ', $q['type'])) ?></span>
                    <strong><?= e($q['question_text']) ?></strong>
                    <span class="marks-tag"><?= e((string)$q['marks']) ?> marks</span>
                </div>
                <ul class="option-list">
                    <?php foreach ($optionsByQuestion[$q['id']] ?? [] as $opt): ?>
                        <li class="<?= $opt['is_correct'] ? 'correct-option' : '' ?>"><?= e($opt['option_text']) ?></li>
                    <?php endforeach; ?>
                    <?php if ($q['type'] === 'fill_blank'): ?>
                        <li class="correct-option">Answer: <?= e($q['correct_answer_text']) ?></li>
                    <?php endif; ?>
                </ul>
                <?php if (!$locked): ?>
                <div class="row-actions">
                    <a class="btn btn-ghost btn-sm" href="/index.php?route=examiner/questions&exam_id=<?= (int)$exam['id'] ?>&edit=<?= (int)$q['id'] ?>">Edit</a>
                    <form method="POST" action="/index.php?route=examiner/questions/delete" class="inline-form" onsubmit="return confirm('Delete this question?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="exam_id" value="<?= (int)$exam['id'] ?>">
                        <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>
<script>
(function () {
    var typeEl = document.getElementById('q-type');
    var optionsBlock = document.getElementById('options-block');
    var tfBlock = document.getElementById('tf-block');
    var fillBlock = document.getElementById('fill-block');

    function sync() {
        var type = typeEl.value || typeEl.getAttribute('value');
        optionsBlock.classList.toggle('hidden', type === 'true_false' || type === 'fill_blank');
        tfBlock.classList.toggle('hidden', type !== 'true_false');
        fillBlock.classList.toggle('hidden', type !== 'fill_blank');
    }

    if (typeEl.tagName === 'SELECT') {
        typeEl.addEventListener('change', sync);
    }
    sync();

    document.getElementById('add-option-row').addEventListener('click', function () {
        var wrap = document.getElementById('option-rows');
        var idx = wrap.children.length;
        var div = document.createElement('div');
        div.className = 'option-row';
        div.innerHTML = '<input type="checkbox" name="option_correct[]" value="' + idx + '">' +
            '<input type="text" name="option_text[]" placeholder="Option text">';
        wrap.appendChild(div);
    });
})();
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
