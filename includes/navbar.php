<?php
/**
 * App shell: sidebar + topbar.
 * Expects session user. Set $activeNav to match current page key.
 */
require_once __DIR__ . '/auth.php';
$user = current_user();
$role = $user['role'] ?? 'student';
$activeNav = $activeNav ?? '';

$roleLabel = [
    'student'  => 'Student Portal',
    'lecturer' => 'Lecturer Portal',
    'admin'    => 'Admin Console',
][$role] ?? 'Portal';

$navItems = [];
if ($role === 'student') {
    $navItems = [
        ['key' => 'dashboard', 'href' => '/student/index.php', 'icon' => 'bi-grid-1x2', 'label' => 'Dashboard'],
        ['key' => 'courses', 'href' => '/student/courses.php', 'icon' => 'bi-journal-bookmark', 'label' => 'My Courses'],
        ['key' => 'assignments', 'href' => '/student/assignments.php', 'icon' => 'bi-file-earmark-text', 'label' => 'Assignments'],
        ['key' => 'quizzes', 'href' => '/student/quizzes.php', 'icon' => 'bi-patch-question', 'label' => 'Quizzes'],
        ['key' => 'progress', 'href' => '/student/progress.php', 'icon' => 'bi-graph-up-arrow', 'label' => 'Progress'],
        ['key' => 'attendance', 'href' => '/student/attendance.php', 'icon' => 'bi-calendar-check', 'label' => 'Attendance'],
        ['key' => 'results', 'href' => '/student/results.php', 'icon' => 'bi-award', 'label' => 'Results'],
        ['key' => 'timetable', 'href' => '/student/timetable.php', 'icon' => 'bi-calendar3-week', 'label' => 'Timetable'],
        ['key' => 'announcements', 'href' => '/student/announcements.php', 'icon' => 'bi-megaphone', 'label' => 'Announcements'],
        ['key' => 'profile', 'href' => '/student/profile.php', 'icon' => 'bi-person-circle', 'label' => 'Profile'],
    ];
} elseif ($role === 'lecturer') {
    $navItems = [
        ['key' => 'dashboard', 'href' => '/lecturer/index.php', 'icon' => 'bi-grid-1x2', 'label' => 'Dashboard'],
        ['key' => 'courses', 'href' => '/lecturer/courses.php', 'icon' => 'bi-journal-bookmark', 'label' => 'My Courses'],
        ['key' => 'resources', 'href' => '/lecturer/resources.php', 'icon' => 'bi-folder2-open', 'label' => 'Resources'],
        ['key' => 'assignments', 'href' => '/lecturer/assignments.php', 'icon' => 'bi-file-earmark-text', 'label' => 'Assignments'],
        ['key' => 'submissions', 'href' => '/lecturer/submissions.php', 'icon' => 'bi-inbox', 'label' => 'Submissions'],
        ['key' => 'quizzes', 'href' => '/lecturer/quizzes.php', 'icon' => 'bi-patch-question', 'label' => 'Quizzes'],
        ['key' => 'students', 'href' => '/lecturer/students.php', 'icon' => 'bi-people', 'label' => 'Students'],
        ['key' => 'announcements', 'href' => '/lecturer/announcements.php', 'icon' => 'bi-megaphone', 'label' => 'Announcements'],
        ['key' => 'profile', 'href' => '/lecturer/profile.php', 'icon' => 'bi-person-circle', 'label' => 'Profile'],
    ];
} else {
    $navItems = [
        ['key' => 'dashboard', 'href' => '/admin/index.php', 'icon' => 'bi-grid-1x2', 'label' => 'Dashboard'],
        ['key' => 'users', 'href' => '/admin/users.php', 'icon' => 'bi-people', 'label' => 'Users'],
        ['key' => 'departments', 'href' => '/admin/departments.php', 'icon' => 'bi-diagram-3', 'label' => 'Departments'],
        ['key' => 'programs', 'href' => '/admin/programs.php', 'icon' => 'bi-mortarboard', 'label' => 'Programs'],
        ['key' => 'courses', 'href' => '/admin/courses.php', 'icon' => 'bi-journal-bookmark', 'label' => 'Courses'],
        ['key' => 'enrollments', 'href' => '/admin/enrollments.php', 'icon' => 'bi-card-checklist', 'label' => 'Enrollments'],
        ['key' => 'attendance', 'href' => '/admin/attendance.php', 'icon' => 'bi-calendar-check', 'label' => 'Attendance'],
        ['key' => 'timetable', 'href' => '/admin/timetable.php', 'icon' => 'bi-calendar3-week', 'label' => 'Timetable'],
        ['key' => 'announcements', 'href' => '/admin/announcements.php', 'icon' => 'bi-megaphone', 'label' => 'Announcements'],
        ['key' => 'reports', 'href' => '/admin/reports.php', 'icon' => 'bi-bar-chart-line', 'label' => 'Reports'],
    ];
}

$initials = user_initials();
$displayName = htmlspecialchars($user['full_name'] ?? 'User');
$subLine = htmlspecialchars($user['email'] ?? '');
?>
<div class="uh-app">
  <div class="modal-backdrop fade d-none" id="sidebarBackdrop" onclick="document.querySelector('.uh-sidebar').classList.remove('show'); this.classList.add('d-none'); this.classList.remove('show');"></div>

  <aside class="uh-sidebar" id="uhSidebar">
    <div class="uh-sidebar-brand">
      <div class="mark"><i class="bi bi-buildings"></i></div>
      <div>
        <div class="name">UniHub</div>
        <div class="role-tag"><?= htmlspecialchars($roleLabel) ?></div>
      </div>
    </div>
    <nav class="uh-nav">
      <?php foreach ($navItems as $item): ?>
        <a class="uh-nav-link <?= $activeNav === $item['key'] ? 'active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>">
          <i class="bi <?= htmlspecialchars($item['icon']) ?>"></i>
          <span><?= htmlspecialchars($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="uh-sidebar-foot">
      <a class="uh-nav-link" href="/logout.php" style="color:#E8B3AC;">
        <i class="bi bi-box-arrow-left"></i><span>Log out</span>
      </a>
    </div>
  </aside>

  <div class="uh-main">
    <header class="uh-topbar">
      <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-ghost d-lg-none p-1" id="sidebarToggle" aria-label="Menu">
          <i class="bi bi-list fs-4"></i>
        </button>
        <div class="uh-search d-none d-md-flex">
          <i class="bi bi-search text-faint"></i>
          <input type="search" placeholder="Search courses, assignments..." disabled title="Search coming soon">
        </div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="dropdown">
          <div class="d-flex align-items-center gap-2" style="cursor:pointer;" data-bs-toggle="dropdown">
            <div class="uh-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="d-none d-md-block">
              <div class="fw-semibold fs-sm lh-1"><?= $displayName ?></div>
              <div class="fs-xs text-faint"><?= $subLine ?></div>
            </div>
            <i class="bi bi-chevron-down fs-xs text-faint"></i>
          </div>
          <ul class="dropdown-menu dropdown-menu-end mt-2">
            <?php
            $profileHref = $role === 'admin' ? '/admin/index.php' : ($role === 'lecturer' ? '/lecturer/profile.php' : '/student/profile.php');
            ?>
            <li><a class="dropdown-item" href="<?= $profileHref ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/logout.php"><i class="bi bi-box-arrow-left me-2"></i>Log out</a></li>
          </ul>
        </div>
      </div>
    </header>
    <main class="uh-content">
