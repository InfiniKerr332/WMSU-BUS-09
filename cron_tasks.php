<?php
// Save as: cron_tasks.php
// Run this file via cron job every hour or manually for testing

// Security: Require secret key
$secret_key = 'WMSU2025';
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret_key) {
    die('Unauthorized access. Use: cron_tasks.php?secret=WMSU2025');
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/notifications.php';

echo "<h2>🔧 WMSU Bus System - Cron Tasks</h2>";
echo "<p><strong>Execution Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// ========================================
// TASK 1: Clean up old notifications
// ========================================
echo "<h3>📧 Task 1: Cleaning Old Notifications</h3>";
try {
    $result = cleanup_old_notifications();
    if ($result) {
        echo "<p style='color:green;'>✓ Successfully cleaned up notifications older than 3 days</p>";
    } else {
        echo "<p style='color:orange;'>⚠ No old notifications to clean</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// ========================================
// TASK 2: Send 24-hour trip reminders
// ========================================
echo "<h3>⏰ Task 2: Sending Trip Reminders (24 hours before)</h3>";
try {
    send_trip_reminders();
    echo "<p style='color:green;'>✓ Trip reminders sent successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error sending reminders: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// ========================================
// TASK 3: Auto-reject overdue reservations
// ========================================
echo "<h3>⚠️ Task 3: Auto-Rejecting Overdue Pending Reservations</h3>";
try {
    $rejected_count = auto_reject_overdue_reservations();
    if ($rejected_count > 0) {
        echo "<p style='color:orange;'><strong>✓ Auto-rejected {$rejected_count} reservation(s)</strong> that missed the 72-hour deadline</p>";
    } else {
        echo "<p style='color:green;'>✓ No overdue reservations to reject</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// ========================================
// TASK 4: Send admin reminders for pending close to deadline
// ========================================
echo "<h3>📩 Task 4: Sending Admin Reminders (Reservations Close to 72hr Deadline)</h3>";
try {
    $db = new Database();
    $conn = $db->connect();
    
    // Find pending reservations that are 60-72 hours away (warning zone)
    $stmt = $conn->prepare("
        SELECT r.*, u.name as user_name, u.email
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status = 'pending'
        AND TIMESTAMPDIFF(HOUR, NOW(), CONCAT(r.reservation_date, ' ', r.reservation_time)) BETWEEN 60 AND 72
    ");
    $stmt->execute();
    $warnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($warnings) > 0) {
        foreach ($warnings as $res) {
            $hours_remaining = round((strtotime($res['reservation_date'] . ' ' . $res['reservation_time']) - time()) / 3600, 1);
            
            // Create notification for admins
            $title = "⏰ Urgent: Reservation #" . $res['id'] . " Close to Deadline";
            $message = "{$res['user_name']}'s reservation is only {$hours_remaining} hours away. Approve or reject soon!";
            $link = "admin/reservations.php?view=" . $res['id'];
            
            create_notification(null, 'reminder', $title, $message, $link);
            
            // Send email to admin
            $email_content = "
                <div style='background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107;'>
                    <h3>⏰ Urgent: Reservation Close to 72-Hour Deadline</h3>
                    <p><strong>Reservation ID:</strong> #{$res['id']}</p>
                    <p><strong>Requester:</strong> {$res['user_name']}</p>
                    <p><strong>Email:</strong> {$res['email']}</p>
                    <p><strong>Date:</strong> " . format_date($res['reservation_date']) . " at " . format_time($res['reservation_time']) . "</p>
                    <p><strong>Time Remaining:</strong> {$hours_remaining} hours</p>
                    <p style='color: #856404;'><strong>Action Required:</strong> This reservation will be auto-rejected if not approved within {$hours_remaining} hours.</p>
                    <a href='" . SITE_URL . "admin/reservations.php?view={$res['id']}' style='display: inline-block; padding: 12px 30px; background: #800000; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Review Now</a>
                </div>
            ";
            
            send_email(ADMIN_EMAIL, "⏰ Urgent: Reservation #{$res['id']} Close to Deadline", $email_content);
        }
        
        echo "<p style='color:orange;'><strong>✓ Sent {count($warnings)} admin reminder(s)</strong> for reservations close to deadline</p>";
    } else {
        echo "<p style='color:green;'>✓ No reservations close to deadline</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>✅ All Cron Tasks Completed</h3>";
echo "<p><em>Next run recommended: In 1 hour</em></p>";
echo "<p><strong>To automate:</strong> Set up a cron job to run this URL hourly:</p>";
echo "<code>0 * * * * /usr/bin/php " . __DIR__ . "/cron_tasks.php</code>";
echo "<p>Or via wget:</p>";
echo "<code>0 * * * * wget -q -O - '" . SITE_URL . "cron_tasks.php?secret=WMSU2025' > /dev/null 2>&1</code>";