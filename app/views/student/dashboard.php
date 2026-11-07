<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub">Welcome back, <?= e(Auth::name()) ?>.</p>

    <section class="card">
        <div class="card-head">
            <h2>Available Exams</h2>
            <a class="btn btn-ghost btn-sm" href="/index.php?route=student/exams">View all &rarr;</a>
        </div>
        <?php if (empty($exams)): ?>
            <p class="empty-state">No exams open right now. Check back later.</p>
        <?php endif; ?>
        <?php foreach (array_slice($exams, 0, 4) as $exam): ?>
            <?php include __DIR__ . '/_exam_row.php'; ?>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <div class="card-head">
            <h2>Recent Results</h2>
            <a class="btn btn-ghost btn-sm" href="/index.php?route=student/results">View all &rarr;</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Exam</th><th>Score</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php if (empty($history)): ?>
                <tr><td colspan="3" class="empty-state">No attempts yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><a href="/index.php?route=student/results/view&attempt_id=<?= (int)$h['id'] ?>"><?= e($h['exam_title']) ?></a></td>
                    <td><?= $h['score'] !== null ? e($h['score']) . ' / ' . e($h['total_marks']) : 'in progress' ?></td>
                    <td><?= $h['submitted_at'] ? e(date('d M Y H:i', strtotime($h['submitted_at']))) : '&mdash;' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
