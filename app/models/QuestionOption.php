<?php

require_once __DIR__ . '/../../core/Database.php';

class QuestionOption
{
    public static function allByQuestion(int $questionId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM question_options WHERE question_id = ? ORDER BY order_no, id');
        $stmt->execute([$questionId]);
        return $stmt->fetchAll();
    }

    public static function allByQuestionIds(array $questionIds): array
    {
        if (empty($questionIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $stmt = Database::connect()->prepare(
            "SELECT * FROM question_options WHERE question_id IN ($placeholders) ORDER BY question_id, order_no, id"
        );
        $stmt->execute($questionIds);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[$row['question_id']][] = $row;
        }
        return $grouped;
    }

    public static function replaceForQuestion(int $questionId, array $options): void
    {
        $pdo = Database::connect();
        $del = $pdo->prepare('DELETE FROM question_options WHERE question_id = ?');
        $del->execute([$questionId]);

        $ins = $pdo->prepare(
            'INSERT INTO question_options (question_id, option_text, is_correct, order_no) VALUES (?, ?, ?, ?)'
        );
        foreach ($options as $i => $opt) {
            $ins->execute([$questionId, $opt['text'], $opt['is_correct'] ? 1 : 0, $i + 1]);
        }
    }

    public static function correctOptionIds(int $questionId): array
    {
        $stmt = Database::connect()->prepare('SELECT id FROM question_options WHERE question_id = ? AND is_correct = 1');
        $stmt->execute([$questionId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
