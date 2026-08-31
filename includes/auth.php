<?php
/**
 * Session auth helpers — aligned with users.role ENUM('student','lecturer','admin')
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * @param string|array $roles e.g. 'student' or ['lecturer','admin']
 */
function require_role($roles): void
{
    require_login();
    $roles = (array) $roles;
    $role  = $_SESSION['user']['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        header('Location: /login.php');
        exit;
    }
}

function login_user(array $userRow): void
{
    $_SESSION['user'] = [
        'user_id'   => (int) $userRow['user_id'],
        'full_name' => $userRow['full_name'],
        'email'     => $userRow['email'],
        'role'      => $userRow['role'],
        'status'    => $userRow['status'],
        'profile_image' => $userRow['profile_image'] ?? null,
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function user_initials(?string $name = null): string
{
    $name = $name ?? ($_SESSION['user']['full_name'] ?? 'U');
    $parts = preg_split('/\s+/', trim($name));
    $ini = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $ini .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $ini ?: 'U';
}

/**
 * Load student_id for current user (or null).
 */
function current_student_id(): ?int
{
    if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== 'student') {
        return null;
    }
    if (isset($_SESSION['student_id'])) {
        return (int) $_SESSION['student_id'];
    }
    $stmt = db()->prepare('SELECT student_id FROM students WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user']['user_id']]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $_SESSION['student_id'] = (int) $id;
        return (int) $id;
    }
    return null;
}

/**
 * Load lecturer_id for current user (or null).
 */
function current_lecturer_id(): ?int
{
    if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== 'lecturer') {
        return null;
    }
    if (isset($_SESSION['lecturer_id'])) {
        return (int) $_SESSION['lecturer_id'];
    }
    $stmt = db()->prepare('SELECT lecturer_id FROM lecturers WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user']['user_id']]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $_SESSION['lecturer_id'] = (int) $id;
        return (int) $id;
    }
    return null;
}

function redirect_by_role(string $role): void
{
    switch ($role) {
        case 'admin':
            header('Location: /admin/index.php');
            break;
        case 'lecturer':
            header('Location: /lecturer/index.php');
            break;
        default:
            header('Location: /student/index.php');
    }
    exit;
}
