<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$pdo = db();
$list = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT r.*, m.title AS module_title, c.course_code
         FROM learning_resources r
         JOIN learning_modules m ON m.module_id = r.module_id
         JOIN courses c ON c.course_id = m.course_id
         JOIN course_lecturers cl ON cl.course_id = c.course_id AND cl.lecturer_id = ?
         WHERE r.uploaded_by = ?
         ORDER BY r.created_at DESC'
    );
    $stmt->execute([$lecturerId, $lecturerId]);
    $list = $stmt->fetchAll();
}
$pageTitle = 'Resources';
$activeNav = 'resources';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/index.php">Dashboard</a> / Resources</div>
    <h1 class="uh-page-title">Learning resources</h1>
  </div>
  <a href="/lecturer/upload_resource.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Upload</a>
</div>
<div class="table-responsive-card">
  <table class="table table-hover mb-0">
    <thead><tr><th>Title</th><th>Type</th><th>Module</th><th>Course</th><th>Uploaded</th></tr></thead>
    <tbody>
    <?php if (!$list): ?><tr><td colspan="5" class="text-center text-muted-2 py-4">No resources uploaded.</td></tr><?php endif; ?>
    <?php foreach ($list as $r): ?>
      <tr>
        <td class="fw-semibold text-dark"><?= htmlspecialchars($r['title']) ?></td>
        <td><span class="badge badge-soft-gray"><?= htmlspecialchars($r['resource_type']) ?></span></td>
        <td><?= htmlspecialchars($r['module_title']) ?></td>
        <td class="font-mono fs-sm"><?= htmlspecialchars($r['course_code']) ?></td>
        <td class="fs-sm text-muted-2"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
