<?php

require_once __DIR__ . '/../../core/Database.php';

class Enrollment
{
    public static function subjectIdsForStudent(int $studentId): array
    {
        $stmt = Database::connect()->prepare('SELECT subject_id FROM student_subjects WHERE student_id = ?');
        $stmt->execute([$studentId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function isEnrolled(int $studentId, int $subjectId): bool
    {
        $stmt = Database::connect()->prepare(
            'SELECT COUNT(*) FROM student_subjects WHERE student_id = ? AND subject_id = ?'
        );
        $stmt->execute([$studentId, $subjectId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
