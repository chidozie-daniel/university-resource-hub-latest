<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$pdo = db();
$error = '';

$modules = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT m.module_id, m.title, c.course_code
         FROM learning_modules m
         JOIN courses c ON c.course_id = m.course_id
         JOIN course_lecturers cl ON cl.course_id = c.course_id AND cl.lecturer_id = ?
         ORDER BY c.course_code, m.order_number'
    );
    $stmt->execute([$lecturerId]);
    $modules = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = (int)($_POST['module_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['resource_type'] ?? 'other';
    $url = trim($_POST['external_url'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $filePath = null;
    $fileName = null;

    $allowedTypes = ['pdf','document','presentation','video','image','link','other'];
    if (!in_array($type, $allowedTypes, true)) $type = 'other';

    if ($title === '' || !$moduleId) {
        $error = 'Title and module are required.';
    } else {
        if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../uploads/resources/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $safe = 'res_' . $lecturerId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . $safe)) {
                $filePath = 'uploads/resources/' . $safe;
                $fileName = $_FILES['file']['name'];
            }
        }
        $pdo->prepare(
            'INSERT INTO learning_resources (module_id, uploaded_by, title, description, resource_type, file_name, file_path, external_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$moduleId, $lecturerId, $title, $desc ?: null, $type, $fileName, $filePath, $url ?: null]);
        header('Location: /lecturer/resources.php');
        exit;
    }
}

$pageTitle = 'Upload Resource';
$activeNav = 'resources';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/resources.php">Resources</a> / Upload</div>
    <h1 class="uh-page-title">Upload learning resource</h1>
  </div>
</div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width:640px;">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Module</label>
        <select class="form-select" name="module_id" required>
          <option value="">Select module</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?= (int)$m['module_id'] ?>"><?= htmlspecialchars($m['course_code'] . ' — ' . $m['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
      <div class="mb-3">
        <label class="form-label">Type</label>
        <select class="form-select" name="resource_type">
          <option value="pdf">PDF</option>
          <option value="document">Document</option>
          <option value="presentation">Presentation</option>
          <option value="video">Video</option>
          <option value="image">Image</option>
          <option value="link">Link</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">File</label><input type="file" class="form-control" name="file"></div>
      <div class="mb-3"><label class="form-label">External URL (optional)</label><input class="form-control" name="external_url" placeholder="https://"></div>
      <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
      <button type="submit" class="btn btn-primary">Upload</button>
      <a href="/lecturer/resources.php" class="btn btn-light-2">Cancel</a>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
