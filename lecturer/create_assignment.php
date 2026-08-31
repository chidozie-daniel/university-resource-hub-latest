<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$pdo = db();
$error = '';

$courses = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.course_code, c.course_name FROM course_lecturers cl
         JOIN courses c ON c.course_id = cl.course_id WHERE cl.lecturer_id = ? ORDER BY c.course_code'
    );
    $stmt->execute([$lecturerId]);
    $courses = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $due = $_POST['due_date'] ?? '';
    $marks = (float)($_POST['total_marks'] ?? 100);
    $desc = trim($_POST['description'] ?? '');
    if (!$courseId || $title === '' || $due === '') {
        $error = 'Course, title and due date are required.';
    } else {
        $pdo->prepare(
            'INSERT INTO assignments (course_id, lecturer_id, title, description, due_date, total_marks)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$courseId, $lecturerId, $title, $desc ?: null, $due, $marks]);
        header('Location: /lecturer/assignments.php');
        exit;
    }
}

$pageTitle = 'Create Assignment';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/assignments.php">Assignments</a> / New</div>
    <h1 class="uh-page-title">Create assignment</h1>
  </div>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width:720px;">
  <div class="card-body">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Course</label>
        <select class="form-select" name="course_id" required>
          <option value="">Select</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['course_id'] ?>"><?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Due date</label><input type="datetime-local" class="form-control" name="due_date" required></div>
        <div class="col-md-6"><label class="form-label">Total marks</label><input type="number" step="0.01" class="form-control" name="total_marks" value="100"></div>
      </div>
      <div class="mb-3"><label class="form-label">Instructions</label><textarea class="form-control" name="description" rows="4"></textarea></div>
      <button type="submit" class="btn btn-primary">Publish</button>
      <a href="/lecturer/assignments.php" class="btn btn-light-2">Cancel</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
