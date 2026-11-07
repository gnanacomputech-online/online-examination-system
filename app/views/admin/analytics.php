<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['total_attempts'] ?></span><span class="stat-label">Total Attempts</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['submitted_attempts'] ?></span><span class="stat-label">Graded Attempts</span></div>
        <div class="stat-tile"><span class="stat-value"><?= number_format($stats['avg_score_pct'], 1) ?>%</span><span class="stat-label">Average Score</span></div>
    </div>

    <section class="card">
        <h2>Exam Performance</h2>
        <table class="data-table">
            <thead><tr><th>Exam</th><th>Subject</th><th>Attempts</th><th>Average Score</th></tr></thead>
            <tbody>
            <?php foreach ($topExams as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['subject_name']) ?></td>
                    <td><?= (int)$row['attempts'] ?></td>
                    <td><?= $row['avg_pct'] !== null ? number_format($row['avg_pct'], 1) . '%' : '&mdash;' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
