<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$courseId = (int)($_GET['course_id'] ?? 0);
$pdo = db();
$chk = $pdo->prepare('SELECT 1 FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?');
$chk->execute([$courseId, $lecturerId]);
if (!$chk->fetch()) { header('Location: /lecturer/courses.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $order = (int)($_POST['order_number'] ?? 1);
    if ($title !== '') {
        $pdo->prepare('INSERT INTO learning_modules (course_id, title, description, order_number) VALUES (?, ?, ?, ?)')
            ->execute([$courseId, $title, trim($_POST['description'] ?? ''), $order]);
    }
    header('Location: /lecturer/modules.php?course_id=' . $courseId);
    exit;
}

$modules = $pdo->prepare('SELECT * FROM learning_modules WHERE course_id = ? ORDER BY order_number');
$modules->execute([$courseId]);
$modules = $modules->fetchAll();
$course = $pdo->prepare('SELECT course_code FROM courses WHERE course_id = ?');
$course->execute([$courseId]);
$code = $course->fetchColumn();

$pageTitle = 'Modules';
$activeNav = 'courses';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/course.php?id=<?= $courseId ?>"><?= htmlspecialchars($code) ?></a> / Modules</div>
    <h1 class="uh-page-title">Learning modules</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <?php foreach ($modules as $i => $m):
      $resources = $pdo->prepare('SELECT * FROM learning_resources WHERE module_id = ? ORDER BY created_at');
      $resources->execute([$m['module_id']]);
      $resources = $resources->fetchAll();
    ?>
    <div class="uh-mod mb-2">
      <div class="uh-mod-head" onclick="const b=this.nextElementSibling; b.style.display = b.style.display==='none'?'block':'none'; this.querySelector('.chev').classList.toggle('bi-chevron-down'); this.querySelector('.chev').classList.toggle('bi-chevron-up');">
        <div class="d-flex justify-content-between w-100 align-items-center">
          <span class="fw-semibold fs-sm">Module <?= $i + 1 ?>: <?= htmlspecialchars($m['title']) ?></span>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-ghost btn-sm p-1"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-ghost btn-sm p-1 text-danger"><i class="bi bi-trash"></i></button>
            <i class="bi bi-chevron-down chev text-faint"></i>
          </div>
        </div>
      </div>
      <div class="uh-mod-body" style="display:block;">
        <?php if (!$resources): ?>
          <p class="fs-sm text-muted-2 mb-0">No resources in this module.</p>
        <?php endif; ?>
        <?php foreach ($resources as $r):
          $ic = 'bi-file-earmark'; $col = 'var(--text-600)'; $bg = '#EEF1F4';
          if ($r['resource_type'] === 'pdf') { $ic = 'bi-file-earmark-pdf'; $col = '#C0392B'; $bg = '#FCEAE8'; }
          elseif ($r['resource_type'] === 'document') { $ic = 'bi-file-earmark-word'; $col = '#2D6E9E'; $bg = '#E7F1F8'; }
          elseif ($r['resource_type'] === 'presentation') { $ic = 'bi-file-earmark-slides'; $col = '#B7791F'; $bg = '#FDF3E0'; }
          elseif ($r['resource_type'] === 'video') { $ic = 'bi-play-circle'; $col = '#1D7A5A'; $bg = '#E7F5EF'; }
          elseif ($r['resource_type'] === 'link') { $ic = 'bi-link-45deg'; $col = 'var(--text-600)'; $bg = '#EEF1F4'; }
        ?>
        <div class="uh-res-row">
          <div class="uh-res-ic" style="color:<?= $col ?>;background:<?= $bg ?>;"><i class="bi <?= $ic ?>"></i></div>
          <div class="flex-grow-1"><div class="fs-sm fw-semibold"><?= htmlspecialchars($r['title']) ?></div><div class="fs-xs text-faint"><?= htmlspecialchars($r['resource_type']) ?></div></div>
          <button class="btn btn-ghost btn-sm p-1"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-ghost btn-sm p-1 text-danger"><i class="bi bi-trash"></i></button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$modules): ?><p class="text-muted-2">No modules yet.</p><?php endif; ?>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Add module</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
          <div class="mb-2"><label class="form-label">Order</label><input type="number" class="form-control" name="order_number" value="<?= count($modules)+1 ?>"></div>
          <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
          <button class="btn btn-primary btn-sm" type="submit">Add module</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>