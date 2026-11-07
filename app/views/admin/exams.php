<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub">Read-only overview across every examiner's exams.</p>

    <section class="card">
        <table class="data-table">
            <thead><tr><th>Title</th><th>Subject</th><th>Examiner</th><th>Window</th><th>Questions</th><th>Attempts</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($exams as $exam): ?>
                <tr>
                    <td><?= e($exam['title']) ?></td>
                    <td><?= e($exam['subject_name']) ?></td>
                    <td><?= e($exam['examiner_name']) ?></td>
                    <td><?= e(date('d M Y H:i', strtotime($exam['start_at']))) ?> &ndash; <?= e(date('d M Y H:i', strtotime($exam['end_at']))) ?></td>
                    <td><?= (int)$exam['question_count'] ?></td>
                    <td><?= (int)$exam['attempt_count'] ?></td>
                    <td><span class="badge badge-<?= e($exam['status']) ?>"><?= e($exam['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
