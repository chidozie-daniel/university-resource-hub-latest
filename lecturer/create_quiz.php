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
         JOIN courses c ON c.course_id = cl.course_id WHERE cl.lecturer_id = ?'
    );
    $stmt->execute([$lecturerId]);
    $courses = $stmt->fetchAll();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 0) ?: null;
    $total = (float)($_POST['total_marks'] ?? 0);
    if (!$courseId || $title === '') {
        $error = 'Course and title required.';
    } else {
        $pdo->prepare(
            'INSERT INTO quizzes (course_id, lecturer_id, title, description, duration_minutes, total_marks, start_date, end_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $courseId, $lecturerId, $title, trim($_POST['description'] ?? '') ?: null,
            $duration, $total,
            $_POST['start_date'] ?: null, $_POST['end_date'] ?: null
        ]);
        $qid = (int)$pdo->lastInsertId();
        header('Location: /lecturer/questions.php?quiz_id=' . $qid);
        exit;
    }
}
$pageTitle = 'Create Quiz';
$activeNav = 'quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/quizzes.php">Quizzes</a> / New</div>
    <h1 class="uh-page-title">Create quiz</h1>
  </div>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width:640px;">
  <div class="card-body">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Course</label>
        <select name="course_id" class="form-select" required>
          <option value="">Select</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['course_id'] ?>"><?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Duration (minutes)</label><input type="number" class="form-control" name="duration_minutes" value="15"></div>
        <div class="col-md-6"><label class="form-label">Total marks</label><input type="number" step="0.01" class="form-control" name="total_marks" value="10"></div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Available from</label><input type="datetime-local" class="form-control" name="start_date"></div>
        <div class="col-md-6"><label class="form-label">Available until</label><input type="datetime-local" class="form-control" name="end_date"></div>
      </div>
      <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
      <button class="btn btn-primary" type="submit">Continue to questions</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
