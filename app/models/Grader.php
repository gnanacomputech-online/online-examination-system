<?php

require_once __DIR__ . '/Question.php';
require_once __DIR__ . '/QuestionOption.php';
require_once __DIR__ . '/AttemptAnswer.php';
require_once __DIR__ . '/ExamAttempt.php';

/**
 * Auto-grading engine: scores every answered question against its stored
 * correct answer(s) and finalizes the attempt's total score.
 */
class Grader
{
    public static function submitAndGrade(int $attemptId, int $examId, float $negativeMarking, string $finalStatus): float
    {
        $questions = Question::allByExam($examId);
        $answers = AttemptAnswer::allByAttempt($attemptId);
        $total = 0.0;

        foreach ($questions as $question) {
            $answer = $answers[$question['id']] ?? null;

            if ($answer === null || self::isBlank($question, $answer)) {
                continue;
            }

            $isCorrect = self::checkAnswer($question, $answer);
            $marks = (float)$question['marks'];
            $awarded = $isCorrect ? $marks : -($negativeMarking * $marks);

            AttemptAnswer::gradeAnswer($attemptId, $question['id'], $isCorrect, $awarded);
            $total += $awarded;
        }

        $score = max(0.0, round($total, 2));

        // finalize() is a compare-and-swap on `status = in_progress`, so exactly one of
        // several racing grading requests closes the attempt. The per-answer grades
        // written above are deterministic (same answers + same key => same result), so a
        // loser having re-written identical values is harmless; what must not happen twice
        // is the score/status/submitted_at flip, and that is what the CAS prevents.
        if (!ExamAttempt::finalize($attemptId, $finalStatus, $score)) {
            $already = ExamAttempt::find($attemptId);
            return (float)($already['score'] ?? $score);
        }

        return $score;
    }

    private static function isBlank(array $question, array $answer): bool
    {
        if ($question['type'] === 'fill_blank') {
            return trim((string)($answer['answer_text'] ?? '')) === '';
        }
        return trim((string)($answer['selected_option_ids'] ?? '')) === '';
    }

    private static function checkAnswer(array $question, array $answer): bool
    {
        if ($question['type'] === 'fill_blank') {
            $given = strtolower(trim((string)$answer['answer_text']));
            $correct = strtolower(trim((string)$question['correct_answer_text']));
            return $given !== '' && $given === $correct;
        }

        $selected = array_filter(array_map('intval', explode(',', (string)$answer['selected_option_ids'])));
        sort($selected);

        $correctIds = QuestionOption::correctOptionIds($question['id']);
        sort($correctIds);

        return $selected === $correctIds;
    }
}
