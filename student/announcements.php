<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$pdo = db();
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare(
        'SELECT * FROM announcements WHERE announcement_id = ? AND status = \'published\''
    );
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) { header('Location: /student/announcements.php'); exit; }
    $pageTitle = $a['title'];
    $activeNav = 'announcements';
    require __DIR__ . '/../includes/header.php';
    require __DIR__ . '/../includes/navbar.php';
    ?>
    <div class="uh-page-head">
      <div>
        <div class="uh-breadcrumb"><a href="/student/announcements.php">Announcements</a> / Detail</div>
        <h1 class="uh-page-title"><?= htmlspecialchars($a['title']) ?></h1>
      </div>
    </div>
    <div class="card <?= $a['priority'] ? 'uh-priority-bar' : '' ?>">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php if ($a['priority']): ?><span class="badge badge-soft-danger">Important</span><?php endif; ?>
          <span class="badge badge-soft-gray"><?= htmlspecialchars($a['target_audience']) ?></span>
          <span class="fs-xs text-faint"><?= date('M j, Y', strtotime($a['created_at'])) ?></span>
        </div>
        <p class="text-muted-2" style="line-height:1.8;"><?= nl2br(htmlspecialchars($a['content'])) ?></p>
      </div>
    </div>
    <a href="/student/announcements.php" class="btn btn-light-2 mt-3"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$list = $pdo->query(
    'SELECT announcement_id, title, content, created_at, target_audience, priority
     FROM announcements
     WHERE status = \'published\' AND target_audience IN (\'all\',\'students\',\'course\',\'department\')
     ORDER BY created_at DESC
     LIMIT 50'
)->fetchAll();

$pageTitle = 'Announcements';
$activeNav = 'announcements';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Announcements</div>
    <h1 class="uh-page-title">Announcements</h1>
  </div>
</div>
<?php if (!$list): ?>
  <div class="card"><div class="card-body text-muted-2">No announcements.</div></div>
<?php endif; ?>
<?php foreach ($list as $a): ?>
  <a href="?id=<?= (int)$a['announcement_id'] ?>" class="card mb-2 uh-hover d-block text-decoration-none <?= $a['priority'] ? 'uh-priority-bar' : '' ?>">
    <div class="card-body py-3">
      <div class="fw-semibold text-dark mb-1"><?= $a['priority'] ? '<span class="badge badge-soft-danger me-2">Important</span>' : '' ?><?= htmlspecialchars($a['title']) ?></div>
      <p class="fs-sm text-muted-2 mb-1"><?= htmlspecialchars(mb_substr($a['content'], 0, 140)) ?>…</p>
      <div class="fs-xs text-faint"><?= date('M j, Y', strtotime($a['created_at'])) ?></div>
    </div>
  </a>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
