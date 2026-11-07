<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub"><a href="/index.php?route=examiner/exams">&larr; back to exams</a></p>

    <section class="card">
        <table class="data-table">
            <thead><tr><th>Student</th><th>Status</th><th>Score</th><th>Violations</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php if (empty($attempts)): ?>
                <tr><td colspan="5" class="empty-state">No attempts yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($attempts as $a): ?>
                <tr>
                    <td><?= e($a['student_name']) ?> <span class="muted"><?= e($a['student_email']) ?></span></td>
                    <td><span class="badge badge-<?= e($a['status']) ?>"><?= e(str_replace('_', ' ', $a['status'])) ?></span></td>
                    <td><?= $a['score'] !== null ? e($a['score']) . ' / ' . e($a['total_marks']) : '&mdash;' ?></td>
                    <td><?= (int)$a['violation_count'] ?></td>
                    <td><?= $a['submitted_at'] ? e(date('d M Y H:i', strtotime($a['submitted_at']))) : 'in progress' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
