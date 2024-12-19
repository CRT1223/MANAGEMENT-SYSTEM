<?php
$servername = "localhost"; 
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()])); 
}

// Check for teamId in the request
$teamId = isset($_GET['teamId']) ? intval($_GET['teamId']) : null;

// Fetch all players excluding players already associated with different
$playersQuery = "
    SELECT 
        PLAYER_ID, 
        CONCAT(FIRSTNAME, ' ', LASTNAME) AS PLAYER_NAME_INFO 
    FROM 
        PLAYER_INFO p
    WHERE 
        PLAYER_ID NOT IN (
            SELECT PLAYER_ID 
            FROM TEAM_PLAYER tp 
            WHERE tp.TEAM_ID != :teamId
        )
    ORDER BY 
        LASTNAME
";

// Query to fetch team-specific players
$query = "
    SELECT 
        t.TEAM_ID, 
        t.TEAM_NAME, 
        t.TEAM_LOGO_PATH, 
        t.TEAM_COACH_NAME,
        p.PLAYER_ID, 
        p.FIRSTNAME, 
        p.LASTNAME, 
        tp.PLAYER_POSITION, 
        CONCAT(p.FIRSTNAME, ' ', p.LASTNAME) AS PLAYER_NAME
    FROM 
        TEAM t
    LEFT JOIN 
        TEAM_PLAYER tp ON tp.TEAM_ID = t.TEAM_ID
    LEFT JOIN 
        PLAYER_INFO p ON tp.PLAYER_ID = p.PLAYER_ID
    ";

// Filter by teamId if provided
if ($teamId !== null) {
    $query .= " WHERE t.TEAM_ID = :teamId";
}

$query .= " ORDER BY t.TEAM_NAME, p.LASTNAME";

// Prepare and execute the updated players query
$stmtPlayers = $pdo->prepare($playersQuery);
$stmtPlayers->bindValue(':teamId', $teamId !== null ? $teamId : 0, PDO::PARAM_INT); // Use 0 if no teamId is provided
$stmtPlayers->execute();
$allPlayers = $stmtPlayers->fetchAll(PDO::FETCH_ASSOC);

// Fetch the team data
$stmt = $pdo->prepare($query);
if ($teamId !== null) {
    $stmt->bindParam(':teamId', $teamId, PDO::PARAM_INT);
}
$stmt->execute();

// Fetch the data for teams
$teamsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process the data into a more usable format
$teams = [];
foreach ($teamsData as $row) {
    $teamId = $row['TEAM_ID'];
    
    // Initialize the team if not already in the array
    if (!isset($teams[$teamId])) {
        $teams[$teamId] = [
            'name' => $row['TEAM_NAME'],
            'logo' => $row['TEAM_LOGO_PATH'],
            'coach' => $row['TEAM_COACH_NAME'],
            'players' => []
        ];
    }

    // Add player data to the team
    if ($row['PLAYER_ID']) {
        $teams[$teamId]['players'][] = [
            'player_id' => $row['PLAYER_ID'],
            'name' => $row['PLAYER_NAME'],
            'position' => $row['PLAYER_POSITION']
        ];
    }
}

// Return both the teams and filtered players as JSON
$response = ['status' => 'success', 'teams' => $teams, 'allPlayers' => $allPlayers];
if ($teamId !== null) {
    // If filtering by teamId, return only the requested team
    $response['team'] = $teams[$teamId] ?? null;
}
echo json_encode($response);
?>