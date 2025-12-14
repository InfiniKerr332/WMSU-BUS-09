<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

if (!is_logged_in()) {
    redirect(SITE_URL . 'login.php');
}

if (is_admin()) {
    redirect(SITE_URL . 'admin/dashboard.php');
}

$user = get_logged_user();

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM reservations WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->fetch();

// Ensure all stats are numbers (not NULL)
$stats['total'] = $stats['total'] ?? 0;
$stats['pending'] = $stats['pending'] ?? 0;
$stats['approved'] = $stats['approved'] ?? 0;
$stats['rejected'] = $stats['rejected'] ?? 0;
$stats['cancelled'] = $stats['cancelled'] ?? 0;

$stmt = $conn->prepare("SELECT r.*, b.bus_name, b.plate_no, d.name as driver_name 
                        FROM reservations r 
                        LEFT JOIN buses b ON r.bus_id = b.id 
                        LEFT JOIN drivers d ON r.driver_id = d.id 
                        WHERE r.user_id = :user_id 
                        AND r.reservation_date >= CURDATE() 
                        AND r.status IN ('pending', 'approved')
                        ORDER BY r.reservation_date ASC, r.reservation_time ASC 
                        LIMIT 5");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$upcoming = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 4px solid var(--wmsu-maroon);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(128, 0, 0, 0.15);
        }
        
        .stat-card h3 {
            font-size: 48px;
            color: var(--wmsu-maroon);
            margin-bottom: 10px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover h3 {
            transform: scale(1.1);
        }
        
        .stat-card p {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .stat-card.pending {
            border-top-color: #ffc107;
        }
        
        .stat-card.approved {
            border-top-color: #28a745;
        }
        
        .stat-card.rejected {
            border-top-color: #dc3545;
        }
        
        .stat-card.cancelled {
            border-top-color: #6c757d;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            flex: 1;
            min-width: 200px;
        }
        
        .page-title {
            color: #800000;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .getting-started {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin: 30px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .getting-started h3 {
            color: #800000;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .step-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #800000;
            transition: all 0.3s ease;
        }
        
        .step-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .step-number {
            background: #800000;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .step-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .step-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo-section">
                <img src="../images/wmsu.png" alt="WMSU Logo" onerror="this.style.display='none'">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <div class="user-info">
                <div class="notification-bell" id="notificationBell">
                    <span class="notification-bell-icon">🔔</span>
                    <span class="notification-count" id="notificationCount">0</span>
                    
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">Notifications</div>
                        <div class="notification-empty">Loading...</div>
                    </div>
                </div>
                
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <nav class="nav">
        <ul>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <?php if ($user['role'] == 'teacher' || $user['role'] == 'employee'): ?>
                <li><a href="reserve.php">New Reservation</a></li>
            <?php endif; ?>
            <li><a href="my_reservations.php">My Reservations</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <div class="container">
        <!-- STATISTICS FIRST -->
        <h2 class="page-title">📊 Your Reservation Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Reservations</p>
            </div>
            <div class="stat-card pending">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending Approval</p>
            </div>
            <div class="stat-card approved">
                <h3><?php echo $stats['approved']; ?></h3>
                <p>Approved Trips</p>
            </div>
            <div class="stat-card rejected">
                <h3><?php echo $stats['rejected']; ?></h3>
                <p>Rejected</p>
            </div>
            <div class="stat-card cancelled">
                <h3><?php echo $stats['cancelled']; ?></h3>
                <p>Cancelled</p>
            </div>
        </div>
        
        <!-- QUICK ACTIONS -->
        <div class="quick-actions">
            <?php if ($user['role'] == 'teacher' || $user['role'] == 'employee'): ?>
                <a href="reserve.php" class="btn btn-primary action-btn">📅 New Reservation</a>
            <?php endif; ?>
            <a href="my_reservations.php" class="btn btn-secondary action-btn">📋 View All Reservations</a>
        </div>
        
        <?php if ($stats['total'] == 0 && ($user['role'] == 'teacher' || $user['role'] == 'employee')): ?>
        <!-- FIRST TIME USER GUIDE -->
        <div class="getting-started">
            <h3>🚀 How to Make Your First Reservation</h3>
            
            <div class="steps-container">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-title">Pick Your Dates</div>
                    <div class="step-desc">Choose departure and return dates. Must be at least 72 hours in advance.</div>
                </div>
                
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-title">Select a Bus</div>
                    <div class="step-desc">Choose from available buses based on schedule and passenger count.</div>
                </div>
                
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-title">Provide Details</div>
                    <div class="step-desc">Fill in trip purpose, destination, and passenger count.</div>
                </div>
                
                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-title">Submit & Wait</div>
                    <div class="step-desc">Admin reviews and assigns driver to your request.</div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="reserve.php" class="btn btn-primary" style="font-size: 18px; padding: 15px 40px;">📋 Make Your First Reservation</a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- UPCOMING RESERVATIONS -->
        <div class="card">
            <div class="card-header">
                <h2>📅 Upcoming Reservations</h2>
            </div>
            
            <?php if (count($upcoming) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Purpose</th>
                            <th>Destination</th>
                            <th>Bus</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $res): ?>
                        <tr>
                            <td>
                                <?php echo format_date($res['reservation_date']); ?><br>
                                <small><?php echo format_time($res['reservation_time']); ?></small>
                                <?php if ($res['return_date']): ?>
                                    <br><small style="color: #666;">Return: <?php echo format_date($res['return_date']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(substr($res['purpose'], 0, 50)) . '...'; ?></td>
                            <td><?php echo htmlspecialchars($res['destination']); ?></td>
                            <td><?php echo $res['bus_name'] ? htmlspecialchars($res['bus_name']) : 'Not assigned'; ?></td>
                            <td><?php echo get_status_badge($res['status']); ?></td>
                            <td>
                                <a href="my_reservations.php?view=<?php echo $res['id']; ?>" class="btn btn-info" style="font-size: 12px; padding: 6px 12px;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="my_reservations.php" class="btn btn-secondary">View All Reservations</a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p style="font-size: 18px; margin-bottom: 20px;">No upcoming reservations</p>
                    <?php if ($user['role'] == 'teacher' || $user['role'] == 'employee'): ?>
                        <a href="reserve.php" class="btn btn-primary" style="font-size: 16px;">Make a Reservation</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Western Mindanao State University. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
    <script src="../js/notifications.js"></script>
</body>
</html>