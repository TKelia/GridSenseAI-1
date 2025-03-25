<?php
require_once 'config.php';

// Set headers for CORS and JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$conn = getDBConnection();
if (!$conn) {
    jsonResponse(['success' => false, 'error' => 'Database connection failed']);
}

// Router for different endpoints
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'current':
        getCurrentUsage($conn);
        break;
    case 'daily':
        getDailyUsage($conn);
        break;
    case 'devices':
        getDevices($conn);
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action']);
}

// Get current power usage for all devices
function getCurrentUsage($conn) {
    try {
        $query = "
            SELECT d.device_name, d.device_type, 
                   COALESCE(pr.power_usage, 0) as power_usage,
                   COALESCE(pr.voltage, 0) as voltage,
                   COALESCE(pr.current, 0) as current,
                   pr.timestamp
            FROM devices d
            LEFT JOIN (
                SELECT device_id, power_usage, voltage, current, timestamp
                FROM power_readings
                WHERE timestamp >= NOW() - INTERVAL 5 MINUTE
                ORDER BY timestamp DESC
                LIMIT 1
            ) pr ON d.device_id = pr.device_id
            WHERE d.user_id = 1
            AND d.status = 'active'";

        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception($conn->error);
        }

        $devices = [];
        while ($row = $result->fetch_assoc()) {
            $devices[] = [
                'name' => $row['device_name'],
                'type' => $row['device_type'],
                'power' => floatval($row['power_usage']),
                'voltage' => floatval($row['voltage']),
                'current' => floatval($row['current']),
                'timestamp' => $row['timestamp']
            ];
        }

        jsonResponse([
            'success' => true,
            'data' => [
                'devices' => $devices,
                'total_power' => array_sum(array_column($devices, 'power'))
            ]
        ]);

    } catch (Exception $e) {
        return handleDBError($conn, $e->getMessage());
    }
}

// Get daily power usage summary
function getDailyUsage($conn) {
    try {
        $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
        $days = min(max($days, 1), 30); // Limit between 1 and 30 days

        $query = "
            SELECT 
                DATE(ds.date) as date,
                SUM(ds.total_usage) as total_usage,
                MAX(ds.peak_usage) as peak_usage,
                AVG(ds.average_usage) as average_usage
            FROM daily_summaries ds
            INNER JOIN devices d ON ds.device_id = d.device_id
            WHERE d.user_id = 1
            AND ds.date >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
            GROUP BY DATE(ds.date)
            ORDER BY ds.date DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            throw new Exception($conn->error);
        }

        $usage = [];
        while ($row = $result->fetch_assoc()) {
            $usage[] = [
                'date' => $row['date'],
                'total' => floatval($row['total_usage']),
                'peak' => floatval($row['peak_usage']),
                'average' => floatval($row['average_usage'])
            ];
        }

        jsonResponse([
            'success' => true,
            'data' => $usage
        ]);

    } catch (Exception $e) {
        return handleDBError($conn, $e->getMessage());
    }
}

// Get list of user's devices
function getDevices($conn) {
    try {
        $query = "
            SELECT 
                d.device_id,
                d.device_name,
                d.device_type,
                d.max_power_rating,
                d.status,
                COALESCE(ds.total_usage, 0) as today_usage
            FROM devices d
            LEFT JOIN daily_summaries ds ON d.device_id = ds.device_id
                AND ds.date = CURRENT_DATE
            WHERE d.user_id = 1
            ORDER BY d.device_name";

        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception($conn->error);
        }

        $devices = [];
        while ($row = $result->fetch_assoc()) {
            $devices[] = [
                'id' => intval($row['device_id']),
                'name' => $row['device_name'],
                'type' => $row['device_type'],
                'max_power' => floatval($row['max_power_rating']),
                'status' => $row['status'],
                'today_usage' => floatval($row['today_usage'])
            ];
        }

        jsonResponse([
            'success' => true,
            'data' => $devices
        ]);

    } catch (Exception $e) {
        return handleDBError($conn, $e->getMessage());
    }
}

function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

function handleDBError($conn, $error) {
    $conn->close();
    jsonResponse(['success' => false, 'error' => $error]);
}
?>
