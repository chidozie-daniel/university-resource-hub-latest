<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['session_id'])) {
        $pdo->prepare('DELETE FROM class_sessions WHERE session_id = ?')->execute([(int)$_POST['session_id']]);
    } else {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $lecturerId = (int)($_POST['lecturer_id'] ?? 0);
        $day = $_POST['day_of_week'] ?? '';
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $room = trim($_POST['room'] ?? '');
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        if ($courseId && $lecturerId && in_array($day, $days, true) && $start && $end) {
            $pdo->prepare(
                'INSERT INTO class_sessions (course_id, lecturer_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$courseId, $lecturerId, $day, $start, $end, $room ?: null]);
        }
    }
    header('Location: /admin/timetable.php');
    exit;
}
$sessions = $pdo->query(
    'SELECT cs.*, c.course_code, u.full_name AS lecturer
     FROM class_sessions cs
     JOIN courses c ON c.course_id = cs.course_id
     JOIN lecturers l ON l.lecturer_id = cs.lecturer_id
     JOIN users u ON u.user_id = l.user_id
     ORDER BY FIELD(cs.day_of_week,\'Monday\',\'Tuesday\',\'Wednesday\',\'Thursday\',\'Friday\',\'Saturday\'), cs.start_time'
)->fetchAll();
$courses = $pdo->query('SELECT course_id, course_code FROM courses ORDER BY course_code')->fetchAll();
$lecturers = $pdo->query(
    'SELECT l.lecturer_id, u.full_name FROM lecturers l JOIN users u ON u.user_id = l.user_id ORDER BY u.full_name'
)->fetchAll();

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
        'session_id' => $s['session_id'],
    ];
}

$pageTitle = 'Timetable';
$activeNav = 'timetable';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/admin/index.php">Dashboard</a> / Timetable</div>
    <h1 class="uh-page-title">Master timetable</h1>
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
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
                    <form method="post" class="mt-1" onsubmit="return confirm('Delete this session?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="session_id" value="<?= (int)$entry['session_id'] ?>">
                      <button class="btn btn-ghost btn-sm p-0 text-danger" type="submit" style="font-size:11px;"><i class="bi bi-trash"></i></button>
                    </form>
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
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Add class slot</div>
      <div class="card-body">
        <form method="post">
          <div class="mb-2">
            <label class="form-label">Course</label>
            <select class="form-select" name="course_id" required>
              <?php foreach ($courses as $c): ?><option value="<?= (int)$c['course_id'] ?>"><?= htmlspecialchars($c['course_code']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Lecturer</label>
            <select class="form-select" name="lecturer_id" required>
              <?php foreach ($lecturers as $l): ?><option value="<?= (int)$l['lecturer_id'] ?>"><?= htmlspecialchars($l['full_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Day</label>
            <select class="form-select" name="day_of_week">
              <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
                <option><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-2">
            <div class="col"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" required></div>
            <div class="col"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" required></div>
          </div>
          <div class="mb-3"><label class="form-label">Room</label><input class="form-control" name="room"></div>
          <button class="btn btn-primary btn-sm" type="submit">Add slot</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>