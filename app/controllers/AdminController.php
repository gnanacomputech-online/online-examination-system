<?php

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/ExamAttempt.php';

class AdminController
{
    public function __construct()
    {
        Auth::requireRole('super_admin');
    }

    public function dashboard(): void
    {
        $stats = [
            'subjects'  => Subject::count(),
            'examiners' => User::countByRole('examiner'),
            'students'  => User::countByRole('student'),
            'exams'     => Exam::count(),
            'published' => Exam::countByStatus('published'),
            'draft'     => Exam::countByStatus('draft'),
        ];
        $attemptStats = ExamAttempt::globalStats();
        $recentExams = array_slice(Exam::allWithDetails(), 0, 5);

        $pageTitle = 'Admin Dashboard';
        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function subjects(): void
    {
        $editing = isset($_GET['edit']) ? Subject::find((int)$_GET['edit']) : null;
        $subjects = Subject::all();
        $pageTitle = 'Manage Subjects';
        require __DIR__ . '/../views/admin/subjects.php';
    }

    public function subjectStore(): void
    {
        Csrf::requireValid();
        $v = new Validator();
        $v->required($_POST, 'name', 'Subject name');
        if ($v->fails()) {
            flash_set('error', 'Please provide a subject name.');
            redirect('admin/subjects');
        }
        Subject::create(trim($_POST['name']), trim($_POST['description'] ?? ''), Auth::id());
        flash_set('success', 'Subject created.');
        redirect('admin/subjects');
    }

    public function subjectUpdate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $v = new Validator();
        $v->required($_POST, 'name', 'Subject name');
        if ($v->fails() || !Subject::find($id)) {
            flash_set('error', 'Invalid subject.');
            redirect('admin/subjects');
        }
        Subject::update($id, trim($_POST['name']), trim($_POST['description'] ?? ''));
        flash_set('success', 'Subject updated.');
        redirect('admin/subjects');
    }

    public function subjectDelete(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Subject::find($id)) {
            if (Subject::isInUse($id)) {
                flash_set('error', 'This subject already has exams or enrolled students — it can no longer be deleted, to keep their exams and recorded results intact.');
                redirect('admin/subjects');
            }
            Subject::delete($id);
            flash_set('success', 'Subject deleted.');
        }
        redirect('admin/subjects');
    }

    public function examiners(): void
    {
        $examiners = User::allByRole('examiner');
        $pageTitle = 'Manage Examiners';
        require __DIR__ . '/../views/admin/examiners.php';
    }

    public function examinerStore(): void
    {
        Csrf::requireValid();
        $v = new Validator();
        $v->required($_POST, 'name', 'Name')
          ->required($_POST, 'email', 'Email')
          ->email($_POST, 'email', 'Email')
          ->required($_POST, 'password', 'Password')
          ->minLength($_POST, 'password', 8, 'Password');

        $email = trim($_POST['email'] ?? '');
        if (!$v->fails() && User::emailExists($email)) {
            flash_set('error', 'That email is already registered.');
            redirect('admin/examiners');
        }
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('admin/examiners');
        }
        User::create(trim($_POST['name']), $email, $_POST['password'], 'examiner');
        flash_set('success', 'Examiner account created.');
        redirect('admin/examiners');
    }

    public function examinerToggleStatus(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $user = User::find($id);
        if ($user && $user['role'] === 'examiner') {
            User::setStatus($id, $user['status'] === 'active' ? 'inactive' : 'active');
            flash_set('success', 'Examiner status updated.');
        }
        redirect('admin/examiners');
    }

    public function students(): void
    {
        $students = User::allByRole('student');
        $pageTitle = 'Manage Students';
        require __DIR__ . '/../views/admin/students.php';
    }

    public function studentStore(): void
    {
        Csrf::requireValid();
        $v = new Validator();
        $v->required($_POST, 'name', 'Name')
          ->required($_POST, 'email', 'Email')
          ->email($_POST, 'email', 'Email')
          ->required($_POST, 'password', 'Password')
          ->minLength($_POST, 'password', 8, 'Password');

        $email = trim($_POST['email'] ?? '');
        if (!$v->fails() && User::emailExists($email)) {
            flash_set('error', 'That email is already registered.');
            redirect('admin/students');
        }
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('admin/students');
        }
        User::create(trim($_POST['name']), $email, $_POST['password'], 'student');
        flash_set('success', 'Student account created.');
        redirect('admin/students');
    }

    public function studentToggleStatus(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $user = User::find($id);
        if ($user && $user['role'] === 'student') {
            User::setStatus($id, $user['status'] === 'active' ? 'inactive' : 'active');
            flash_set('success', 'Student status updated.');
        }
        redirect('admin/students');
    }

    public function exams(): void
    {
        $exams = Exam::allWithDetails();
        $pageTitle = 'All Exams';
        require __DIR__ . '/../views/admin/exams.php';
    }

    public function analytics(): void
    {
        $stats = ExamAttempt::globalStats();
        $topExams = ExamAttempt::topExamsByAttempts(10);
        $pageTitle = 'Global Analytics';
        require __DIR__ . '/../views/admin/analytics.php';
    }
}
