<?php
/** @var array $exam expected in scope */
$now = time();
$notOpenYet = strtotime($exam['start_at']) > $now;
$alreadyClosed = strtotime($exam['end_at']) < $now;
?>
<div class="exam-row">
    <div class="exam-row-main">
        <strong><?= e($exam['title']) ?></strong>
        <span class="muted"><?= e($exam['subject_name']) ?> &middot; <?= e((string)$exam['total_marks']) ?> marks</span>
    </div>
    <div class="exam-row-action">
        <?php if ($exam['attempt_status'] === 'in_progress'): ?>
            <a class="btn btn-primary btn-sm" href="/index.php?route=student/exam/start&exam_id=<?= (int)$exam['id'] ?>">Resume</a>
        <?php elseif ($exam['attempt_status'] === 'submitted' || $exam['attempt_status'] === 'auto_submitted'): ?>
            <a class="btn btn-ghost btn-sm" href="/index.php?route=student/results/view&attempt_id=<?= (int)$exam['attempt_id'] ?>">View Result</a>
        <?php elseif ($notOpenYet): ?>
            <span class="badge badge-draft">Opens <?= e(date('d M H:i', strtotime($exam['start_at']))) ?></span>
        <?php elseif ($alreadyClosed): ?>
            <?php /* The window has passed and this student never sat it — the server would
                     reject a Start, so don't offer a button that can only ever bounce. */ ?>
            <span class="badge badge-closed">Closed <?= e(date('d M H:i', strtotime($exam['end_at']))) ?></span>
        <?php else: ?>
            <a class="btn btn-primary btn-sm" href="/index.php?route=student/exam/start&exam_id=<?= (int)$exam['id'] ?>">Start Exam</a>
        <?php endif; ?>
    </div>
</div>
