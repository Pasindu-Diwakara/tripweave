<?php
/**
 * TripWeave — includes/functions.php
 * Shared helper functions used across the application.
 */

/**
 * Sanitize a string input:
 * strips tags, trims whitespace, and converts special HTML chars.
 * Use this before inserting into DB or outputting to HTML.
 *
 * @param  string $input  Raw user input
 * @return string         Cleaned string
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to another page and exit immediately.
 *
 * @param string $url  Target URL (relative or absolute)
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Store a flash message in the session for one-time display.
 * Type: 'success' | 'error' | 'info'
 *
 * @param string $type
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the stored flash message.
 * Returns null if none is set.
 *
 * @return array|null  ['type' => ..., 'message' => ...]
 */
function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']); // Show only once
    return $flash;
}

/**
 * Render a flash message as an HTML alert div (if one exists).
 */
function renderFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $msg  = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    echo "<div class=\"alert-flash {$type}\" role=\"alert\">{$msg}</div>";
}

/**
 * Check whether a user is currently logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require the user to be logged in; otherwise redirect to login page.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('info', 'Please log in to access that page.');
        redirect('../auth/login.php');
    }
}

/**
 * Validate an email address format.
 *
 * @param  string $email
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format a MySQL DATE string (YYYY-MM-DD) to a human-readable format.
 *
 * @param  string $date   e.g. "2025-08-15"
 * @param  string $format PHP date format string, default "j M Y"
 * @return string         e.g. "15 Aug 2025"
 */
function fmtDate(string $date, string $format = 'j M Y'): string {
    if (empty($date)) return '—';
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format($format) : $date;
}

/**
 * Safely output a value to HTML (prevents XSS).
 *
 * @param  mixed  $val
 * @return string
 */
function e($val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

/**
 * Get the number of days between two date strings (inclusive).
 *
 * @param  string $start  YYYY-MM-DD
 * @param  string $end    YYYY-MM-DD
 * @return int
 */
function tripDays(string $start, string $end): int {
    $s = new DateTime($start);
    $e = new DateTime($end);
    $diff = $s->diff($e)->days;
    return max(1, $diff + 1);
}
