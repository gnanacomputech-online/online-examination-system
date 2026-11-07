<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>

    <section class="card">
        <table class="data-table">
            <thead><tr><th>Exam</th><th>Subject</th><th>Status</th><th>Score</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($history)): ?>
                <tr><td colspan="6" class="empty-state">No attempts yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= e($h['exam_title']) ?></td>
                    <td><?= e($h['subject_name']) ?></td>
                    <td><span class="badge badge-<?= e($h['status']) ?>"><?= e(str_replace('_', ' ', $h['status'])) ?></span></td>
                    <td><?= $h['score'] !== null ? e($h['score']) . ' / ' . e($h['total_marks']) : '&mdash;' ?></td>
                    <td><?= $h['submitted_at'] ? e(date('d M Y H:i', strtotime($h['submitted_at']))) : 'in progress' ?></td>
                    <td>
                        <?php if ($h['status'] === 'in_progress'): ?>
                            <a class="btn btn-primary btn-sm" href="/index.php?route=student/exam/start&exam_id=<?= (int)$h['exam_id'] ?>">Resume</a>
                        <?php else: ?>
                            <a class="btn btn-ghost btn-sm" href="/index.php?route=student/results/view&attempt_id=<?= (int)$h['id'] ?>">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
