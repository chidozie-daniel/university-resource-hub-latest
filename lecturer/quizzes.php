<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$pdo = db();
$list = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT q.*, c.course_code,
                (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.quiz_id) AS qcount
         FROM quizzes q JOIN courses c ON c.course_id = q.course_id
         WHERE q.lecturer_id = ? ORDER BY q.created_at DESC'
    );
    $stmt->execute([$lecturerId]);
    $list = $stmt->fetchAll();
}
$pageTitle = 'Quizzes';
$activeNav = 'quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/index.php">Dashboard</a> / Quizzes</div>
    <h1 class="uh-page-title">Quizzes</h1>
  </div>
  <a href="/lecturer/create_quiz.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create</a>
</div>
<div class="table-responsive-card">
  <table class="table table-hover mb-0">
    <thead><tr><th>Title</th><th>Course</th><th>Questions</th><th>Duration</th><th></th></tr></thead>
    <tbody>
    <?php if (!$list): ?><tr><td colspan="5" class="text-center text-muted-2 py-4">No quizzes.</td></tr><?php endif; ?>
    <?php foreach ($list as $q): ?>
      <tr>
        <td class="fw-semibold text-dark"><?= htmlspecialchars($q['title']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($q['course_code']) ?></td>
        <td><?= (int)$q['qcount'] ?></td>
        <td><?= $q['duration_minutes'] ? (int)$q['duration_minutes'] . ' min' : '—' ?></td>
        <td class="text-end"><a class="btn btn-sm btn-light-2" href="/lecturer/questions.php?quiz_id=<?= (int)$q['quiz_id'] ?>">Questions</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
