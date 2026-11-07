<?php require __DIR__ . '/../partials/header.php'; ?>
<?php require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1><?= e($pageTitle) ?></h1>

    <?php if ($msg = flash_get('success')): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($msg = flash_get('error')): ?><div class="alert alert-error"><?= e($msg) ?></div><?php endif; ?>

    <section class="card">
        <h2>Add Student</h2>
        <form method="POST" action="/index.php?route=admin/students/store" class="form-grid">
            <?= Csrf::field() ?>
            <label>Name <input type="text" name="name" required></label>
            <label>Email <input type="email" name="email" required></label>
            <label>Temporary Password <input type="password" name="password" minlength="8" required></label>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Create Account</button></div>
        </form>
    </section>

    <section class="card">
        <h2>All Students (<?= count($students) ?>)</h2>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($students as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge badge-<?= e($u['status']) ?>"><?= e($u['status']) ?></span></td>
                    <td class="row-actions">
                        <form method="POST" action="/index.php?route=admin/students/status" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm"><?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
