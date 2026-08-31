<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$studentId = current_student_id();
$quizId = (int)($_GET['id'] ?? 0);
$pdo = db();

if (session_status() === PHP_SESSION_NONE) session_start();

$stmt = $pdo->prepare(
    'SELECT q.*, c.course_code FROM quizzes q
     JOIN courses c ON c.course_id = q.course_id
     JOIN enrollments e ON e.course_id = q.course_id AND e.student_id = ? AND e.status = \'enrolled\'
     WHERE q.quiz_id = ?'
);
$stmt->execute([$studentId, $quizId]);
$quiz = $stmt->fetch();
if (!$quiz) {
    header('Location: /student/quizzes.php');
    exit;
}

$qstmt = $pdo->prepare(
    'SELECT question_id, question_text, marks, question_order FROM quiz_questions
     WHERE quiz_id = ? ORDER BY question_order, question_id'
);
$qstmt->execute([$quizId]);
$questions = $qstmt->fetchAll();

$options = [];
if ($questions) {
    $ids = array_column($questions, 'question_id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $ostmt = $pdo->prepare("SELECT option_id, question_id, option_text, is_correct FROM quiz_options WHERE question_id IN ($in)");
    $ostmt->execute($ids);
    foreach ($ostmt->fetchAll() as $o) {
        $options[$o['question_id']][] = $o;
    }
}

$sessionKey = 'quiz_' . $quizId;
if (!isset($_SESSION[$sessionKey])) {
    $ins = $pdo->prepare(
        'INSERT INTO quiz_attempts (quiz_id, student_id, start_time, status) VALUES (?, ?, NOW(), \'in_progress\')'
    );
    $ins->execute([$quizId, $studentId]);
    $_SESSION[$sessionKey] = [
        'attempt_id' => (int)$pdo->lastInsertId(),
        'qi' => 0,
        'answers' => array_fill(0, count($questions), null),
    ];
}

$state = $_SESSION[$sessionKey];
$qi = $state['qi'];
$answers = $state['answers'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ans'])) {
        $answers[$qi] = (int)$_POST['ans'];
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'next' && $qi < count($questions) - 1) {
        $qi++;
    } elseif ($action === 'prev' && $qi > 0) {
        $qi--;
    } elseif ($action === 'submit') {
        $pdo->beginTransaction();
        try {
            $score = 0;
            $total = 0;
            $ansIns = $pdo->prepare(
                'INSERT INTO quiz_answers (attempt_id, question_id, selected_option_id, is_correct) VALUES (?, ?, ?, ?)'
            );
            foreach ($questions as $i => $q) {
                $total += (float)$q['marks'];
                $sel = $answers[$i] ?? null;
                $correct = false;
                if ($sel !== null) {
                    foreach ($options[$q['question_id']] ?? [] as $o) {
                        if ((int)$o['option_id'] === (int)$sel && $o['is_correct']) {
                            $correct = true;
                            break;
                        }
                    }
                }
                if ($correct) $score += (float)$q['marks'];
                $ansIns->execute([$state['attempt_id'], $q['question_id'], $sel, $correct ? 1 : 0]);
            }
            $pdo->prepare('UPDATE quiz_attempts SET score = ?, status = \'graded\' WHERE attempt_id = ?')
                ->execute([$score, $state['attempt_id']]);
            $pdo->commit();
            unset($_SESSION[$sessionKey]);
            header('Location: /student/quiz_result.php?id=' . $state['attempt_id']);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Could not submit quiz.';
        }
    } elseif ($action === 'goto' && isset($_POST['qi_override'])) {
        $qi = max(0, min(count($questions) - 1, (int)$_POST['qi_override']));
    }
    $state['qi'] = $qi;
    $state['answers'] = $answers;
    $_SESSION[$sessionKey] = $state;
    header('Location: /student/take_quiz.php?id=' . $quizId . '&qi=' . $qi);
    exit;
}

$question = $questions[$qi] ?? $questions[0];
$answered = count(array_filter($answers));

$pageTitle = $quiz['title'];
$activeNav = 'quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-quiz-shell">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="fs-xs text-faint"><?= htmlspecialchars($quiz['course_code']) ?></div>
      <h1 class="uh-page-title mb-0"><?= htmlspecialchars($quiz['title']) ?></h1>
    </div>
    <?php if ($quiz['duration_minutes']): ?>
      <div class="uh-timer" id="quizTimer"><i class="bi bi-stopwatch me-1"></i><span id="timerDisplay"><?= (int)$quiz['duration_minutes'] ?>:00</span></div>
    <?php endif; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <span class="fs-sm text-muted-2">Question <?= $qi + 1 ?> of <?= count($questions) ?></span>
    <span class="fs-sm text-muted-2"><?= $answered ?>/<?= count($questions) ?> answered</span>
  </div>
  <div class="progress mb-4"><div class="progress-bar pb-accent" style="width:<?= ((($qi + 1) / count($questions)) * 100) ?>%"></div></div>

  <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (!$questions): ?>
    <div class="card"><div class="card-body text-muted-2">This quiz has no questions yet.</div></div>
  <?php else: ?>
  <form method="post" id="quizForm">
    <input type="hidden" name="action" value="">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="fw-semibold mb-4"><?= $qi + 1 ?>. <?= htmlspecialchars($question['question_text']) ?>
          <span class="fs-sm text-muted-2 fw-normal">(<?= htmlspecialchars($question['marks']) ?> marks)</span>
        </h5>
        <?php foreach (($options[$question['question_id']] ?? []) as $oi => $o): ?>
        <label class="uh-quiz-opt <?= ($answers[$qi] ?? null) === (int)$o['option_id'] ? 'selected' : '' ?>" onclick="selectOption(this, <?= (int)$o['option_id'] ?>)">
          <div class="uh-avatar-sm" style="background:<?= ($answers[$qi] ?? null) === (int)$o['option_id'] ? 'var(--brand-700)' : '#EEF1F4' ?>;color:<?= ($answers[$qi] ?? null) === (int)$o['option_id'] ? '#fff' : 'var(--text-600)' ?>;"><?= chr(65 + $oi) ?></div>
          <div class="pt-1"><?= htmlspecialchars($o['option_text']) ?></div>
        </label>
        <?php endforeach; ?>
        <input type="hidden" name="ans" id="selectedAns" value="<?= $answers[$qi] ?? '' ?>">
        <div class="d-flex justify-content-between align-items-center mt-4">
          <button type="submit" name="action" value="prev" class="btn btn-light-2" <?= $qi === 0 ? 'disabled' : '' ?>><i class="bi bi-arrow-left me-1"></i>Previous</button>
          <div class="d-flex gap-2 flex-wrap justify-content-center" style="max-width:340px;">
            <?php foreach ($questions as $i => $_q): ?>
            <button type="submit" name="action" value="goto" class="qi-btn btn btn-sm <?= $i === $qi ? 'btn-primary' : (($answers[$i] ?? null) !== null ? 'btn-light-2' : 'btn-outline-secondary') ?>" style="width:34px;" data-qi="<?= $i ?>"><?= $i + 1 ?></button>
            <?php endforeach; ?>
          </div>
          <?php if ($qi < count($questions) - 1): ?>
            <button type="submit" name="action" value="next" class="btn btn-primary">Next<i class="bi bi-arrow-right ms-1"></i></button>
          <?php else: ?>
            <button type="submit" name="action" value="submit" class="btn btn-accent" onclick="return confirm('Submit quiz? This cannot be undone.')"><i class="bi bi-check2 me-1"></i>Submit Quiz</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </form>
  <?php endif; ?>
</div>

<script>
function selectOption(el, val) {
  document.querySelectorAll('.uh-quiz-opt').forEach(function(e) {
    e.classList.remove('selected');
    var sm = e.querySelector('.uh-avatar-sm');
    if (sm) { sm.style.background = '#EEF1F4'; sm.style.color = 'var(--text-600)'; }
  });
  el.classList.add('selected');
  var sm = el.querySelector('.uh-avatar-sm');
  if (sm) { sm.style.background = 'var(--brand-700)'; sm.style.color = '#fff'; }
  document.getElementById('selectedAns').value = val;
}

(function() {
  var duration = <?= (int)($quiz['duration_minutes'] ?? 0) ?>;
  if (!duration) return;
  var endTime = Date.now() + duration * 60 * 1000;
  var timerDisplay = document.getElementById('timerDisplay');
  function update() {
    var remaining = Math.max(0, Math.ceil((endTime - Date.now()) / 1000));
    var m = Math.floor(remaining / 60);
    var s = remaining % 60;
    if (timerDisplay) timerDisplay.textContent = m + ':' + (s < 10 ? '0' : '') + s;
    if (remaining <= 0 && document.getElementById('quizForm')) {
      document.getElementById('quizForm').submit();
    }
  }
  update();
  setInterval(update, 1000);
})();

document.querySelectorAll('.qi-btn').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    var qi = parseInt(this.dataset.qi, 10);
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'qi_override';
    input.value = qi;
    var form = document.querySelector('#quizForm');
    var existing = form.querySelector('input[name=qi_override]');
    if (existing) existing.remove();
    form.appendChild(input);
    var actionInput = form.querySelector('input[name=action]');
    if (actionInput) actionInput.value = 'goto';
    form.submit();
  });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>