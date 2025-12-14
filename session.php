<?php
// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    
    // Regenerate session ID to prevent fixation
    if (!isset($_SESSION)) {
        session_start();
        
        // Regenerate session ID on first access
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

// Session timeout (30 minutes of inactivity)
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

// Check if user is logged in
function is_logged_in() {
    // Must have user_id AND it must match a valid session
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    // Verify session integrity
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    // Check if user agent matches (prevent session hijacking)
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

// Check if user is admin
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Require login (redirect if not logged in)
function require_login() {
    if (!is_logged_in()) {
        // Clear any partial session data
        session_unset();
        session_destroy();
        redirect(SITE_URL . 'login.php?timeout=1');
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            redirect(SITE_URL . 'login.php?timeout=1');
        }
    }
    
    $_SESSION['last_activity'] = time();
}

// Require admin (redirect if not admin)
function require_admin() {
    require_login();
    
    if (!is_admin()) {
        redirect(SITE_URL . 'student/dashboard.php');
    }
}

// Get current user info from session
function get_user_session() {
    if (is_logged_in()) {
        return array(
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        );
    }
    return null;
}

// Destroy session (logout)
function destroy_session() {
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    session_destroy();
}
?>