<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]));
}

$teamId = isset($_GET['teamId']) ? intval($_GET['teamId']) : null;

if ($teamId === null) {
    echo json_encode(["status" => "error", "message" => "Team ID is required."]);
    exit;
}

// Query to fetch players already assigned to the team
$currentTeamPlayersQuery = "
    SELECT 
        p.PLAYER_ID, 
        CONCAT(p.FIRSTNAME, ' ', p.LASTNAME) AS PLAYER_NAME_INFO
    FROM 
        PLAYER_INFO p
    INNER JOIN 
        TEAM_PLAYER tp ON p.PLAYER_ID = tp.PLAYER_ID
    WHERE 
        tp.TEAM_ID = :teamId
";

// Query to fetch players who are not assigned to any team
$unassignedPlayersQuery = "
    SELECT 
        p.PLAYER_ID, 
        CONCAT(p.FIRSTNAME, ' ', p.LASTNAME) AS PLAYER_NAME_INFO
    FROM 
        PLAYER_INFO p
    LEFT JOIN 
        TEAM_PLAYER tp ON p.PLAYER_ID = tp.PLAYER_ID
    WHERE 
        tp.PLAYER_ID IS NULL
";

// Fetch players already assigned to the team
$stmtCurrentPlayers = $pdo->prepare($currentTeamPlayersQuery);
$stmtCurrentPlayers->bindParam(':teamId', $teamId, PDO::PARAM_INT);
$stmtCurrentPlayers->execute();
$currentPlayers = $stmtCurrentPlayers->fetchAll(PDO::FETCH_ASSOC);

// Fetch players who are not assigned to any team
$stmtUnassignedPlayers = $pdo->query($unassignedPlayersQuery);
$unassignedPlayers = $stmtUnassignedPlayers->fetchAll(PDO::FETCH_ASSOC);

// Merge the two lists: current team players and unassigned players
$allPlayers = array_merge($currentPlayers, $unassignedPlayers);

// Return the results as JSON
echo json_encode([
    "status" => "success",
    "players" => $allPlayers, // All players (both current and unassigned)
    "currentTeamPlayers" => $currentPlayers // Players already assigned to the team
]);
?>
