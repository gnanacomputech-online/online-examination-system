<?php

require_once __DIR__ . '/../../core/Database.php';

class AttemptAnswer
{
    public static function allByAttempt(int $attemptId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM attempt_answers WHERE attempt_id = ?');
        $stmt->execute([$attemptId]);

        $byQuestion = [];
        foreach ($stmt->fetchAll() as $row) {
            $byQuestion[$row['question_id']] = $row;
        }
        return $byQuestion;
    }

    public static function autosave(int $attemptId, int $questionId, ?string $selectedOptionIds, ?string $answerText): void
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO attempt_answers (attempt_id, question_id, selected_option_ids, answer_text)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE selected_option_ids = VALUES(selected_option_ids), answer_text = VALUES(answer_text)'
        );
        $stmt->execute([$attemptId, $questionId, $selectedOptionIds, $answerText]);
    }

    public static function gradeAnswer(int $attemptId, int $questionId, ?bool $isCorrect, float $marksAwarded): void
    {
        $stmt = Database::connect()->prepare(
            'UPDATE attempt_answers SET is_correct = ?, marks_awarded = ? WHERE attempt_id = ? AND question_id = ?'
        );
        $stmt->execute([$isCorrect === null ? null : (int)$isCorrect, $marksAwarded, $attemptId, $questionId]);
    }

    public static function questionWiseStatsForExam(int $examId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT q.id, q.question_text, q.type, q.marks,
                    COUNT(aa.id) AS answered_count,
                    SUM(aa.is_correct = 1) AS correct_count
             FROM questions q
             LEFT JOIN attempt_answers aa ON aa.question_id = q.id AND aa.is_correct IS NOT NULL
             WHERE q.exam_id = ?
             GROUP BY q.id
             ORDER BY q.order_no, q.id"
        );
        $stmt->execute([$examId]);
        return $stmt->fetchAll();
    }
}
