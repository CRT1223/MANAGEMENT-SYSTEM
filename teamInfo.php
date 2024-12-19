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

    if ($actionType === 'checkTeamName') {
        $teamName = $_POST['teamName'];
    
        $query = "SELECT COUNT(*) AS count FROM TEAM WHERE TEAM_NAME = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $teamName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        if ($row['count'] > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Team name already exists.']);
        } else {
            echo json_encode(['status' => 'success']);
        }
    } elseif ($actionType === 'fetchPlayers') {
        $query = "
            SELECT 
                PLAYER_ID, 
                CONCAT(FIRSTNAME, ' ', LASTNAME) AS PLAYER_NAME 
            FROM 
                PLAYER_INFO 
            WHERE 
                PLAYER_ID NOT IN (SELECT PLAYER_ID FROM TEAM_PLAYER)
        ";
        $result = $conn->query($query);
    
        if ($result->num_rows > 0) {
            $players = [];
            while ($row = $result->fetch_assoc()) {
                $players[] = $row;
            }
            echo json_encode(['status' => 'success', 'players' => $players]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No players found.']);
        }
    } elseif ($actionType === 'saveTeamInfo') {
        $teamName = $_POST['teamName'];
        $coachName = $_POST['coachName'];
        $selectedPlayers = json_decode($_POST['selectedPlayers']);
    
        // Handle file upload
        $teamLogoPath = null; // Default null if no image provided
        if (isset($_FILES['teamLogo']) && $_FILES['teamLogo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['teamLogo']['tmp_name'];
            $fileName = basename($_FILES['teamLogo']['name']);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
            // Generate unique file path based on team name
            $safeTeamName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $teamName); // Sanitize team name
            $uploadDir = 'logo/';
            $newFileName = $safeTeamName . '.' . $fileExtension;
            $teamLogoPath = $uploadDir . $newFileName;
    
            // Move the uploaded file to the designated folder
            if (!move_uploaded_file($fileTmpPath, $teamLogoPath)) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save team logo.']);
                exit;
            }
        }
    
        // Insert team info into TEAM table
        $query = "INSERT INTO TEAM (TEAM_NAME, TEAM_COACH_NAME, TEAM_LOGO_PATH) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $teamName, $coachName, $teamLogoPath);
        $stmt->execute();
        $teamId = $stmt->insert_id; // Get the inserted team's ID
        $stmt->close();
    
        // Insert players into TEAM_PLAYER table
        foreach ($selectedPlayers as $playerId) {
            $query = "INSERT INTO TEAM_PLAYER (TEAM_ID, PLAYER_ID) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $teamId, $playerId);
            $stmt->execute();
        }
    
        echo json_encode(['status' => 'success']);
    }    
}

// Close the database connection
$conn->close();
?>
