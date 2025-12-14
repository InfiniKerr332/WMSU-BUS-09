<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';
require_once '../includes/notifications.php';

if (!is_logged_in()) {
    redirect(SITE_URL . 'login.php');
}

if (is_admin()) {
    redirect(SITE_URL . 'admin/dashboard.php');
}

$user = get_logged_user();
$success = '';
$error = '';

$db = new Database();
$conn = $db->connect();

// Handle cancellation
if (isset($_POST['cancel_reservation'])) {
    $reservation_id = clean_input($_POST['reservation_id']);
    
    $stmt = $conn->prepare("SELECT r.*, b.bus_name, b.plate_no, d.name as driver_name 
                            FROM reservations r
                            LEFT JOIN buses b ON r.bus_id = b.id
                            LEFT JOIN drivers d ON r.driver_id = d.id
                            WHERE r.id = :id AND r.user_id = :user_id");
    $stmt->bindParam(':id', $reservation_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $reservation = $stmt->fetch();
    
    if ($reservation && $reservation['status'] == 'pending') {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = :id");
        $stmt->bindParam(':id', $reservation_id);
        
        if ($stmt->execute()) {
            $success = 'Reservation cancelled successfully.';
            
            // Get user data for notification
            $reservation['user_name'] = $user['name'];
            $reservation['email'] = $user['email'];
            
            // NOTIFY USER + ADMIN
            notify_reservation_cancelled_by_user($reservation);
        } else {
            $error = 'Failed to cancel reservation.';
        }
    } else {
        $error = 'Cannot cancel this reservation.';
    }
}

// Handle deletion
if (isset($_POST['delete_reservation'])) {
    $reservation_id = clean_input($_POST['reservation_id']);
    
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $reservation_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $reservation = $stmt->fetch();
    
    if ($reservation) {
        $can_delete = in_array($reservation['status'], ['cancelled', 'rejected']) || 
                      ($reservation['status'] == 'pending' && is_past_date($reservation['reservation_date']));
        
        if (!$can_delete) {
            $error = 'Cannot delete active reservations. Please cancel first.';
        } else {
            $stmt = $conn->prepare("DELETE FROM reservations WHERE id = :id");
            $stmt->bindParam(':id', $reservation_id);
            
            if ($stmt->execute()) {
                $success = 'Reservation permanently deleted.';
            } else {
                $error = 'Failed to delete reservation.';
            }
        }
    }
}

// Get all reservations
$stmt = $conn->prepare("SELECT r.*, b.bus_name, b.plate_no, d.name as driver_name 
                        FROM reservations r 
                        LEFT JOIN buses b ON r.bus_id = b.id 
                        LEFT JOIN drivers d ON r.driver_id = d.id 
                        WHERE r.user_id = :user_id 
                        ORDER BY r.reservation_date DESC, r.reservation_time DESC");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$reservations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <style>
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 20px;
            background: white;
            border: 2px solid var(--wmsu-maroon);
            color: var(--wmsu-maroon);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background: var(--wmsu-maroon);
            color: white;
        }
        
        .reservation-details {
            display: none;
            background: #f9f9f9;
            padding: 15px;
            margin-top: 10px;
            border-radius: 5px;
        }
        
        .reservation-details.show {
            display: block;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
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
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="reserve.php">New Reservation</a></li>
            <li><a href="my_reservations.php" class="active">My Reservations</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>My Reservations</h2>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <span class="alert-close">&times;</span>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                    <span class="alert-close">&times;</span>
                </div>
            <?php endif; ?>
            
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterReservations('all')">All</button>
                <button class="filter-tab" onclick="filterReservations('pending')">Pending</button>
                <button class="filter-tab" onclick="filterReservations('approved')">Approved</button>
                <button class="filter-tab" onclick="filterReservations('rejected')">Rejected</button>
                <button class="filter-tab" onclick="filterReservations('cancelled')">Cancelled</button>
            </div>
            
            <?php if (count($reservations) > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Destination</th>
                            <th>Bus</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reservationsTable">
                        <?php foreach ($reservations as $res): ?>
                        <tr data-status="<?php echo $res['status']; ?>">
                            <td><?php echo $res['id']; ?></td>
                            <td>
                                <?php echo format_date($res['reservation_date']); ?><br>
                                <small><?php echo format_time($res['reservation_time']); ?></small>
                                <?php if ($res['return_date']): ?>
                                    <br><small style="color: #666;">Return: <?php echo format_date($res['return_date']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(substr($res['destination'], 0, 40)); ?></td>
                            <td><?php echo htmlspecialchars($res['bus_name'] ?: 'Not assigned'); ?></td>
                            <td><?php echo htmlspecialchars($res['driver_name'] ?: 'Not assigned'); ?></td>
                            <td><?php echo get_status_badge($res['status']); ?></td>
                            <td>
                                <button onclick="toggleDetails(<?php echo $res['id']; ?>)" class="btn btn-info" style="font-size: 12px; padding: 6px 12px;">View</button>
                                
                                <?php if ($res['status'] == 'pending' && !is_past_date($res['reservation_date'])): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this reservation?');">
                                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                        <button type="submit" name="cancel_reservation" class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;">Cancel</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php 
                                $can_delete = in_array($res['status'], ['cancelled', 'rejected']) || 
                                             ($res['status'] == 'pending' && is_past_date($res['reservation_date']));
                                if ($can_delete): 
                                ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Permanently delete? Cannot be undone!');">
                                        <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                        <button type="submit" name="delete_reservation" class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="details-<?php echo $res['id']; ?>" class="reservation-details">
                            <td colspan="7">
                                <h3 style="color: var(--wmsu-maroon); margin-bottom: 15px;">Reservation Details</h3>
                                
                                <div class="detail-row">
                                    <div class="detail-label">ID:</div>
                                    <div><?php echo isset($res['id']) ? htmlspecialchars($res['id']) : 'N/A'; ?></div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Purpose:</div>
                                    <div><?php echo htmlspecialchars($res['purpose']); ?></div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Destination:</div>
                                    <div><?php echo htmlspecialchars($res['destination']); ?></div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Departure:</div>
                                    <div><?php echo format_date($res['reservation_date']); ?> at <?php echo format_time($res['reservation_time']); ?></div>
                                </div>
                                
                                <?php if ($res['return_date']): ?>
                                <div class="detail-row">
                                    <div class="detail-label">Return:</div>
                                    <div><?php echo format_date($res['return_date']); ?> at <?php echo format_time($res['return_time']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Passengers:</div>
                                    <div><?php echo $res['passenger_count']; ?></div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Bus:</div>
                                    <div><?php echo $res['bus_name'] ? htmlspecialchars($res['bus_name']) . ' (' . htmlspecialchars($res['plate_no']) . ')' : 'Not assigned'; ?></div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Driver:</div>
                                    <div><?php echo $res['driver_name'] ? htmlspecialchars($res['driver_name']) : 'Not assigned'; ?></div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Status:</div>
                                    <div><?php echo get_status_badge($res['status']); ?></div>
                                </div>
                                
                                <?php if ($res['admin_remarks']): ?>
                                <div class="detail-row">
                                    <div class="detail-label">Remarks:</div>
                                    <div><?php echo htmlspecialchars($res['admin_remarks']); ?></div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p>No reservations yet</p>
                    <a href="reserve.php" class="btn btn-primary" style="margin-top: 15px;">Make Your First Reservation</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Western Mindanao State University. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
    <script src="../js/notifications.js"></script>
    <script>
        function toggleDetails(id) {
            const detailsRow = document.getElementById('details-' + id);
            detailsRow.classList.toggle('show');
        }
        
        function filterReservations(status) {
            const tabs = document.querySelectorAll('.filter-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            const rows = document.querySelectorAll('#reservationsTable tr[data-status]');
            rows.forEach(row => {
                const nextRow = row.nextElementSibling;
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                    if (nextRow && nextRow.classList.contains('reservation-details')) {
                        nextRow.style.display = '';
                    }
                } else {
                    row.style.display = 'none';
                    if (nextRow && nextRow.classList.contains('reservation-details')) {
                        nextRow.style.display = 'none';
                        nextRow.classList.remove('show');
                    }
                }
            });
        }
    </script>
</body>
</html>