<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['course_id'])) {
        $pdo->prepare('DELETE FROM courses WHERE course_id = ?')->execute([(int)$_POST['course_id']]);
    } else {
        $dept = (int)($_POST['department_id'] ?? 0);
        $code = trim($_POST['course_code'] ?? '');
        $name = trim($_POST['course_name'] ?? '');
        $credits = (int)($_POST['credit_hours'] ?? 3);
        $desc = trim($_POST['description'] ?? '');
        if ($dept && $code && $name) {
            try {
                $pdo->prepare(
                    'INSERT INTO courses (department_id, course_code, course_name, description, credit_hours) VALUES (?, ?, ?, ?, ?)'
                )->execute([$dept, $code, $name, $desc ?: null, $credits]);
            } catch (Throwable $e) {}
        }
    }
    header('Location: /admin/courses.php');
    exit;
}
$depts = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$rows = $pdo->query(
    'SELECT c.*, d.department_name,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.status = \'enrolled\') AS enrolled,
            (SELECT u.full_name FROM course_lecturers cl
             JOIN lecturers l ON l.lecturer_id = cl.lecturer_id
             JOIN users u ON u.user_id = l.user_id WHERE cl.course_id = c.course_id LIMIT 1) AS lecturer
     FROM courses c JOIN departments d ON d.department_id = c.department_id
     ORDER BY c.course_code'
)->fetchAll();
$pageTitle = 'Courses';
$activeNav = 'courses';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Courses</div>
    <h1 class="uh-page-title">Courses</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="table-responsive-card">
      <table class="table table-hover mb-0">
        <thead><tr><th>Code</th><th>Name</th><th>Dept</th><th>Credits</th><th>Lecturer</th><th>Enrolled</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="font-mono fw-semibold"><?= htmlspecialchars($r['course_code']) ?></td>
            <td><?= htmlspecialchars($r['course_name']) ?></td>
            <td class="fs-sm text-muted-2"><?= htmlspecialchars($r['department_name']) ?></td>
            <td><?= (int)$r['credit_hours'] ?></td>
            <td class="fs-sm"><?= htmlspecialchars($r['lecturer'] ?? '—') ?></td>
            <td><?= (int)$r['enrolled'] ?></td>
            <td class="text-end">
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this course?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="course_id" value="<?= (int)$r['course_id'] ?>">
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
      <div class="card-header">Add course</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2">
            <label class="form-label">Department</label>
            <select class="form-select" name="department_id" required>
              <?php foreach ($depts as $d): ?>
                <option value="<?= (int)$d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2"><label class="form-label">Code</label><input class="form-control" name="course_code" required></div>
          <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="course_name" required></div>
          <div class="mb-2"><label class="form-label">Credits</label><input type="number" class="form-control" name="credit_hours" value="3"></div>
          <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
          <button class="btn btn-primary btn-sm" type="submit">Create</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
