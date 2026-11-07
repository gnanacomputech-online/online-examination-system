<?php

require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../models/Exam.php';
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/QuestionOption.php';
require_once __DIR__ . '/../models/ExamAttempt.php';
require_once __DIR__ . '/../models/AttemptAnswer.php';

class ExaminerController
{
    public function __construct()
    {
        Auth::requireRole('examiner');
    }

    private function ownedExamOrFail(int $examId): array
    {
        $exam = Exam::findWithSubject($examId);
        if (!$exam || (int)$exam['examiner_id'] !== Auth::id()) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            exit;
        }
        return $exam;
    }

    public function dashboard(): void
    {
        $exams = Exam::allByExaminer(Auth::id());
        $pageTitle = 'Examiner Dashboard';
        require __DIR__ . '/../views/examiner/dashboard.php';
    }

    public function exams(): void
    {
        $exams = Exam::allByExaminer(Auth::id());
        $pageTitle = 'My Exams';
        require __DIR__ . '/../views/examiner/exams.php';
    }

    public function examCreateForm(): void
    {
        $subjects = Subject::all();
        $editing = null;
        $pageTitle = 'Create Exam';
        require __DIR__ . '/../views/examiner/exam_form.php';
    }

    public function examEditForm(): void
    {
        $editing = $this->ownedExamOrFail((int)($_GET['id'] ?? 0));
        if (ExamAttempt::existsForExam($editing['id'])) {
            flash_set('error', 'A student has already started this exam — it can no longer be edited.');
            redirect('examiner/exams');
        }
        $subjects = Subject::all();
        $pageTitle = 'Edit Exam';
        require __DIR__ . '/../views/examiner/exam_form.php';
    }

    private function validateExamInput(array $data): Validator
    {
        $v = new Validator();
        $v->required($data, 'title', 'Title')
          ->required($data, 'subject_id', 'Subject')
          ->required($data, 'duration_minutes', 'Duration')
          ->numeric($data, 'duration_minutes', 'Duration')
          ->min($data, 'duration_minutes', 1, 'Duration')
          ->required($data, 'negative_marking', 'Negative marking')
          ->numeric($data, 'negative_marking', 'Negative marking')
          ->required($data, 'start_at', 'Start time')
          ->required($data, 'end_at', 'End time');
        return $v;
    }

    public function examStore(): void
    {
        Csrf::requireValid();
        $v = $this->validateExamInput($_POST);
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('examiner/exams/create');
        }
        $id = Exam::create([
            'subject_id'        => (int)$_POST['subject_id'],
            'examiner_id'       => Auth::id(),
            'title'             => trim($_POST['title']),
            'description'       => trim($_POST['description'] ?? ''),
            'duration_minutes'  => (int)$_POST['duration_minutes'],
            'negative_marking'  => (float)$_POST['negative_marking'],
            'shuffle_questions' => isset($_POST['shuffle_questions']) ? 1 : 0,
            'status'            => 'draft',
            'start_at'          => $_POST['start_at'],
            'end_at'            => $_POST['end_at'],
        ]);
        flash_set('success', 'Exam created as draft. Add questions before publishing.');
        redirect('examiner/questions&exam_id=' . $id);
    }

    public function examUpdate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $this->ownedExamOrFail($id);
        if (ExamAttempt::existsForExam($id)) {
            flash_set('error', 'A student has already started this exam — it can no longer be edited.');
            redirect('examiner/exams');
        }
        $v = $this->validateExamInput($_POST);
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            redirect('examiner/exams/edit&id=' . $id);
        }
        Exam::update($id, [
            'subject_id'        => (int)$_POST['subject_id'],
            'title'             => trim($_POST['title']),
            'description'       => trim($_POST['description'] ?? ''),
            'duration_minutes'  => (int)$_POST['duration_minutes'],
            'negative_marking'  => (float)$_POST['negative_marking'],
            'shuffle_questions' => isset($_POST['shuffle_questions']) ? 1 : 0,
            'start_at'          => $_POST['start_at'],
            'end_at'            => $_POST['end_at'],
        ]);
        flash_set('success', 'Exam updated.');
        redirect('examiner/exams');
    }

    public function examDelete(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $this->ownedExamOrFail($id);
        if (ExamAttempt::existsForExam($id)) {
            flash_set('error', 'A student has already started this exam — it can no longer be deleted, to keep their recorded result intact.');
            redirect('examiner/exams');
        }
        Exam::delete($id);
        flash_set('success', 'Exam deleted.');
        redirect('examiner/exams');
    }

    public function examPublishToggle(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $exam = $this->ownedExamOrFail($id);

        if ($exam['status'] === 'draft') {
            if (Question::sumMarksByExam($id) <= 0) {
                flash_set('error', 'Add at least one question before publishing.');
                redirect('examiner/questions&exam_id=' . $id);
            }
            Exam::setStatus($id, 'published');
            flash_set('success', 'Exam published — visible to enrolled students during its window.');
        } else {
            Exam::setStatus($id, 'draft');
            flash_set('success', 'Exam moved back to draft.');
        }
        redirect('examiner/exams');
    }

    public function questions(): void
    {
        $exam = $this->ownedExamOrFail((int)($_GET['exam_id'] ?? 0));
        $questions = Question::allByExam($exam['id']);
        $optionsByQuestion = QuestionOption::allByQuestionIds(array_column($questions, 'id'));
        $editing = isset($_GET['edit']) ? Question::find((int)$_GET['edit']) : null;
        $editingOptions = $editing ? ($optionsByQuestion[$editing['id']] ?? []) : [];
        $oldInput = old_input_get();
        $locked = ExamAttempt::existsForExam($exam['id']);
        $pageTitle = 'Question Bank — ' . $exam['title'];
        require __DIR__ . '/../views/examiner/questions.php';
    }

    private function validateOptionsInput(string $type): ?string
    {
        if ($type !== 'single' && $type !== 'multi') {
            return null;
        }

        $texts = $_POST['option_text'] ?? [];
        $correctIndexes = array_map('intval', $_POST['option_correct'] ?? []);
        $nonEmptyCount = 0;
        $correctCount = 0;
        foreach ($texts as $i => $text) {
            if (trim((string)$text) === '') {
                continue;
            }
            $nonEmptyCount++;
            if (in_array($i, $correctIndexes, true)) {
                $correctCount++;
            }
        }

        if ($nonEmptyCount < 2) {
            return 'Add at least two options.';
        }
        if ($type === 'single' && $correctCount !== 1) {
            return 'Select exactly one correct option for a single-correct MCQ.';
        }
        if ($type === 'multi' && $correctCount < 2) {
            return 'Select more than one correct option for a multiple-correct MCQ.';
        }
        return null;
    }

    private function saveQuestionOptions(int $questionId, string $type): void
    {
        if ($type === 'fill_blank') {
            QuestionOption::replaceForQuestion($questionId, []);
            return;
        }

        if ($type === 'true_false') {
            $correct = ($_POST['tf_correct'] ?? 'true') === 'true';
            QuestionOption::replaceForQuestion($questionId, [
                ['text' => 'True', 'is_correct' => $correct],
                ['text' => 'False', 'is_correct' => !$correct],
            ]);
            return;
        }

        $texts = $_POST['option_text'] ?? [];
        $correctIndexes = array_map('intval', $_POST['option_correct'] ?? []);
        $options = [];
        foreach ($texts as $i => $text) {
            $text = trim((string)$text);
            if ($text === '') {
                continue;
            }
            $options[] = ['text' => $text, 'is_correct' => in_array($i, $correctIndexes, true)];
        }
        QuestionOption::replaceForQuestion($questionId, $options);
    }

    public function questionStore(): void
    {
        Csrf::requireValid();
        $examId = (int)($_POST['exam_id'] ?? 0);
        $this->ownedExamOrFail($examId);
        if (ExamAttempt::existsForExam($examId)) {
            flash_set('error', 'A student has already started this exam — the question bank is locked.');
            redirect('examiner/questions&exam_id=' . $examId);
        }

        $v = new Validator();
        $v->required($_POST, 'question_text', 'Question text')
          ->required($_POST, 'type', 'Type')
          ->inArray($_POST, 'type', ['single', 'multi', 'true_false', 'fill_blank'], 'Type')
          ->required($_POST, 'marks', 'Marks')
          ->numeric($_POST, 'marks', 'Marks')
          ->min($_POST, 'marks', 0.5, 'Marks');
        if ($_POST['type'] === 'fill_blank') {
            $v->required($_POST, 'correct_answer_text', 'Correct answer');
        }
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            old_input_set($_POST);
            redirect('examiner/questions&exam_id=' . $examId);
        }

        $optionsError = $this->validateOptionsInput($_POST['type']);
        if ($optionsError !== null) {
            flash_set('error', $optionsError);
            old_input_set($_POST);
            redirect('examiner/questions&exam_id=' . $examId);
        }

        $questionId = Question::create([
            'exam_id'              => $examId,
            'question_text'        => trim($_POST['question_text']),
            'type'                 => $_POST['type'],
            'marks'                => (float)$_POST['marks'],
            'order_no'             => Question::nextOrderNo($examId),
            'correct_answer_text'  => $_POST['type'] === 'fill_blank' ? trim($_POST['correct_answer_text']) : null,
        ]);
        $this->saveQuestionOptions($questionId, $_POST['type']);

        flash_set('success', 'Question added.');
        redirect('examiner/questions&exam_id=' . $examId);
    }

    public function questionUpdate(): void
    {
        Csrf::requireValid();
        $examId = (int)($_POST['exam_id'] ?? 0);
        $this->ownedExamOrFail($examId);
        if (ExamAttempt::existsForExam($examId)) {
            flash_set('error', 'A student has already started this exam — the question bank is locked.');
            redirect('examiner/questions&exam_id=' . $examId);
        }
        $questionId = (int)($_POST['id'] ?? 0);
        if (!Question::belongsToExam($questionId, $examId)) {
            flash_set('error', 'Invalid question.');
            redirect('examiner/questions&exam_id=' . $examId);
        }

        $v = new Validator();
        $v->required($_POST, 'question_text', 'Question text')
          ->required($_POST, 'marks', 'Marks')
          ->numeric($_POST, 'marks', 'Marks')
          ->min($_POST, 'marks', 0.5, 'Marks');
        if ($v->fails()) {
            flash_set('error', 'Please correct the highlighted fields.');
            old_input_set($_POST);
            redirect('examiner/questions&exam_id=' . $examId . '&edit=' . $questionId);
        }

        $existing = Question::find($questionId);

        $optionsError = $this->validateOptionsInput($existing['type']);
        if ($optionsError !== null) {
            flash_set('error', $optionsError);
            old_input_set($_POST);
            redirect('examiner/questions&exam_id=' . $examId . '&edit=' . $questionId);
        }

        Question::update($questionId, [
            'question_text'       => trim($_POST['question_text']),
            'type'                => $existing['type'],
            'marks'               => (float)$_POST['marks'],
            'order_no'            => $existing['order_no'],
            'correct_answer_text' => $existing['type'] === 'fill_blank' ? trim($_POST['correct_answer_text']) : null,
        ]);
        $this->saveQuestionOptions($questionId, $existing['type']);

        flash_set('success', 'Question updated.');
        redirect('examiner/questions&exam_id=' . $examId);
    }

    public function questionDelete(): void
    {
        Csrf::requireValid();
        $examId = (int)($_POST['exam_id'] ?? 0);
        $this->ownedExamOrFail($examId);
        if (ExamAttempt::existsForExam($examId)) {
            flash_set('error', 'A student has already started this exam — the question bank is locked.');
            redirect('examiner/questions&exam_id=' . $examId);
        }
        $questionId = (int)($_POST['id'] ?? 0);
        if (Question::belongsToExam($questionId, $examId)) {
            Question::delete($questionId);
            flash_set('success', 'Question deleted.');
        }
        redirect('examiner/questions&exam_id=' . $examId);
    }

    public function results(): void
    {
        $exam = $this->ownedExamOrFail((int)($_GET['exam_id'] ?? 0));
        $attempts = ExamAttempt::allByExamWithStudent($exam['id']);
        $pageTitle = 'Results — ' . $exam['title'];
        require __DIR__ . '/../views/examiner/results.php';
    }

    public function analytics(): void
    {
        $exam = $this->ownedExamOrFail((int)($_GET['exam_id'] ?? 0));
        $questionStats = AttemptAnswer::questionWiseStatsForExam($exam['id']);
        $pageTitle = 'Analytics — ' . $exam['title'];
        require __DIR__ . '/../views/examiner/analytics.php';
    }
}
