<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$attemptId = (int)($_GET['id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT qa.*, q.title, q.total_marks, c.course_code,
            TIMESTAMPDIFF(MINUTE, qa.start_time, qa.end_time) AS duration_min
     FROM quiz_attempts qa
     JOIN quizzes q ON q.quiz_id = qa.quiz_id
     JOIN courses c ON c.course_id = q.course_id
     WHERE qa.attempt_id = ? AND qa.student_id = ?'
);
$stmt->execute([$attemptId, $studentId]);
$attempt = $stmt->fetch();
if (!$attempt) {
    header('Location: /student/quizzes.php');
    exit;
}

$questions = $pdo->prepare(
    'SELECT qq.question_id, qq.question_text, qq.marks, qq.question_order
     FROM quiz_questions qq WHERE qq.quiz_id = ? ORDER BY qq.question_order, qq.question_id'
);
$questions->execute([$attempt['quiz_id']]);
$questions = $questions->fetchAll();

$options = [];
if ($questions) {
    $ids = array_column($questions, 'question_id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $ostmt = $pdo->prepare("SELECT option_id, question_id, option_text, is_correct FROM quiz_options WHERE question_id IN ($in)");
    $ostmt->execute($ids);
    foreach ($ostmt->fetchAll() as $o) {
        $options[$o['question_id']][] = $o;
    }
}

$answers = $pdo->prepare(
    'SELECT qa.*, qq.marks FROM quiz_answers qa
     JOIN quiz_questions qq ON qq.question_id = qa.question_id
     WHERE qa.attempt_id = ?'
);
$answers->execute([$attemptId]);
$answers = $answers->fetchAll();
$answerMap = [];
foreach ($answers as $a) {
    $answerMap[$a['question_id']] = $a;
}

$score = (float)$attempt['score'];
$total = (float)$attempt['total_marks'];
$pct = $total > 0 ? (int)round(($score / $total) * 100) : 0;

$pageTitle = 'Quiz Result';
$activeNav = 'quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-quiz-shell">
  <div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/quizzes.php">Quizzes</a> / <?= htmlspecialchars($attempt['title']) ?></div>
    <h1 class="uh-page-title">Quiz Result</h1>
  </div>
</div>

  <div class="card mb-4">
    <div class="card-body text-center py-5">
      <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:96px;height:96px;border-radius:50%;background:<?= $pct >= 70 ? 'var(--success-bg)' : ($pct >= 40 ? 'var(--warning-bg)' : 'var(--danger-bg)') ?>;">
        <span class="font-display fw-bold fs-2" style="color:<?= $pct >= 70 ? 'var(--success)' : ($pct >= 40 ? 'var(--warning)' : 'var(--danger)') ?>;"><?= $pct ?>%</span>
      </div>
      <h4 class="fw-bold mb-1"><?= (int)$score ?> / <?= (int)$total ?> correct</h4>
      <p class="text-muted-2"><?= htmlspecialchars($attempt['title']) ?> — <?= htmlspecialchars($attempt['course_code']) ?></p>
      <div class="d-flex justify-content-center gap-4 mt-3 fs-sm text-muted-2">
        <div><i class="bi bi-clock me-1"></i>Completed in <?= (int)$attempt['duration_min'] ?> min</div>
        <div><i class="bi bi-calendar-event me-1"></i><?= date('M j, Y', strtotime($attempt['end_time'])) ?></div>
      </div>
    </div>
  </div>

  <h6 class="fw-bold mb-3">Question Summary</h6>
  <?php foreach ($questions as $q):
    $ans = $answerMap[$q['question_id']] ?? null;
    $selectedOptId = $ans ? (int)$ans['selected_option_id'] : 0;
    $isCorrect = $ans ? (bool)$ans['is_correct'] : false;
    $opts = $options[$q['question_id']] ?? [];
    $correctOptId = 0;
    foreach ($opts as $o) { if ($o['is_correct']) { $correctOptId = (int)$o['option_id']; break; } }
    $selectedText = '';
    $correctText = '';
    foreach ($opts as $o) {
      if ((int)$o['option_id'] === $selectedOptId) $selectedText = $o['option_text'];
      if ((int)$o['option_id'] === $correctOptId) $correctText = $o['option_text'];
    }
  ?>
  <div class="card mb-2">
    <div class="card-body py-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="fw-semibold fs-sm"><?= $q['question_order'] ?>. <?= htmlspecialchars($q['question_text']) ?></span>
        <?= $isCorrect ? '<span class="badge badge-soft-success">Correct</span>' : '<span class="badge badge-soft-danger">Incorrect</span>' ?>
      </div>
      <div class="fs-sm text-muted-2">
        Your answer: <b class="text-dark"><?= $selectedText ?: 'Not answered' ?></b>
        <?php if (!$isCorrect): ?>&nbsp;•&nbsp; Correct answer: <b class="text-dark"><?= $correctText ?></b><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <a href="/student/quizzes.php" class="btn btn-primary mt-3"><i class="bi bi-arrow-left me-1"></i>Back to Quizzes</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>