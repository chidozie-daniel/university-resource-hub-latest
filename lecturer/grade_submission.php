<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$id = (int)($_GET['id'] ?? $_POST['submission_id'] ?? 0);
$pdo = db();

$stmt = $pdo->prepare(
    'SELECT s.*, a.title, a.total_marks, a.assignment_id, u.full_name, st.registration_number, c.course_code
     FROM submissions s
     JOIN assignments a ON a.assignment_id = s.assignment_id
     JOIN courses c ON c.course_id = a.course_id
     JOIN students st ON st.student_id = s.student_id
     JOIN users u ON u.user_id = st.user_id
     WHERE s.submission_id = ? AND a.lecturer_id = ?'
);
$stmt->execute([$id, $lecturerId]);
$s = $stmt->fetch();
if (!$s) { header('Location: /lecturer/submissions.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marks = (float)($_POST['marks'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    $pdo->prepare('UPDATE submissions SET marks = ?, feedback = ?, status = \'graded\' WHERE submission_id = ?')
        ->execute([$marks, $feedback ?: null, $id]);
    header('Location: /lecturer/submissions.php?assignment_id=' . (int)$s['assignment_id']);
    exit;
}

$pageTitle = 'Grade';
$activeNav = 'submissions';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/submissions.php">Submissions</a> / Grade</div>
    <h1 class="uh-page-title">Grade submission</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><?= htmlspecialchars($s['title']) ?> · <?= htmlspecialchars($s['course_code']) ?></div>
      <div class="card-body">
        <div class="mb-3">
          <div class="fw-semibold"><?= htmlspecialchars($s['full_name']) ?></div>
          <div class="fs-xs text-faint font-mono"><?= htmlspecialchars($s['registration_number']) ?> · <?= date('M j, Y H:i', strtotime($s['submitted_at'])) ?></div>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="/<?= htmlspecialchars($s['file_path']) ?>" target="_blank"><i class="bi bi-download me-1"></i>Download file</a>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Grading</div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="submission_id" value="<?= (int)$id ?>">
          <div class="mb-3">
            <label class="form-label">Marks (out of <?= htmlspecialchars($s['total_marks']) ?>)</label>
            <input type="number" step="0.01" class="form-control" name="marks" value="<?= htmlspecialchars($s['marks'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Feedback</label>
            <textarea class="form-control" name="feedback" rows="5"><?= htmlspecialchars($s['feedback'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Mark as graded</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
