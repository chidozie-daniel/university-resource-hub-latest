<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['program_id'])) {
        $pdo->prepare('DELETE FROM programs WHERE program_id = ?')->execute([(int)$_POST['program_id']]);
    } else {
        $dept = (int)($_POST['department_id'] ?? 0);
        $name = trim($_POST['program_name'] ?? '');
        $years = $_POST['duration_years'] !== '' ? (float)$_POST['duration_years'] : null;
        $degree = trim($_POST['degree_type'] ?? '') ?: null;
        if ($dept && $name) {
            try {
                $pdo->prepare('INSERT INTO programs (department_id, program_name, duration_years, degree_type) VALUES (?, ?, ?, ?)')
                    ->execute([$dept, $name, $years, $degree]);
            } catch (Throwable $e) {}
        }
    }
    header('Location: /admin/programs.php');
    exit;
}
$depts = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$rows = $pdo->query(
    'SELECT p.*, d.department_name,
            (SELECT COUNT(*) FROM students s WHERE s.program_id = p.program_id) AS student_n
     FROM programs p JOIN departments d ON d.department_id = p.department_id
     ORDER BY p.program_name'
)->fetchAll();
$pageTitle = 'Programs';
$activeNav = 'programs';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Programs</div>
    <h1 class="uh-page-title">Programs</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="table-responsive-card">
      <table class="table table-hover mb-0">
        <thead><tr><th>Program</th><th>Department</th><th>Duration</th><th>Students</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="fw-semibold text-dark"><?= htmlspecialchars($r['program_name']) ?></td>
            <td class="fs-sm text-muted-2"><?= htmlspecialchars($r['department_name']) ?></td>
            <td><?= $r['duration_years'] !== null ? htmlspecialchars($r['duration_years']) . ' yrs' : '—' ?></td>
            <td><?= (int)$r['student_n'] ?></td>
            <td class="text-end">
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this program?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="program_id" value="<?= (int)$r['program_id'] ?>">
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
      <div class="card-header">Add program</div>
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
          <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="program_name" required></div>
          <div class="mb-2"><label class="form-label">Duration (years)</label><input type="number" step="0.5" class="form-control" name="duration_years" value="4"></div>
          <div class="mb-3"><label class="form-label">Degree type</label><input class="form-control" name="degree_type" placeholder="B.Tech"></div>
          <button class="btn btn-primary btn-sm" type="submit">Create</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
