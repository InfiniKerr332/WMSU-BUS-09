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

if ($user['role'] != 'teacher' && $user['role'] != 'employee') {
    redirect(SITE_URL . 'student/dashboard.php?error=only_teachers');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $purpose = isset($_POST['purpose']) ? clean_input($_POST['purpose']) : '';
    $destination = isset($_POST['destination']) ? clean_input($_POST['destination']) : '';
    $reservation_date = isset($_POST['reservation_date']) ? clean_input($_POST['reservation_date']) : '';
    $reservation_time = isset($_POST['reservation_time']) ? clean_input($_POST['reservation_time']) : '';
    $return_date = isset($_POST['return_date']) ? clean_input($_POST['return_date']) : null;
    $return_time = isset($_POST['return_time']) ? clean_input($_POST['return_time']) : null;
    $passenger_count = isset($_POST['passenger_count']) ? (int)clean_input($_POST['passenger_count']) : 0;
    $bus_id = isset($_POST['bus_id']) ? (int)clean_input($_POST['bus_id']) : 0;
    
    // Validation
    if (empty($purpose)) {
        $errors[] = 'Purpose is required';
    }
    
    if (empty($destination)) {
        $errors[] = 'Destination is required';
    }
    
    // CRITICAL FIX: Strict 72-hour validation with proper timezone
    if (empty($reservation_date)) {
        $errors[] = 'Departure date is required';
    } elseif (empty($reservation_time)) {
        $errors[] = 'Departure time is required';
    } else {
        // Use PHP timezone
        date_default_timezone_set('Asia/Manila');
        
        $now = new DateTime('now');
        $reservation_datetime = new DateTime($reservation_date . ' ' . $reservation_time);
        
        // Check if in past
        if ($reservation_datetime <= $now) {
            $errors[] = '❌ Cannot reserve for past dates/times';
        } else {
            // Calculate difference in hours
            $interval = $now->diff($reservation_datetime);
            $total_hours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
            
            if ($total_hours < 72) {
                $hours_remaining = 72 - $total_hours;
                $minimum_datetime = clone $now;
                $minimum_datetime->modify('+72 hours');
                
                $errors[] = sprintf(
                    '❌ <strong>Must reserve at least 72 hours (3 full days) in advance.</strong><br><br>' .
                    '📅 <strong>You selected:</strong> %s<br>' .
                    '⏰ <strong>Current time:</strong> %s<br>' .
                    '📊 <strong>Time difference:</strong> %.1f hours (%.2f days)<br>' .
                    '⚠️ <strong>Need:</strong> %.1f more hours<br><br>' .
                    '✅ <strong>Earliest you can book:</strong> %s or later',
                    $reservation_datetime->format('l, F d, Y g:i A'),
                    $now->format('l, F d, Y g:i A'),
                    $total_hours,
                    $total_hours / 24,
                    $hours_remaining,
                    $minimum_datetime->format('l, F d, Y g:i A')
                );
            }
        }
        
        // Check if Sunday
        if ($reservation_datetime->format('w') == 0) {
            $errors[] = '❌ Departures on Sundays are not allowed';
        }
    }
    
    // Return date validation
    if (empty($return_date)) {
        $errors[] = 'Return date is required';
    } elseif (empty($return_time)) {
        $errors[] = 'Return time is required';
    } else {
        $return_datetime = new DateTime($return_date . ' ' . $return_time);
        
        if ($return_datetime->format('w') == 0) {
            $errors[] = '❌ Returns on Sundays are not allowed';
        }
        
        if (!empty($reservation_date) && !empty($reservation_time)) {
            $depart_dt = new DateTime($reservation_date . ' ' . $reservation_time);
            
            if ($return_datetime <= $depart_dt) {
                $errors[] = '❌ Return date/time must be after departure';
            }
            
            $trip_interval = $depart_dt->diff($return_datetime);
            $trip_days = $trip_interval->days;
            
            if ($trip_days > 7) {
                $errors[] = "❌ Maximum trip duration is 7 days. Your trip is {$trip_days} days.";
            }
        }
    }
    
    if ($passenger_count < 1) {
        $errors[] = 'Valid passenger count is required';
    }
    
    if ($bus_id < 1) {
        $errors[] = 'Please select a bus';
    }
    
    // Bus capacity check
    $db = new Database();
    $conn = $db->connect();
    
    if ($bus_id > 0 && $passenger_count > 0) {
        $stmt = $conn->prepare("SELECT capacity, bus_name FROM buses WHERE id = :bus_id AND (deleted = 0 OR deleted IS NULL)");
        $stmt->bindParam(':bus_id', $bus_id, PDO::PARAM_INT);
        $stmt->execute();
        $selected_bus = $stmt->fetch();
        
        if (!$selected_bus) {
            $errors[] = 'Selected bus not found or unavailable';
        } elseif ($passenger_count > $selected_bus['capacity']) {
            $errors[] = "Passenger count ({$passenger_count}) exceeds bus capacity ({$selected_bus['capacity']})";
        }
    }
    
    // Final availability check
    if (empty($errors) && $bus_id > 0 && !empty($reservation_date) && !empty($return_date)) {
        $check_sql = "
            SELECT COUNT(*) as conflict_count
            FROM reservations 
            WHERE bus_id = :bus_id 
            AND status IN ('pending', 'approved')
            AND (
                (reservation_date <= :end_date AND COALESCE(return_date, reservation_date) >= :start_date)
            )
        ";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':bus_id', $bus_id, PDO::PARAM_INT);
        $check_stmt->bindParam(':start_date', $reservation_date, PDO::PARAM_STR);
        $check_stmt->bindParam(':end_date', $return_date, PDO::PARAM_STR);
        $check_stmt->execute();
        $conflict_check = $check_stmt->fetch();
        
        if ($conflict_check && $conflict_check['conflict_count'] > 0) {
            $errors[] = '❌ This bus was just booked by another user. Please refresh and select another bus.';
        }
    }
    
    // Insert if no errors
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO reservations 
                    (user_id, bus_id, driver_id, purpose, destination, reservation_date, reservation_time, 
                     return_date, return_time, passenger_count, status, created_at) 
                    VALUES 
                    (:user_id, :bus_id, NULL, :purpose, :destination, :reservation_date, :reservation_time, 
                     :return_date, :return_time, :passenger_count, 'pending', NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':bus_id', $bus_id, PDO::PARAM_INT);
            $stmt->bindValue(':purpose', $purpose, PDO::PARAM_STR);
            $stmt->bindValue(':destination', $destination, PDO::PARAM_STR);
            $stmt->bindValue(':reservation_date', $reservation_date, PDO::PARAM_STR);
            $stmt->bindValue(':reservation_time', $reservation_time, PDO::PARAM_STR);
            $stmt->bindValue(':return_date', $return_date, PDO::PARAM_STR);
            $stmt->bindValue(':return_time', $return_time, PDO::PARAM_STR);
            $stmt->bindValue(':passenger_count', $passenger_count, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $reservation_id = $conn->lastInsertId();
                $success = '✅ Reservation submitted! Check your notifications for updates.';
                
                // Get bus info
                $stmt = $conn->prepare("SELECT * FROM buses WHERE id = :bus_id");
                $stmt->bindParam(':bus_id', $bus_id, PDO::PARAM_INT);
                $stmt->execute();
                $bus = $stmt->fetch();
                
                // ✅ NOTIFY USER + ADMIN (Bell Notification)
                notify_new_reservation(
                    $reservation_id, 
                    $_SESSION['user_id'], 
                    $user['name'], 
                    $reservation_date, 
                    $destination
                );
                
                // Email admin
                $email_message = "
                    <h2 style='color: #800000; margin-bottom: 20px;'>New Reservation Request</h2>
                    <p style='color: #333;'><strong>From:</strong> " . htmlspecialchars($user['name']) . "</p>
                    <p style='color: #333;'><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>
                    <p style='color: #333;'><strong>Departure:</strong> " . format_date($reservation_date) . " at " . format_time($reservation_time) . "</p>
                    <p style='color: #333;'><strong>Return:</strong> " . format_date($return_date) . " at " . format_time($return_time) . "</p>
                    <p style='color: #333;'><strong>Destination:</strong> " . htmlspecialchars($destination) . "</p>
                    <p style='color: #333;'><strong>Passengers:</strong> {$passenger_count}</p>
                    <p style='color: #333;'><strong>Bus:</strong> " . htmlspecialchars($bus['bus_name']) . "</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "admin/reservations.php?view={$reservation_id}' class='button'>Review Reservation</a>
                    </div>
                ";
                
                send_email(ADMIN_EMAIL, 'New Bus Reservation - Driver Assignment Needed', $email_message);
                
                // Clear form
                $_POST = [];
            } else {
                $errors[] = 'Failed to submit reservation';
            }
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Get buses
$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT * FROM buses WHERE (deleted = 0 OR deleted IS NULL) AND status = 'available' ORDER BY bus_name");
$stmt->execute();
$all_buses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Reservation - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <style>
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 6px;
        }
        
        .info-box strong {
            color: #1565c0;
            font-size: 16px;
        }
        
        .info-box ul {
            margin: 15px 0 0 25px;
            color: #0d47a1;
        }
        
        .info-box li {
            margin: 8px 0;
            font-weight: 500;
        }
        
        .bus-selector {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .bus-card {
            border: 3px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            text-align: center;
        }
        
        .bus-card:hover:not(.unavailable):not(.disabled) {
            border-color: #800000;
            box-shadow: 0 6px 16px rgba(128, 0, 0, 0.2);
            transform: translateY(-4px);
        }
        
        .bus-card.selected {
            border-color: #800000;
            background: #fff5f5;
            box-shadow: 0 8px 20px rgba(128, 0, 0, 0.3);
        }
        
        .bus-card.unavailable {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }
        
        .bus-card.disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .bus-name {
            font-weight: 700;
            color: #800000;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .bus-plate {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .bus-capacity {
            font-size: 13px;
            color: #888;
            margin-bottom: 12px;
        }
        
        .bus-status {
            padding: 8px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .bus-status.available {
            background: #d4edda;
            color: #155724;
        }
        
        .bus-status.unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        
        .bus-status.checking {
            background: #fff3cd;
            color: #856404;
        }
        
        .form-section {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid #800000;
        }
        
        .form-section h3 {
            color: #800000;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
        }
        
        .step-indicator {
            background: #800000;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 12px;
            font-size: 18px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .bus-selector {
                grid-template-columns: 1fr;
            }
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
                </div>
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</span>
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
            <li><a href="reserve.php" class="active">New Reservation</a></li>
            <li><a href="my_reservations.php">My Reservations</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>📅 New Bus Reservation</h2>
            </div>
            
            <div class="info-box">
                <strong>⚠️ Important Reservation Guidelines:</strong>
                <ul>
                    <li><strong>Reservations must be made at least 3 days (72 hours) in advance</strong></li>
                    <li>Maximum trip duration: 7 days</li>
                    <li>Pickup location is always WMSU Campus, Normal Road, Baliwasan</li>
                    <li>Return date and time are required</li>
                    <li>No reservations on Sundays (both departure and return)</li>
                    <li>Driver will be assigned by admin</li>
                </ul>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <span class="alert-close">&times;</span>
                </div>
                <div style="text-align: center; margin: 25px 0;">
                    <a href="my_reservations.php" class="btn btn-primary">View My Reservations</a>
                    <a href="reserve.php" class="btn btn-secondary">Make Another Reservation</a>
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
            
            <?php if (!$success): ?>
            <form method="POST" action="" id="reservationForm">
                
                <!-- STEP 1: DATE & TIME -->
                <div class="form-section">
                    <h3><span class="step-indicator">1</span>Select Date & Time</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reservation_date">Departure Date <span class="required">*</span></label>
                            <input type="date" id="reservation_date" name="reservation_date" class="form-control" required>
                            <small style="color: #dc3545; font-weight: 600;">⚠️ Must be 72+ hours from now</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="reservation_time">Departure Time <span class="required">*</span></label>
                            <input type="time" id="reservation_time" name="reservation_time" class="form-control" required>
                            <small style="color: #666;">Pickup from WMSU</small>
                        </div>
                    </div>
                    
                    <hr style="margin: 25px 0; border-color: #ccc;">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="return_date">Return Date <span class="required">*</span></label>
                            <input type="date" id="return_date" name="return_date" class="form-control" required>
                            <small style="color: #666;">Max 7 days from departure</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="return_time">Return Time <span class="required">*</span></label>
                            <input type="time" id="return_time" name="return_time" class="form-control" required>
                            <small style="color: #666;">Pickup from destination</small>
                        </div>
                    </div>
                </div>
                
                <!-- STEP 2: SELECT BUS -->
                <div class="form-section" id="busSection">
                    <h3><span class="step-indicator">2</span>Select an Available Bus</h3>
                    <p style="color: #dc3545; font-weight: 600; margin-bottom: 15px;" id="busHint">
                        ⚠️ Please fill ALL date and time fields above first
                    </p>
                    
                    <div class="bus-selector">
                        <?php foreach ($all_buses as $bus): ?>
                        <div class="bus-card disabled" 
                             data-bus-id="<?php echo $bus['id']; ?>"
                             data-capacity="<?php echo $bus['capacity']; ?>"
                             onclick="selectBus(this, <?php echo $bus['id']; ?>, '<?php echo addslashes($bus['bus_name']); ?>', <?php echo $bus['capacity']; ?>)">
                            <div class="bus-name"><?php echo htmlspecialchars($bus['bus_name']); ?></div>
                            <div class="bus-plate"><?php echo htmlspecialchars($bus['plate_no']); ?></div>
                            <div class="bus-capacity">Capacity: <?php echo $bus['capacity']; ?> passengers</div>
                            <div class="bus-status" id="status-<?php echo $bus['id']; ?>">Select dates first</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <input type="hidden" name="bus_id" id="selected_bus_id" value="">
                </div>
                
                <!-- STEP 3: TRIP INFO -->
                <div class="form-section">
                    <h3><span class="step-indicator">3</span>Trip Information</h3>
                    
                    <div class="form-group">
                        <label for="purpose">Purpose <span class="required">*</span></label>
                        <textarea id="purpose" name="purpose" class="form-control" rows="3" 
                                  placeholder="Educational field trip, Official business meeting, etc." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="destination">Destination <span class="required">*</span></label>
                        <input type="text" id="destination" name="destination" class="form-control" 
                               placeholder="Zamboanga City Museum, Fort Pilar, etc." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="passenger_count">Number of Passengers <span class="required">*</span></label>
                        <input type="number" id="passenger_count" name="passenger_count" class="form-control" 
                               min="1" max="45" value="1" required>
                        <small style="color: #666;" id="capacity-hint">Select a bus to see capacity limit</small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" style="font-size: 18px; padding: 18px;">
                    📤 Submit Reservation Request
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Western Mindanao State University. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
    <script src="../js/notifications.js"></script>
    <script>
        let selectedBusId = null;
        let selectedBusCapacity = 0;
        let allDatesFilled = false;
        
        // Calculate minimum datetime (exactly 72 hours from now)
        const now = new Date();
        const minDateTime = new Date(now.getTime() + (72 * 60 * 60 * 1000));
        
        // Set minimum date
        const minYear = minDateTime.getFullYear();
        const minMonth = String(minDateTime.getMonth() + 1).padStart(2, '0');
        const minDay = String(minDateTime.getDate()).padStart(2, '0');
        const minDateStr = `${minYear}-${minMonth}-${minDay}`;
        
        document.getElementById('reservation_date').setAttribute('min', minDateStr);
        
        console.log('Current:', now.toLocaleString());
        console.log('72hrs from now:', minDateTime.toLocaleString());
        console.log('Min date:', minDateStr);
        
        // Check if all date/time fields filled
        function checkAllDatesFilled() {
            const dDate = document.getElementById('reservation_date').value;
            const dTime = document.getElementById('reservation_time').value;
            const rDate = document.getElementById('return_date').value;
            const rTime = document.getElementById('return_time').value;
            
            if (dDate && dTime && rDate && rTime) {
                // Validate 72-hour rule
                const selectedDT = new Date(dDate + 'T' + dTime);
                const hoursDiff = (selectedDT - now) / (1000 * 60 * 60);
                
                if (hoursDiff < 72) {
                    alert(`❌ INVALID: Only ${hoursDiff.toFixed(1)} hours away!\n\nMust be at least 72 hours in advance.\n\nEarliest: ${minDateTime.toLocaleString()}`);
                    document.getElementById('reservation_date').value = '';
                    document.getElementById('reservation_time').value = '';
                    return;
                }
                
                allDatesFilled = true;
                document.getElementById('busHint').textContent = '✅ Choose an available bus:';
                document.getElementById('busHint').style.color = '#28a745';
                
                // Enable bus checking
                checkBusAvailability();
            }
        }
        
        // Date validations
        document.getElementById('reservation_date').addEventListener('change', function() {
            if (!this.value) return;
            
            const sel = new Date(this.value);
            if (sel.getDay() === 0) {
                alert('❌ Sundays not allowed');
                this.value = '';
                return;
            }
            
            // Set return date constraints
            document.getElementById('return_date').setAttribute('min', this.value);
            
            const maxReturn = new Date(sel);
            maxReturn.setDate(maxReturn.getDate() + 7);
            document.getElementById('return_date').setAttribute('max', maxReturn.toISOString().split('T')[0]);
            
            checkAllDatesFilled();
        });
        
        document.getElementById('reservation_time').addEventListener('change', checkAllDatesFilled);
        
        document.getElementById('return_date').addEventListener('change', function() {
            const dDate = document.getElementById('reservation_date').value;
            if (!dDate) {
                alert('Select departure date first');
                this.value = '';
                return;
            }
            
            const rDate = this.value;
            if (!rDate) return;
            
            const retDT = new Date(rDate);
            if (retDT.getDay() === 0) {
                alert('❌ Return on Sunday not allowed');
                this.value = '';
                return;
            }
            
            const depTS = new Date(dDate).getTime();
            const retTS = retDT.getTime();
            
            if (retTS < depTS) {
                alert('❌ Return cannot be before departure');
                this.value = '';
                return;
            }
            
            const days = Math.ceil((retTS - depTS) / (1000 * 60 * 60 * 24));
            if (days > 7) {
                alert(`❌ Max 7 days! Your trip: ${days} days`);
                this.value = '';
                return;
            }
            
            checkAllDatesFilled();
        });
        
        document.getElementById('return_time').addEventListener('change', function() {
            const dDate = document.getElementById('reservation_date').value;
            const dTime = document.getElementById('reservation_time').value;
            const rDate = document.getElementById('return_date').value;
            const rTime = this.value;
            
            if (dDate === rDate && dTime && rTime) {
                const dTS = new Date(dDate + 'T' + dTime).getTime();
                const rTS = new Date(rDate + 'T' + rTime).getTime();
                
                if (rTS <= dTS) {
                    alert('❌ Return must be after departure on same day');
                    this.value = '';
                    return;
                }
            }
            
            checkAllDatesFilled();
        });
        
        // Check bus availability
        function checkBusAvailability() {
            const dDate = document.getElementById('reservation_date').value;
            const rDate = document.getElementById('return_date').value;
            
            if (!dDate || !rDate) return;
            
            document.querySelectorAll('.bus-card').forEach(card => {
                const busId = card.getAttribute('data-bus-id');
                card.classList.remove('disabled');
                card.querySelector('.bus-status').textContent = 'Checking...';
                card.querySelector('.bus-status').className = 'bus-status checking';
                
                fetch(`../api/check_availability.php?date=${dDate}&return_date=${rDate}&bus_id=${busId}`)
                    .then(res => res.json())
                    .then(data => {
                        const statusEl = card.querySelector('.bus-status');
                        
                        if (data.available) {
                            card.classList.remove('unavailable');
                            statusEl.textContent = '✅ Available';
                            statusEl.className = 'bus-status available';
                        } else {
                            card.classList.add('unavailable');
                            statusEl.textContent = '❌ Unavailable';
                            statusEl.className = 'bus-status unavailable';
                        }
                    })
                    .catch(err => console.error('Error:', err));
            });
        }
        
        // Select bus
        function selectBus(card, busId, busName, capacity) {
            if (!allDatesFilled) {
                alert('⚠️ Fill all date/time fields first');
                return;
            }
            
            if (card.classList.contains('unavailable')) {
                alert('❌ This bus is not available for your dates');
                return;
            }
            
            if (card.classList.contains('disabled')) {
                return;
            }
            
            // Deselect all
            document.querySelectorAll('.bus-card').forEach(c => c.classList.remove('selected'));
            
            // Select this one
            card.classList.add('selected');
            selectedBusId = busId;
            selectedBusCapacity = capacity;
            document.getElementById('selected_bus_id').value = busId;
            
            // Update passenger limit
            const passengerInput = document.getElementById('passenger_count');
            passengerInput.setAttribute('max', capacity);
            
            document.getElementById('capacity-hint').textContent = `Maximum: ${capacity} passengers for ${busName}`;
            document.getElementById('capacity-hint').style.color = '#28a745';
            document.getElementById('capacity-hint').style.fontWeight = '600';
            
            if (parseInt(passengerInput.value) > capacity) {
                passengerInput.value = capacity;
                alert(`Adjusted to ${capacity} passengers (max for ${busName})`);
            }
        }
        
        // Passenger count validation
        document.getElementById('passenger_count').addEventListener('input', function() {
            if (selectedBusCapacity > 0) {
                const count = parseInt(this.value);
                if (count > selectedBusCapacity) {
                    this.value = selectedBusCapacity;
                    alert(`Max ${selectedBusCapacity} passengers`);
                }
            }
        });
        
        // Form submission validation
        document.getElementById('reservationForm').addEventListener('submit', function(e) {
            if (!selectedBusId) {
                e.preventDefault();
                alert('⚠️ Please select a bus');
                return false;
            }
            
            const dDate = document.getElementById('reservation_date').value;
            const dTime = document.getElementById('reservation_time').value;
            const rDate = document.getElementById('return_date').value;
            const rTime = document.getElementById('return_time').value;
            
            if (!dDate || !dTime || !rDate || !rTime) {
                e.preventDefault();
                alert('⚠️ Please fill all required fields');
                return false;
            }
            
            // Final 72-hour check
            const selectedDT = new Date(dDate + 'T' + dTime);
            const hoursDiff = (selectedDT - now) / (1000 * 60 * 60);
            
            if (hoursDiff < 72) {
                e.preventDefault();
                alert(`❌ Only ${hoursDiff.toFixed(1)} hours away! Must be 72+ hours.`);
                return false;
            }
            
            const passengers = parseInt(document.getElementById('passenger_count').value);
            if (passengers > selectedBusCapacity) {
                e.preventDefault();
                alert(`❌ Passenger count exceeds bus capacity (${selectedBusCapacity})`);
                return false;
            }
            
            return confirm(`📋 Confirm Reservation?\n\nBus: ${document.querySelector('.bus-card.selected .bus-name').textContent}\nPassengers: ${passengers}\nDeparture: ${dDate} ${dTime}\nReturn: ${rDate} ${rTime}\n\nProceed?`);
        });
    </script>
</body>
</html>