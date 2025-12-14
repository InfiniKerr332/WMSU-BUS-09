<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/functions.php';

/**
 * Create a notification in the database
 */
function create_notification($user_id, $type, $title, $message, $link = null) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        if ($user_id === null) {
            // Notify ALL admins
            $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($admins as $admin_id) {
                $insert_stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$admin_id, $type, $title, $message, $link]);
            }
        } else {
            // Notify specific user
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $type, $title, $message, $link]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications for a user
 */
function get_unread_notifications($user_id) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        error_log("Failed to fetch notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 */
function get_unread_count($user_id) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = 0
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['count'] ?? 0);
    } catch (Exception $e) {
        error_log("Failed to get notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notification_id]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to mark notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 */
function mark_all_notifications_read($user_id) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to mark all as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete old notifications (older than 7 days)
 */
function cleanup_old_notifications() {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("Cleanup failed: " . $e->getMessage());
        return false;
    }
}

// ============================================
// NOTIFICATION EVENTS - PROFESSIONAL EMAILS
// ============================================

/**
 * Notify when user submits a new reservation
 */
function notify_new_reservation($reservation_id, $user_id, $user_name, $reservation_date, $destination) {
    $first_name = get_first_name($user_name);
    
    // Notify USER
    $user_title = "Reservation Submitted";
    $user_message = "Your reservation for " . format_date($reservation_date) . " is pending admin approval";
    $user_link = "student/my_reservations.php?view=$reservation_id";
    create_notification($user_id, 'reservation', $user_title, $user_message, $user_link);
    
    // Notify ADMIN
    $admin_title = "New Reservation from $first_name";
    $admin_message = format_date($reservation_date) . " to " . substr($destination, 0, 30) . " - Awaiting approval";
    $admin_link = "admin/reservations.php?view=$reservation_id";
    create_notification(null, 'reservation', $admin_title, $admin_message, $admin_link);
}

/**
 * Notify when user registers and verifies email
 */
function notify_new_registration($user_id, $user_name, $user_email) {
    $first_name = get_first_name($user_name);
    
    // Notify ADMIN
    $title = "New User: $first_name";
    $message = "Email verified - Review employee/teacher ID for approval";
    $link = "admin/users.php?view=$user_id";
    create_notification(null, 'approval', $title, $message, $link);
    
    // Professional email to admin
    $email_content = "
        <p>Dear Administrator,</p>
        
        <p>A new user has completed email verification and requires account approval.</p>
        
        <div class='info-section'>
            <p><strong>User Information:</strong></p>
            <p>Name: $user_name</p>
            <p>Email: $user_email</p>
        </div>
        
        <p>Please review the employee/teacher ID documents to approve or reject this account.</p>
        
        <p>Review account: <a href='" . SITE_URL . "admin/users.php?view=$user_id'>" . SITE_URL . "admin/users.php?view=$user_id</a></p>
        
        <p>Best regards,<br>WMSU Bus Reserve System</p>
    ";
    
    send_email(ADMIN_EMAIL, "New User Verification - $user_name", $email_content);
}

/**
 * Notify user when account is approved
 */
function notify_account_approved($user_id, $user_name, $user_email) {
    $first_name = get_first_name($user_name);
    
    // Notify USER
    $title = "Account Approved";
    $message = "Your account has been approved. You can now login and make reservations";
    $link = "login.php";
    create_notification($user_id, 'approval', $title, $message, $link);
    
    // Professional email
    $email_content = "
        <p>Dear $first_name,</p>
        
        <p>Your account registration has been approved by the administrator.</p>
        
        <p>You may now login to the WMSU Bus Reserve System and begin making bus reservations for official university activities.</p>
        
        <p>Login: <a href='" . SITE_URL . "login.php'>" . SITE_URL . "login.php</a></p>
        
        <p>If you have any questions, please contact the administration office.</p>
        
        <p>Best regards,<br>WMSU Administration</p>
    ";
    
    send_email($user_email, "Account Approved - WMSU Bus Reserve", $email_content);
}

/**
 * Notify user when account is rejected
 */
function notify_account_rejected($user_id, $user_name, $user_email, $reason) {
    $first_name = get_first_name($user_name);
    
    // Notify USER
    $title = "Account Not Approved";
    $message = "Your registration was rejected. Reason: " . substr($reason, 0, 50);
    $link = null;
    create_notification($user_id, 'rejection', $title, $message, $link);
    
    // Professional email
    $email_content = "
        <p>Dear $first_name,</p>
        
        <p>We regret to inform you that your account registration has not been approved.</p>
        
        <div class='info-section'>
            <p><strong>Reason for rejection:</strong></p>
            <p>" . htmlspecialchars($reason) . "</p>
        </div>
        
        <p>If you believe this decision was made in error, please contact the administration office at " . ADMIN_EMAIL . " for assistance.</p>
        
        <p>Best regards,<br>WMSU Administration</p>
    ";
    
    send_email($user_email, "Account Status Update - WMSU Bus Reserve", $email_content);
}

/**
 * Notify user when reservation is approved
 */
function notify_reservation_approved($reservation) {
    $first_name = get_first_name($reservation['user_name']);
    
    // Notify USER
    $title = "Reservation Approved";
    $message = format_date($reservation['reservation_date']) . " - " . substr($reservation['destination'], 0, 30);
    $link = "student/my_reservations.php?view=" . $reservation['id'];
    create_notification($reservation['user_id'], 'approval', $title, $message, $link);
    
    $return_info = '';
    if (!empty($reservation['return_date'])) {
        $return_info = "<p>Return: " . format_date($reservation['return_date']) . " at " . format_time($reservation['return_time']) . "</p>";
    }
    
    // Professional email
    $email_content = "
        <p>Dear $first_name,</p>
        
        <p>Your bus reservation has been approved by the administration.</p>
        
        <div class='info-section'>
            <p><strong>Reservation Details:</strong></p>
            <p>Departure: " . format_date($reservation['reservation_date']) . " at " . format_time($reservation['reservation_time']) . "</p>
            $return_info
            <p>Destination: " . htmlspecialchars($reservation['destination']) . "</p>
            <p>Bus: " . htmlspecialchars($reservation['bus_name']) . "</p>
            <p>Driver: " . htmlspecialchars($reservation['driver_name']) . "</p>
        </div>
        
        <p>Please arrive at the designated pickup location 15 minutes before departure time.</p>
        
        <p>View reservation: <a href='" . SITE_URL . "student/my_reservations.php'>" . SITE_URL . "student/my_reservations.php</a></p>
        
        <p>Best regards,<br>WMSU Transportation Services</p>
    ";
    
    send_email($reservation['email'], "Reservation Approved - WMSU Bus Reserve", $email_content);
}

/**
 * Notify user when reservation is rejected
 */
function notify_reservation_rejected($reservation, $reason) {
    $first_name = get_first_name($reservation['user_name']);
    
    // Notify USER
    $title = "Reservation Not Approved";
    $message = format_date($reservation['reservation_date']) . " - Reason: " . substr($reason, 0, 40);
    $link = "student/reserve.php";
    create_notification($reservation['user_id'], 'rejection', $title, $message, $link);
    
    // Professional email
    $email_content = "
        <p>Dear $first_name,</p>
        
        <p>We regret to inform you that your bus reservation for " . format_date($reservation['reservation_date']) . " has not been approved.</p>
        
        <div class='info-section'>
            <p><strong>Reason for rejection:</strong></p>
            <p>" . htmlspecialchars($reason) . "</p>
        </div>
        
        <p>You may submit a new reservation request if needed.</p>
        
        <p>Make new reservation: <a href='" . SITE_URL . "student/reserve.php'>" . SITE_URL . "student/reserve.php</a></p>
        
        <p>Best regards,<br>WMSU Transportation Services</p>
    ";
    
    send_email($reservation['email'], "Reservation Update - WMSU Bus Reserve", $email_content);
}

/**
 * Notify when user cancels their own reservation
 */
function notify_reservation_cancelled_by_user($reservation) {
    $first_name = get_first_name($reservation['user_name']);
    
    // Notify USER
    $title = "Reservation Cancelled";
    $message = "Your reservation for " . format_date($reservation['reservation_date']) . " has been cancelled";
    $link = "student/my_reservations.php";
    create_notification($reservation['user_id'], 'cancellation', $title, $message, $link);
    
    // Notify ADMIN
    $admin_title = "Reservation Cancelled by $first_name";
    $admin_message = format_date($reservation['reservation_date']) . " to " . substr($reservation['destination'], 0, 30);
    $admin_link = "admin/reservations.php";
    create_notification(null, 'cancellation', $admin_title, $admin_message, $admin_link);
    
    // Professional email to user
    $email_content = "
        <p>Dear $first_name,</p>
        
        <p>This confirms that your bus reservation has been cancelled as requested.</p>
        
        <div class='info-section'>
            <p><strong>Cancelled Reservation:</strong></p>
            <p>Date: " . format_date($reservation['reservation_date']) . " at " . format_time($reservation['reservation_time']) . "</p>
            <p>Destination: " . htmlspecialchars($reservation['destination']) . "</p>
            <p>Bus: " . htmlspecialchars($reservation['bus_name']) . "</p>
        </div>
        
        <p>The bus and driver have been made available for other bookings.</p>
        
        <p>Best regards,<br>WMSU Transportation Services</p>
    ";
    
    send_email($reservation['email'], "Reservation Cancelled - WMSU Bus Reserve", $email_content);
}

/**
 * Notify when admin deletes a user's reservation
 */
function notify_reservation_deleted_by_admin($reservation, $deletion_reason) {
    $first_name = get_first_name($reservation['user_name']);
    
    // Notify USER
    $title = "Reservation Deleted by Admin";
    $message = format_date($reservation['reservation_date']) . " - " . substr($deletion_reason, 0, 40);
    $link = "student/reserve.php";
    create_notification($reservation['user_id'], 'cancellation', $title, $message, $link);
    
    // Professional email
    $email_content = "
        <p>Dear $first_name,</p>
        
        <p>Your bus reservation has been deleted by the administrator.</p>
        
        <div class='info-section'>
            <p><strong>Deleted Reservation:</strong></p>
            <p>Reservation ID: #" . $reservation['id'] . "</p>
            <p>Date: " . format_date($reservation['reservation_date']) . " at " . format_time($reservation['reservation_time']) . "</p>
            <p>Destination: " . htmlspecialchars($reservation['destination']) . "</p>
            <p>Bus: " . htmlspecialchars($reservation['bus_name']) . "</p>
        </div>
        
        <div class='info-section'>
            <p><strong>Reason for deletion:</strong></p>
            <p>" . htmlspecialchars($deletion_reason) . "</p>
        </div>
        
        <p>If you have questions regarding this action, please contact the administration office.</p>
        
        <p>Make new reservation: <a href='" . SITE_URL . "student/reserve.php'>" . SITE_URL . "student/reserve.php</a></p>
        
        <p>Best regards,<br>WMSU Transportation Services</p>
    ";
    
    send_email($reservation['email'], "Reservation Deleted - WMSU Bus Reserve", $email_content);
}

/**
 * Send 24-hour trip reminders
 */
function send_trip_reminders() {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            SELECT r.*, u.name as user_name, u.email, b.bus_name, d.name as driver_name, d.contact_no as driver_contact
            FROM reservations r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN buses b ON r.bus_id = b.id
            LEFT JOIN drivers d ON r.driver_id = d.id
            WHERE r.status = 'approved'
            AND r.reminder_sent = 0
            AND r.reservation_date BETWEEN DATE_ADD(NOW(), INTERVAL 23 HOUR) AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
        ");
        $stmt->execute();
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($reservations as $res) {
            $first_name = get_first_name($res['user_name']);
            
            // Notify USER
            $title = "Trip Tomorrow";
            $message = "Your trip to " . substr($res['destination'], 0, 30) . " is tomorrow at " . format_time($res['reservation_time']);
            $link = "student/my_reservations.php?view=" . $res['id'];
            create_notification($res['user_id'], 'reminder', $title, $message, $link);
            
            // Professional email
            $email_content = "
                <p>Dear $first_name,</p>
                
                <p>This is a reminder that your scheduled bus trip is tomorrow.</p>
                
                <div class='info-section'>
                    <p><strong>Trip Details:</strong></p>
                    <p>Date: " . format_date($res['reservation_date']) . "</p>
                    <p>Time: " . format_time($res['reservation_time']) . "</p>
                    <p>Destination: " . htmlspecialchars($res['destination']) . "</p>
                    <p>Bus: " . htmlspecialchars($res['bus_name']) . "</p>
                    <p>Driver: " . htmlspecialchars($res['driver_name']) . "</p>
                </div>
                
                <p>Please arrive at the pickup location at least 15 minutes before departure time.</p>
                
                <p>Best regards,<br>WMSU Transportation Services</p>
            ";
            
            send_email($res['email'], "Trip Reminder - Tomorrow", $email_content);
            
            $update_stmt = $conn->prepare("UPDATE reservations SET reminder_sent = 1 WHERE id = ?");
            $update_stmt->execute([$res['id']]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to send reminders: " . $e->getMessage());
        return false;
    }
}

/**
 * Auto-reject overdue pending reservations
 */
function auto_reject_overdue_reservations() {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            SELECT r.*, u.name as user_name, u.email
            FROM reservations r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.status = 'pending'
            AND TIMESTAMPDIFF(HOUR, NOW(), CONCAT(r.reservation_date, ' ', r.reservation_time)) < 72
        ");
        $stmt->execute();
        $overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($overdue as $res) {
            $reject_reason = "Not approved within 72-hour deadline";
            $update_stmt = $conn->prepare("UPDATE reservations SET status = 'rejected', admin_remarks = ? WHERE id = ?");
            $update_stmt->execute([$reject_reason, $res['id']]);
            
            notify_reservation_rejected($res, $reject_reason);
            
            $first_name = get_first_name($res['user_name']);
            $admin_title = "Auto-Rejected: $first_name";
            $admin_message = "Reservation for " . format_date($res['reservation_date']) . " auto-rejected (missed 72-hour deadline)";
            $admin_link = "admin/reservations.php?view=" . $res['id'];
            create_notification(null, 'rejection', $admin_title, $admin_message, $admin_link);
        }
        
        return count($overdue);
    } catch (Exception $e) {
        error_log("Auto-reject failed: " . $e->getMessage());
        return 0;
    }
}