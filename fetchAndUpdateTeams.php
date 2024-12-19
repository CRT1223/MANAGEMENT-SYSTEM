<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch team names
        $stmt = $conn->prepare("SELECT TEAM_NAME FROM TEAM");
        $stmt->execute();
        $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(["status" => "success", "teams" => $teams]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update currently playing match
        $data = json_decode(file_get_contents("php://input"), true);

        $teamLeft = $data['teamLeft'];
        $teamRight = $data['teamRight'];

        // Validate inputs
        if (empty($teamLeft) || empty($teamRight)) {
            echo json_encode(["status" => "error", "message" => "Teams cannot be empty"]);
            exit;
        }

        // Fetch TEAM_ID for both teams based on TEAM_NAME
        $stmt = $conn->prepare("SELECT TEAM_ID FROM TEAM WHERE TEAM_NAME = ?");
        $stmt->execute([$teamLeft]);
        $teamLeftId = $stmt->fetchColumn();

        $stmt->execute([$teamRight]);
        $teamRightId = $stmt->fetchColumn();

        if (!$teamLeftId || !$teamRightId) {
            echo json_encode(["status" => "error", "message" => "One or both teams not found"]);
            exit;
        }

        // Update the CURRENTLY_PLAYING table with the TEAM_IDs of the teams
        $stmt = $conn->prepare("UPDATE CURRENTLY_PLAYING SET TEAM_1 = ?, TEAM_2 = ? WHERE id = 1");
        $stmt->execute([$teamLeftId, $teamRightId]);

        echo json_encode(["status" => "success", "message" => "Match updated successfully"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
