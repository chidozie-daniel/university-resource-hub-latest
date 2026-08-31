<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$assignmentId = (int)($_GET['id'] ?? $_POST['assignment_id'] ?? 0);
$pdo = db();
$error = '';
$success = false;

$stmt = $pdo->prepare(
    'SELECT a.*, c.course_code FROM assignments a
     JOIN courses c ON c.course_id = a.course_id
     JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = ? AND e.status = \'enrolled\'
     WHERE a.assignment_id = ?'
);
$stmt->execute([$studentId, $assignmentId]);
$a = $stmt->fetch();
if (!$a) {
    header('Location: /student/assignments.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload failed. Please try again.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'txt'];
        if (!in_array($ext, $allowed, true)) {
            $error = 'File type not allowed.';
        } else {
            $dir = __DIR__ . '/../uploads/submissions/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $safe = 'sub_' . $studentId . '_' . $assignmentId . '_' . time() . '.' . $ext;
            $dest = $dir . $safe;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $rel = 'uploads/submissions/' . $safe;
                $late = strtotime($a['due_date']) < time();
                $status = $late ? 'late' : 'submitted';
                $existing = $pdo->prepare('SELECT submission_id FROM submissions WHERE assignment_id = ? AND student_id = ?');
                $existing->execute([$assignmentId, $studentId]);
                $sid = $existing->fetchColumn();
                if ($sid) {
                    $upd = $pdo->prepare('UPDATE submissions SET file_path = ?, submitted_at = NOW(), status = ?, marks = NULL, feedback = NULL WHERE submission_id = ?');
                    $upd->execute([$rel, $status, $sid]);
                } else {
                    $ins = $pdo->prepare('INSERT INTO submissions (assignment_id, student_id, file_path, status) VALUES (?, ?, ?, ?)');
                    $ins->execute([$assignmentId, $studentId, $rel, $status]);
                }
                header('Location: /student/assignments.php?id=' . $assignmentId);
                exit;
            }
            $error = 'Could not save file.';
        }
    }
}

$pageTitle = 'Submit Assignment';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/assignments.php">Assignments</a> / Submit</div>
    <h1 class="uh-page-title">Submit Assignment</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><?= htmlspecialchars($a['title']) ?></div>
      <div class="card-body">
        <div class="d-flex gap-4 fs-sm text-muted-2 mb-4">
          <div><i class="bi bi-journal-bookmark me-1"></i><?= htmlspecialchars($a['course_code']) ?></div>
          <div><i class="bi bi-calendar-event me-1"></i>Due <?= date('M j, Y H:i', strtotime($a['due_date'])) ?></div>
          <div><i class="bi bi-award me-1"></i><?= htmlspecialchars($a['total_marks']) ?> marks</div>
        </div>
        <?php if ($error): ?><div class="alert alert-danger py-2 fs-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="assignment_id" value="<?= (int)$assignmentId ?>">
          <label class="form-label">Upload file</label>
          <input type="file" name="file" class="form-control mb-3" required>
          <p class="form-text">PDF, DOCX, PPTX, ZIP — max size per server limits.</p>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" required id="declare">
            <label class="form-check-label fs-sm text-muted-2" for="declare">I confirm this is my own work.</label>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Submit</button>
          <a href="/student/assignments.php?id=<?= (int)$assignmentId ?>" class="btn btn-light-2">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
