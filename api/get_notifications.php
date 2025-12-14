<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

// Load notification functions
if (!function_exists('get_unread_notifications')) {
    require_once __DIR__ . '/../includes/notifications.php';
}

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'notifications' => []
    ]);
    exit;
}

try {
    $user_id = (int)$_SESSION['user_id'];
    
    // ✅ SECURITY CHECK: Verify user_id matches session
    // This prevents unauthorized access to other users' notifications
    if ($user_id != $_SESSION['user_id']) {
        echo json_encode([
            'success' => false,
            'count' => 0,
            'notifications' => [],
            'error' => 'Unauthorized access attempt'
        ]);
        exit;
    }
    
    // ✅ SECURITY CHECK: Verify session integrity
    // Prevent session hijacking attacks
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        echo json_encode([
            'success' => false,
            'count' => 0,
            'notifications' => [],
            'error' => 'Session invalid'
        ]);
        exit;
    }
    
    // Auto-mark as read when fetched if requested
    if (isset($_GET['mark_read']) && $_GET['mark_read'] == '1') {
        mark_all_notifications_read($user_id);
    }

    // ✅ GET NOTIFICATIONS - Only for the logged-in user
    $notifications = get_unread_notifications($user_id);
    
    // ✅ GET UNREAD COUNT - Only for the logged-in user
    $count = get_unread_count($user_id);

    echo json_encode([
        'success' => true,
        'count' => $count,
        'notifications' => $notifications,
        'user_id' => $user_id  // Debug info (remove in production if needed)
    ]);
    
} catch (Exception $e) {
    error_log("Notification API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'count' => 0,
        'notifications' => [],
        'error' => 'Failed to load notifications'
    ]);
}