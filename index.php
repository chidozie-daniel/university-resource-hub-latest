<?php
/**
 * Public landing page — UniHub
 */
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) {
    redirect_by_role($_SESSION['user']['role']);
}
$pageTitle = 'Your University. One Hub.';
require __DIR__ . '/includes/header.php';
?>
<nav class="lp-nav">
  <div class="container d-flex align-items-center justify-content-between" style="max-width:1120px;">
    <a href="/index.php" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
      <span class="d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;border-radius:9px;background:var(--accent);color:#fff;font-family:Lexend,sans-serif;font-weight:800;">U</span>
      <span class="font-display fw-bold" style="font-size:16.5px;">UniHub</span>
    </a>
    <div class="d-flex gap-2">
      <a href="/login.php" class="btn btn-light-2 btn-sm">Sign in</a>
      <a href="/register.php" class="btn btn-primary btn-sm">Register</a>
    </div>
  </div>
</nav>

<section class="lp-hero">
  <div class="container" style="max-width:1120px;">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <div class="uh-kicker" style="color:var(--accent);">University Resource Hub</div>
        <h1 class="font-display fw-bold text-white mb-3" style="font-size:clamp(2rem,4vw,2.75rem);">Your University.<br>One Hub.</h1>
        <p class="mb-4" style="color:rgba(255,255,255,.78);max-width:34rem;font-size:15.5px;">
          Course materials, assignments, quizzes, attendance, grades, and announcements —
          centralized for students, lecturers, and administrators.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a href="/login.php" class="btn btn-accent btn-lg">Sign in to UniHub</a>
          <a href="#features" class="btn btn-lg" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;">See what's inside</a>
        </div>
        <div class="d-flex flex-wrap gap-4 mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,.12);">
          <div><div class="fs-4 fw-bold font-display">186+</div><div class="fs-sm" style="color:rgba(255,255,255,.55);">Active courses</div></div>
          <div><div class="fs-4 fw-bold font-display">4,286</div><div class="fs-sm" style="color:rgba(255,255,255,.55);">Students</div></div>
          <div><div class="fs-4 fw-bold font-display">214</div><div class="fs-sm" style="color:rgba(255,255,255,.55);">Faculty</div></div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="p-4 rounded-3" style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);">
          <div class="text-uppercase fw-semibold mb-3" style="font-size:12px;letter-spacing:.06em;color:rgba(255,255,255,.7);">One platform · Three roles</div>
          <div class="d-flex gap-3 mb-3 pb-3 border-bottom" style="border-color:rgba(255,255,255,.1)!important;">
            <div class="uh-avatar" style="background:rgba(184,135,59,.25);color:var(--accent);"><i class="bi bi-mortarboard"></i></div>
            <div>
              <div class="fw-bold text-white">Students</div>
              <div class="fs-sm" style="color:rgba(255,255,255,.65);">Courses, modules, submit work, quizzes, progress, attendance &amp; results.</div>
            </div>
          </div>
          <div class="d-flex gap-3 mb-3 pb-3 border-bottom" style="border-color:rgba(255,255,255,.1)!important;">
            <div class="uh-avatar" style="background:rgba(184,135,59,.25);color:var(--accent);"><i class="bi bi-person-video3"></i></div>
            <div>
              <div class="fw-bold text-white">Lecturers</div>
              <div class="fs-sm" style="color:rgba(255,255,255,.65);">Modules &amp; resources, assignments, grading, quizzes, and student progress.</div>
            </div>
          </div>
          <div class="d-flex gap-3">
            <div class="uh-avatar" style="background:rgba(184,135,59,.25);color:var(--accent);"><i class="bi bi-shield-check"></i></div>
            <div>
              <div class="fw-bold text-white">Administrators</div>
              <div class="fs-sm" style="color:rgba(255,255,255,.65);">Users, departments, programs, courses, enrollments, timetable &amp; reports.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5" id="features">
  <div class="container" style="max-width:1120px;">
    <div class="uh-kicker">Platform</div>
    <h2 class="font-display fw-bold mb-2">Everything academic — in one place</h2>
    <p class="text-muted-2 mb-4" style="max-width:36rem;">A focused resource hub built around real university workflows, not a social feed or marketing site.</p>
    <div class="row g-3">
      <?php
      $features = [
          ['bi-journal-bookmark', 'brand', 'Course Learning Hub', 'Modules, PDFs, notes, slides, videos, and links under each course — a lightweight LMS students can navigate without training.'],
          ['bi-file-earmark-text', 'accent', 'Assignments &amp; grading', 'Publish briefs, collect files, track pending / submitted / graded / late, and return marks with written feedback.'],
          ['bi-patch-question', 'info', 'Quizzes', 'Timed multiple-choice quizzes with availability windows, progress, and clear results students can review afterward.'],
          ['bi-graph-up-arrow', 'success', 'Progress &amp; results', 'Module completion, resource activity, assessment marks, and semester overview without chasing spreadsheets.'],
          ['bi-calendar-check', 'brand', 'Attendance &amp; timetable', 'Course-level attendance summaries and a weekly timetable so presence and schedule live next to learning work.'],
          ['bi-megaphone', 'accent', 'Official announcements', 'Department and course notices in one channel — important items marked clearly without chat noise.'],
      ];
      foreach ($features as [$icon, $color, $title, $desc]):
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="uh-res-ic mb-2" style="background:var(--<?= $color ?>-bg,var(--brand-100));color:var(--<?= $color ?>,var(--brand-700));"><i class="bi <?= $icon ?>"></i></div>
            <h3 class="h6 fw-bold"><?= $title ?></h3>
            <p class="fs-sm text-muted-2 mb-0"><?= $desc ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="py-5 lp-problem">
  <div class="container" style="max-width:1120px;">
    <div class="uh-kicker">The problem</div>
    <h2 class="font-display fw-bold mb-2">Scattered tools break the academic day</h2>
    <p class="text-muted-2 mb-4" style="max-width:36rem;">When materials live in group chats and grades live in email, students miss deadlines and lecturers lose track of submissions.</p>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card h-100" style="background:#FBFCFD;">
          <div class="card-body">
            <h3 class="h6 fw-bold mb-3"><i class="bi bi-x-circle me-2" style="color:var(--danger);"></i>Without UniHub</h3>
            <ul class="fs-sm text-muted-2 mb-0 ps-3" style="line-height:1.8;">
              <li>Course PDFs shared in WhatsApp / Telegram</li>
              <li>Assignment briefs buried in email threads</li>
              <li>Deadlines remembered (or forgotten) ad hoc</li>
              <li>Attendance on paper or separate sheets</li>
              <li>Grades announced late or inconsistently</li>
              <li>No single place for official notices</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card h-100" style="background:var(--brand-50);border-color:var(--brand-100);">
          <div class="card-body">
            <h3 class="h6 fw-bold mb-3"><i class="bi bi-check-circle me-2" style="color:var(--success);"></i>With UniHub</h3>
            <ul class="fs-sm text-muted-2 mb-0 ps-3" style="line-height:1.8;">
              <li>One login for all enrolled courses</li>
              <li>Modules and resources in a clear hierarchy</li>
              <li>Assignments and quizzes with visible due dates</li>
              <li>Attendance and results in the same portal</li>
              <li>Lecturers grade in context; students see feedback</li>
              <li>Admins manage structure, users, and enrollments</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container" style="max-width:1120px;">
    <div class="uh-kicker">Who it's for</div>
    <h2 class="font-display fw-bold mb-2">Role-based experiences, shared design</h2>
    <p class="text-muted-2 mb-4" style="max-width:36rem;">Same visual language and navigation patterns for everyone — tools matched to responsibility.</p>
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="fs-xs fw-bold mb-2" style="color:var(--accent-dark);text-transform:uppercase;letter-spacing:.06em;">Student</div>
            <h3 class="h6 fw-bold mb-2">Learn and stay on track</h3>
            <p class="fs-sm text-muted-2 mb-3">Access materials, submit work, take quizzes, and see progress, attendance, and grades without switching apps.</p>
            <ul class="fs-sm text-muted-2 mb-3 ps-3" style="line-height:1.8;">
              <li>Dashboard &amp; continue learning</li>
              <li>Course hub &amp; modules</li>
              <li>Assignments, quizzes, results</li>
              <li>Timetable &amp; announcements</li>
            </ul>
            <a href="/login.php" class="btn btn-primary btn-sm">Open as student</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="fs-xs fw-bold mb-2" style="color:var(--accent-dark);text-transform:uppercase;letter-spacing:.06em;">Lecturer</div>
            <h3 class="h6 fw-bold mb-2">Teach and assess clearly</h3>
            <p class="fs-sm text-muted-2 mb-3">Publish modules and resources, set assignments and quizzes, review submissions, and monitor student progress.</p>
            <ul class="fs-sm text-muted-2 mb-3 ps-3" style="line-height:1.8;">
              <li>Course &amp; module management</li>
              <li>Upload resources</li>
              <li>Grade submissions</li>
              <li>Create quizzes &amp; announce</li>
            </ul>
            <a href="/login.php" class="btn btn-primary btn-sm">Open as lecturer</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="fs-xs fw-bold mb-2" style="color:var(--accent-dark);text-transform:uppercase;letter-spacing:.06em;">Administrator</div>
            <h3 class="h6 fw-bold mb-2">Run the academic structure</h3>
            <p class="fs-sm text-muted-2 mb-3">Manage users, departments, programs, courses, enrollments, attendance overview, timetable, and reports.</p>
            <ul class="fs-sm text-muted-2 mb-3 ps-3" style="line-height:1.8;">
              <li>User management</li>
              <li>Departments &amp; programs</li>
              <li>Courses &amp; enrollments</li>
              <li>Timetable &amp; reports</li>
            </ul>
            <a href="/login.php" class="btn btn-primary btn-sm">Open as admin</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5" style="background:var(--brand-900);color:#fff;">
  <div class="container text-center" style="max-width:640px;">
    <h2 class="font-display fw-bold mb-2" style="color:#fff;">Ready to explore UniHub?</h2>
    <p class="mb-4" style="color:rgba(255,255,255,.7);">Sign in to access the full platform with courses, assignments, quizzes, and announcements.</p>
    <a href="/login.php" class="btn btn-accent btn-lg">Go to sign in</a>
  </div>
</section>

<footer class="border-top bg-white py-3">
  <div class="container d-flex justify-content-between flex-wrap gap-2" style="max-width:1120px;">
    <span class="fs-sm text-faint"><strong class="text-dark font-display">UniHub</strong> · Your University. One Hub.</span>
    <a class="fs-sm" href="/login.php">Sign in</a>
  </div>
</footer>
</body>
</html>