<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$assignmentId = (int)($_GET['assignment_id'] ?? 0);
$pdo = db();

$list = [];
$title = 'Submissions';
if ($assignmentId && $lecturerId) {
    $a = $pdo->prepare('SELECT title FROM assignments WHERE assignment_id = ? AND lecturer_id = ?');
    $a->execute([$assignmentId, $lecturerId]);
    $title = $a->fetchColumn() ?: $title;
    $stmt = $pdo->prepare(
        'SELECT s.*, u.full_name, st.registration_number
         FROM submissions s
         JOIN students st ON st.student_id = s.student_id
         JOIN users u ON u.user_id = st.user_id
         WHERE s.assignment_id = ?
         ORDER BY s.submitted_at DESC'
    );
    $stmt->execute([$assignmentId]);
    $list = $stmt->fetchAll();
} elseif ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT s.*, u.full_name, st.registration_number, a.title AS assignment_title, c.course_code
         FROM submissions s
         JOIN assignments a ON a.assignment_id = s.assignment_id
         JOIN courses c ON c.course_id = a.course_id
         JOIN students st ON st.student_id = s.student_id
         JOIN users u ON u.user_id = st.user_id
         WHERE a.lecturer_id = ?
         ORDER BY s.submitted_at DESC LIMIT 50'
    );
    $stmt->execute([$lecturerId]);
    $list = $stmt->fetchAll();
}

$pageTitle = 'Submissions';
$activeNav = 'submissions';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/assignments.php">Assignments</a> / Submissions</div>
    <h1 class="uh-page-title"><?= htmlspecialchars($title) ?></h1>
  </div>
</div>
<div class="table-responsive-card">
  <table class="table table-hover mb-0">
    <thead><tr><th>Student</th><th>Reg</th><?php if (!$assignmentId): ?><th>Assignment</th><?php endif; ?><th>Submitted</th><th>Status</th><th>Marks</th><th></th></tr></thead>
    <tbody>
    <?php if (!$list): ?><tr><td colspan="7" class="text-center text-muted-2 py-4">No submissions.</td></tr><?php endif; ?>
    <?php foreach ($list as $s): ?>
      <tr>
        <td class="fw-semibold text-dark"><?= htmlspecialchars($s['full_name']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($s['registration_number']) ?></td>
        <?php if (!$assignmentId): ?><td><?= htmlspecialchars($s['assignment_title'] ?? '') ?></td><?php endif; ?>
        <td><?= date('M j, Y H:i', strtotime($s['submitted_at'])) ?></td>
        <td>
          <?php
          if ($s['status'] === 'graded') echo '<span class="badge badge-soft-success">Graded</span>';
          elseif ($s['status'] === 'late') echo '<span class="badge badge-soft-danger">Late</span>';
          else echo '<span class="badge badge-soft-info">Submitted</span>';
          ?>
        </td>
        <td><?= $s['marks'] !== null ? htmlspecialchars($s['marks']) : '—' ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/lecturer/grade_submission.php?id=<?= (int)$s['submission_id'] ?>">Grade</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
