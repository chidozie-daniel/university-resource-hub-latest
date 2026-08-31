<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
$stats = [
    'students' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'lecturers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='lecturer'")->fetchColumn(),
    'courses' => (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'enrollments' => (int)$pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='enrolled'")->fetchColumn(),
    'submissions' => (int)$pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn(),
    'graded' => (int)$pdo->query("SELECT COUNT(*) FROM submissions WHERE status='graded'")->fetchColumn(),
];
$pageTitle = 'Reports';
$activeNav = 'reports';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Reports</div>
    <h1 class="uh-page-title">Reports</h1>
  </div>
</div>
<div class="row g-3">
  <?php
  $cards = [
      ['Students', $stats['students'], 'bi-mortarboard', 'brand'],
      ['Lecturers', $stats['lecturers'], 'bi-person-video3', 'info'],
      ['Courses', $stats['courses'], 'bi-journal-bookmark', 'success'],
      ['Active enrollments', $stats['enrollments'], 'bi-card-checklist', 'accent'],
      ['Submissions', $stats['submissions'], 'bi-inbox', 'warning'],
      ['Graded submissions', $stats['graded'], 'bi-check2-circle', 'success'],
  ];
  foreach ($cards as [$label, $val, $icon, $color]):
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="card uh-stat h-100">
      <div class="d-flex justify-content-between">
        <div>
          <div class="val"><?= $val ?></div>
          <div class="lbl"><?= $label ?></div>
        </div>
        <div class="ic" style="background:var(--<?= $color === 'brand' ? 'brand-100' : ($color.'-bg') ?>);color:var(--<?= $color === 'brand' ? 'brand-700' : $color ?>);">
          <i class="bi <?= $icon ?>"></i>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<p class="text-muted-2 fs-sm mt-4">Detailed PDF export can be added later; these figures come live from your UniHub schema.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
