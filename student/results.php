<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$pdo = db();

$grades = [];
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT g.*, c.course_code, c.course_name
         FROM grades g JOIN courses c ON c.course_id = g.course_id
         WHERE g.student_id = ?
         ORDER BY c.course_code, g.assessment_name'
    );
    $stmt->execute([$studentId]);
    foreach ($stmt->fetchAll() as $g) {
        $grades[$g['course_id']]['meta'] = ['code' => $g['course_code'], 'name' => $g['course_name'], 'grade' => $g['grade']];
        $grades[$g['course_id']]['items'][] = $g;
    }
}

$pageTitle = 'Results';
$activeNav = 'results';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Results</div>
    <h1 class="uh-page-title">Results &amp; Grades</h1>
  </div>
</div>
<?php if (!$grades): ?>
  <div class="card"><div class="card-body text-muted-2">No grades published yet.</div></div>
<?php endif; ?>
<?php foreach ($grades as $block): ?>
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
      <span><?= htmlspecialchars($block['meta']['name']) ?> <span class="text-faint font-mono fs-sm"><?= htmlspecialchars($block['meta']['code']) ?></span></span>
      <?php if ($block['meta']['grade']): ?>
        <span class="badge badge-soft-success">Grade: <?= htmlspecialchars($block['meta']['grade']) ?></span>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>Assessment</th><th>Marks</th><th>Total</th><th>%</th></tr></thead>
        <tbody>
        <?php foreach ($block['items'] as $i):
          $pct = (float)$i['total_marks'] > 0 ? round(((float)$i['marks'] / (float)$i['total_marks']) * 100) : 0;
        ?>
          <tr>
            <td class="fw-semibold text-dark"><?= htmlspecialchars($i['assessment_name']) ?></td>
            <td><?= htmlspecialchars($i['marks']) ?></td>
            <td><?= htmlspecialchars($i['total_marks']) ?></td>
            <td><?= $pct ?>%</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
