<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$pdo = db();
$courses = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.course_code, c.course_name, c.credit_hours, c.description,
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.status = \'enrolled\') AS students
         FROM course_lecturers cl
         JOIN courses c ON c.course_id = cl.course_id
         WHERE cl.lecturer_id = ?
         ORDER BY c.course_code'
    );
    $stmt->execute([$lecturerId]);
    $courses = $stmt->fetchAll();
}
$pageTitle = 'My Courses';
$activeNav = 'courses';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/index.php">Dashboard</a> / My Courses</div>
    <h1 class="uh-page-title">My Courses</h1>
  </div>
</div>
<div class="row g-3">
  <?php if (!$courses): ?>
    <div class="col-12"><div class="card"><div class="card-body text-muted-2">No courses assigned.</div></div></div>
  <?php endif; ?>
  <?php foreach ($courses as $c): ?>
  <div class="col-md-6 col-xl-4">
    <div class="card uh-hover h-100">
      <div class="card-body d-flex flex-column">
        <span class="badge badge-soft-info font-mono mb-2 align-self-start"><?= htmlspecialchars($c['course_code']) ?></span>
        <h3 class="h6 fw-bold"><?= htmlspecialchars($c['course_name']) ?></h3>
        <div class="fs-sm text-muted-2 mb-3"><?= (int)$c['students'] ?> students · <?= (int)$c['credit_hours'] ?> credits</div>
        <a href="/lecturer/course.php?id=<?= (int)$c['course_id'] ?>" class="btn btn-light-2 btn-sm mt-auto">Manage course</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
