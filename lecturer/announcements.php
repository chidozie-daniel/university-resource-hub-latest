<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$user = current_user();
$lecturerId = current_lecturer_id();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $courseId = (int)($_POST['course_id'] ?? 0) ?: null;
    if ($title !== '' && $content !== '') {
        $priority = isset($_POST['priority']) ? 1 : 0;
        $pdo->prepare(
            'INSERT INTO announcements (created_by, title, content, target_audience, course_id, status, priority) VALUES (?, ?, ?, ?, ?, \'published\', ?)'
        )->execute([$user['user_id'], $title, $content, $courseId ? 'course' : 'students', $courseId, $priority]);
    }
    header('Location: /lecturer/announcements.php');
    exit;
}

$courses = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.course_code FROM course_lecturers cl JOIN courses c ON c.course_id = cl.course_id WHERE cl.lecturer_id = ?'
    );
    $stmt->execute([$lecturerId]);
    $courses = $stmt->fetchAll();
}

$list = $pdo->prepare(
    'SELECT * FROM announcements WHERE created_by = ? ORDER BY created_at DESC LIMIT 30'
);
$list->execute([$user['user_id']]);
$list = $list->fetchAll();

$pageTitle = 'Announcements';
$activeNav = 'announcements';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/index.php">Dashboard</a> / Announcements</div>
    <h1 class="uh-page-title">Announcements</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <?php foreach ($list as $a): ?>
      <div class="card mb-2">
        <div class="card-body py-3">
          <div class="fw-semibold"><?= htmlspecialchars($a['title']) ?></div>
          <p class="fs-sm text-muted-2 mb-1"><?= htmlspecialchars(mb_substr($a['content'], 0, 120)) ?>…</p>
          <div class="fs-xs text-faint"><?= date('M j, Y', strtotime($a['created_at'])) ?> · <?= htmlspecialchars($a['status']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$list): ?><p class="text-muted-2">No announcements yet.</p><?php endif; ?>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Post announcement</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
          <div class="mb-2">
            <label class="form-label">Course (optional)</label>
            <select class="form-select" name="course_id">
              <option value="">All my students</option>
              <?php foreach ($courses as $c): ?>
                <option value="<?= (int)$c['course_id'] ?>"><?= htmlspecialchars($c['course_code']) ?></option>
              <?php endforeach; ?>
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
