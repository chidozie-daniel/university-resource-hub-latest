<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_by_role($_SESSION['user']['role']);
}

$error = '';
$email = '';
$selectedRole = $_POST['role'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'student';

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $stmt = db()->prepare(
            'SELECT user_id, full_name, email, password_hash, role, status, profile_image
             FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            $error = 'Invalid email or password.';
        } elseif ($row['status'] !== 'active') {
            $error = 'This account is not active. Contact administration.';
        } else {
            login_user($row);
            // Warm student/lecturer ids
            if ($row['role'] === 'student') {
                current_student_id();
            } elseif ($row['role'] === 'lecturer') {
                current_lecturer_id();
            }
            redirect_by_role($row['role']);
        }
    }
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<div class="uh-auth-wrap">
  <div class="uh-auth-side">
    <div>
      <div class="d-flex align-items-center gap-2 mb-5">
        <div style="width:38px;height:38px;border-radius:10px;background:var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Lexend';font-weight:800;">U</div>
        <span class="font-display fw-bold fs-4">UniHub</span>
      </div>
      <h1 class="font-display fw-bold display-6 mb-3" style="max-width:480px;">Your University.<br>One Hub.</h1>
      <p class="opacity-75" style="max-width:420px;font-size:15px;">Course materials, assignments, quizzes, attendance, grades and announcements — in one platform.</p>
    </div>
    <div class="d-flex gap-4 flex-wrap">
      <div><div class="fs-4 fw-bold font-display">One</div><div class="opacity-50 fs-sm">Login for all roles</div></div>
      <div><div class="fs-4 fw-bold font-display">Secure</div><div class="opacity-50 fs-sm">University accounts</div></div>
    </div>
  </div>
  <div class="uh-auth-form">
    <div class="w-100" style="max-width:360px;">
      <h2 class="font-display fw-bold h3 mb-1">Sign in to UniHub</h2>
      <p class="text-muted-2 fs-sm mb-4">Use your university email and password.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2 fs-sm"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <label class="form-label">Signing in as</label>
        <div class="row g-2 mb-3">
          <?php
          $roles = [
              'student'  => ['bi-mortarboard', 'Student'],
              'lecturer' => ['bi-person-video3', 'Lecturer'],
              'admin'    => ['bi-shield-check', 'Admin'],
          ];
          foreach ($roles as $r => [$icon, $label]):
          ?>
          <div class="col-4">
            <label class="uh-role-pill w-100 mb-0 <?= $selectedRole === $r ? 'active' : '' ?>">
              <input type="radio" name="role" value="<?= $r ?>" class="d-none" <?= $selectedRole === $r ? 'checked' : '' ?>
                     onchange="this.closest('form').querySelectorAll('.uh-role-pill').forEach(p=>p.classList.remove('active')); this.closest('.uh-role-pill').classList.add('active');">
              <i class="bi <?= $icon ?>"></i>
              <?= $label ?>
            </label>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="mb-3">
          <label class="form-label" for="email">University Email</label>
          <input type="email" class="form-control" id="email" name="email" required
                 value="<?= htmlspecialchars($email) ?>"
                 placeholder="you@uni.edu">
        </div>
        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
          Sign In <i class="bi bi-arrow-right ms-1"></i>
        </button>
      </form>
      <p class="text-center fs-sm text-muted-2 mb-0">
        New student? <a href="/register.php">Create an account</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>
