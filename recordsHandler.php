<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['actionType'] ?? '';

    if ($actionType === 'getRecords') {
        // SQL query to join the tables and fetch required columns
        $query = "
            SELECT
                BT.REFERENCE_NO, BI.BUYER_NAME,
                DATE_FORMAT(S.SCHED_DATE, '%Y-%m-%d') AS SCHEDULE_DATE,
                TIME_FORMAT(S.SCHED_TIME, '%h:%i %p') AS SCHEDULE_TIME,
                S.EVENT_NAME, BT.SEAT, BT.QUANTITY, BT.TOTAL
            FROM BUYER_TICKET BT
            JOIN BUYER_INFO BI ON BT.BUYER_ID = BI.BUYER_ID
            JOIN SCHEDULE S ON BT.SCHED_ID = S.SCHED_ID
            ORDER BY REFERENCE_NO, S.SCHED_DATE DESC, S.SCHED_TIME DESC, 
                     BI.BUYER_NAME, SEAT, QUANTITY, TOTAL, DATE_ADDED DESC;
        ";
    
        $result = $conn->query($query);
    
        if ($result->num_rows > 0) {
            $records = [];
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            echo json_encode(['status' => 'success', 'records' => $records]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No records found.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action type.']);
    }
}

// Close the database connection
$conn->close();
?>
