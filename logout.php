<?php
// Complete session destruction
session_start();

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

// Clear any auto-login cookies if they exist
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time()-3600, '/');
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login
header("Location: login.php");
exit();
?>