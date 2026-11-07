<?php $pageTitle = 'Login'; require __DIR__ . '/../partials/header.php'; ?>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">&#9673;</span>
            <h1>ExamCore</h1>
            <p class="auth-sub">Distraction-free online examinations</p>
        </div>

        <?php if ($error = flash_get('error')): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success = flash_get('success')): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/index.php?route=login" class="auth-form">
            <?= Csrf::field() ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= old($old ?? [], 'email') ?>" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <details class="demo-creds">
            <summary>Demo credentials</summary>
            <table>
                <tr><th>Role</th><th>Email</th><th>Password</th></tr>
                <tr><td>Super Admin</td><td>superadmin@exam.test</td><td>Admin@123</td></tr>
                <tr><td>Examiner</td><td>examiner1@exam.test</td><td>Examiner@123</td></tr>
                <tr><td>Student</td><td>student1@exam.test</td><td>Student@123</td></tr>
            </table>
        </details>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
