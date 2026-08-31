<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$pdo = db();

$byCourse = [];
$history = [];
$overall = null;
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT c.course_code,
                SUM(CASE WHEN a.status = \'present\' THEN 1 ELSE 0 END) AS present_n,
                SUM(CASE WHEN a.status = \'absent\' THEN 1 ELSE 0 END) AS absent_n,
                SUM(CASE WHEN a.status = \'late\' THEN 1 ELSE 0 END) AS late_n,
                COUNT(*) AS total_n
         FROM attendance a
         JOIN courses c ON c.course_id = a.course_id
         WHERE a.student_id = ?
         GROUP BY c.course_id, c.course_code
         ORDER BY c.course_code'
    );
    $stmt->execute([$studentId]);
    $byCourse = $stmt->fetchAll();
    $p = $t = 0;
    foreach ($byCourse as $r) {
        $p += (int)$r['present_n'];
        $t += (int)$r['total_n'];
    }
    if ($t > 0) $overall = (int) round(($p / $t) * 100);

    $h = $pdo->prepare(
        'SELECT a.attendance_date, a.status, c.course_code
         FROM attendance a JOIN courses c ON c.course_id = a.course_id
         WHERE a.student_id = ? ORDER BY a.attendance_date DESC LIMIT 20'
    );
    $h->execute([$studentId]);
    $history = $h->fetchAll();
}

$pageTitle = 'Attendance';
$activeNav = 'attendance';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Attendance</div>
    <h1 class="uh-page-title">Attendance</h1>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card uh-stat"><div class="val"><?= $overall !== null ? $overall . '%' : '—' ?></div><div class="lbl">Overall</div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">By course</div>
      <div class="card-body">
        <?php if (!$byCourse): ?><p class="text-muted-2 fs-sm mb-0">No attendance records.</p><?php endif; ?>
        <?php foreach ($byCourse as $r):
          $pct = (int)$r['total_n'] ? (int) round(((int)$r['present_n'] / (int)$r['total_n']) * 100) : 0;
        ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between fs-sm mb-1">
              <span class="fw-semibold font-mono"><?= htmlspecialchars($r['course_code']) ?></span>
              <span class="fw-semibold"><?= $pct ?>%</span>
            </div>
            <div class="progress progress-thin"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $pct >= 90 ? 'var(--success)' : ($pct >= 75 ? 'var(--warning)' : 'var(--danger)') ?>"></div></div>
            <div class="fs-xs text-faint mt-1"><?= (int)$r['present_n'] ?> present · <?= (int)$r['absent_n'] ?> absent · <?= (int)$r['late_n'] ?> late</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">Recent history</div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Date</th><th>Course</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><?= date('M j, Y', strtotime($h['attendance_date'])) ?></td>
              <td class="font-mono fs-sm"><?= htmlspecialchars($h['course_code']) ?></td>
              <td>
                <?php
                if ($h['status'] === 'present') echo '<span class="badge badge-soft-success">Present</span>';
                elseif ($h['status'] === 'late') echo '<span class="badge badge-soft-warning">Late</span>';
                else echo '<span class="badge badge-soft-danger">Absent</span>';
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$history): ?><tr><td colspan="3" class="text-muted-2 text-center py-3">No records</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
