<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['enrollment_id'])) {
        $pdo->prepare('DELETE FROM enrollments WHERE enrollment_id = ?')->execute([(int)$_POST['enrollment_id']]);
    } else {
        $sid = (int)($_POST['student_id'] ?? 0);
        $cid = (int)($_POST['course_id'] ?? 0);
        $year = trim($_POST['academic_year'] ?? '') ?: date('Y');
        if ($sid && $cid) {
            try {
                $pdo->prepare(
                    'INSERT INTO enrollments (student_id, course_id, enrollment_date, academic_year, status)
                     VALUES (?, ?, CURDATE(), ?, \'enrolled\')'
                )->execute([$sid, $cid, $year]);
            } catch (Throwable $e) {}
        }
    }
    header('Location: /admin/enrollments.php');
    exit;
}
$students = $pdo->query(
    'SELECT st.student_id, u.full_name, st.registration_number FROM students st JOIN users u ON u.user_id = st.user_id ORDER BY u.full_name'
)->fetchAll();
$courses = $pdo->query('SELECT course_id, course_code, course_name FROM courses WHERE status = \'active\' ORDER BY course_code')->fetchAll();
$rows = $pdo->query(
    'SELECT e.*, u.full_name, st.registration_number, c.course_code, p.program_name
     FROM enrollments e
     JOIN students st ON st.student_id = e.student_id
     JOIN users u ON u.user_id = st.user_id
     JOIN courses c ON c.course_id = e.course_id
     JOIN programs p ON p.program_id = st.program_id
     ORDER BY e.enrollment_date DESC LIMIT 100'
)->fetchAll();
$pageTitle = 'Enrollments';
$activeNav = 'enrollments';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Enrollments</div>
    <h1 class="uh-page-title">Enrollments</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="table-responsive-card">
      <table class="table table-hover mb-0">
        <thead><tr><th>Student</th><th>Reg</th><th>Course</th><th>Program</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="fw-semibold text-dark"><?= htmlspecialchars($r['full_name']) ?></td>
            <td class="font-mono fs-sm"><?= htmlspecialchars($r['registration_number']) ?></td>
            <td class="font-mono"><?= htmlspecialchars($r['course_code']) ?></td>
            <td class="fs-sm text-muted-2"><?= htmlspecialchars($r['program_name']) ?></td>
            <td><span class="badge badge-soft-success"><?= htmlspecialchars($r['status']) ?></span></td>
            <td class="fs-sm"><?= htmlspecialchars($r['enrollment_date']) ?></td>
            <td class="text-end">
              <form method="post" class="d-inline" onsubmit="return confirm('Remove this enrollment?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="enrollment_id" value="<?= (int)$r['enrollment_id'] ?>">
                <button class="btn btn-ghost btn-sm text-danger" type="submit"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">New enrollment</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2">
            <label class="form-label">Student</label>
            <select class="form-select" name="student_id" required>
              <?php foreach ($students as $s): ?>
                <option value="<?= (int)$s['student_id'] ?>"><?= htmlspecialchars($s['full_name'] . ' (' . $s['registration_number'] . ')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Course</label>
            <select class="form-select" name="course_id" required>
              <?php foreach ($courses as $c): ?>
                <option value="<?= (int)$c['course_id'] ?>"><?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Academic year</label><input class="form-control" name="academic_year" value="<?= date('Y') ?>"></div>
          <button class="btn btn-primary btn-sm" type="submit">Enroll</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
