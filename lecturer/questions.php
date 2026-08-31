<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('lecturer');
$lecturerId = current_lecturer_id();
$quizId = (int)($_GET['quiz_id'] ?? 0);
$pdo = db();

$quiz = $pdo->prepare('SELECT * FROM quizzes WHERE quiz_id = ? AND lecturer_id = ?');
$quiz->execute([$quizId, $lecturerId]);
$quiz = $quiz->fetch();
if (!$quiz) { header('Location: /lecturer/quizzes.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_text'])) {
    $text = trim($_POST['question_text']);
    $marks = (float)($_POST['marks'] ?? 1);
    $correctIdx = (int)($_POST['correct'] ?? 0);
    $opts = array_filter(array_map('trim', $_POST['options'] ?? []));
    if ($text !== '' && count($opts) >= 2) {
        $ordStmt = $pdo->prepare('SELECT COALESCE(MAX(question_order), 0) + 1 FROM quiz_questions WHERE quiz_id = ?');
        $ordStmt->execute([$quizId]);
        $order = (int) $ordStmt->fetchColumn();
        $pdo->prepare(
            'INSERT INTO quiz_questions (quiz_id, question_text, question_type, marks, question_order)
             VALUES (?, ?, \'multiple_choice\', ?, ?)'
        )->execute([$quizId, $text, $marks, $order]);
        $qid = (int) $pdo->lastInsertId();
        $i = 0;
        foreach ($opts as $opt) {
            $pdo->prepare('INSERT INTO quiz_options (question_id, option_text, is_correct) VALUES (?, ?, ?)')
                ->execute([$qid, $opt, $i === $correctIdx ? 1 : 0]);
            $i++;
        }
    }
    header('Location: /lecturer/questions.php?quiz_id=' . $quizId);
    exit;
}

$questions = $pdo->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order, question_id');
$questions->execute([$quizId]);
$questions = $questions->fetchAll();
$options = [];
if ($questions) {
    $ids = array_column($questions, 'question_id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $o = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id IN ($in)");
    $o->execute($ids);
    foreach ($o->fetchAll() as $row) $options[$row['question_id']][] = $row;
}

$pageTitle = 'Quiz Questions';
$activeNav = 'quizzes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="uh-page-head">
  <div>
    <div class="uh-breadcrumb"><a href="/lecturer/quizzes.php">Quizzes</a> / <?= htmlspecialchars($quiz['title']) ?></div>
    <h1 class="uh-page-title">Questions</h1>
  </div>
</div>
<?php foreach ($questions as $i => $q): ?>
  <div class="card mb-2">
    <div class="card-body py-3">
      <div class="fw-semibold fs-sm mb-2">Q<?= $i+1 ?>. <?= htmlspecialchars($q['question_text']) ?></div>
      <div class="row g-2">
        <?php foreach ($options[$q['question_id']] ?? [] as $oi => $opt): ?>
          <div class="col-md-6">
            <div class="px-2 py-1 rounded-2 fs-sm <?= $opt['is_correct'] ? '' : '' ?>" style="<?= $opt['is_correct'] ? 'background:var(--success-bg);' : '' ?>">
              <?= chr(65+$oi) ?>. <?= htmlspecialchars($opt['option_text']) ?>
              <?= $opt['is_correct'] ? ' ✓' : '' ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<div class="card mt-3">
  <div class="card-header">Add question</div>
  <div class="card-body">
    <form method="post">
      <div class="mb-2"><label class="form-label">Question</label><textarea class="form-control" name="question_text" rows="2" required></textarea></div>
      <div class="mb-2"><label class="form-label">Marks</label><input type="number" step="0.01" class="form-control" name="marks" value="1" style="max-width:120px;"></div>
      <label class="form-label">Options (select correct)</label>
      <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="input-group mb-2">
          <span class="input-group-text"><input type="radio" name="correct" value="<?= $i ?>" <?= $i===0?'checked':'' ?>></span>
          <input class="form-control" name="options[]" placeholder="Option <?= chr(65+$i) ?>" <?= $i < 2 ? 'required' : '' ?>>
        </div>
      <?php endfor; ?>
      <button type="submit" class="btn btn-primary btn-sm">Add question</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
