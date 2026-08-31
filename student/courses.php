<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$pdo = db();

$courses = [];
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.course_code, c.course_name, c.credit_hours, c.description,
                lp.completion_percentage,
                (SELECT u.full_name FROM course_lecturers cl
                 JOIN lecturers l ON l.lecturer_id = cl.lecturer_id
                 JOIN users u ON u.user_id = l.user_id
                 WHERE cl.course_id = c.course_id LIMIT 1) AS lecturer_name
         FROM enrollments e
         JOIN courses c ON c.course_id = e.course_id
         LEFT JOIN learning_progress lp ON lp.course_id = c.course_id AND lp.student_id = e.student_id
         WHERE e.student_id = ? AND e.status = \'enrolled\'
         ORDER BY c.course_code'
    );
    $stmt->execute([$studentId]);
    $courses = $stmt->fetchAll();
}

$pageTitle = 'My Courses';
$activeNav = 'courses';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / My Courses</div>
    <h1 class="uh-page-title">My Courses</h1>
  </div>
</div>
<div class="row g-3">
  <?php if (!$courses): ?>
    <div class="col-12"><div class="card"><div class="card-body text-muted-2">You are not enrolled in any courses yet.</div></div></div>
  <?php endif; ?>
  <?php foreach ($courses as $c):
    $pct = $c['completion_percentage'] !== null ? (int) round((float)$c['completion_percentage']) : 0;
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="card uh-hover h-100">
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between mb-2">
          <span class="badge badge-soft-info font-mono"><?= htmlspecialchars($c['course_code']) ?></span>
          <span class="text-faint fs-xs"><?= (int)$c['credit_hours'] ?> credits</span>
        </div>
        <h3 class="h6 fw-bold mb-1"><?= htmlspecialchars($c['course_name']) ?></h3>
        <div class="fs-sm text-muted-2 mb-3"><i class="bi bi-person-video3 me-1"></i><?= htmlspecialchars($c['lecturer_name'] ?? 'TBA') ?></div>
        <div class="mt-auto">
          <div class="d-flex justify-content-between fs-xs text-muted-2 mb-1"><span>Progress</span><span class="fw-semibold"><?= $pct ?>%</span></div>
          <div class="progress progress-thin mb-3"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
          <a href="/student/materials.php?course_id=<?= (int)$c['course_id'] ?>" class="btn btn-primary btn-sm w-100">Open course <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
