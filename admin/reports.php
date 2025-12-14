<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

require_admin();

$user = get_logged_user();

$db = new Database();
$conn = $db->connect();

$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : date('Y-m-d');
$bus_filter = isset($_GET['bus_id']) ? clean_input($_GET['bus_id']) : 'all';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';

$where = "r.reservation_date BETWEEN :date_from AND :date_to";

if ($bus_filter != 'all') {
    $where .= " AND r.bus_id = :bus_id";
}

if ($status_filter != 'all') {
    $where .= " AND r.status = :status";
}

$stmt = $conn->prepare("SELECT r.*, u.name as user_name, u.email, u.contact_no,
                        b.bus_name, b.plate_no, d.name as driver_name 
                        FROM reservations r 
                        LEFT JOIN users u ON r.user_id = u.id
                        LEFT JOIN buses b ON r.bus_id = b.id 
                        LEFT JOIN drivers d ON r.driver_id = d.id 
                        WHERE {$where}
                        ORDER BY r.reservation_date DESC, r.reservation_time DESC");

$stmt->bindParam(':date_from', $date_from);
$stmt->bindParam(':date_to', $date_to);

if ($bus_filter != 'all') {
    $stmt->bindParam(':bus_id', $bus_filter);
}

if ($status_filter != 'all') {
    $stmt->bindParam(':status', $status_filter);
}

$stmt->execute();
$reservations = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM buses WHERE (deleted = 0 OR deleted IS NULL) ORDER BY bus_name");
$stmt->execute();
$buses = $stmt->fetchAll();

$total_trips = count($reservations);
$approved_trips = count(array_filter($reservations, function($r) { return $r['status'] == 'approved'; }));
$pending_trips = count(array_filter($reservations, function($r) { return $r['status'] == 'pending'; }));
$total_passengers = array_sum(array_column($reservations, 'passenger_count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <style>
        /* SCREEN STYLES */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: var(--wmsu-gray);
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 28px;
            color: var(--wmsu-maroon);
            margin-bottom: 5px;
        }
        
        .stat-box p {
            color: #666;
            font-size: 13px;
        }
        
        /* PROFESSIONAL PRINT STYLES */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0.5cm;
            }
            
            body {
                background: white !important;
                font-family: 'Arial', sans-serif;
                color: #000;
                font-size: 10pt;
            }
            
            /* Hide non-print elements */
            header, nav, .filter-section, .no-print, footer {
                display: none !important;
            }
            
            .container {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
                border: none !important;
                page-break-inside: avoid;
            }
            
            /* PRINT HEADER with WMSU Branding */
            .print-header {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                padding: 15px 20px;
                border-bottom: 4px solid #800000;
                margin-bottom: 20px;
                page-break-after: avoid;
            }
            
            .print-logo-section {
                display: flex !important;
                align-items: center;
                gap: 15px;
            }
            
            .print-logo-section img {
                width: 70px;
                height: 70px;
            }
            
            .print-title-section {
                flex: 1;
                text-align: center;
            }
            
            .print-title-section h1 {
                font-size: 18pt;
                color: #800000;
                margin: 0 0 5px 0;
                font-weight: bold;
            }
            
            .print-title-section p {
                font-size: 9pt;
                color: #333;
                margin: 0;
            }
            
            .print-report-info {
                text-align: right;
                font-size: 8pt;
                color: #666;
            }
            
            /* STATS SECTION for Print */
            .print-stats {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin: 20px 0;
                page-break-after: avoid;
            }
            
            .print-stat-box {
                border: 2px solid #800000;
                border-radius: 8px;
                padding: 12px;
                text-align: center;
                background: #f8f8f8;
            }
            
            .print-stat-box h3 {
                font-size: 24pt;
                color: #800000;
                margin: 0 0 5px 0;
                font-weight: bold;
            }
            
            .print-stat-box p {
                font-size: 9pt;
                color: #333;
                margin: 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            /* TABLE STYLING for Print */
            .table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                font-size: 8pt;
            }
            
            .table thead {
                background: #800000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .table th {
                background: #800000 !important;
                color: white !important;
                padding: 8px 6px;
                text-align: left;
                font-weight: bold;
                border: 1px solid #600000;
                font-size: 8pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .table td {
                padding: 6px 6px;
                border: 1px solid #ddd;
                color: #000;
                background: white;
            }
            
            .table tbody tr:nth-child(even) {
                background: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* STATUS BADGES for Print */
            .badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 7pt;
                font-weight: bold;
                text-transform: uppercase;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .badge-pending {
                background: #fff3cd !important;
                color: #856404 !important;
                border: 1px solid #ffc107;
            }
            
            .badge-approved {
                background: #d4edda !important;
                color: #155724 !important;
                border: 1px solid #28a745;
            }
            
            .badge-rejected {
                background: #f8d7da !important;
                color: #721c24 !important;
                border: 1px solid #dc3545;
            }
            
            .badge-cancelled {
                background: #e2e3e5 !important;
                color: #383d41 !important;
                border: 1px solid #6c757d;
            }
            
            /* FOOTER for Print */
            .print-footer {
                display: block !important;
                margin-top: 30px;
                padding-top: 15px;
                border-top: 2px solid #800000;
                text-align: center;
                font-size: 8pt;
                color: #666;
                page-break-inside: avoid;
            }
            
            .print-footer p {
                margin: 3px 0;
            }
            
            .signature-section {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 50px;
                margin-top: 40px;
                page-break-inside: avoid;
            }
            
            .signature-box {
                text-align: center;
            }
            
            .signature-line {
                border-top: 2px solid #000;
                margin-top: 50px;
                padding-top: 5px;
                font-weight: bold;
            }
        }
        
        /* HIDE print-only elements on screen */
        @media screen {
            .print-header,
            .print-stats,
            .print-footer,
            .signature-section {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- SCREEN VIEW -->
    <header>
        <div class="header-content">
            <div class="logo-section">
                <img src="../images/wmsu.png" alt="WMSU Logo">
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
        <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
        <li><a href="reservations.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'class="active"' : ''; ?>>Reservations</a></li>
        <li><a href="buses.php" <?php echo basename($_SERVER['PHP_SELF']) == 'buses.php' ? 'class="active"' : ''; ?>>Buses</a></li>
        <li><a href="drivers.php" <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'class="active"' : ''; ?>>Drivers</a></li>
        <li><a href="reports.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active"' : ''; ?>>Reports</a></li>
        <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>Users</a></li>
    </ul>
</nav>

    <div class="container">
        <!-- FILTER SECTION (screen only) -->
        <div class="filter-section no-print">
            <h3 style="color: var(--wmsu-maroon); margin-bottom: 15px;">📊 Generate Report</h3>
            
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo $date_to; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bus_id">Bus</label>
                        <select id="bus_id" name="bus_id" class="form-control">
                            <option value="all">All Buses</option>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?php echo $bus['id']; ?>" <?php echo $bus_filter == $bus['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($bus['bus_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <button type="button" onclick="window.print()" class="btn btn-secondary">🖨️ Print Report</button>
                    <button type="button" onclick="exportToCSV()" class="btn btn-info">📥 Export CSV</button>
                </div>
            </form>
        </div>
        
        <!-- PRINT HEADER (print only) -->
        <div class="print-header">
            <div class="print-logo-section">
                <img src="../images/wmsu.png" alt="WMSU Logo">
                <div>
                    <h2 style="margin: 0; font-size: 11pt; color: #800000;">WESTERN MINDANAO</h2>
                    <h2 style="margin: 0; font-size: 11pt; color: #800000;">STATE UNIVERSITY</h2>
                    <p style="margin: 0; font-size: 7pt; color: #666;">Normal Road, Baliwasan, Zamboanga City</p>
                </div>
            </div>
            
            <div class="print-title-section">
                <h1>BUS RESERVATION REPORT</h1>
                <p>Report Period: <?php echo format_date($date_from) . ' - ' . format_date($date_to); ?></p>
            </div>
            
            <div class="print-report-info">
                <p><strong>Generated:</strong> <?php echo date('F d, Y g:i A'); ?></p>
                <p><strong>By:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Page:</strong> 1 of 1</p>
            </div>
        </div>
        
        <!-- PRINT STATS (print only) -->
        <div class="print-stats">
            <div class="print-stat-box">
                <h3><?php echo $total_trips; ?></h3>
                <p>Total Reservations</p>
            </div>
            
            <div class="print-stat-box">
                <h3><?php echo $approved_trips; ?></h3>
                <p>Approved Trips</p>
            </div>
            
            <div class="print-stat-box">
                <h3><?php echo $pending_trips; ?></h3>
                <p>Pending Trips</p>
            </div>
            
            <div class="print-stat-box">
                <h3><?php echo $total_passengers; ?></h3>
                <p>Total Passengers</p>
            </div>
        </div>
        
        <!-- REPORT CONTENT -->
        <div class="card">
            <div class="card-header no-print">
                <h2>Trip Report</h2>
                <p style="color: #666; font-size: 14px; margin-top: 5px;">
                    Period: <?php echo format_date($date_from) . ' - ' . format_date($date_to); ?>
                </p>
            </div>
            
            <!-- SCREEN STATS (screen only) -->
            <div class="stats-row no-print">
                <div class="stat-box">
                    <h3><?php echo $total_trips; ?></h3>
                    <p>Total Reservations</p>
                </div>
                
                <div class="stat-box">
                    <h3><?php echo $approved_trips; ?></h3>
                    <p>Approved Trips</p>
                </div>
                
                <div class="stat-box">
                    <h3><?php echo $pending_trips; ?></h3>
                    <p>Pending Trips</p>
                </div>
                
                <div class="stat-box">
                    <h3><?php echo $total_passengers; ?></h3>
                    <p>Total Passengers</p>
                </div>
            </div>
            
            <?php if (count($reservations) > 0): ?>
                <table class="table table-striped" id="reportTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Requester</th>
                            <th>Purpose</th>
                            <th>Destination</th>
                            <th>Bus</th>
                            <th>Driver</th>
                            <th>Passengers</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td><?php echo format_date($res['reservation_date']); ?></td>
                            <td><?php echo format_time($res['reservation_time']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($res['user_name']); ?><br>
                                <small><?php echo htmlspecialchars($res['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars(substr($res['purpose'], 0, 30)) . '...'; ?></td>
                            <td>
                                 <?php echo htmlspecialchars($res['destination']); ?>
                                <?php if ($res['return_date']): ?>
                                    <br><small style="color: #666;">Return: <?php echo format_date($res['return_date']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $res['bus_name'] ? htmlspecialchars($res['bus_name']) : 'N/A'; ?></td>
                            <td><?php echo $res['driver_name'] ? htmlspecialchars($res['driver_name']) : 'N/A'; ?></td>
                            <td><?php echo $res['passenger_count']; ?></td>
                            <td><?php echo get_status_badge($res['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- PRINT FOOTER (print only) -->
                <div class="print-footer">
                    <p><strong>Total Records:</strong> <?php echo $total_trips; ?> | <strong>Generated By:</strong> <?php echo htmlspecialchars($user['name']); ?> | <strong>Date:</strong> <?php echo date('F d, Y g:i A'); ?></p>
                    <p style="margin-top: 15px; font-size: 7pt; color: #999;">
                        This is a computer-generated report from the WMSU Bus Reserve System. No signature required.
                    </p>
                    
                    <div class="signature-section">
                        <div class="signature-box">
                            <div class="signature-line">
                                Prepared By: <?php echo htmlspecialchars($user['name']); ?>
                            </div>
                            <p style="font-size: 7pt; margin-top: 5px;">Administrator</p>
                        </div>
                        
                        <div class="signature-box">
                            <div class="signature-line">
                                Approved By
                            </div>
                            <p style="font-size: 7pt; margin-top: 5px;">Director, Transportation Services</p>
                        </div>
                    </div>
                    
                    <p style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 7pt; color: #999;">
                        &copy; <?php echo date('Y'); ?> Western Mindanao State University. All rights reserved.
                    </p>
                </div>
                
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p>No reservations found for the selected criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Western Mindanao State University. All rights reserved.</p>
    </footer>

    <script src="../js/main.js"></script>
    <script>
        function exportToCSV() {
            const table = document.getElementById('reportTable');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'WMSU_Bus_Report_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
    <script src="../js/notifications.js"></script>
</body>
</html>