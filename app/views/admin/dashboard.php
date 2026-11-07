<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub">System-wide overview across all examiners and students.</p>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['subjects'] ?></span><span class="stat-label">Subjects</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['examiners'] ?></span><span class="stat-label">Examiners</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['students'] ?></span><span class="stat-label">Students</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['exams'] ?></span><span class="stat-label">Total Exams</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['published'] ?></span><span class="stat-label">Published</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['draft'] ?></span><span class="stat-label">Draft</span></div>
    </div>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= (int)$attemptStats['total_attempts'] ?></span><span class="stat-label">Total Attempts</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$attemptStats['submitted_attempts'] ?></span><span class="stat-label">Graded Attempts</span></div>
        <div class="stat-tile"><span class="stat-value"><?= number_format($attemptStats['avg_score_pct'], 1) ?>%</span><span class="stat-label">Average Score</span></div>
    </div>

    <section class="card">
        <h2>Recent Exams</h2>
        <table class="data-table">
            <thead><tr><th>Title</th><th>Subject</th><th>Examiner</th><th>Questions</th><th>Attempts</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentExams as $exam): ?>
                <tr>
                    <td><?= e($exam['title']) ?></td>
                    <td><?= e($exam['subject_name']) ?></td>
                    <td><?= e($exam['examiner_name']) ?></td>
                    <td><?= (int)$exam['question_count'] ?></td>
                    <td><?= (int)$exam['attempt_count'] ?></td>
                    <td><span class="badge badge-<?= e($exam['status']) ?>"><?= e($exam['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <a class="btn btn-ghost" href="/index.php?route=admin/exams">View all exams &rarr;</a>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
