<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'student';
        $pass = $_POST['password'] ?? 'ChangeMe123!';
        if (!in_array($role, ['student','lecturer','admin'], true)) $role = 'student';
        if ($name && $email) {
            try {
                $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, \'active\')')
                    ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
            } catch (Throwable $e) {
                $error = 'Could not create user (email may exist).';
            }
        }
    }
    if ($_POST['action'] === 'toggle' && isset($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $pdo->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE user_id = ?")->execute([$uid]);
    }
    if ($_POST['action'] === 'delete' && isset($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$uid]);
    }
    if (!$error) { header('Location: /admin/users.php'); exit; }
}

$roleFilter = $_GET['role'] ?? '';
$sql = 'SELECT user_id, full_name, email, role, status, created_at FROM users WHERE 1=1';
$params = [];
if (in_array($roleFilter, ['student','lecturer','admin'], true)) {
    $sql .= ' AND role = ?';
    $params[] = $roleFilter;
}
$sql .= ' ORDER BY created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
$activeNav = 'users';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Users</div>
    <h1 class="uh-page-title">User management</h1>
  </div>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="d-flex gap-2 mb-3">
      <a href="?" class="btn btn-sm <?= $roleFilter===''?'btn-primary':'btn-light-2' ?>">All</a>
      <a href="?role=student" class="btn btn-sm <?= $roleFilter==='student'?'btn-primary':'btn-light-2' ?>">Students</a>
      <a href="?role=lecturer" class="btn btn-sm <?= $roleFilter==='lecturer'?'btn-primary':'btn-light-2' ?>">Lecturers</a>
      <a href="?role=admin" class="btn btn-sm <?= $roleFilter==='admin'?'btn-primary':'btn-light-2' ?>">Admins</a>
    </div>
  <div class="table-responsive-card">
    <table class="table table-hover mb-0">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="fw-semibold text-dark"><?= htmlspecialchars($u['full_name']) ?></td>
          <td class="fs-sm text-muted-2"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge badge-soft-gray"><?= htmlspecialchars($u['role']) ?></span></td>
          <td><?= $u['status']==='active' ? '<span class="badge badge-soft-success">Active</span>' : '<span class="badge badge-soft-gray">'.htmlspecialchars($u['status']).'</span>' ?></td>
          <td class="fs-sm text-muted-2"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <div class="d-flex gap-1">
              <form method="post" class="d-inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit" title="Toggle status"><i class="bi bi-toggle-on"></i></button>
              </form>
              <button class="btn btn-ghost btn-sm text-danger" type="button" title="Delete" onclick="confirmDeleteUser(<?= (int)$u['user_id'] ?>)"><i class="bi bi-trash"></i></button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Add user</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="action" value="create">
          <div class="mb-2"><label class="form-label">Full name</label><input class="form-control" name="full_name" required></div>
          <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
          <div class="mb-2">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
              <option value="student">Student</option>
              <option value="lecturer">Lecturer</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Temp password</label><input class="form-control" name="password" value="ChangeMe123!"></div>
          <button class="btn btn-primary btn-sm w-100" type="submit">Create user</button>
          <p class="form-text mt-2">Students still need a row in <code>students</code>; lecturers need <code>lecturers</code>.</p>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Delete user</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">Are you sure you want to delete this user? This action cannot be undone.</div>
      <div class="modal-footer">
        <button class="btn btn-light-2" data-bs-dismiss="modal">Cancel</button>
        <form method="post" class="d-inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" id="deleteUserId"><button class="btn btn-danger" type="submit">Delete</button></form>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDeleteUser(uid) { document.getElementById('deleteUserId').value = uid; new bootstrap.Modal(document.getElementById('deleteUserModal')).show(); }
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
