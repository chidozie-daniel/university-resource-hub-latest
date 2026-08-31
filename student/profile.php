<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$user = current_user();
$pdo = db();

$meta = null;
$profile = null;
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT s.registration_number, s.semester, s.year_level, p.program_name
         FROM students s JOIN programs p ON p.program_id = s.program_id WHERE s.student_id = ?'
    );
    $stmt->execute([$studentId]);
    $meta = $stmt->fetch();
    $p = $pdo->prepare('SELECT * FROM student_profiles WHERE student_id = ?');
    $p->execute([$studentId]);
    $profile = $p->fetch();
}

$pageTitle = 'Profile';
$activeNav = 'profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Profile</div>
    <h1 class="uh-page-title">My Profile</h1>
  </div>
  <a href="/student/edit_profile.php" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
</div>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-body text-center py-4">
        <div class="uh-avatar-lg mx-auto mb-3"><?= htmlspecialchars(user_initials()) ?></div>
        <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['full_name']) ?></h5>
        <div class="text-muted-2 fs-sm mb-3"><?= htmlspecialchars($meta['program_name'] ?? '') ?></div>
        <?php if ($meta): ?>
          <span class="badge badge-soft-info font-mono"><?= htmlspecialchars($meta['registration_number']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">Personal information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6"><div class="fs-xs text-faint">FULL NAME</div><div class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></div></div>
          <div class="col-md-6"><div class="fs-xs text-faint">EMAIL</div><div class="fw-semibold"><?= htmlspecialchars($user['email']) ?></div></div>
          <div class="col-md-6"><div class="fs-xs text-faint">REGISTRATION NO.</div><div class="fw-semibold font-mono"><?= htmlspecialchars($meta['registration_number'] ?? '—') ?></div></div>
          <div class="col-md-6"><div class="fs-xs text-faint">PROGRAM</div><div class="fw-semibold"><?= htmlspecialchars($meta['program_name'] ?? '—') ?></div></div>
          <div class="col-md-6"><div class="fs-xs text-faint">SEMESTER</div><div class="fw-semibold"><?= $meta && $meta['semester'] ? (int)$meta['semester'] : '—' ?></div></div>
          <div class="col-md-6"><div class="fs-xs text-faint">DATE OF BIRTH</div><div class="fw-semibold"><?= $profile && $profile['date_of_birth'] ? htmlspecialchars($profile['date_of_birth']) : '—' ?></div></div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
