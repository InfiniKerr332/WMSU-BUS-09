<?php
// api/check_availability.php - COMPLETE FIXED VERSION

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate parameters
if (!isset($_GET['date']) || !isset($_GET['bus_id'])) {
    echo json_encode([
        'error' => 'Missing parameters',
        'available' => false,
        'message' => 'Date and bus_id required'
    ]);
    exit;
}

$departure_date = clean_input($_GET['date']);
$bus_id = (int)clean_input($_GET['bus_id']);
$return_date = isset($_GET['return_date']) && !empty($_GET['return_date']) ? clean_input($_GET['return_date']) : $departure_date;

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check if bus exists and is available
    $stmt = $conn->prepare("SELECT id, bus_name, plate_no, status FROM buses WHERE id = :bus_id AND (deleted = 0 OR deleted IS NULL)");
    $stmt->bindParam(':bus_id', $bus_id, PDO::PARAM_INT);
    $stmt->execute();
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        echo json_encode([
            'available' => false,
            'error' => 'Bus not found',
            'message' => 'This bus does not exist or has been removed'
        ]);
        exit;
    }
    
    if ($bus['status'] === 'unavailable') {
        echo json_encode([
            'available' => false,
            'bus_id' => $bus_id,
            'bus_name' => $bus['bus_name'],
            'message' => 'This bus is currently unavailable (disabled by admin)'
        ]);
        exit;
    }
    
    // Check for overlapping reservations
    // Two ranges overlap if: (Start1 <= End2) AND (End1 >= Start2)
    $sql = "
        SELECT 
            id,
            reservation_date,
            COALESCE(return_date, reservation_date) as effective_return,
            user_id,
            status
        FROM reservations 
        WHERE bus_id = :bus_id 
        AND status IN ('pending', 'approved')
        AND (
            (reservation_date <= :return_date)
            AND
            (COALESCE(return_date, reservation_date) >= :departure_date)
        )
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':bus_id', $bus_id, PDO::PARAM_INT);
    $stmt->bindParam(':departure_date', $departure_date, PDO::PARAM_STR);
    $stmt->bindParam(':return_date', $return_date, PDO::PARAM_STR);
    $stmt->execute();
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $is_available = (count($conflicts) === 0);
    
    // Build message
    if ($is_available) {
        $depart = new DateTime($departure_date);
        $return = new DateTime($return_date);
        $days = $depart->diff($return)->days;
        
        if ($departure_date === $return_date) {
            $message = "✅ Available for same-day trip on " . $depart->format('M d, Y');
        } else {
            $message = "✅ Available for your " . ($days + 1) . "-day trip (" . $depart->format('M d') . " to " . $return->format('M d') . ")";
        }
    } else {
        // Build conflict information
        $conflict_dates = [];
        foreach ($conflicts as $c) {
            if ($c['reservation_date'] === $c['effective_return']) {
                $conflict_dates[] = date('M d, Y', strtotime($c['reservation_date']));
            } else {
                $conflict_dates[] = date('M d', strtotime($c['reservation_date'])) . ' to ' . date('M d, Y', strtotime($c['effective_return']));
            }
        }
        
        $message = "❌ Already booked: " . implode(', ', $conflict_dates);
    }
    
    echo json_encode([
        'available' => $is_available,
        'bus_id' => $bus_id,
        'bus_name' => $bus['bus_name'],
        'date' => $departure_date,
        'return_date' => $return_date,
        'conflict_count' => count($conflicts),
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log("check_availability.php error: " . $e->getMessage());
    echo json_encode([
        'available' => false,
        'error' => 'System error',
        'message' => 'Error checking availability: ' . $e->getMessage()
    ]);
}
?>