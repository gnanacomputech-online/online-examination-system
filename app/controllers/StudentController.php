<?php

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/QuestionOption.php';
require_once __DIR__ . '/../models/ExamAttempt.php';
require_once __DIR__ . '/../models/AttemptAnswer.php';
require_once __DIR__ . '/../models/Grader.php';

class StudentController
{
    /** Tab-switch/focus-loss violations tolerated before an in-progress attempt is force-submitted as disqualified. */
    private const VIOLATION_THRESHOLD = 5;

    public function __construct()
    {
        Auth::requireRole('student');
    }

    public function dashboard(): void
    {
        $subjectIds = Enrollment::subjectIdsForStudent(Auth::id());
        $exams = $this->availableExams($subjectIds);
        $history = array_slice(ExamAttempt::allByStudentWithExam(Auth::id()), 0, 5);
        $pageTitle = 'Student Dashboard';
        require __DIR__ . '/../views/student/dashboard.php';
    }

    public function exams(): void
    {
        $subjectIds = Enrollment::subjectIdsForStudent(Auth::id());
        $exams = $this->availableExams($subjectIds);
        $pageTitle = 'Available Exams';
        require __DIR__ . '/../views/student/exams.php';
    }

    private function availableExams(array $subjectIds): array
    {
        $exams = Exam::publishedForSubjects($subjectIds);
        $studentId = Auth::id();
        foreach ($exams as &$exam) {
            $attempt = ExamAttempt::findForStudentExam($exam['id'], $studentId);
            $exam['attempt_status'] = $attempt['status'] ?? null;
            $exam['attempt_id'] = $attempt['id'] ?? null;
            $exam['attempt_score'] = $attempt['score'] ?? null;
        }
        return $exams;
    }

    public function examStart(): void
    {
        $examId = (int)($_GET['exam_id'] ?? 0);
        $studentId = Auth::id();

        // Resolve an existing attempt BEFORE any of the availability gates below. An
        // attempt already under way is the student's own work and must stay reachable
        // even if the exam has since become unavailable to *start* — its window closed
        // while they were sitting it, the examiner unpublished it, or the Super Admin
        // deactivated the examiner. Gating the resume on those (as this used to) left the
        // attempt permanently `in_progress`: never graded, no result, no way back in,
        // while "Resume" still showed on My Results. examAttempt() applies the real
        // deadline, auto-grading the attempt if its duration has already elapsed.
        $attempt = ExamAttempt::findForStudentExam($examId, $studentId);
        if ($attempt !== null) {
            if ($attempt['status'] === 'in_progress') {
                redirect('student/exam/attempt&attempt_id=' . $attempt['id']);
            }
            redirect('student/results/view&attempt_id=' . $attempt['id']);
        }

        $exam = Exam::findWithSubject($examId);
        if (!$exam || $exam['status'] !== 'published' || !Enrollment::isEnrolled($studentId, (int)$exam['subject_id'])) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }

        if (($exam['examiner_status'] ?? 'active') !== 'active') {
            flash_set('error', 'This exam is unavailable — the examiner who set it is no longer an active account.');
            redirect('student/exams');
        }

        $now = time();
        if ($now < strtotime($exam['start_at']) || $now > strtotime($exam['end_at'])) {
            flash_set('error', 'This exam is not open right now.');
            redirect('student/exams');
        }

        $totalMarks = Question::sumMarksByExam($examId);
        if ($totalMarks <= 0) {
            flash_set('error', 'This exam has no questions yet.');
            redirect('student/exams');
        }

        try {
            $attemptId = ExamAttempt::create($examId, $studentId, $totalMarks);
        } catch (PDOException $ex) {
            // UNIQUE(exam_id, student_id) tripped — a double-clicked "Start Exam" reached
            // here twice. The row the other request inserted is the one to resume.
            $existing = ExamAttempt::findForStudentExam($examId, $studentId);
            if ($existing === null) {
                throw $ex;
            }
            $attemptId = (int)$existing['id'];
        }

        redirect('student/exam/attempt&attempt_id=' . $attemptId);
    }

    /**
     * Server-side deadline enforcement, shared by every write path that touches a live
     * attempt (autosave, integrity-violation logging, submit). Returns true when the
     * attempt's allotted time was already up — in which case it has just been
     * force-graded as `auto_submitted` and the caller must record nothing further.
     */
    private function expireIfTimeUp(array $attempt, array $exam): bool
    {
        $elapsed = time() - strtotime($attempt['started_at']);
        if ($elapsed <= ((int)$exam['duration_minutes'] * 60)) {
            return false;
        }
        Grader::submitAndGrade(
            (int)$attempt['id'],
            (int)$exam['id'],
            (float)$exam['negative_marking'],
            'auto_submitted'
        );
        return true;
    }

    public function examAttempt(): void
    {
        $attemptId = (int)($_GET['attempt_id'] ?? 0);
        $attempt = ExamAttempt::findWithExamForStudent($attemptId, Auth::id());

        if (!$attempt) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }

        if ($attempt['status'] !== 'in_progress') {
            redirect('student/results/view&attempt_id=' . $attemptId);
        }

        $elapsed = time() - strtotime($attempt['started_at']);
        $remaining = ($attempt['duration_minutes'] * 60) - $elapsed;

        if ($remaining <= 0) {
            Grader::submitAndGrade($attemptId, (int)$attempt['exam_id'], (float)$attempt['negative_marking'], 'auto_submitted');
            redirect('student/results/view&attempt_id=' . $attemptId);
        }

        $questions = Question::allByExam((int)$attempt['exam_id']);
        if ($attempt['shuffle_questions']) {
            mt_srand($attemptId);
            shuffle($questions);
        }

        $optionsByQuestion = QuestionOption::allByQuestionIds(array_column($questions, 'id'));
        foreach ($optionsByQuestion as &$opts) {
            foreach ($opts as &$opt) {
                unset($opt['is_correct']);
            }
        }
        unset($opts, $opt);

        $existingAnswers = AttemptAnswer::allByAttempt($attemptId);

        $pageTitle = $attempt['exam_title'];
        require __DIR__ . '/../views/student/exam_attempt.php';
    }

    public function autosave(): void
    {
        header('Content-Type: application/json');
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false]);
            return;
        }

        $attemptId = (int)($_POST['attempt_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);
        $attempt = ExamAttempt::find($attemptId);

        if (!$attempt || (int)$attempt['student_id'] !== Auth::id() || $attempt['status'] !== 'in_progress') {
            http_response_code(403);
            echo json_encode(['ok' => false]);
            return;
        }

        // Time may have run out without the client ever reloading the page (backgrounded
        // tab, laptop sleep) — force-grade now instead of accepting further answers.
        $exam = Exam::find((int)$attempt['exam_id']);
        if ($this->expireIfTimeUp($attempt, $exam)) {
            echo json_encode(['ok' => false, 'expired' => true]);
            return;
        }

        $selectedIds = isset($_POST['selected_option_ids']) ? trim((string)$_POST['selected_option_ids']) : null;
        $answerText = isset($_POST['answer_text']) ? trim((string)$_POST['answer_text']) : null;

        AttemptAnswer::autosave($attemptId, $questionId, $selectedIds ?: null, $answerText ?: null);
        echo json_encode(['ok' => true]);
    }

    public function violation(): void
    {
        header('Content-Type: application/json');
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            echo json_encode(['ok' => false]);
            return;
        }

        $attemptId = (int)($_POST['attempt_id'] ?? 0);
        $attempt = ExamAttempt::find($attemptId);

        if (!$attempt || (int)$attempt['student_id'] !== Auth::id() || $attempt['status'] !== 'in_progress') {
            http_response_code(403);
            echo json_encode(['ok' => false]);
            return;
        }

        // Third write path into a live attempt, so it gets the same deadline check as
        // autosave/submit: a backgrounded tab firing visibilitychange after time is up
        // must not keep incrementing violation_count on what is really a finished exam.
        $exam = Exam::find((int)$attempt['exam_id']);
        if ($this->expireIfTimeUp($attempt, $exam)) {
            echo json_encode(['ok' => false, 'expired' => true]);
            return;
        }

        $violationCount = ExamAttempt::recordViolation($attemptId);

        if ($violationCount >= self::VIOLATION_THRESHOLD) {
            Grader::submitAndGrade($attemptId, (int)$exam['id'], (float)$exam['negative_marking'], 'disqualified');
            echo json_encode([
                'ok'               => true,
                'violation_count'  => $violationCount,
                'threshold'        => self::VIOLATION_THRESHOLD,
                'disqualified'     => true,
                'redirect'         => '/index.php?route=student/results/view&attempt_id=' . $attemptId,
            ]);
            return;
        }

        echo json_encode([
            'ok'              => true,
            'violation_count' => $violationCount,
            'threshold'       => self::VIOLATION_THRESHOLD,
        ]);
    }

    public function submit(): void
    {
        Csrf::requireValid();
        $attemptId = (int)($_POST['attempt_id'] ?? 0);
        $attempt = ExamAttempt::find($attemptId);

        if (!$attempt || (int)$attempt['student_id'] !== Auth::id() || $attempt['status'] !== 'in_progress') {
            redirect('student/exams');
        }

        $exam = Exam::find((int)$attempt['exam_id']);
        $elapsed = time() - strtotime($attempt['started_at']);
        $isLate = $elapsed > ($exam['duration_minutes'] * 60);
        Grader::submitAndGrade($attemptId, (int)$exam['id'], (float)$exam['negative_marking'], $isLate ? 'auto_submitted' : 'submitted');

        flash_set('success', $isLate ? 'Time was already up — your answers were auto-submitted as-is.' : 'Exam submitted and graded.');
        redirect('student/results/view&attempt_id=' . $attemptId);
    }

    public function results(): void
    {
        $history = ExamAttempt::allByStudentWithExam(Auth::id());
        $pageTitle = 'My Results';
        require __DIR__ . '/../views/student/results.php';
    }

    public function resultView(): void
    {
        $attemptId = (int)($_GET['attempt_id'] ?? 0);
        $attempt = ExamAttempt::findWithExamForStudent($attemptId, Auth::id());

        if (!$attempt || $attempt['status'] === 'in_progress') {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }

        $questions = Question::allByExam((int)$attempt['exam_id']);
        $optionsByQuestion = QuestionOption::allByQuestionIds(array_column($questions, 'id'));
        $answers = AttemptAnswer::allByAttempt($attemptId);

        $pageTitle = 'Result — ' . $attempt['exam_title'];
        require __DIR__ . '/../views/student/result_detail.php';
    }
}
