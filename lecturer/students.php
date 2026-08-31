<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$courseId = (int)($_GET['course_id'] ?? 0);
$pdo = db();
$list = [];
if ($lecturerId) {
    $sql = 'SELECT DISTINCT u.full_name, st.registration_number, st.student_id, c.course_code,
                   lp.completion_percentage
            FROM enrollments e
            JOIN students st ON st.student_id = e.student_id
            JOIN users u ON u.user_id = st.user_id
            JOIN courses c ON c.course_id = e.course_id
            JOIN course_lecturers cl ON cl.course_id = e.course_id AND cl.lecturer_id = ?
            LEFT JOIN learning_progress lp ON lp.student_id = st.student_id AND lp.course_id = e.course_id
            WHERE e.status = \'enrolled\'';
    $params = [$lecturerId];
    if ($courseId) {
        $sql .= ' AND e.course_id = ?';
        $params[] = $courseId;
    }
    $sql .= ' ORDER BY u.full_name LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $list = $stmt->fetchAll();
}
$pageTitle = 'Students';
$activeNav = 'students';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/index.php">Dashboard</a> / Students</div>
    <h1 class="uh-page-title">Students</h1>
  </div>
</div>
<div class="table-responsive-card">
  <table class="table table-hover mb-0">
    <thead><tr><th>Name</th><th>Reg No.</th><th>Course</th><th>Progress</th><th></th></tr></thead>
    <tbody>
    <?php if (!$list): ?><tr><td colspan="5" class="text-center text-muted-2 py-4">No students found.</td></tr><?php endif; ?>
    <?php foreach ($list as $s): ?>
      <tr>
        <td class="fw-semibold text-dark"><?= htmlspecialchars($s['full_name']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($s['registration_number']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($s['course_code']) ?></td>
        <td><?= $s['completion_percentage'] !== null ? (int)round((float)$s['completion_percentage']) . '%' : '—' ?></td>
        <td class="text-end"><a class="btn btn-sm btn-light-2" href="/lecturer/progress.php?student_id=<?= (int)$s['student_id'] ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
