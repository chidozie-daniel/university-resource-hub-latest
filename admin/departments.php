<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['department_id'])) {
        $did = (int)$_POST['department_id'];
        $pdo->prepare('DELETE FROM departments WHERE department_id = ?')->execute([$did]);
    } else {
        $name = trim($_POST['department_name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name && $code) {
            try {
                $pdo->prepare('INSERT INTO departments (department_name, code, description) VALUES (?, ?, ?)')
                    ->execute([$name, $code, $desc ?: null]);
            } catch (Throwable $e) { /* unique conflict */ }
        }
    }
    header('Location: /admin/departments.php');
    exit;
}
$rows = $pdo->query(
    'SELECT d.*,
            (SELECT COUNT(*) FROM programs p WHERE p.department_id = d.department_id) AS program_n,
            (SELECT COUNT(*) FROM lecturers l WHERE l.department_id = d.department_id) AS faculty_n
     FROM departments d ORDER BY d.department_name'
)->fetchAll();
$pageTitle = 'Departments';
$activeNav = 'departments';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Departments</div>
    <h1 class="uh-page-title">Departments</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="table-responsive-card">
      <table class="table table-hover mb-0">
        <thead><tr><th>Name</th><th>Code</th><th>Programs</th><th>Faculty</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="fw-semibold text-dark"><?= htmlspecialchars($r['department_name']) ?></td>
            <td class="font-mono"><?= htmlspecialchars($r['code']) ?></td>
            <td><?= (int)$r['program_n'] ?></td>
            <td><?= (int)$r['faculty_n'] ?></td>
            <td class="text-end">
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this department?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="department_id" value="<?= (int)$r['department_id'] ?>">
                <button class="btn btn-ghost btn-sm text-danger" type="submit"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted-2 py-3">None yet</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Add department</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2"><label class="form-label">Name</label><input class="form-control" name="department_name" required></div>
          <div class="mb-2"><label class="form-label">Code</label><input class="form-control" name="code" required></div>
          <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
          <button class="btn btn-primary btn-sm" type="submit">Create</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
