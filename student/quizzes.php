<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$pdo = db();

$list = [];
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT q.quiz_id, q.title, q.duration_minutes, q.total_marks, c.course_code,
                (SELECT score FROM quiz_attempts qa
                 WHERE qa.quiz_id = q.quiz_id AND qa.student_id = ? AND qa.status IN (\'submitted\',\'graded\')
                 ORDER BY attempt_id DESC LIMIT 1) AS last_score
         FROM quizzes q
         JOIN courses c ON c.course_id = q.course_id
         JOIN enrollments e ON e.course_id = q.course_id AND e.student_id = ? AND e.status = \'enrolled\'
         ORDER BY q.created_at DESC'
    );
    $stmt->execute([$studentId, $studentId]);
    $list = $stmt->fetchAll();
}

$pageTitle = 'Quizzes';
$activeNav = 'quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Quizzes</div>
    <h1 class="uh-page-title">Quizzes</h1>
  </div>
</div>
<div class="table-responsive-card">
  <table class="table table-hover mb-0">
    <thead><tr><th>Quiz</th><th>Course</th><th>Duration</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$list): ?>
      <tr><td colspan="5" class="text-center text-muted-2 py-4">No quizzes available.</td></tr>
    <?php endif; ?>
    <?php foreach ($list as $q): ?>
      <tr>
        <td class="fw-semibold text-dark"><?= htmlspecialchars($q['title']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($q['course_code']) ?></td>
        <td><?= $q['duration_minutes'] ? (int)$q['duration_minutes'] . ' min' : '—' ?></td>
        <td>
          <?php if ($q['last_score'] !== null): ?>
            <span class="badge badge-soft-success">Completed</span>
            <span class="fs-xs text-muted-2 ms-1"><?= htmlspecialchars($q['last_score']) ?>/<?= htmlspecialchars($q['total_marks']) ?></span>
          <?php else: ?>
            <span class="badge badge-soft-info">Available</span>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <?php if ($q['last_score'] !== null): ?>
            <span class="fs-sm text-muted-2">Done</span>
          <?php else: ?>
            <a class="btn btn-sm btn-primary" href="/student/take_quiz.php?id=<?= (int)$q['quiz_id'] ?>">Start</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
