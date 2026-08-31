<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$studentId = (int)($_GET['student_id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT u.full_name, st.registration_number FROM students st JOIN users u ON u.user_id = st.user_id WHERE st.student_id = ?'
);
$stmt->execute([$studentId]);
$stu = $stmt->fetch();
if (!$stu) { header('Location: /lecturer/students.php'); exit; }

$rows = $pdo->prepare(
    'SELECT c.course_code, c.course_name, lp.completion_percentage, lp.completed_modules
     FROM learning_progress lp
     JOIN courses c ON c.course_id = lp.course_id
     JOIN course_lecturers cl ON cl.course_id = c.course_id AND cl.lecturer_id = ?
     WHERE lp.student_id = ?'
);
$rows->execute([$lecturerId, $studentId]);
$rows = $rows->fetchAll();

$pageTitle = $stu['full_name'];
$activeNav = 'students';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/students.php">Students</a> / Progress</div>
    <h1 class="uh-page-title"><?= htmlspecialchars($stu['full_name']) ?></h1>
    <p class="text-muted-2 font-mono"><?= htmlspecialchars($stu['registration_number']) ?></p>
  </div>
</div>
<div class="card">
  <div class="card-header">Course progress</div>
  <div class="card-body">
    <?php if (!$rows): ?><p class="text-muted-2 mb-0">No progress data.</p><?php endif; ?>
    <?php foreach ($rows as $r):
      $p = (int)round((float)$r['completion_percentage']);
    ?>
      <div class="mb-3">
        <div class="d-flex justify-content-between fs-sm mb-1">
          <span class="fw-semibold"><?= htmlspecialchars($r['course_name']) ?></span>
          <span><?= $p ?>%</span>
        </div>
        <div class="progress"><div class="progress-bar" style="width:<?= $p ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
