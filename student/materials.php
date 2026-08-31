<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$courseId = (int)($_GET['course_id'] ?? 0);
$pdo = db();

if (!$courseId || !$studentId) {
    header('Location: /student/courses.php');
    exit;
}

// Enrollment check
$chk = $pdo->prepare('SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ? AND status = \'enrolled\'');
$chk->execute([$studentId, $courseId]);
if (!$chk->fetch()) {
    header('Location: /student/courses.php');
    exit;
}

$stmt = $pdo->prepare('SELECT course_id, course_code, course_name, description, credit_hours FROM courses WHERE course_id = ?');
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) {
    header('Location: /student/courses.php');
    exit;
}

$lecturer = $pdo->prepare(
    'SELECT u.full_name FROM course_lecturers cl
     JOIN lecturers l ON l.lecturer_id = cl.lecturer_id
     JOIN users u ON u.user_id = l.user_id WHERE cl.course_id = ? LIMIT 1'
);
$lecturer->execute([$courseId]);
$lecturerName = $lecturer->fetchColumn() ?: 'TBA';

$progress = $pdo->prepare('SELECT completion_percentage FROM learning_progress WHERE student_id = ? AND course_id = ?');
$progress->execute([$studentId, $courseId]);
$pct = (int) round((float)($progress->fetchColumn() ?: 0));

$modules = $pdo->prepare(
    'SELECT module_id, title, description, order_number FROM learning_modules WHERE course_id = ? ORDER BY order_number, module_id'
);
$modules->execute([$courseId]);
$modules = $modules->fetchAll();

$resourcesByModule = [];
if ($modules) {
    $ids = array_column($modules, 'module_id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $rstmt = $pdo->prepare("SELECT * FROM learning_resources WHERE module_id IN ($in) ORDER BY created_at");
    $rstmt->execute($ids);
    foreach ($rstmt->fetchAll() as $r) {
        $resourcesByModule[$r['module_id']][] = $r;
    }
}

$assignments = $pdo->prepare(
    'SELECT a.*, s.status AS sub_status, s.marks AS sub_marks
     FROM assignments a
     LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.student_id = ?
     WHERE a.course_id = ? ORDER BY a.due_date'
);
$assignments->execute([$studentId, $courseId]);
$assignments = $assignments->fetchAll();

$quizzes = $pdo->prepare(
    'SELECT q.*,
       (SELECT score FROM quiz_attempts qa WHERE qa.quiz_id = q.quiz_id AND qa.student_id = ? AND qa.status IN (\'submitted\',\'graded\') ORDER BY attempt_id DESC LIMIT 1) AS last_score
     FROM quizzes q WHERE q.course_id = ? ORDER BY q.created_at DESC'
);
$quizzes->execute([$studentId, $courseId]);
$quizzes = $quizzes->fetchAll();

function res_icon(string $type): array {
    $map = [
        'pdf' => ['bi-file-earmark-pdf', '#C0392B', '#FCEAE8'],
        'document' => ['bi-file-earmark-word', '#2D6E9E', '#E7F1F8'],
        'presentation' => ['bi-file-earmark-slides', '#B7791F', '#FDF3E0'],
        'video' => ['bi-play-circle', '#1D7A5A', '#E7F5EF'],
        'link' => ['bi-link-45deg', '#55606C', '#EEF1F4'],
        'image' => ['bi-image', '#2D6E9E', '#E7F1F8'],
    ];
    return $map[$type] ?? ['bi-file-earmark', '#55606C', '#EEF1F4'];
}

$pageTitle = $course['course_code'];
$activeNav = 'courses';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb">
      <a href="/student/index.php">Dashboard</a> /
      <a href="/student/courses.php">My Courses</a> /
      <?= htmlspecialchars($course['course_code']) ?>
    </div>
    <h1 class="uh-page-title"><?= htmlspecialchars($course['course_name']) ?></h1>
  </div>
</div>

<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-3">
      <div style="max-width:640px;">
        <span class="badge badge-soft-info font-mono mb-2"><?= htmlspecialchars($course['course_code']) ?></span>
        <p class="text-muted-2 mb-2"><?= htmlspecialchars($course['description'] ?? '') ?></p>
        <div class="d-flex gap-4 fs-sm text-muted-2">
          <div><i class="bi bi-person-video3 me-1"></i><?= htmlspecialchars($lecturerName) ?></div>
          <div><i class="bi bi-award me-1"></i><?= (int)$course['credit_hours'] ?> Credits</div>
        </div>
      </div>
      <div style="min-width:180px;">
        <div class="fs-sm text-muted-2 mb-1">Course progress</div>
        <div class="d-flex align-items-center gap-2">
          <div class="progress flex-grow-1"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
          <span class="fw-bold fs-sm"><?= $pct ?>%</span>
        </div>
      </div>
    </div>
  </div>
</div>

<ul class="nav uh-tab-pills gap-2 mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-modules" type="button">Modules</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-assignments" type="button">Assignments <span class="badge badge-soft-gray ms-1"><?= count($assignments) ?></span></button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-quizzes" type="button">Quizzes <span class="badge badge-soft-gray ms-1"><?= count($quizzes) ?></span></button></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tab-modules">
    <?php if (!$modules): ?>
      <div class="card"><div class="card-body text-muted-2">No modules published yet.</div></div>
    <?php endif; ?>
    <?php foreach ($modules as $i => $m):
      $ress = $resourcesByModule[$m['module_id']] ?? [];
    ?>
    <div class="uh-mod">
      <div class="uh-mod-head" data-bs-toggle="collapse" data-bs-target="#mod<?= (int)$m['module_id'] ?>">
        <div class="d-flex justify-content-between w-100 align-items-center">
          <span class="fw-semibold fs-sm">Module <?= $i + 1 ?>: <?= htmlspecialchars($m['title']) ?></span>
          <i class="bi bi-chevron-down text-faint"></i>
        </div>
      </div>
      <div class="collapse <?= $i < 2 ? 'show' : '' ?>" id="mod<?= (int)$m['module_id'] ?>">
        <div class="uh-mod-body">
          <?php if (!$ress): ?>
            <p class="fs-sm text-muted-2 mb-0">No resources in this module.</p>
          <?php endif; ?>
          <?php foreach ($ress as $r):
            [$ic, $col, $bg] = res_icon($r['resource_type']);
            $href = $r['external_url'] ?: ($r['file_path'] ? '/uploads/resources/' . basename($r['file_path']) : '#');
          ?>
          <div class="uh-res-row">
            <div class="uh-res-ic" style="color:<?= $col ?>;background:<?= $bg ?>"><i class="bi <?= $ic ?>"></i></div>
            <div class="flex-grow-1">
              <div class="fs-sm fw-semibold"><?= htmlspecialchars($r['title']) ?></div>
              <div class="fs-xs text-faint"><?= htmlspecialchars($r['resource_type']) ?></div>
            </div>
            <a class="btn btn-ghost btn-sm" href="<?= htmlspecialchars($href) ?>" target="_blank" rel="noopener"><i class="bi bi-download"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="tab-pane fade" id="tab-assignments">
    <?php if (!$assignments): ?>
      <div class="card"><div class="card-body text-muted-2">No assignments.</div></div>
    <?php else: ?>
    <div class="table-responsive-card">
      <table class="table table-hover mb-0">
        <thead><tr><th>Title</th><th>Due</th><th>Marks</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($assignments as $a): ?>
          <tr>
            <td class="fw-semibold text-dark"><?= htmlspecialchars($a['title']) ?></td>
            <td><?= date('M j, Y', strtotime($a['due_date'])) ?></td>
            <td><?= $a['sub_marks'] !== null ? htmlspecialchars($a['sub_marks']) . '/' . $a['total_marks'] : $a['total_marks'] ?></td>
            <td>
              <?php
              $st = $a['sub_status'];
              if ($st === 'graded') echo '<span class="badge badge-soft-success">Graded</span>';
              elseif ($st === 'late') echo '<span class="badge badge-soft-danger">Late</span>';
              elseif ($st === 'submitted') echo '<span class="badge badge-soft-info">Submitted</span>';
              else echo '<span class="badge badge-soft-warning">Pending</span>';
              ?>
            </td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/student/assignments.php?id=<?= (int)$a['assignment_id'] ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tab-quizzes">
    <?php if (!$quizzes): ?>
      <div class="card"><div class="card-body text-muted-2">No quizzes.</div></div>
    <?php else: ?>
    <div class="table-responsive-card">
      <table class="table table-hover mb-0">
        <thead><tr><th>Quiz</th><th>Duration</th><th>Marks</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($quizzes as $q): ?>
          <tr>
            <td class="fw-semibold text-dark"><?= htmlspecialchars($q['title']) ?></td>
            <td><?= $q['duration_minutes'] ? (int)$q['duration_minutes'] . ' min' : '—' ?></td>
            <td><?= $q['last_score'] !== null ? htmlspecialchars($q['last_score']) . '/' . $q['total_marks'] : $q['total_marks'] ?></td>
            <td>
              <?php if ($q['last_score'] !== null): ?>
                <span class="badge badge-soft-success">Completed</span>
              <?php else: ?>
                <span class="badge badge-soft-info">Available</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($q['last_score'] !== null): ?>
                <a class="btn btn-sm btn-light-2" href="/student/quizzes.php">Results</a>
              <?php else: ?>
                <a class="btn btn-sm btn-primary" href="/student/take_quiz.php?id=<?= (int)$q['quiz_id'] ?>">Start</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
