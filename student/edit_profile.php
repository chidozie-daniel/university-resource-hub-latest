<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$user = current_user();
$pdo = db();
$error = '';

$profile = null;
if ($studentId) {
    $p = $pdo->prepare('SELECT * FROM student_profiles WHERE student_id = ?');
    $p->execute([$studentId]);
    $profile = $p->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['date_of_birth'] ?: null;
    $address = trim($_POST['address'] ?? '');
    $newPass = $_POST['password'] ?? '';
    if ($name === '') {
        $error = 'Name is required.';
    } else {
        $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?')
            ->execute([$name, $phone ?: null, $user['user_id']]);
        if ($studentId) {
            if ($profile) {
                $pdo->prepare('UPDATE student_profiles SET date_of_birth = ?, address = ? WHERE student_id = ?')
                    ->execute([$dob, $address ?: null, $studentId]);
            } else {
                $pdo->prepare('INSERT INTO student_profiles (student_id, date_of_birth, address) VALUES (?, ?, ?)')
                    ->execute([$studentId, $dob, $address ?: null]);
            }
        }
        if ($newPass !== '') {
            if (strlen($newPass) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                    ->execute([password_hash($newPass, PASSWORD_DEFAULT), $user['user_id']]);
            }
        }
        if (!$error) {
            $_SESSION['user']['full_name'] = $name;
            header('Location: /student/profile.php');
            exit;
        }
    }
}

$pageTitle = 'Edit Profile';
$activeNav = 'profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/profile.php">Profile</a> / Edit</div>
    <h1 class="uh-page-title">Edit Profile</h1>
  </div>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full name</label><input class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Date of birth</label><input type="date" class="form-control" name="date_of_birth" value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>"></div>
        <div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">New password</label><input type="password" class="form-control" name="password" placeholder="Leave blank to keep"></div>
      </div>
      <div class="mt-4">
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a href="/student/profile.php" class="btn btn-light-2">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
