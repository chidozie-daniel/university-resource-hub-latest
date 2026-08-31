<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
$byDept = $pdo->query(
    'SELECT d.department_name,
            SUM(CASE WHEN a.status = \'present\' THEN 1 ELSE 0 END) AS present_n,
            COUNT(a.attendance_id) AS total_n
     FROM departments d
     LEFT JOIN courses c ON c.department_id = d.department_id
     LEFT JOIN attendance a ON a.course_id = c.course_id
     GROUP BY d.department_id, d.department_name
     ORDER BY d.department_name'
)->fetchAll();
$pageTitle = 'Attendance';
$activeNav = 'attendance';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Attendance</div>
    <h1 class="uh-page-title">Attendance overview</h1>
  </div>
</div>
<div class="card">
  <div class="card-header">By department</div>
  <div class="card-body">
    <?php foreach ($byDept as $r):
      $pct = (int)$r['total_n'] ? (int)round(((int)$r['present_n'] / (int)$r['total_n']) * 100) : null;
    ?>
      <div class="mb-3">
        <div class="d-flex justify-content-between fs-sm mb-1">
          <span class="fw-semibold"><?= htmlspecialchars($r['department_name']) ?></span>
          <span><?= $pct !== null ? $pct . '%' : 'No data' ?></span>
        </div>
        <?php if ($pct !== null): ?>
          <div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $pct>=90?'var(--success)':($pct>=80?'var(--warning)':'var(--danger)') ?>"></div></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
