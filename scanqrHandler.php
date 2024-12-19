<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

$actionType = isset($_GET['actionType']) ? $_GET['actionType'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle different action types for POST requests
    $data = json_decode(file_get_contents("php://input"), true);

    if ($actionType === 'fetchTicketInfo' && isset($data['referenceNo'])) {
        // Fetch ticket details based on REFERENCE_NO
        $referenceNo = $data['referenceNo'];

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("SELECT t.REFERENCE_NO, t.SCHED_ID, t.BUYER_ID, t.SEAT, t.QUANTITY, t.TOTAL, t.STATUS, i.BUYER_NAME
                                    FROM BUYER_TICKET t
                                    JOIN BUYER_INFO i ON t.BUYER_ID = i.BUYER_ID
                                    WHERE t.REFERENCE_NO = :referenceNo");
            $stmt->bindParam(':referenceNo', $referenceNo);
            $stmt->execute();

            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ticket) {
                echo json_encode([
                    "status" => "success",
                    "ticket" => $ticket
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Ticket not found"
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    } elseif ($actionType === 'updateTicketStatus' && isset($data['referenceNo']) && isset($data['status'])) {
        // Update ticket status to PAID
        $referenceNo = $data['referenceNo'];
        $status = $data['status'];

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("UPDATE BUYER_TICKET SET STATUS = :status WHERE REFERENCE_NO = :referenceNo");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':referenceNo', $referenceNo);
            $stmt->execute();

            echo json_encode([
                "status" => "success",
                "message" => "Ticket status updated to PAID."
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    }
    exit;
}

?>