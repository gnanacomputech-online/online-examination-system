<?php
$role = Auth::role();
$navItems = [];
if ($role === 'super_admin') {
    $navItems = [
        'admin/dashboard'  => 'Dashboard',
        'admin/subjects'   => 'Subjects',
        'admin/examiners'  => 'Examiners',
        'admin/students'   => 'Students',
        'admin/exams'      => 'All Exams',
        'admin/analytics'  => 'Analytics',
    ];
} elseif ($role === 'examiner') {
    $navItems = [
        'examiner/dashboard' => 'Dashboard',
        'examiner/exams'     => 'My Exams',
    ];
} elseif ($role === 'student') {
    $navItems = [
        'student/dashboard' => 'Dashboard',
        'student/exams'     => 'Exams',
        'student/results'   => 'My Results',
    ];
}
$currentRoute = $_GET['route'] ?? '';
?>
<nav class="navbar">
    <div class="navbar-brand">
        <span class="brand-mark">&#9673;</span> ExamCore
    </div>
    <ul class="navbar-links">
        <?php foreach ($navItems as $route => $label): ?>
            <li>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="<?= str_starts_with($currentRoute, explode('/', $route)[0] . '/') ? 'active' : '' ?>">
                    <?= e($label) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="navbar-user">
        <span class="user-name"><?= e(Auth::name()) ?></span>
        <span class="user-role"><?= e(str_replace('_', ' ', $role)) ?></span>
        <form method="POST" action="/index.php?route=logout" class="inline-form">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-ghost">Logout</button>
        </form>
    </div>
</nav>
