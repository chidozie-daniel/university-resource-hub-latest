<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$pdo = db();

$rows = [];
$avg = null;
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT c.course_code, c.course_name, lp.completion_percentage, lp.completed_modules, lp.completed_resources, lp.last_accessed
         FROM learning_progress lp
         JOIN courses c ON c.course_id = lp.course_id
         WHERE lp.student_id = ?
         ORDER BY c.course_code'
    );
    $stmt->execute([$studentId]);
    $rows = $stmt->fetchAll();
    if ($rows) {
        $sum = 0;
        foreach ($rows as $r) $sum += (float)$r['completion_percentage'];
        $avg = (int) round($sum / count($rows));
    }
}

$pageTitle = 'Progress';
$activeNav = 'progress';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Progress</div>
    <h1 class="uh-page-title">Learning Progress</h1>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body text-center py-4">
      <div class="fs-xs text-faint mb-1">OVERALL</div>
      <div class="font-display fw-bold" style="font-size:40px;color:var(--brand-700);"><?= $avg !== null ? $avg . '%' : '—' ?></div>
    </div></div>
  </div>
  <div class="col-md-8">
    <div class="card h-100"><div class="card-body">
      <div class="fw-semibold mb-2">Course progress</div>
      <?php if (!$rows): ?>
        <p class="text-muted-2 fs-sm mb-0">No progress data yet. Open course materials to start tracking.</p>
      <?php endif; ?>
      <?php foreach ($rows as $r):
        $p = (int) round((float)$r['completion_percentage']);
      ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between fs-sm mb-1">
            <span class="fw-semibold"><?= htmlspecialchars($r['course_name']) ?> <span class="text-faint font-mono"><?= htmlspecialchars($r['course_code']) ?></span></span>
            <span class="fw-semibold"><?= $p ?>%</span>
          </div>
          <div class="progress"><div class="progress-bar" style="width:<?= $p ?>%"></div></div>
          <div class="fs-xs text-faint mt-1"><?= (int)$r['completed_modules'] ?> modules · <?= (int)$r['completed_resources'] ?> resources</div>
        </div>
      <?php endforeach; ?>
    </div></div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
