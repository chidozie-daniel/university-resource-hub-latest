<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$pdo = db();

$sessions = [];
if ($studentId) {
    $stmt = $pdo->prepare(
        'SELECT cs.day_of_week, cs.start_time, cs.end_time, cs.room, c.course_code, u.full_name AS lecturer
         FROM class_sessions cs
         JOIN courses c ON c.course_id = cs.course_id
         JOIN enrollments e ON e.course_id = cs.course_id AND e.student_id = ? AND e.status = \'enrolled\'
         JOIN lecturers l ON l.lecturer_id = cs.lecturer_id
         JOIN users u ON u.user_id = l.user_id
         ORDER BY FIELD(cs.day_of_week,\'Monday\',\'Tuesday\',\'Wednesday\',\'Thursday\',\'Friday\',\'Saturday\'), cs.start_time'
    );
    $stmt->execute([$studentId]);
    $sessions = $stmt->fetchAll();
}

function timeToSlot(string $time): int {
    $h = (int)substr($time, 0, 2);
    return max(0, $h - 9);
}
function durationSlots(string $start, string $end): int {
    $sh = (int)substr($start, 0, 2);
    $eh = (int)substr($end, 0, 2);
    return max(1, $eh - $sh);
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$slots = ['9:00','10:00','11:00','12:00','1:00','2:00','3:00','4:00'];
$grid = [];
foreach ($sessions as $s) {
    $dayIdx = array_search($s['day_of_week'], $days, true);
    if ($dayIdx === false) continue;
    $startSlot = timeToSlot($s['start_time']);
    $span = durationSlots($s['start_time'], $s['end_time']);
    $grid[] = [
        'day' => $dayIdx,
        'start' => $startSlot,
        'span' => $span,
        'course' => $s['course_code'],
        'room' => $s['room'] ?? '—',
        'lecturer' => $s['lecturer'],
    ];
}

$pageTitle = 'Timetable';
$activeNav = 'timetable';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/student/index.php">Dashboard</a> / Timetable</div>
    <h1 class="uh-page-title">Timetable</h1>
  </div>
</div>
<p class="text-muted-2 mb-3" style="margin-top:-0.75rem;">Semester 5 · Section A · Effective from Aug 1, 2026</p>
<div class="card">
  <div class="card-body uh-scroll-x">
    <div class="uh-tt-grid">
      <div class="uh-tt-head"></div>
      <?php foreach ($days as $d): ?>
        <div class="uh-tt-head"><?= substr($d, 0, 3) ?></div>
      <?php endforeach; ?>
      <?php foreach ($slots as $si => $slot): ?>
        <div class="uh-tt-time"><?= $slot ?></div>
        <?php foreach ($days as $di => $d): ?>
          <?php
            $entry = null;
            foreach ($grid as $g) {
              if ($g['day'] === $di && $g['start'] === $si) { $entry = $g; break; }
            }
            $occupied = false;
            foreach ($grid as $g) {
              if ($g['day'] === $di && $si > $g['start'] && $si < $g['start'] + $g['span']) { $occupied = true; break; }
            }
          ?>
          <?php if ($entry): ?>
            <div class="uh-tt-cell" style="grid-row: span <?= $entry['span'] ?>;">
              <div class="uh-tt-block">
                <b><?= htmlspecialchars($entry['course']) ?></b><?= htmlspecialchars($entry['lecturer']) ?><br><?= htmlspecialchars($entry['room']) ?>
              </div>
            </div>
          <?php elseif (!$occupied): ?>
            <div class="uh-tt-cell"></div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="d-flex gap-3 mt-3 fs-sm text-muted-2">
  <div><span class="d-inline-block me-1" style="width:12px;height:12px;background:var(--brand-50);border:1px solid var(--brand-100);border-left:3px solid var(--brand-600);"></span>Lecture</div>
  <div><span class="d-inline-block me-1" style="width:12px;height:12px;background:var(--accent-bg);border-left:3px solid var(--accent);"></span>Lab Session</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>