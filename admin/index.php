<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$user = current_user();
$pdo = db();

$counts = [
    'students'  => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'lecturers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'lecturer'")->fetchColumn(),
    'courses'   => (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'departments' => (int) $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn(),
    'enrollments' => (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'enrolled'")->fetchColumn(),
    'programs'  => (int) $pdo->query('SELECT COUNT(*) FROM programs')->fetchColumn(),
];

$recentUsers = $pdo->query(
    'SELECT full_name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 6'
)->fetchAll();

$departments = $pdo->query(
    'SELECT d.department_name, COUNT(DISTINCT s.student_id) AS student_n
     FROM departments d
     LEFT JOIN programs p ON p.department_id = d.department_id
     LEFT JOIN students s ON s.program_id = p.program_id
     GROUP BY d.department_id, d.department_name
     ORDER BY student_n DESC
     LIMIT 6'
)->fetchAll();

$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="uh-page-head">
  <div>
    <h1 class="uh-page-title">Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
    <p class="text-muted-2 mb-0">University management overview</p>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="card uh-stat h-100"><div class="val"><?= $counts['students'] ?></div><div class="lbl">Students</div></div></div>
  <div class="col-6 col-lg-3"><div class="card uh-stat h-100"><div class="val"><?= $counts['lecturers'] ?></div><div class="lbl">Lecturers</div></div></div>
  <div class="col-6 col-lg-3"><div class="card uh-stat h-100"><div class="val"><?= $counts['courses'] ?></div><div class="lbl">Courses</div></div></div>
  <div class="col-6 col-lg-3"><div class="card uh-stat h-100"><div class="val"><?= $counts['departments'] ?></div><div class="lbl">Departments</div></div></div>
</div>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card uh-stat h-100"><div class="val"><?= $counts['enrollments'] ?></div><div class="lbl">Active enrollments</div></div></div>
  <div class="col-6 col-lg-3"><div class="card uh-stat h-100"><div class="val"><?= $counts['programs'] ?></div><div class="lbl">Programs</div></div></div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <a href="/admin/users.php" class="btn btn-primary btn-sm">Manage users</a>
        <a href="/admin/courses.php" class="btn btn-light-2 btn-sm">Courses</a>
        <a href="/admin/enrollments.php" class="btn btn-light-2 btn-sm">Enrollments</a>
        <a href="/admin/reports.php" class="btn btn-light-2 btn-sm">Reports</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span>Recent users</span>
        <a class="fs-sm" href="/admin/users.php">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Created</th></tr></thead>
          <tbody>
            <?php foreach ($recentUsers as $u): ?>
              <tr>
                <td class="fw-semibold text-dark"><?= htmlspecialchars($u['full_name']) ?></td>
                <td><span class="badge badge-soft-gray"><?= htmlspecialchars($u['role']) ?></span></td>
                <td>
                  <?php if ($u['status'] === 'active'): ?>
                    <span class="badge badge-soft-success">Active</span>
                  <?php else: ?>
                    <span class="badge badge-soft-gray"><?= htmlspecialchars($u['status']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="fs-sm text-muted-2"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Departments by students</div>
      <div class="card-body pt-2">
        <?php foreach ($departments as $d): ?>
          <div class="uh-list-row">
            <div class="flex-grow-1 fs-sm fw-semibold"><?= htmlspecialchars($d['department_name']) ?></div>
            <span class="fs-xs text-faint"><?= (int) $d['student_n'] ?> students</span>
          </div>
        <?php endforeach; ?>
        <?php if (!$departments): ?>
          <p class="text-muted-2 fs-sm mb-0">No departments yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
