<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

header('Content-Type: application/json');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] === 'getMatches') {
            if (isset($_GET['id'])) {
                // Fetch a specific match by ID and join with TEAM table
                $stmt = $conn->prepare("
                    SELECT gs.GAME_SCORE_ID, t1.TEAM_NAME AS TEAM_1_NAME, gs.TEAM_1_SCORE, t2.TEAM_NAME AS TEAM_2_NAME, gs.TEAM_2_SCORE
                    FROM GAME_SCORE gs
                    JOIN TEAM t1 ON gs.TEAM_1 = t1.TEAM_ID
                    JOIN TEAM t2 ON gs.TEAM_2 = t2.TEAM_ID
                    WHERE gs.GAME_SCORE_ID = ?
                ");
                $stmt->execute([$_GET['id']]);
                $match = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($match) {
                    echo json_encode(["status" => "success", "match" => $match]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Match not found."]);
                }
            } else {
                // Fetch all matches and join with TEAM table
                $stmt = $conn->prepare("
                    SELECT gs.GAME_SCORE_ID, t1.TEAM_NAME AS TEAM_1, gs.TEAM_1_SCORE, t2.TEAM_NAME AS TEAM_2, gs.TEAM_2_SCORE,
                    t1.TEAM_LOGO_PATH AS TEAM1_LOGO, t2.TEAM_LOGO_PATH AS TEAM2_LOGO 
                    FROM GAME_SCORE gs
                    JOIN TEAM t1 ON gs.TEAM_1 = t1.TEAM_ID
                    JOIN TEAM t2 ON gs.TEAM_2 = t2.TEAM_ID
                ");
                $stmt->execute();
                $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(["status" => "success", "matches" => $matches]);
            }
        } else {
            // Fetch team names
            $stmt = $conn->prepare("SELECT TEAM_NAME FROM TEAM");
            $stmt->execute();
            $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(["status" => "success", "teams" => $teams]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save new match or update existing match
        $data = json_decode(file_get_contents("php://input"), true);

        $teamLeft = $data['teamLeft'];
        $teamRight = $data['teamRight'];
        $scoreLeft = $data['scoreLeft'];
        $scoreRight = $data['scoreRight'];
        $matchId = $data['matchId']; // If `matchId` is provided, update the match

        // Validate input
        if (empty($teamLeft) || empty($teamRight) || $scoreLeft === "" || $scoreRight === "") {
            echo json_encode(["status" => "error", "message" => "All fields are required."]);
            exit;
        }

        // Fetch TEAM_ID for teamLeft and teamRight
        $stmt = $conn->prepare("SELECT TEAM_ID FROM TEAM WHERE TEAM_NAME = ?");
        $stmt->execute([$teamLeft]);
        $teamLeftId = $stmt->fetchColumn();

        $stmt->execute([$teamRight]);
        $teamRightId = $stmt->fetchColumn();

        if (!$teamLeftId || !$teamRightId) {
            echo json_encode(["status" => "error", "message" => "One or both team names not found."]);
            exit;
        }

        if ($matchId) {
            // Update an existing match
            $stmt = $conn->prepare("
                UPDATE GAME_SCORE
                SET TEAM_1 = ?, TEAM_1_SCORE = ?, TEAM_2 = ?, TEAM_2_SCORE = ?
                WHERE GAME_SCORE_ID = ?
            ");
            $stmt->execute([$teamLeftId, $scoreLeft, $teamRightId, $scoreRight, $matchId]);
            echo json_encode(["status" => "success", "message" => "Match updated successfully."]);
        } else {
            // Insert new match
            $stmt = $conn->prepare("
                INSERT INTO GAME_SCORE (TEAM_1, TEAM_1_SCORE, TEAM_2, TEAM_2_SCORE)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$teamLeftId, $scoreLeft, $teamRightId, $scoreRight]);
            echo json_encode(["status" => "success", "message" => "Match added successfully."]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (isset($_GET['action']) && $_GET['action'] === 'deleteMatch') {
            $matchId = isset($_GET['id']) ? $_GET['id'] : null;
            if ($matchId === null) {
                echo json_encode(["status" => "error", "message" => "Match ID is required for deletion."]);
                exit;
            }

            if ($matchId) {
                // Delete the match
                $stmt = $conn->prepare("DELETE FROM GAME_SCORE WHERE GAME_SCORE_ID = ?");
                $stmt->execute([$matchId]);

                if ($stmt->rowCount() > 0) {
                    echo json_encode(["status" => "success", "message" => "Match deleted successfully."]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Match ID is required for deletion."]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
