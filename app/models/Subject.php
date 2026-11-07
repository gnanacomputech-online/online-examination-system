<?php

require_once __DIR__ . '/../../core/Database.php';

class Subject
{
    public static function all(): array
    {
        return Database::connect()->query(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM exams e WHERE e.subject_id = s.id) AS exam_count,
                    (SELECT COUNT(*) FROM student_subjects ss WHERE ss.subject_id = s.id) AS enrolment_count
             FROM subjects s
             ORDER BY s.name'
        )->fetchAll();
    }

    /**
     * Whether anything downstream still hangs off this subject. `exams.subject_id` and
     * `student_subjects.subject_id` are both ON DELETE CASCADE, so deleting a subject in
     * use would silently take its exams -> questions -> exam_attempts -> attempt_answers
     * (i.e. students' graded results) and every enrolment with it. Same lock idea as
     * ExamAttempt::existsForExam(), one level up the tree.
     */
    public static function isInUse(int $id): bool
    {
        $stmt = Database::connect()->prepare(
            'SELECT (SELECT COUNT(*) FROM exams WHERE subject_id = :id)
                  + (SELECT COUNT(*) FROM student_subjects WHERE subject_id = :id)'
        );
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM subjects WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $description, int $createdBy): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO subjects (name, description, created_by) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $description, $createdBy]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, string $name, string $description): void
    {
        $stmt = Database::connect()->prepare('UPDATE subjects SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $description, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connect()->prepare('DELETE FROM subjects WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) FROM subjects')->fetchColumn();
    }
}
