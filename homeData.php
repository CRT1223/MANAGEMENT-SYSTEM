<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

header('Content-Type: application/json');

try {
    // Use PDO for database connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action']) && $_GET['action'] == 'getMatches') {
            // SQL query to fetch the latest matches with join to GAME_SCORE, TEAM, and SCHEDULE
            $sql = "
                SELECT gs.GAME_SCORE_ID, gs.TEAM_1, gs.TEAM_1_SCORE, gs.TEAM_2, gs.TEAM_2_SCORE, 
                       t1.TEAM_NAME AS TEAM1_NAME, t1.TEAM_LOGO_PATH AS TEAM1_LOGO_PATH, 
                       t2.TEAM_NAME AS TEAM2_NAME, t2.TEAM_LOGO_PATH AS TEAM2_LOGO_PATH, 
                       s.EVENT_NAME, s.SCHED_DATE, s.SCHED_TIME
                FROM GAME_SCORE gs
                LEFT JOIN TEAM t1 ON gs.TEAM_1 = t1.TEAM_ID
                LEFT JOIN TEAM t2 ON gs.TEAM_2 = t2.TEAM_ID
                LEFT JOIN SCHEDULE s ON gs.SCHED_ID = s.SCHED_ID
                ORDER BY s.SCHED_DATE DESC
                LIMIT 3"; // Get the latest 3 matches

            // Execute the query
            $stmt = $conn->prepare($sql);
            $stmt->execute();

            // Fetch the results
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Return the matches as JSON
            echo json_encode($matches);
        } else {
            // Invalid action
            echo json_encode(["status" => "error", "message" => "Invalid action."]);
        }
    } else {
        // Invalid request method
        echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    }
} catch (PDOException $e) {
    // Catch database connection errors
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
