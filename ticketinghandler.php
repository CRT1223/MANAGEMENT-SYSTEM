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

    if ($actionType === 'getFilteredEvents') {
        $month = $_POST['month'] ?? null;
        $year = $_POST['year'] ?? null;
    
        if ($month && $year) {
            $query = "
                SELECT S.SCHED_ID, CONCAT(S.EVENT_NAME, ' - ', DATE_FORMAT(S.SCHED_DATE, '%m/%d/%Y'), ', ', TIME_FORMAT(S.SCHED_TIME, '%h:%i %p')) AS EVENT_DISPLAY
                FROM SCHEDULE S
                WHERE MONTH(S.SCHED_DATE) = ? AND YEAR(S.SCHED_DATE) = ?
            ";
    
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $month, $year);
            $stmt->execute();
            $result = $stmt->get_result();
    
            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
    
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'events' => $events]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid month or year']);
        }
    }
     elseif ($actionType === 'getTableData') {
        // Fetch table data based on filters
        $schedId = intval($_POST['schedId']);

        $query = "
            SELECT T.REFERENCE_NO, I.BUYER_NAME
            FROM BUYER_TICKET T
            JOIN BUYER_INFO I ON T.BUYER_ID = I.BUYER_ID
            JOIN SCHEDULE S ON T.SCHED_ID = S.SCHED_ID
            WHERE S.SCHED_ID = ? AND T.SEAT IN ('Upperbox', 'Lowerbox') AND T.STATUS = 'PAID'
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $schedId);
        $stmt->execute();
        $result = $stmt->get_result();

        $tableData = [];
        while ($row = $result->fetch_assoc()) {
            $tableData[] = $row;
        }

        echo json_encode(['status' => 'success', 'tableData' => $tableData]);
    } elseif ($actionType === 'getTicketCounts') {
        // Get the sched_id from the request
        $schedId = isset($_POST['schedId']) ? $_POST['schedId'] : '';
    
        // If sched_id is empty, return an empty result
        if ($schedId === 0) {
            echo json_encode(['status' => 'success', 'counts' => ['Upperbox' => 0, 'Lowerbox' => 0, 'TOTAL' => 0]]);
            exit;
        }
    
        // Query to count the tickets for Upperbox and Lowerbox based on sched_id
        $query = "
            SELECT T.SEAT, COUNT(*) AS COUNT
            FROM BUYER_TICKET T
            WHERE T.SCHED_ID = ? AND T.SEAT IN ('Upperbox', 'Lowerbox') AND T.STATUS = 'PAID'
            GROUP BY T.SEAT
        ";
    
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $schedId);
        $stmt->execute();
        $result = $stmt->get_result();
    
        // Initialize the counts for UPPER BOX and LOWER BOX
        $counts = ['Upperbox' => 0, 'Lowerbox' => 0, 'TOTAL' => 0];
    
        while ($row = $result->fetch_assoc()) {
            $counts[$row['SEAT']] = $row['COUNT']; // Count for UPPER BOX or LOWER BOX
            $counts['TOTAL'] += $row['COUNT']; // Total count
        }
    
        echo json_encode(['status' => 'success', 'counts' => $counts]);    
    } elseif ($actionType === 'getSelectedRowDetails') {
        // Fetch row details for the lower section
        $referenceNo = $conn->real_escape_string($_POST['referenceNo']);

        $query = "
            SELECT I.BUYER_NAME, S.EVENT_NAME, T1.TEAM_NAME AS TEAM_1, T2.TEAM_NAME AS TEAM_2, T.SEAT, T.QUANTITY, T.TOTAL
            FROM BUYER_TICKET T
            JOIN BUYER_INFO I ON T.BUYER_ID = I.BUYER_ID
            JOIN SCHEDULE S ON T.SCHED_ID = S.SCHED_ID
            JOIN TEAM T1 ON S.TEAM_1 = T1.TEAM_ID
            JOIN TEAM T2 ON S.TEAM_2 = T2.TEAM_ID
            WHERE T.REFERENCE_NO = ? AND T.SEAT IN ('Upperbox', 'Lowerbox') AND T.STATUS = 'PAID'
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $referenceNo);
        $stmt->execute();
        $result = $stmt->get_result();

        $details = $result->fetch_assoc();
        if ($details) {
            echo json_encode(['status' => 'success', 'details' => $details]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No details found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action type']);
    }
}

// Close the database connection
$conn->close();
?>
