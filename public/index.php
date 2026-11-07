<?php

declare(strict_types=1);

// Let PHP's built-in dev server (`php -S`) serve real static files directly
// instead of routing every asset request through the front controller.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $assetPath = __DIR__ . $requestPath;
    if (!str_ends_with($requestPath, '.php') && is_file($assetPath)) {
        return false;
    }
}

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/ExaminerController.php';
require_once __DIR__ . '/../app/controllers/StudentController.php';

$router = new Router();

// ---- Auth ----
$router->get('login', fn() => (new AuthController())->showLogin());
$router->post('login', fn() => (new AuthController())->login());
$router->post('logout', fn() => (new AuthController())->logout());

// ---- Super Admin ----
$router->get('admin/dashboard', fn() => (new AdminController())->dashboard());
$router->get('admin/subjects', fn() => (new AdminController())->subjects());
$router->post('admin/subjects/store', fn() => (new AdminController())->subjectStore());
$router->post('admin/subjects/update', fn() => (new AdminController())->subjectUpdate());
$router->post('admin/subjects/delete', fn() => (new AdminController())->subjectDelete());
$router->get('admin/examiners', fn() => (new AdminController())->examiners());
$router->post('admin/examiners/store', fn() => (new AdminController())->examinerStore());
$router->post('admin/examiners/status', fn() => (new AdminController())->examinerToggleStatus());
$router->get('admin/students', fn() => (new AdminController())->students());
$router->post('admin/students/store', fn() => (new AdminController())->studentStore());
$router->post('admin/students/status', fn() => (new AdminController())->studentToggleStatus());
$router->get('admin/exams', fn() => (new AdminController())->exams());
$router->get('admin/analytics', fn() => (new AdminController())->analytics());

// ---- Examiner ----
$router->get('examiner/dashboard', fn() => (new ExaminerController())->dashboard());
$router->get('examiner/exams', fn() => (new ExaminerController())->exams());
$router->get('examiner/exams/create', fn() => (new ExaminerController())->examCreateForm());
$router->post('examiner/exams/store', fn() => (new ExaminerController())->examStore());
$router->get('examiner/exams/edit', fn() => (new ExaminerController())->examEditForm());
$router->post('examiner/exams/update', fn() => (new ExaminerController())->examUpdate());
$router->post('examiner/exams/delete', fn() => (new ExaminerController())->examDelete());
$router->post('examiner/exams/publish', fn() => (new ExaminerController())->examPublishToggle());
$router->get('examiner/questions', fn() => (new ExaminerController())->questions());
$router->post('examiner/questions/store', fn() => (new ExaminerController())->questionStore());
$router->post('examiner/questions/update', fn() => (new ExaminerController())->questionUpdate());
$router->post('examiner/questions/delete', fn() => (new ExaminerController())->questionDelete());
$router->get('examiner/results', fn() => (new ExaminerController())->results());
$router->get('examiner/analytics', fn() => (new ExaminerController())->analytics());

// ---- Student ----
$router->get('student/dashboard', fn() => (new StudentController())->dashboard());
$router->get('student/exams', fn() => (new StudentController())->exams());
$router->get('student/exam/start', fn() => (new StudentController())->examStart());
$router->get('student/exam/attempt', fn() => (new StudentController())->examAttempt());
$router->post('student/exam/autosave', fn() => (new StudentController())->autosave());
$router->post('student/exam/violation', fn() => (new StudentController())->violation());
$router->post('student/exam/submit', fn() => (new StudentController())->submit());
$router->get('student/results', fn() => (new StudentController())->results());
$router->get('student/results/view', fn() => (new StudentController())->resultView());

$route = $_GET['route'] ?? '';

if ($route === '') {
    if (Auth::check()) {
        redirect(Auth::dashboardRoute(Auth::role()));
    }
    redirect('login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $route);
