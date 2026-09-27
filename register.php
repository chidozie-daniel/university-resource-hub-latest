<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_by_role($_SESSION['user']['role']);
}

$error = '';
$success = false;

$programs = [];
try {
    $programs = db()->query(
        'SELECT program_id, program_name FROM programs ORDER BY program_name'
    )->fetchAll();
} catch (Throwable $e) {
    $programs = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $regNo = trim($_POST['registration_number'] ?? '');
    $programId = (int) ($_POST['program_id'] ?? 0);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($fullName === '' || $email === '' || $regNo === '' || !$programId || $password === '') {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                throw new RuntimeException('Email is already registered.');
            }

            $check2 = $pdo->prepare('SELECT student_id FROM students WHERE registration_number = ?');
            $check2->execute([$regNo]);
            if ($check2->fetch()) {
                throw new RuntimeException('Registration number is already in use.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, status)
                 VALUES (?, ?, ?, \'student\', \'active\')'
            );
            $ins->execute([$fullName, $email, $hash]);
            $userId = (int) $pdo->lastInsertId();

            $insS = $pdo->prepare(
                'INSERT INTO students (user_id, registration_number, program_id)
                 VALUES (?, ?, ?)'
            );
            $insS->execute([$userId, $regNo, $programId]);

            $pdo->commit();
            $success = true;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="uh-auth-wrap">
  <div class="uh-auth-side">
    <div>
      <a href="/index.php" class="d-flex align-items-center gap-2 mb-5 text-decoration-none" style="color:inherit;">
        <div style="width:38px;height:38px;border-radius:10px;background:var(--accent);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;"><i class="bi bi-buildings"></i></div>
        <span class="font-display fw-bold fs-4">UniHub</span>
      </a>
      <h1 class="font-display fw-bold display-6 mb-3">Join your Learning Hub.</h1>
      <p class="opacity-75" style="max-width:420px;">Register with your official student details to access courses and resources.</p>
    </div>
    <div></div>
  </div>
  <div class="uh-auth-form">
    <div class="w-100" style="max-width:380px;">
      <a href="/index.php" class="d-inline-flex align-items-center gap-1 fs-sm mb-3"><i class="bi bi-arrow-left"></i> Back to home</a>
      <h2 class="font-display fw-bold h3 mb-1">Create student account</h2>
      <p class="text-muted-2 fs-sm mb-4">Lecturer and admin accounts are provisioned by administration.</p>

      <?php if ($success): ?>
        <div class="alert alert-success fs-sm">Account created. <a href="/login.php">Sign in</a></div>
      <?php else: ?>
        <?php if ($error): ?>
          <div class="alert alert-danger py-2 fs-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-2">
            <label class="form-label">Full name</label>
            <input class="form-control" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Registration number</label>
            <input class="form-control" name="registration_number" required value="<?= htmlspecialchars($_POST['registration_number'] ?? '') ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">University email</label>
            <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Program</label>
            <select class="form-select" name="program_id" required>
              <option value="">Select program</option>
              <?php foreach ($programs as $p): ?>
                <option value="<?= (int) $p['program_id'] ?>" <?= ((int)($_POST['program_id'] ?? 0) === (int)$p['program_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['program_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm password</label>
            <input type="password" class="form-control" name="password_confirm" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Create account</button>
        </form>
      <?php endif; ?>
      <p class="text-center fs-sm text-muted-2 mb-0">Already registered? <a href="/login.php">Sign in</a></p>
    </div>
  </div>
</div>
</body>
</html>
