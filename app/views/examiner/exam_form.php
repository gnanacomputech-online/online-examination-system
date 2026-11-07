<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>

    <section class="card">
        <form method="POST" action="/index.php?route=examiner/exams/<?= $editing ? 'update' : 'store' ?>" class="form-grid">
            <?= Csrf::field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>

            <label>Title
                <input type="text" name="title" value="<?= e($editing['title'] ?? '') ?>" required>
            </label>

            <label>Subject
                <select name="subject_id" required>
                    <option value="">Select a subject&hellip;</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= ($editing['subject_id'] ?? null) == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Description
                <textarea name="description" rows="2"><?= e($editing['description'] ?? '') ?></textarea>
            </label>

            <label>Duration (minutes)
                <input type="number" name="duration_minutes" min="1" value="<?= e((string)($editing['duration_minutes'] ?? 30)) ?>" required>
            </label>

            <label>Negative Marking (fraction of question marks deducted per wrong answer, 0&ndash;1)
                <input type="number" step="0.05" min="0" max="1" name="negative_marking" value="<?= e((string)($editing['negative_marking'] ?? '0.00')) ?>" required>
            </label>

            <label class="checkbox-label">
                <input type="checkbox" name="shuffle_questions" <?= ($editing['shuffle_questions'] ?? 0) ? 'checked' : '' ?>>
                Shuffle question order per student
            </label>

            <label>Opens At
                <input type="datetime-local" name="start_at" value="<?= e($editing ? date('Y-m-d\TH:i', strtotime($editing['start_at'])) : '') ?>" required>
            </label>

            <label>Closes At
                <input type="datetime-local" name="end_at" value="<?= e($editing ? date('Y-m-d\TH:i', strtotime($editing['end_at'])) : '') ?>" required>
            </label>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Create Exam' ?></button>
                <a class="btn btn-ghost" href="/index.php?route=examiner/exams">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
