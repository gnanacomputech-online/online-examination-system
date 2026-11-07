<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <div class="card-head">
        <h1><?= e($pageTitle) ?></h1>
        <a class="btn btn-primary" href="/index.php?route=examiner/exams/create">+ New Exam</a>
    </div>

    <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>

    <section class="card">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Subject</th><th>Duration</th><th>Neg. Marking</th><th>Window</th><th>Questions</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($exams as $exam): ?>
                <tr>
                    <td><?= e($exam['title']) ?></td>
                    <td><?= e($exam['subject_name']) ?></td>
                    <td><?= (int)$exam['duration_minutes'] ?> min</td>
                    <td><?= e($exam['negative_marking']) ?></td>
                    <td><?= e(date('d M H:i', strtotime($exam['start_at']))) ?> &ndash; <?= e(date('d M H:i', strtotime($exam['end_at']))) ?></td>
                    <td><?= (int)$exam['question_count'] ?> (<?= e($exam['total_marks']) ?> marks)</td>
                    <td><span class="badge badge-<?= e($exam['status']) ?>"><?= e($exam['status']) ?></span></td>
                    <td class="row-actions">
                        <a class="btn btn-ghost btn-sm" href="/index.php?route=examiner/questions&exam_id=<?= (int)$exam['id'] ?>">Questions</a>
                        <?php if ((int)$exam['attempt_count'] === 0): ?>
                            <a class="btn btn-ghost btn-sm" href="/index.php?route=examiner/exams/edit&id=<?= (int)$exam['id'] ?>">Edit</a>
                        <?php endif; ?>
                        <a class="btn btn-ghost btn-sm" href="/index.php?route=examiner/results&exam_id=<?= (int)$exam['id'] ?>">Results</a>
                        <a class="btn btn-ghost btn-sm" href="/index.php?route=examiner/analytics&exam_id=<?= (int)$exam['id'] ?>">Analytics</a>
                        <form method="POST" action="/index.php?route=examiner/exams/publish" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$exam['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"><?= $exam['status'] === 'draft' ? 'Publish' : 'Unpublish' ?></button>
                        </form>
                        <?php if ((int)$exam['attempt_count'] === 0): ?>
                            <form method="POST" action="/index.php?route=examiner/exams/delete" class="inline-form" onsubmit="return confirm('Delete this exam and all its questions?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$exam['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
