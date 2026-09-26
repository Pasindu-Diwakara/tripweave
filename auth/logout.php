<?php
/**
 * TripWeave — auth/logout.php
 * Destroys the current session and redirects to the home page.
 * Called via a link or form POST (POST preferred to prevent CSRF via image/link).
 */

session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session cookie on the client side
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'],   $params['domain'],
        $params['secure'],  $params['httponly']
    );
}

// Destroy the session on the server side
session_destroy();

// Redirect to home with a goodbye flash
// We use a new session briefly just to pass the flash message
session_start();
$_SESSION['flash'] = ['type' => 'success', 'message' => 'You have been logged out. See you soon!'];

header('Location: ../index.php');
exit;
