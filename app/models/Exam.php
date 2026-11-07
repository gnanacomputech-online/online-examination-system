<?php

require_once __DIR__ . '/../../core/Database.php';

class Exam
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM exams WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findWithSubject(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT e.*, s.name AS subject_name, u.status AS examiner_status
             FROM exams e
             JOIN subjects s ON s.id = e.subject_id
             JOIN users u ON u.id = e.examiner_id
             WHERE e.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function allByExaminer(int $examinerId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT e.*, s.name AS subject_name,
                    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count,
                    (SELECT COALESCE(SUM(q.marks),0) FROM questions q WHERE q.exam_id = e.id) AS total_marks,
                    (SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id = e.id) AS attempt_count
             FROM exams e JOIN subjects s ON s.id = e.subject_id
             WHERE e.examiner_id = ?
             ORDER BY e.created_at DESC'
        );
        $stmt->execute([$examinerId]);
        return $stmt->fetchAll();
    }

    public static function allWithDetails(): array
    {
        return Database::connect()->query(
            'SELECT e.*, s.name AS subject_name, u.name AS examiner_name,
                    (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count,
                    (SELECT COUNT(*) FROM exam_attempts a WHERE a.exam_id = e.id) AS attempt_count
             FROM exams e
             JOIN subjects s ON s.id = e.subject_id
             JOIN users u ON u.id = e.examiner_id
             ORDER BY e.created_at DESC'
        )->fetchAll();
    }

    public static function publishedForSubjects(array $subjectIds): array
    {
        if (empty($subjectIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($subjectIds), '?'));
        // An exam is only offerable while the examiner who owns it still has an active
        // account — deactivating an examiner (the Super Admin's only lever over them)
        // otherwise left every one of their published exams fully live and startable.
        $stmt = Database::connect()->prepare(
            "SELECT e.*, s.name AS subject_name,
                    (SELECT COALESCE(SUM(q.marks),0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
             FROM exams e
             JOIN subjects s ON s.id = e.subject_id
             JOIN users u ON u.id = e.examiner_id
             WHERE e.status = 'published' AND u.status = 'active' AND e.subject_id IN ($placeholders)
             ORDER BY e.start_at"
        );
        $stmt->execute($subjectIds);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO exams (subject_id, examiner_id, title, description, duration_minutes, negative_marking, shuffle_questions, status, start_at, end_at)
             VALUES (:subject_id, :examiner_id, :title, :description, :duration_minutes, :negative_marking, :shuffle_questions, :status, :start_at, :end_at)'
        );
        $stmt->execute($data);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = Database::connect()->prepare(
            'UPDATE exams SET subject_id = :subject_id, title = :title, description = :description,
                    duration_minutes = :duration_minutes, negative_marking = :negative_marking,
                    shuffle_questions = :shuffle_questions, start_at = :start_at, end_at = :end_at
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE exams SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connect()->prepare('DELETE FROM exams WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function belongsToExaminer(int $examId, int $examinerId): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM exams WHERE id = ? AND examiner_id = ?');
        $stmt->execute([$examId, $examinerId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) FROM exams')->fetchColumn();
    }

    public static function countByStatus(string $status): int
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM exams WHERE status = ?');
        $stmt->execute([$status]);
        return (int)$stmt->fetchColumn();
    }
}
