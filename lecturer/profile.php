<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$user = current_user();
$lecturerId = current_lecturer_id();
$pdo = db();
$meta = null;
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT l.employee_number, l.specialization, d.department_name
         FROM lecturers l JOIN departments d ON d.department_id = l.department_id
         WHERE l.lecturer_id = ?'
    );
    $stmt->execute([$lecturerId]);
    $meta = $stmt->fetch();
}
$pageTitle = 'Profile';
$activeNav = 'profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/index.php">Dashboard</a> / Profile</div>
    <h1 class="uh-page-title">My Profile</h1>
  </div>
  <a href="/lecturer/edit_profile.php" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Profile</a>
</div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center py-4">
        <div class="uh-avatar-lg mx-auto mb-3"><?= htmlspecialchars(user_initials()) ?></div>
        <h5 class="fw-bold"><?= htmlspecialchars($user['full_name']) ?></h5>
        <div class="text-muted-2 fs-sm"><?= htmlspecialchars($meta['department_name'] ?? '') ?></div>
        <?php if ($meta): ?><span class="badge badge-soft-info font-mono mt-2"><?= htmlspecialchars($meta['employee_number']) ?></span><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">Faculty information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6"><div class="fs-xs text-faint">EMAIL</div><div class="fw-semibold"><?= htmlspecialchars($user['email']) ?></div></div>
          <div class="col-md-6"><div class="fs-xs text-faint">SPECIALIZATION</div><div class="fw-semibold"><?= htmlspecialchars($meta['specialization'] ?? '—') ?></div></div>
          <div class="col-md-6"><div class="fs-xs text-faint">DEPARTMENT</div><div class="fw-semibold"><?= htmlspecialchars($meta['department_name'] ?? '—') ?></div></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
