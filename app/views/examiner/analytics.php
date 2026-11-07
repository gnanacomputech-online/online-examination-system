<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>
    <p class="page-sub"><a href="/index.php?route=examiner/exams">&larr; back to exams</a></p>

    <section class="card">
        <h2>Question-wise Performance</h2>
        <table class="data-table">
            <thead><tr><th>Question</th><th>Type</th><th>Marks</th><th>Answered</th><th>Correct</th><th>% Correct</th></tr></thead>
            <tbody>
            <?php foreach ($questionStats as $q): ?>
                <?php $pct = $q['answered_count'] > 0 ? round(($q['correct_count'] / $q['answered_count']) * 100, 1) : null; ?>
                <tr>
                    <td><?= e($q['question_text']) ?></td>
                    <td><?= e(str_replace('_', ' ', $q['type'])) ?></td>
                    <td><?= e((string)$q['marks']) ?></td>
                    <td><?= (int)$q['answered_count'] ?></td>
                    <td><?= (int)$q['correct_count'] ?></td>
                    <td>
                        <?php if ($pct === null): ?>&mdash;<?php else: ?>
                            <span class="<?= $pct < 40 ? 'text-danger' : '' ?>"><?= $pct ?>%</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="page-sub">Questions under 40% correct are flagged &mdash; worth reviewing for clarity or difficulty.</p>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
