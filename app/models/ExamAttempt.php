<?php

require_once __DIR__ . '/../../core/Database.php';

class ExamAttempt
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM exam_attempts WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Whether any student has ever started (or finished) an attempt on this exam — used to lock the exam/question bank against edits that would desync already-recorded results. */
    public static function existsForExam(int $examId): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ?');
        $stmt->execute([$examId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function findForStudentExam(int $examId, int $studentId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ?');
        $stmt->execute([$examId, $studentId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $examId, int $studentId, float $totalMarks): int
    {
        // Use PHP's clock (Asia/Kolkata, set in public/index.php) rather than the DB
        // server's NOW(), so remaining-time math never disagrees with the app's own timezone.
        $stmt = Database::connect()->prepare(
            'INSERT INTO exam_attempts (exam_id, student_id, started_at, status, total_marks)
             VALUES (?, ?, ?, "in_progress", ?)'
        );
        $stmt->execute([$examId, $studentId, date('Y-m-d H:i:s'), $totalMarks]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function belongsToStudent(int $attemptId, int $studentId): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM exam_attempts WHERE id = ? AND student_id = ?');
        $stmt->execute([$attemptId, $studentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Increments the attempt's violation count (only while still in progress) and returns the resulting total. */
    public static function recordViolation(int $attemptId): int
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('UPDATE exam_attempts SET violation_count = violation_count + 1 WHERE id = ? AND status = "in_progress"');
        $stmt->execute([$attemptId]);

        $stmt = $pdo->prepare('SELECT violation_count FROM exam_attempts WHERE id = ?');
        $stmt->execute([$attemptId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Compare-and-swap finalize: only an attempt that is still `in_progress` can be
     * closed, and the UPDATE itself is the mutex. Two grading requests can legitimately
     * land at once (a double-clicked Submit, the JS timer firing while the student's own
     * click is still in flight, or a background autosave expiring in parallel) — without
     * the status predicate the loser would re-write score/submitted_at and could flip a
     * `submitted` attempt to `auto_submitted` after the fact. Returns true only for the
     * request that actually closed the attempt.
     */
    public static function finalize(int $attemptId, string $status, float $score): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE exam_attempts SET status = ?, score = ?, submitted_at = ?
             WHERE id = ? AND status = "in_progress"'
        );
        $stmt->execute([$status, $score, date('Y-m-d H:i:s'), $attemptId]);
        return $stmt->rowCount() === 1;
    }

    public static function allByExamWithStudent(int $examId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT a.*, u.name AS student_name, u.email AS student_email
             FROM exam_attempts a JOIN users u ON u.id = a.student_id
             WHERE a.exam_id = ?
             ORDER BY a.score DESC, a.submitted_at ASC'
        );
        $stmt->execute([$examId]);
        return $stmt->fetchAll();
    }

    public static function allByStudentWithExam(int $studentId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT a.*, e.title AS exam_title, s.name AS subject_name
             FROM exam_attempts a
             JOIN exams e ON e.id = a.exam_id
             JOIN subjects s ON s.id = e.subject_id
             WHERE a.student_id = ?
             ORDER BY a.started_at DESC'
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public static function findWithExamForStudent(int $attemptId, int $studentId): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT a.*, e.title AS exam_title, e.negative_marking, e.duration_minutes, e.shuffle_questions, s.name AS subject_name
             FROM exam_attempts a
             JOIN exams e ON e.id = a.exam_id
             JOIN subjects s ON s.id = e.subject_id
             WHERE a.id = ? AND a.student_id = ?'
        );
        $stmt->execute([$attemptId, $studentId]);
        return $stmt->fetch() ?: null;
    }

    public static function globalStats(): array
    {
        $pdo = Database::connect();
        return [
            'total_attempts'     => (int)$pdo->query('SELECT COUNT(*) FROM exam_attempts')->fetchColumn(),
            'submitted_attempts' => (int)$pdo->query("SELECT COUNT(*) FROM exam_attempts WHERE status IN ('submitted','auto_submitted')")->fetchColumn(),
            'avg_score_pct'      => (float)($pdo->query(
                "SELECT AVG(score / NULLIF(total_marks,0)) * 100 FROM exam_attempts WHERE status IN ('submitted','auto_submitted')"
            )->fetchColumn() ?: 0),
        ];
    }

    public static function topExamsByAttempts(int $limit = 5): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT e.title, s.name AS subject_name, COUNT(a.id) AS attempts,
                    AVG(a.score / NULLIF(a.total_marks,0)) * 100 AS avg_pct
             FROM exams e
             JOIN subjects s ON s.id = e.subject_id
             LEFT JOIN exam_attempts a ON a.exam_id = e.id AND a.status IN ('submitted','auto_submitted')
             GROUP BY e.id
             ORDER BY attempts DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
