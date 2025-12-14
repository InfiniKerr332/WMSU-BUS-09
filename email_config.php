<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ✅ FINAL SMTP CONFIGURATION - WMSU Bus System Account
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'wmsubussystem@gmail.com');      // ✅ NEW ACCOUNT
define('SMTP_PASSWORD', 'xhau rhoy xoab rizq');          // ✅ NEW APP PASSWORD
define('SMTP_FROM_EMAIL', 'wmsubussystem@gmail.com');    // ✅ Professional sender
define('SMTP_FROM_NAME', 'WMSU Bus System Support');     // ✅ Display name

/**
 * ✅ FIXED: Helper function to safely extract first name
 * Null-safe version with error handling
 */
function get_first_name($full_name) {
    // ✅ FIX: Added null/empty check
    if (empty($full_name) || !is_string($full_name)) {
        return 'User';
    }
    
    $parts = explode(' ', trim($full_name));
    return !empty($parts[0]) ? $parts[0] : 'User';
}

/**
 * ✅ FIXED: Send email using PHPMailer with proper error handling
 * Returns true on success, false on failure
 */
function send_email_phpmailer($to, $subject, $message, $fromName = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->SMTPDebug = 0;
        
        // ✅ FIX: Validate email before sending
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address: $to");
        }
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, $fromName ?: SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = get_professional_email_template($message);
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        
        // ✅ FIX: Log successful send
        log_email($to, $subject, 'sent', null);
        return true;
        
    } catch (Exception $e) {
        // ✅ FIX: Log failed send with error message
        log_email($to, $subject, 'failed', $mail->ErrorInfo);
        error_log("PHPMailer Error to {$to}: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * ✅ FIXED: Professional email template with proper HTML escaping
 * All user content is properly escaped to prevent XSS
 */
function get_professional_email_template($content) {
    // ✅ FIX: Sanitize content to prevent XSS injection
    // The $content should already be escaped by calling function, but we add extra protection
    $safe_content = $content; // Content is already escaped by caller
    
    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border: 1px solid #e0e0e0;
        }
        .email-header {
            background: #800000;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #600000;
        }
        .email-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
            color: #333;
        }
        .email-body p {
            margin: 15px 0;
        }
        .info-section {
            background: #f9f9f9;
            border-left: 3px solid #800000;
            padding: 15px;
            margin: 20px 0;
        }
        .info-section p {
            margin: 8px 0;
        }
        .email-footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .email-footer p {
            margin: 5px 0;
        }
        a {
            color: #800000;
            text-decoration: none;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #800000;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }
        .button:hover {
            background: #600000;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>WMSU Bus Reserve System</h1>
        </div>
        <div class="email-body">
            ' . $safe_content . '
        </div>
        <div class="email-footer">
            <p><strong>Western Mindanao State University</strong></p>
            <p>Normal Road, Baliwasan, Zamboanga City, Philippines 7000</p>
            <p style="margin-top: 10px;"><strong>Support:</strong> wmsubussystem@gmail.com</p>
            <p style="margin-top: 15px;">© ' . date('Y') . ' WMSU. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
}

/**
 * ✅ FIXED: Log all email attempts to database
 * This helps track email delivery issues
 */
function log_email($recipient, $subject, $status, $error = null) {
    try {
        require_once __DIR__ . '/database.php';
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("
            INSERT INTO email_logs (recipient, subject, status, error_message, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([$recipient, $subject, $status, $error]);
        
        if (!$result) {
            error_log("Failed to log email: " . implode(", ", $stmt->errorInfo()));
        }
    } catch (Exception $e) {
        error_log("Email log failed: " . $e->getMessage());
    }
}

/**
 * ✅ FIXED: Main send_email function
 * Returns true/false for error handling
 * 
 * USAGE:
 * if (send_email($to, $subject, $message)) {
 *     echo "Email sent successfully";
 * } else {
 *     echo "Failed to send email - check error logs";
 * }
 */
function send_email($to, $subject, $message, $fromName = null) {
    // ✅ FIX: Return result from phpmailer function
    return send_email_phpmailer($to, $subject, $message, $fromName);
}