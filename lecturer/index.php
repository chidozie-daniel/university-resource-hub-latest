<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');

$lecturerId = current_lecturer_id();
$user = current_user();
$pdo = db();

$courseCount = 0;
$studentCount = 0;
$pendingSubs = 0;

if ($lecturerId) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM course_lecturers WHERE lecturer_id = ?');
    $stmt->execute([$lecturerId]);
    $courseCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT e.student_id)
         FROM enrollments e
         JOIN course_lecturers cl ON cl.course_id = e.course_id
         WHERE cl.lecturer_id = ? AND e.status = \'enrolled\''
    );
    $stmt->execute([$lecturerId]);
    $studentCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM submissions s
         JOIN assignments a ON a.assignment_id = s.assignment_id
         WHERE a.lecturer_id = ? AND s.status = \'submitted\''
    );
    $stmt->execute([$lecturerId]);
    $pendingSubs = (int) $stmt->fetchColumn();
}

$pendingList = [];
if ($lecturerId) {
    $stmt = $pdo->prepare(
        'SELECT s.submission_id, s.submitted_at, s.status, u.full_name, st.registration_number,
                a.title AS assignment_title, c.course_code
         FROM submissions s
         JOIN assignments a ON a.assignment_id = s.assignment_id
         JOIN courses c ON c.course_id = a.course_id
         JOIN students st ON st.student_id = s.student_id
         JOIN users u ON u.user_id = st.user_id
         WHERE a.lecturer_id = ? AND s.status IN (\'submitted\', \'late\')
         ORDER BY s.submitted_at DESC
         LIMIT 8'
    );
    $stmt->execute([$lecturerId]);
    $pendingList = $stmt->fetchAll();
}

$activities = [];
if ($lecturerId) {
    $acts = [];
    $stmt = $pdo->prepare(
        'SELECT r.title, r.created_at, c.course_code, \'resource\' AS type
         FROM learning_resources r
         JOIN learning_modules m ON m.module_id = r.module_id
         JOIN courses c ON c.course_id = m.course_id
         JOIN course_lecturers cl ON cl.course_id = c.course_id
         WHERE cl.lecturer_id = ?
         ORDER BY r.created_at DESC LIMIT 3'
    );
    $stmt->execute([$lecturerId]);
    foreach ($stmt->fetchAll() as $r) $acts[] = $r;

    $stmt = $pdo->prepare(
        'SELECT a.title, a.created_at, c.course_code, \'assignment\' AS type
         FROM assignments a
         JOIN courses c ON c.course_id = a.course_id
         WHERE a.lecturer_id = ?
         ORDER BY a.created_at DESC LIMIT 3'
    );
    $stmt->execute([$lecturerId]);
    foreach ($stmt->fetchAll() as $r) $acts[] = $r;

    $stmt = $pdo->prepare(
        'SELECT q.title, q.created_at, c.course_code, \'quiz\' AS type
         FROM quizzes q
         JOIN courses c ON c.course_id = q.course_id
         WHERE q.lecturer_id = ?
         ORDER BY q.created_at DESC LIMIT 3'
    );
    $stmt->execute([$lecturerId]);
    foreach ($stmt->fetchAll() as $r) $acts[] = $r;

    usort($acts, function($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });
    $activities = array_slice($acts, 0, 6);
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="uh-page-head">
  <div>
    <h1 class="uh-page-title">Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
    <p class="text-muted-2 mb-0">Lecturer portal · manage courses, resources, and grading</p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="val"><?= $courseCount ?></div>
      <div class="lbl">Courses taught</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="val"><?= $studentCount ?></div>
      <div class="lbl">Students</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="val"><?= $pendingSubs ?></div>
      <div class="lbl">Pending submissions</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="d-grid gap-2">
        <a href="/lecturer/create_assignment.php" class="btn btn-primary btn-sm">Create assignment</a>
        <a href="/lecturer/upload_resource.php" class="btn btn-light-2 btn-sm">Upload resource</a>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between">
    <span>Submissions awaiting grading</span>
    <a class="fs-sm" href="/lecturer/submissions.php">View all</a>
  </div>
  <div class="card-body p-0">
    <?php if (!$pendingList): ?>
      <p class="text-muted-2 fs-sm p-3 mb-0">No pending submissions.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Student</th>
              <th>Assignment</th>
              <th>Course</th>
              <th>Submitted</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingList as $row): ?>
              <tr>
                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['full_name']) ?>
                  <div class="fs-xs text-faint font-mono"><?= htmlspecialchars($row['registration_number']) ?></div>
                </td>
                <td><?= htmlspecialchars($row['assignment_title']) ?></td>
                <td class="font-mono fs-sm"><?= htmlspecialchars($row['course_code']) ?></td>
                <td><?= date('M j, Y H:i', strtotime($row['submitted_at'])) ?></td>
                <td>
                  <?php if ($row['status'] === 'late'): ?>
                    <span class="badge badge-soft-danger">Late</span>
                  <?php else: ?>
                    <span class="badge badge-soft-info">Submitted</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-primary" href="/lecturer/grade_submission.php?id=<?= (int) $row['submission_id'] ?>">Grade</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-clock-history me-2 text-muted-2"></i>Recent Activity</div>
  <div class="card-body pt-2">
    <?php if (!$activities): ?>
      <p class="text-muted-2 fs-sm mb-0">No activity yet.</p>
    <?php else: ?>
      <?php foreach ($activities as $act):
        $verb = 'Uploaded'; $icon = 'bi-upload'; $meta = '';
        if ($act['type'] === 'assignment') { $verb = 'Created'; $icon = 'bi-file-earmark-plus'; }
        elseif ($act['type'] === 'quiz') { $verb = 'Created'; $icon = 'bi-patch-question'; }
        $meta = htmlspecialchars($act['course_code']) . ' · ' . date('M j', strtotime($act['created_at']));
      ?>
      <div class="uh-list-row">
        <div class="uh-res-ic" style="color:var(--brand-700);background:var(--brand-100);"><i class="bi <?= $icon ?>"></i></div>
        <div class="flex-grow-1"><div class="fs-sm"><b><?= $verb ?></b> <?= htmlspecialchars($act['title']) ?></div><div class="fs-xs text-faint"><?= $meta ?></div></div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
