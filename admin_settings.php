<?php
// Save as: admin/settings.php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

require_admin();

$user = get_logged_user();
$success = '';
$errors = [];

$db = new Database();
$conn = $db->connect();

// Handle email update
if (isset($_POST['update_email'])) {
    $new_email = clean_input($_POST['admin_email']);
    
    if (empty($new_email)) {
        $errors[] = 'Email is required';
    } elseif (!validate_email($new_email)) {
        $errors[] = 'Invalid email format';
    } else {
        $stmt = $conn->prepare("UPDATE users SET email = :email WHERE id = :id");
        $stmt->bindParam(':email', $new_email);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['email'] = $new_email;
            $success = '✅ Admin email updated successfully! All notifications will be sent to: ' . $new_email;
            
            // Test email
            send_email($new_email, 'WMSU Admin Email Updated', "
                <div style='padding: 20px; background: #d4edda; border-left: 4px solid #28a745;'>
                    <h3 style='color: #155724;'>✅ Email Updated Successfully</h3>
                    <p>Your admin email has been updated to: <strong>$new_email</strong></p>
                    <p>All system notifications will be sent to this address.</p>
                </div>
            ");
        } else {
            $errors[] = 'Failed to update email';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/notifications.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo-section">
                <img src="../images/wmsu.png" alt="WMSU Logo" onerror="this.style.display='none'">
                <h1><?php echo SITE_NAME; ?> - Admin</h1>
            </div>
            <div class="user-info">
                <div class="notification-bell" id="notificationBell">
                    <span class="notification-bell-icon">🔔</span>
                    <span class="notification-count" id="notificationCount">0</span>
                </div>
                <span class="user-name">Admin: <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">Notifications</div>
        <div class="notification-empty">Loading...</div>
    </div>

    <nav class="nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="reservations.php">Reservations</a></li>
            <li><a href="buses.php">Buses</a></li>
            <li><a href="drivers.php">Drivers</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="users.php">Users</a></li>
            <li><a href="settings.php" class="active">Settings</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <span class="alert-close">&times;</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
                <span class="alert-close">&times;</span>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>⚙️ Admin Settings</h2>
            </div>
            
            <div style="background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; border-radius: 5px; margin-bottom: 30px;">
                <h3 style="color: #856404; margin-bottom: 10px;">📧 Current Email Configuration</h3>
                <p style="color: #856404; margin: 0;">
                    <strong>Your current email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <p style="color: #856404; margin: 10px 0 0 0; font-size: 14px;">
                    All system notifications (new reservations, user registrations) will be sent to this email.
                </p>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="admin_email">Admin Email Address <span class="required">*</span></label>
                    <input type="email" id="admin_email" name="admin_email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           placeholder="your-email@gmail.com" required>
                    <small style="color: #666;">
                        Use your actual Gmail or any working email address to receive notifications
                    </small>
                </div>
                
                <button type="submit" name="update_email" class="btn btn-primary" 
                        onclick="return confirm('Update admin email? A test email will be sent to verify.');">
                    💾 Update Email
                </button>
            </form>
            
            <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e0e0e0;">
                <h3 style="color: #800000; margin-bottom: 20px;">ℹ️ Email System Information</h3>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <p style="margin: 10px 0;"><strong>SMTP Server:</strong> <?php echo SMTP_HOST; ?></p>
                    <p style="margin: 10px 0;"><strong>SMTP Port:</strong> <?php echo SMTP_PORT; ?></p>
                    <p style="margin: 10px 0;"><strong>From Email:</strong> <?php echo SMTP_FROM_EMAIL; ?></p>
                    <p style="margin: 10px 0;"><strong>From Name:</strong> <?php echo SMTP_FROM_NAME; ?></p>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="../test_email.php" class="btn btn-info" target="_blank">
                        🧪 Test Email System
                    </a>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Western Mindanao State University. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
    <script src="../js/notifications.js"></script>
</body>
</html>