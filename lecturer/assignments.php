<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$pdo = db();
$list = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT a.*, c.course_code,
                (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.assignment_id) AS sub_count
         FROM assignments a
         JOIN courses c ON c.course_id = a.course_id
         WHERE a.lecturer_id = ?
         ORDER BY a.due_date DESC'
    );
    $stmt->execute([$lecturerId]);
    $list = $stmt->fetchAll();
}
$pageTitle = 'Assignments';
$activeNav = 'assignments';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/index.php">Dashboard</a> / Assignments</div>
    <h1 class="uh-page-title">Assignments</h1>
  </div>
  <a href="/lecturer/create_assignment.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create</a>
</div>
<div class="table-responsive-card">
  <table class="table table-hover mb-0">
    <thead><tr><th>Title</th><th>Course</th><th>Due</th><th>Marks</th><th>Submissions</th><th></th></tr></thead>
    <tbody>
    <?php if (!$list): ?><tr><td colspan="6" class="text-center text-muted-2 py-4">No assignments.</td></tr><?php endif; ?>
    <?php foreach ($list as $a): ?>
      <tr>
        <td class="fw-semibold text-dark"><?= htmlspecialchars($a['title']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($a['course_code']) ?></td>
        <td><?= date('M j, Y', strtotime($a['due_date'])) ?></td>
        <td><?= htmlspecialchars($a['total_marks']) ?></td>
        <td><?= (int)$a['sub_count'] ?></td>
        <td class="text-end"><a class="btn btn-sm btn-light-2" href="/lecturer/submissions.php?assignment_id=<?= (int)$a['assignment_id'] ?>">Submissions</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
