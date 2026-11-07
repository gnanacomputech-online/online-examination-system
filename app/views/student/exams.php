<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>

    <section class="card">
        <?php if (empty($exams)): ?>
            <p class="empty-state">No exams available for your enrolled subjects right now.</p>
        <?php endif; ?>
        <?php foreach ($exams as $exam): ?>
            <?php include __DIR__ . '/_exam_row.php'; ?>
        <?php endforeach; ?>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
