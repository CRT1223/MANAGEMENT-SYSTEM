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

    if ($actionType === 'fetchSchedule') {
        // Fetch schedule data from the SCHEDULE table, join with TEAM to get team names
        $query = "
            SELECT SCHED_ID, EVENT_NAME, SCHED_DATE, SCHED_TIME, 
                T1.TEAM_NAME AS TEAM_1_NAME, T2.TEAM_NAME AS TEAM_2_NAME
            FROM SCHEDULE S
            JOIN TEAM T1 ON S.TEAM_1 = T1.TEAM_ID
            JOIN TEAM T2 ON S.TEAM_2 = T2.TEAM_ID
        ";

        $result = $conn->query($query);

        $scheduleData = [];

        while ($row = $result->fetch_assoc()) {
            $scheduleData[] = [
                'schedId' => $row['SCHED_ID'],
                'eventName' => $row['EVENT_NAME'],
                'team1Name' => $row['TEAM_1_NAME'],
                'team2Name' => $row['TEAM_2_NAME'],
                'schedDate' => $row['SCHED_DATE'],
                'schedTime' => $row['SCHED_TIME'],
            ];
        }

        echo json_encode($scheduleData);

    } elseif ($actionType === 'saveBuyerTicket') {
        // Collect data from POST
        $name = $_POST['name'];
        $email = $_POST['email'];
        $schedId = $_POST['schedId'];
        $seat = $_POST['seat']; // e.g., "Upperbox"
        $quantity = $_POST['quantity'];
        $total = $_POST['total'];

        // Generate unique reference number
        do {
            $referenceNo = str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
            $query = $conn->prepare("SELECT COUNT(*) FROM BUYER_TICKET WHERE REFERENCE_NO = ?");
            $query->bind_param('s', $referenceNo);
            $query->execute();
            $query->bind_result($count);
            $query->fetch();
            $query->close();
        } while ($count > 0);

        // Save to BUYER_INFO
        $query = $conn->prepare("INSERT INTO BUYER_INFO (BUYER_NAME, EMAIL) VALUES (?, ?)");
        $query->bind_param('ss', $name, $email);
        if ($query->execute()) {
            $buyerId = $query->insert_id; // Get the auto-generated BUYER_ID
            $query->close();

            // Save to BUYER_TICKET
            $query = $conn->prepare("INSERT INTO BUYER_TICKET (REFERENCE_NO, SCHED_ID, BUYER_ID, SEAT, QUANTITY, TOTAL) VALUES (?, ?, ?, ?, ?, ?)");
            $query->bind_param('siisii', $referenceNo, $schedId, $buyerId, $seat, $quantity, $total);
            if ($query->execute()) {
                $query->close();

                // Success response
                echo json_encode([
                    "status" => "success",
                    "referenceNo" => $referenceNo
                ]);
                exit;
            }
        }

        // Error response
        echo json_encode(["status" => "error", "message" => "Failed to save buyer ticket."]);
    }
}

// Close the database connection
$conn->close();
?>
