<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$user = current_user();
$pdo = db();
$error = '';

$meta = null;
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT l.employee_number, l.specialization, l.department_id, d.department_name
         FROM lecturers l JOIN departments d ON d.department_id = l.department_id
         WHERE l.lecturer_id = ?'
    );
    $stmt->execute([$lecturerId]);
    $meta = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $spec = trim($_POST['specialization'] ?? '');
    $newPass = $_POST['password'] ?? '';
    $newPass2 = $_POST['password2'] ?? '';
    if ($name === '') {
        $error = 'Name is required.';
    } elseif ($newPass !== '' && $newPass !== $newPass2) {
        $error = 'Passwords do not match.';
    } elseif ($newPass !== '' && strlen($newPass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?')
            ->execute([$name, $phone ?: null, $user['user_id']]);
        if ($lecturerId) {
            $pdo->prepare('UPDATE lecturers SET specialization = ? WHERE lecturer_id = ?')
                ->execute([$spec ?: null, $lecturerId]);
        }
        if ($newPass !== '') {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                ->execute([password_hash($newPass, PASSWORD_DEFAULT), $user['user_id']]);
        }
        $_SESSION['user']['full_name'] = $name;
        header('Location: /lecturer/profile.php');
        exit;
    }
}

$pageTitle = 'Edit Profile';
$activeNav = 'profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/profile.php">Profile</a> / Edit</div>
    <h1 class="uh-page-title">Edit Profile</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">Update Personal Information</div>
      <div class="card-body">
        <form method="post">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Full Name</label><input class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
          <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Specialization</label><input class="form-control" name="specialization" value="<?= htmlspecialchars($meta['specialization'] ?? '') ?>"></div>
          <div class="col-md-6"><label class="form-label">Department</label><input class="form-control" value="<?= htmlspecialchars($meta['department_name'] ?? '') ?>" disabled></div>
          <div class="col-md-6"><label class="form-label">Employee No.</label><input class="form-control" value="<?= htmlspecialchars($meta['employee_number'] ?? '') ?>" disabled></div>
        </div>
        <div class="uh-divider"></div>
        <h6 class="fw-bold mb-2">Change Password</h6>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">New Password</label><input type="password" class="form-control" name="password" placeholder="Leave blank to keep current"></div>
          <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="password2" placeholder="Confirm new password"></div>
        </div>
        <?php if ($error): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <div class="d-flex gap-2 mt-4">
          <button class="btn btn-primary" type="submit" name="save" value="1"><i class="bi bi-check2 me-1"></i>Save Changes</button>
          <a href="/lecturer/profile.php" class="btn btn-light-2">Cancel</a>
        </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>