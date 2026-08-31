<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');

$studentId = current_student_id();
$user = current_user();
$firstName = explode(' ', $user['full_name'])[0] ?? 'Student';

$pdo = db();

// Student meta
$meta = null;
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT s.registration_number, s.semester, p.program_name
         FROM students s
         JOIN programs p ON p.program_id = s.program_id
         WHERE s.student_id = ?'
    );
    $stmt->execute([$studentId]);
    $meta = $stmt->fetch();
}

// Enrolled course count
$courseCount = 0;
$pendingAssignments = 0;
$attendancePct = null;
$progressAvg = null;

if ($studentId) {
    $courseCount = (int) $pdo->prepare(
        'SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = \'enrolled\''
    )->execute([$studentId]) ? $pdo->query('SELECT FOUND_ROWS()')->fetchColumn() : 0;

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND status = \'enrolled\''
    );
    $stmt->execute([$studentId]);
    $courseCount = (int) $stmt->fetchColumn();

    // Pending = assignments for enrolled courses with no submission or not graded
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM assignments a
         JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = ? AND e.status = \'enrolled\'
         LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = ?
         WHERE s.submission_id IS NULL AND a.due_date >= NOW()'
    );
    $stmt->execute([$studentId, $studentId]);
    $pendingAssignments = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT
           SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) AS present_n,
           COUNT(*) AS total_n
         FROM attendance WHERE student_id = ?'
    );
    $stmt->execute([$studentId]);
    $att = $stmt->fetch();
    if ($att && (int) $att['total_n'] > 0) {
        $attendancePct = round(((int) $att['present_n'] / (int) $att['total_n']) * 100);
    }

    $stmt = $pdo->prepare(
        'SELECT AVG(completion_percentage) FROM learning_progress WHERE student_id = ?'
    );
    $stmt->execute([$studentId]);
    $avg = $stmt->fetchColumn();
    if ($avg !== null) {
        $progressAvg = (int) round((float) $avg);
    }
}

// Upcoming assignments
$upcoming = [];
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT a.assignment_id, a.title, a.due_date, a.total_marks, c.course_code,
                s.status AS submission_status
         FROM assignments a
         JOIN courses c ON c.course_id = a.course_id
         JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = ? AND e.status = \'enrolled\'
         LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = ?
         WHERE a.due_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY a.due_date ASC
         LIMIT 5'
    );
    $stmt->execute([$studentId, $studentId]);
    $upcoming = $stmt->fetchAll();
}

// Continue learning course
$lastCourse = null;
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.course_code, c.course_name, lp.completion_percentage,
                (SELECT r.title FROM learning_resources r
                 JOIN learning_modules m ON m.module_id = r.module_id
                 WHERE m.course_id = c.course_id AND r.resource_type = \'video\'
                 ORDER BY r.created_at DESC LIMIT 1) AS last_resource
         FROM learning_progress lp
         JOIN courses c ON c.course_id = lp.course_id
         WHERE lp.student_id = ? AND lp.completion_percentage > 0 AND lp.completion_percentage < 100
         ORDER BY lp.last_accessed DESC
         LIMIT 1'
    );
    $stmt->execute([$studentId]);
    $lastCourse = $stmt->fetch();
}

// Upcoming quizzes
$upQuizzes = [];
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT q.quiz_id, q.title, q.duration_minutes, q.total_marks, c.course_code,
                (SELECT score FROM quiz_attempts qa
                 WHERE qa.quiz_id = q.quiz_id AND qa.student_id = ? AND qa.status IN (\'submitted\',\'graded\')
                 ORDER BY attempt_id DESC LIMIT 1) AS last_score
         FROM quizzes q
         JOIN courses c ON c.course_id = q.course_id
         JOIN enrollments e ON e.course_id = q.course_id AND e.student_id = ? AND e.status = \'enrolled\'
         WHERE q.start_date IS NULL OR q.start_date <= NOW()
         ORDER BY q.created_at DESC
         LIMIT 5'
    );
    $stmt->execute([$studentId, $studentId]);
    $upQuizzes = $stmt->fetchAll();
}

// Announcements
$announcements = $pdo->query(
    'SELECT announcement_id, title, content, created_at, target_audience, priority
     FROM announcements
     WHERE status = \'published\'
       AND (target_audience IN (\'all\', \'students\') OR target_audience = \'course\')
     ORDER BY created_at DESC
     LIMIT 5'
)->fetchAll();

function assignment_status_badge(?string $subStatus, string $dueDate): string
{
    if ($subStatus === 'graded') {
        return '<span class="badge badge-soft-success">Graded</span>';
    }
    if ($subStatus === 'late') {
        return '<span class="badge badge-soft-danger">Late</span>';
    }
    if ($subStatus === 'submitted') {
        return '<span class="badge badge-soft-info">Submitted</span>';
    }
    if (strtotime($dueDate) < time()) {
        return '<span class="badge badge-soft-danger">Overdue</span>';
    }
    return '<span class="badge badge-soft-warning">Pending</span>';
}

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>

<div class="uh-page-head">
  <div>
    <h1 class="uh-page-title">Good day, <?= htmlspecialchars($firstName) ?></h1>
    <p class="text-muted-2 mb-0">
      <?php if ($meta): ?>
        <?= $meta['semester'] ? 'Semester ' . (int) $meta['semester'] . ' · ' : '' ?>
        <?= htmlspecialchars($meta['program_name']) ?>
        · Reg. <?= htmlspecialchars($meta['registration_number']) ?>
      <?php else: ?>
        Student portal
      <?php endif; ?>
    </p>
  </div>
  <a href="/student/timetable.php" class="btn btn-light-2 btn-sm">
    <i class="bi bi-calendar3 me-1"></i> Timetable
  </a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="d-flex justify-content-between">
        <div>
          <div class="val"><?= $courseCount ?></div>
          <div class="lbl">Enrolled courses</div>
        </div>
        <div class="ic" style="background:var(--brand-100);color:var(--brand-700);"><i class="bi bi-journal-bookmark"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="d-flex justify-content-between">
        <div>
          <div class="val"><?= $pendingAssignments ?></div>
          <div class="lbl">Assignments due</div>
        </div>
        <div class="ic" style="background:var(--warning-bg);color:var(--warning);"><i class="bi bi-file-earmark-text"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="d-flex justify-content-between">
        <div>
          <div class="val"><?= $attendancePct !== null ? $attendancePct . '%' : '—' ?></div>
          <div class="lbl">Attendance</div>
        </div>
        <div class="ic" style="background:var(--success-bg);color:var(--success);"><i class="bi bi-calendar-check"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card uh-stat h-100">
      <div class="d-flex justify-content-between">
        <div>
          <div class="val"><?= $progressAvg !== null ? $progressAvg . '%' : '—' ?></div>
          <div class="lbl">Learning progress</div>
        </div>
        <div class="ic" style="background:var(--info-bg);color:var(--info);"><i class="bi bi-graph-up-arrow"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <?php if ($lastCourse): ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-play-circle me-2 text-muted-2"></i>Continue Learning</span>
        <a class="fs-sm" href="/student/materials.php?course_id=<?= (int)$lastCourse['course_id'] ?>">Go to course</a>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center gap-3">
          <div class="uh-res-ic" style="width:52px;height:52px;font-size:22px;color:#fff;background:var(--brand-700);"><i class="bi bi-database"></i></div>
          <div class="flex-grow-1">
            <div class="fw-semibold"><?= htmlspecialchars($lastCourse['course_name']) ?> <span class="text-faint fs-sm font-mono"><?= htmlspecialchars($lastCourse['course_code']) ?></span></div>
            <div class="fs-sm text-muted-2 mb-2"><?= htmlspecialchars($lastCourse['last_resource'] ?? 'Continue your modules') ?></div>
            <div class="progress progress-thin"><div class="progress-bar" style="width:<?= (int)$lastCourse['completion_percentage'] ?>%"></div></div>
          </div>
          <a href="/student/materials.php?course_id=<?= (int)$lastCourse['course_id'] ?>" class="btn btn-primary btn-sm">Resume</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-file-earmark-text me-2 text-muted-2"></i>Upcoming assignments</span>
        <a class="fs-sm" href="/student/assignments.php">View all</a>
      </div>
      <div class="card-body p-0">
        <?php if (!$upcoming): ?>
          <p class="text-muted-2 fs-sm p-3 mb-0">No upcoming assignments.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead>
                <tr>
                  <th>Assignment</th>
                  <th>Course</th>
                  <th>Due</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($upcoming as $a): ?>
                  <tr>
                    <td class="fw-semibold text-dark"><?= htmlspecialchars($a['title']) ?></td>
                    <td class="font-mono fs-sm"><?= htmlspecialchars($a['course_code']) ?></td>
                    <td><?= date('M j, Y', strtotime($a['due_date'])) ?></td>
                    <td><?= assignment_status_badge($a['submission_status'], $a['due_date']) ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary" href="/student/assignments.php?id=<?= (int) $a['assignment_id'] ?>">Open</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($upQuizzes): ?>
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-patch-question me-2 text-muted-2"></i>Upcoming Quizzes</span>
        <a class="fs-sm" href="/student/quizzes.php">View all</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Quiz</th><th>Course</th><th>Time</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($upQuizzes as $q): ?>
              <tr>
                <td class="fw-semibold text-dark"><?= htmlspecialchars($q['title']) ?></td>
                <td class="font-mono fs-sm"><?= htmlspecialchars($q['course_code']) ?></td>
                <td><?= (int)$q['duration_minutes'] ?> min</td>
                <td class="text-end">
                  <?php if ($q['last_score'] !== null): ?>
                    <span class="fs-sm text-muted-2">Done</span>
                  <?php else: ?>
                    <a class="btn btn-sm btn-primary" href="/student/take_quiz.php?id=<?= (int)$q['quiz_id'] ?>">Start</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-megaphone me-2 text-muted-2"></i>Announcements</span>
        <a class="fs-sm" href="/student/announcements.php">All</a>
      </div>
      <div class="card-body pt-2">
        <?php if (!$announcements): ?>
          <p class="text-muted-2 fs-sm mb-0">No announcements yet.</p>
        <?php else: ?>
          <?php foreach ($announcements as $an): ?>
            <div class="uh-list-row">
              <div class="flex-grow-1">
                <div class="fw-semibold fs-sm"><?= htmlspecialchars($an['title']) ?></div>
                <div class="fs-xs text-faint"><?= date('M j, Y', strtotime($an['created_at'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Quick actions</div>
      <div class="card-body d-grid gap-2 pt-2">
        <a class="btn btn-light-2 btn-sm text-start" href="/student/courses.php"><i class="bi bi-journal-bookmark me-2"></i>My courses</a>
        <a class="btn btn-light-2 btn-sm text-start" href="/student/assignments.php"><i class="bi bi-upload me-2"></i>Submit assignment</a>
        <a class="btn btn-light-2 btn-sm text-start" href="/student/quizzes.php"><i class="bi bi-patch-question me-2"></i>Quizzes</a>
        <a class="btn btn-light-2 btn-sm text-start" href="/student/results.php"><i class="bi bi-award me-2"></i>Results</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
