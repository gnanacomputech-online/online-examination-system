<?php

require_once __DIR__ . '/../../core/Database.php';

class Question
{
    public static function allByExam(int $examId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM questions WHERE exam_id = ? ORDER BY order_no, id');
        $stmt->execute([$examId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM questions WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO questions (exam_id, question_text, type, marks, order_no, correct_answer_text)
             VALUES (:exam_id, :question_text, :type, :marks, :order_no, :correct_answer_text)'
        );
        $stmt->execute($data);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = Database::connect()->prepare(
            'UPDATE questions SET question_text = :question_text, type = :type, marks = :marks,
                    order_no = :order_no, correct_answer_text = :correct_answer_text
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connect()->prepare('DELETE FROM questions WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function belongsToExam(int $questionId, int $examId): bool
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM questions WHERE id = ? AND exam_id = ?');
        $stmt->execute([$questionId, $examId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function sumMarksByExam(int $examId): float
    {
        $stmt = Database::connect()->prepare('SELECT COALESCE(SUM(marks),0) FROM questions WHERE exam_id = ?');
        $stmt->execute([$examId]);
        return (float)$stmt->fetchColumn();
    }

    public static function nextOrderNo(int $examId): int
    {
        $stmt = Database::connect()->prepare('SELECT COALESCE(MAX(order_no),0) + 1 FROM questions WHERE exam_id = ?');
        $stmt->execute([$examId]);
        return (int)$stmt->fetchColumn();
    }
}
