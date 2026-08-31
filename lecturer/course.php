<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$courseId = (int)($_GET['id'] ?? 0);
$pdo = db();

$chk = $pdo->prepare('SELECT 1 FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?');
$chk->execute([$courseId, $lecturerId]);
if (!$chk->fetch()) { header('Location: /lecturer/courses.php'); exit; }

$course = $pdo->prepare('SELECT * FROM courses WHERE course_id = ?');
$course->execute([$courseId]);
$course = $course->fetch();

$modules = $pdo->prepare('SELECT * FROM learning_modules WHERE course_id = ? ORDER BY order_number');
$modules->execute([$courseId]);
$modules = $modules->fetchAll();

$assignments = $pdo->prepare(
    'SELECT a.*, (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.assignment_id) AS sub_count
     FROM assignments a WHERE a.course_id = ? ORDER BY a.due_date DESC'
);
$assignments->execute([$courseId]);
$assignments = $assignments->fetchAll();

$quizzes = $pdo->prepare(
    'SELECT q.*, (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.quiz_id) AS qcount
     FROM quizzes q WHERE q.course_id = ? ORDER BY q.created_at DESC'
);
$quizzes->execute([$courseId]);
$quizzes = $quizzes->fetchAll();

$students = $pdo->prepare(
    'SELECT DISTINCT u.full_name, st.registration_number, st.student_id, c.course_code,
            lp.completion_percentage
     FROM enrollments e
     JOIN students st ON st.student_id = e.student_id
     JOIN users u ON u.user_id = st.user_id
     JOIN courses c ON c.course_id = e.course_id
     LEFT JOIN learning_progress lp ON lp.student_id = st.student_id AND lp.course_id = e.course_id
     WHERE e.course_id = ? AND e.status = \'enrolled\'
     ORDER BY u.full_name LIMIT 100'
);
$students->execute([$courseId]);
$students = $students->fetchAll();

$studentCount = count($students);

$pageTitle = $course['course_code'] ?? 'Course';
$activeNav = 'courses';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/courses.php">My Courses</a> / <?= htmlspecialchars($course['course_code']) ?></div>
    <h1 class="uh-page-title"><?= htmlspecialchars($course['course_name']) ?></h1>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-light-2 btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="bi bi-upload me-1"></i>Upload Resource</button>
    <a href="/lecturer/modules.php?course_id=<?= $courseId ?>" class="btn btn-light-2 btn-sm"><i class="bi bi-folder-plus me-1"></i>New Module</button>
  </div>
</div>
<div class="card mb-4">
  <div class="card-body">
    <p class="text-muted-2 mb-2"><?= htmlspecialchars($course['description'] ?? '') ?></p>
    <div class="fs-sm text-muted-2"><?= $studentCount ?> students · <?= (int)$course['credit_hours'] ?> credits · <?= count($modules) ?> modules</div>
  </div>
</div>

<ul class="nav uh-tab-pills gap-2 mb-3" id="lecCourseTabs">
  <li class="nav-item"><a class="nav-link active" data-tab="modules" onclick="tabSwitch(this,'lecCourseTabs')">Modules &amp; Resources</a></li>
  <li class="nav-item"><a class="nav-link" data-tab="assignments" onclick="tabSwitch(this,'lecCourseTabs')">Assignments <span class="badge badge-soft-gray ms-1"><?= count($assignments) ?></span></a></li>
  <li class="nav-item"><a class="nav-link" data-tab="quizzes" onclick="tabSwitch(this,'lecCourseTabs')">Quizzes <span class="badge badge-soft-gray ms-1"><?= count($quizzes) ?></span></a></li>
  <li class="nav-item"><a class="nav-link" data-tab="students" onclick="tabSwitch(this,'lecCourseTabs')">Students</a></li>
</ul>

<div class="tab-pane-modules">
  <?php if (!$modules): ?>
    <div class="card"><div class="card-body text-muted-2">No modules yet. Create one to get started.</div></div>
  <?php endif; ?>
  <?php foreach ($modules as $i => $m):
    $resources = $pdo->prepare('SELECT * FROM learning_resources WHERE module_id = ? ORDER BY created_at');
    $resources->execute([$m['module_id']]);
    $resources = $resources->fetchAll();
  ?>
  <div class="uh-mod mb-2">
    <div class="uh-mod-head">
      <div class="d-flex justify-content-between w-100 align-items-center">
        <span class="fw-semibold fs-sm">Module <?= $i + 1 ?>: <?= htmlspecialchars($m['title']) ?></span>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-ghost btn-sm p-1"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-ghost btn-sm p-1 text-danger"><i class="bi bi-trash"></i></button>
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
</div>

<div class="tab-pane-assignments" style="display:none;">
  <div class="d-flex justify-content-end mb-2"><a href="/lecturer/create_assignment.php?course_id=<?= $courseId ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create Assignment</a></div>
  <div class="table-responsive-card">
    <table class="table table-hover mb-0">
      <thead><tr><th>Title</th><th>Due</th><th>Marks</th><th>Submissions</th><th></th></tr></thead>
      <tbody>
      <?php if (!$assignments): ?><tr><td colspan="5" class="text-center text-muted-2 py-4">No assignments.</td></tr><?php endif; ?>
      <?php foreach ($assignments as $a): ?>
        <tr>
          <td class="fw-semibold text-dark"><?= htmlspecialchars($a['title']) ?></td>
          <td><?= date('M j, Y', strtotime($a['due_date'])) ?></td>
          <td><?= htmlspecialchars($a['total_marks']) ?></td>
          <td><?= (int)$a['sub_count'] ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light-2" href="/lecturer/submissions.php?assignment_id=<?= (int)$a['assignment_id'] ?>">Submissions</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="tab-pane-quizzes" style="display:none;">
  <div class="d-flex justify-content-end mb-2"><a href="/lecturer/create_quiz.php?course_id=<?= $courseId ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create Quiz</a></div>
  <div class="table-responsive-card">
    <table class="table table-hover mb-0">
      <thead><tr><th>Title</th><th>Questions</th><th>Duration</th><th></th></tr></thead>
      <tbody>
      <?php if (!$quizzes): ?><tr><td colspan="4" class="text-center text-muted-2 py-4">No quizzes.</td></tr><?php endif; ?>
      <?php foreach ($quizzes as $q): ?>
        <tr>
          <td class="fw-semibold text-dark"><?= htmlspecialchars($q['title']) ?></td>
          <td><?= (int)$q['qcount'] ?></td>
          <td><?= $q['duration_minutes'] ? (int)$q['duration_minutes'] . ' min' : '—' ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light-2" href="/lecturer/questions.php?quiz_id=<?= (int)$q['quiz_id'] ?>">Questions</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="tab-pane-students" style="display:none;">
  <div class="table-responsive-card">
    <table class="table table-hover mb-0">
      <thead><tr><th>Name</th><th>Reg No.</th><th>Progress</th><th></th></tr></thead>
      <tbody>
      <?php if (!$students): ?><tr><td colspan="4" class="text-center text-muted-2 py-4">No students found.</td></tr><?php endif; ?>
      <?php foreach ($students as $s): ?>
        <tr>
          <td class="fw-semibold text-dark"><?= htmlspecialchars($s['full_name']) ?></td>
          <td class="font-mono fs-sm"><?= htmlspecialchars($s['registration_number']) ?></td>
          <td><?= $s['completion_percentage'] !== null ? (int)round((float)$s['completion_percentage']) . '%' : '—' ?></td>
          <td class="text-end"><a class="btn btn-sm btn-light-2" href="/lecturer/progress.php?student_id=<?= (int)$s['student_id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$uploadModules = $modules;
include __DIR__ . '/../includes/upload_resource_modal.php';
?>

<script>
function tabSwitch(el, groupId) {
  var group = el.closest('.nav');
  group.querySelectorAll('.nav-link').forEach(function(l) { l.classList.remove('active'); });
  el.classList.add('active');
  var tab = el.dataset.tab;
  var container = group.parentElement;
  container.querySelectorAll('[class*="tab-pane-"]').forEach(function(p) { p.style.display = 'none'; });
  var pane = container.querySelector('.tab-pane-' + tab);
  if (pane) pane.style.display = 'block';
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>