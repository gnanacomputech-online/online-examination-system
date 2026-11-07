<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>

    <section class="card">
        <h2><?= $editing ? 'Edit Subject' : 'Add Subject' ?></h2>
        <form method="POST" action="/index.php?route=admin/subjects/<?= $editing ? 'update' : 'store' ?>" class="form-grid">
            <?= Csrf::field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><?php endif; ?>
            <label>Name
                <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required>
            </label>
            <label>Description
                <input type="text" name="description" value="<?= e($editing['description'] ?? '') ?>">
            </label>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Create' ?></button>
                <?php if ($editing): ?><a class="btn btn-ghost" href="/index.php?route=admin/subjects">Cancel</a><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>All Subjects (<?= count($subjects) ?>)</h2>
        <p class="muted">A subject can only be deleted while nothing depends on it. Once it has exams or enrolled students, deleting it would cascade through exams &rarr; questions &rarr; attempts and wipe students' recorded results, so the control is withdrawn.</p>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Description</th><th>In use</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($subjects as $subject): ?>
                <?php $inUse = (int)$subject['exam_count'] > 0 || (int)$subject['enrolment_count'] > 0; ?>
                <tr>
                    <td><?= e($subject['name']) ?></td>
                    <td><?= e($subject['description']) ?></td>
                    <td class="muted"><?= (int)$subject['exam_count'] ?> exams &middot; <?= (int)$subject['enrolment_count'] ?> enrolled</td>
                    <td class="row-actions">
                        <a class="btn btn-ghost btn-sm" href="/index.php?route=admin/subjects&edit=<?= (int)$subject['id'] ?>">Edit</a>
                        <?php if ($inUse): ?>
                            <span class="muted">Locked &mdash; in use</span>
                        <?php else: ?>
                            <form method="POST" action="/index.php?route=admin/subjects/delete" class="inline-form" onsubmit="return confirm('Delete this subject?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$subject['id'] ?>">
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
