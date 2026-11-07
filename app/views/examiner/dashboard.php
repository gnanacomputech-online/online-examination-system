<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub">Welcome back, <?= e(Auth::name()) ?>.</p>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= count($exams) ?></span><span class="stat-label">My Exams</span></div>
        <div class="stat-tile"><span class="stat-value"><?= count(array_filter($exams, fn($x) => $x['status'] === 'published')) ?></span><span class="stat-label">Published</span></div>
        <div class="stat-tile"><span class="stat-value"><?= count(array_filter($exams, fn($x) => $x['status'] === 'draft')) ?></span><span class="stat-label">Draft</span></div>
    </div>

    <section class="card">
        <div class="card-head">
            <h2>My Exams</h2>
            <a class="btn btn-primary btn-sm" href="/index.php?route=examiner/exams/create">+ New Exam</a>
        </div>
        <table class="data-table">
            <thead><tr><th>Title</th><th>Subject</th><th>Questions</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach (array_slice($exams, 0, 8) as $exam): ?>
                <tr>
                    <td><?= e($exam['title']) ?></td>
                    <td><?= e($exam['subject_name']) ?></td>
                    <td><?= (int)$exam['question_count'] ?></td>
                    <td><span class="badge badge-<?= e($exam['status']) ?>"><?= e($exam['status']) ?></span></td>
                    <td><a class="btn btn-ghost btn-sm" href="/index.php?route=examiner/questions&exam_id=<?= (int)$exam['id'] ?>">Manage</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
