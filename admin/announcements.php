<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$user = current_user();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $audience = $_POST['target_audience'] ?? 'all';
    if (!in_array($audience, ['all','students','lecturers','course','department'], true)) $audience = 'all';
    if ($title && $content) {
        $priority = isset($_POST['priority']) ? 1 : 0;
        $pdo->prepare(
            'INSERT INTO announcements (created_by, title, content, target_audience, status, priority) VALUES (?, ?, ?, ?, \'published\', ?)'
        )->execute([$user['user_id'], $title, $content, $audience, $priority]);
    }
    header('Location: /admin/announcements.php');
    exit;
}
$list = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC LIMIT 40')->fetchAll();
$pageTitle = 'Announcements';
$activeNav = 'announcements';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Announcements</div>
    <h1 class="uh-page-title">University announcements</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <?php foreach ($list as $a): ?>
      <div class="card mb-2">
        <div class="card-body py-3">
          <div class="fw-semibold"><?= htmlspecialchars($a['title']) ?></div>
          <p class="fs-sm text-muted-2 mb-1"><?= htmlspecialchars(mb_substr($a['content'], 0, 140)) ?>…</p>
          <div class="fs-xs text-faint"><?= date('M j, Y', strtotime($a['created_at'])) ?> · <?= htmlspecialchars($a['target_audience']) ?> · <?= htmlspecialchars($a['status']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Post announcement</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
          <div class="mb-2">
            <label class="form-label">Audience</label>
            <select class="form-select" name="target_audience">
              <option value="all">Everyone</option>
              <option value="students">Students</option>
              <option value="lecturers">Lecturers</option>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="content" rows="4" required></textarea></div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="priority" id="annPriority" value="1">
            <label class="form-check-label" for="annPriority">Mark as important</label>
          </div>
          <button class="btn btn-primary btn-sm" type="submit">Publish</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
