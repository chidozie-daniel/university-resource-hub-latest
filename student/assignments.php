<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$pdo = db();
$detailId = (int)($_GET['id'] ?? 0);

if ($detailId && $studentId) {
    $stmt = $pdo->prepare(
        'SELECT a.*, c.course_code, c.course_name,
                s.submission_id, s.file_path, s.submitted_at, s.marks AS sub_marks, s.feedback, s.status AS sub_status
         FROM assignments a
         JOIN courses c ON c.course_id = a.course_id
         JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = ? AND e.status = \'enrolled\'
         LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = ?
         WHERE a.assignment_id = ?'
    );
    $stmt->execute([$studentId, $studentId, $detailId]);
    $a = $stmt->fetch();
    if (!$a) {
        header('Location: /student/assignments.php');
        exit;
    }
    $pageTitle = $a['title'];
    $activeNav = 'assignments';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/navbar.php';
    ?>
    <div class="uh-page-head">
      <div>
        <div class="uh-breadcrumb"><a href="/student/assignments.php">Assignments</a> / <?= htmlspecialchars($a['course_code']) ?></div>
        <h1 class="uh-page-title"><?= htmlspecialchars($a['title']) ?></h1>
      </div>
    </div>
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between">
            <span>Details</span>
            <?php
            $st = $a['sub_status'];
            if ($st === 'graded') echo '<span class="badge badge-soft-success">Graded</span>';
            elseif ($st === 'late') echo '<span class="badge badge-soft-danger">Late</span>';
            elseif ($st === 'submitted') echo '<span class="badge badge-soft-info">Submitted</span>';
            else echo '<span class="badge badge-soft-warning">Pending</span>';
            ?>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-6 col-md-3"><div class="fs-xs text-faint">COURSE</div><div class="fw-semibold font-mono"><?= htmlspecialchars($a['course_code']) ?></div></div>
              <div class="col-6 col-md-3"><div class="fs-xs text-faint">DUE</div><div class="fw-semibold"><?= date('M j, Y H:i', strtotime($a['due_date'])) ?></div></div>
              <div class="col-6 col-md-3"><div class="fs-xs text-faint">TOTAL MARKS</div><div class="fw-semibold"><?= htmlspecialchars($a['total_marks']) ?></div></div>
            </div>
            <div class="uh-divider"></div>
            <h6 class="fw-bold">Description</h6>
            <p class="text-muted-2"><?= nl2br(htmlspecialchars($a['description'] ?? '')) ?></p>
          </div>
        </div>
        <?php if ($a['sub_status'] === 'graded' || $a['sub_status'] === 'late'): ?>
        <div class="card">
          <div class="card-header">Feedback</div>
          <div class="card-body">
            <div class="fw-bold fs-5 mb-2"><?= htmlspecialchars($a['sub_marks'] ?? '—') ?> / <?= htmlspecialchars($a['total_marks']) ?></div>
            <p class="text-muted-2 fs-sm mb-0"><?= nl2br(htmlspecialchars($a['feedback'] ?? 'No written feedback.')) ?></p>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-4">
        <div class="card">
          <div class="card-header">Submission</div>
          <div class="card-body">
            <?php if (!$a['submission_id']): ?>
              <p class="fs-sm text-muted-2">You have not submitted yet.</p>
              <a href="/student/submit_assignment.php?id=<?= (int)$a['assignment_id'] ?>" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Submit</a>
            <?php else: ?>
              <div class="fs-xs text-faint mb-1">FILE</div>
              <div class="fs-sm fw-semibold mb-1"><?= htmlspecialchars(basename($a['file_path'])) ?></div>
              <div class="fs-xs text-faint mb-3">Submitted <?= date('M j, Y H:i', strtotime($a['submitted_at'])) ?></div>
              <?php if ($a['sub_status'] === 'submitted'): ?>
                <a href="/student/submit_assignment.php?id=<?= (int)$a['assignment_id'] ?>" class="btn btn-light-2 w-100">Resubmit</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// List view
$list = [];
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT a.assignment_id, a.title, a.due_date, a.total_marks, c.course_code,
                s.status AS sub_status, s.marks AS sub_marks
         FROM assignments a
         JOIN courses c ON c.course_id = a.course_id
         JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = ? AND e.status = \'enrolled\'
         LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = ?
         ORDER BY a.due_date DESC'
    );
    $stmt->execute([$studentId, $studentId]);
    $list = $stmt->fetchAll();
}

$pageTitle = 'Assignments';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';

$counts = ['all' => 0, 'pending' => 0, 'submitted' => 0, 'graded' => 0];
foreach ($list as $row) {
    $counts['all']++;
    $st = strtolower($row['sub_status'] ?? 'pending');
    if ($st === 'graded') $counts['graded']++;
    elseif ($st === 'submitted' || $st === 'late') $counts['submitted']++;
    else $counts['pending']++;
}
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Assignments</div>
    <h1 class="uh-page-title">Assignments</h1>
  </div>
</div>
<div class="d-flex gap-2 mb-3 flex-wrap">
  <button class="btn btn-light-2 btn-sm active" onclick="filterAssignments(this, 'all')">All (<?= $counts['all'] ?>)</button>
  <button class="btn btn-light-2 btn-sm" onclick="filterAssignments(this, 'pending')">Pending (<?= $counts['pending'] ?>)</button>
  <button class="btn btn-light-2 btn-sm" onclick="filterAssignments(this, 'submitted')">Submitted (<?= $counts['submitted'] ?>)</button>
  <button class="btn btn-light-2 btn-sm" onclick="filterAssignments(this, 'graded')">Graded (<?= $counts['graded'] ?>)</button>
</div>
<div class="table-responsive-card">
  <table class="table table-hover mb-0" id="assignmentsTable">
    <thead><tr><th>Assignment</th><th>Course</th><th>Due</th><th>Marks</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$list): ?>
      <tr><td colspan="6" class="text-muted-2 text-center py-4">No assignments.</td></tr>
    <?php endif; ?>
    <?php foreach ($list as $row):
      $st = strtolower($row['sub_status'] ?? 'pending');
      $filterClass = 'all';
      if ($st === 'graded') $filterClass = 'graded';
      elseif ($st === 'submitted' || $st === 'late') $filterClass = 'submitted';
      else $filterClass = 'pending';
    ?>
      <tr data-status="<?= $filterClass ?>">
        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['title']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($row['course_code']) ?></td>
        <td><?= date('M j, Y', strtotime($row['due_date'])) ?></td>
        <td><?= $row['sub_marks'] !== null ? htmlspecialchars($row['sub_marks']) . '/' . $row['total_marks'] : $row['total_marks'] ?></td>
        <td>
          <?php
          if ($st === 'graded') echo '<span class="badge badge-soft-success">Graded</span>';
          elseif ($st === 'late') echo '<span class="badge badge-soft-danger">Late</span>';
          elseif ($st === 'submitted') echo '<span class="badge badge-soft-info">Submitted</span>';
          else echo '<span class="badge badge-soft-warning">Pending</span>';
          ?>
        </td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="?id=<?= (int)$row['assignment_id'] ?>">Open</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
function filterAssignments(btn, status) {
  document.querySelectorAll('#assignmentsTable').forEach(function(t) {
    t.querySelectorAll('tbody tr').forEach(function(tr) {
      if (status === 'all' || tr.dataset.status === status) {
        tr.style.display = '';
      } else {
        tr.style.display = 'none';
      }
    });
  });
  document.querySelectorAll('.btn-light-2.btn-sm').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
