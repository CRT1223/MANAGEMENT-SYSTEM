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

    if ($actionType === 'fetchPlayers') {
        $query = "SELECT PI.PLAYER_ID, PI.FIRSTNAME, PI.LASTNAME, T.TEAM_NAME AS PLAYER_TEAM 
                  FROM PLAYER_INFO PI LEFT JOIN TEAM_PLAYER TP ON PI.PLAYER_ID = TP.PLAYER_ID
                  LEFT JOIN TEAM T ON T.TEAM_ID = TP.TEAM_ID";
        $result = $conn->query($query);
        $players = [];
        while ($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
        echo json_encode(['status' => 'success', 'players' => $players]);
    
    } elseif ($actionType === 'getPlayerDetails') {
        $playerId = $_POST['playerId'];
        $query = "SELECT PLAYER_ID, FIRSTNAME, LASTNAME FROM PLAYER_INFO WHERE PLAYER_ID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['status' => 'success', 'player' => $result]);
    
    } elseif ($actionType === 'addPlayer') {
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
    
        $checkQuery = "SELECT COUNT(*) AS COUNT FROM PLAYER_INFO WHERE FIRSTNAME = ? AND LASTNAME = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ss", $firstName, $lastName);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['COUNT'];
    
        if ($count > 0) {
            echo json_encode(['status' => 'error', 'message' => 'The Player Name already added.']);
        } else {
            $insertQuery = "INSERT INTO PLAYER_INFO (FIRSTNAME, LASTNAME) VALUES (?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ss", $firstName, $lastName);
            $stmt->execute();
            echo json_encode(['status' => 'success']);
        }
    
    } elseif ($actionType === 'editPlayer') {
        $playerId = $_POST['playerId'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
    
        $checkQuery = "SELECT COUNT(*) AS COUNT FROM PLAYER_INFO WHERE FIRSTNAME = ? AND LASTNAME = ? AND PLAYER_ID != ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ssi", $firstName, $lastName, $playerId);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['COUNT'];
    
        if ($count > 0) {
            echo json_encode(['status' => 'error', 'message' => 'The Player Info already belongs to someone else. Please provide other name.']);
        } else {
            $updateQuery = "UPDATE PLAYER_INFO SET FIRSTNAME = ?, LASTNAME = ? WHERE PLAYER_ID = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssi", $firstName, $lastName, $playerId);
            $stmt->execute();
            echo json_encode(['status' => 'success']);
        }
    
    } elseif ($actionType === 'deletePlayer') {
        $playerId = $_POST['playerId'];
    
        $deleteTeamPlayerQuery = "DELETE FROM TEAM_PLAYER WHERE PLAYER_ID = ?";
        $stmt = $conn->prepare($deleteTeamPlayerQuery);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
    
        $deletePlayerQuery = "DELETE FROM PLAYER_INFO WHERE PLAYER_ID = ?";
        $stmt = $conn->prepare($deletePlayerQuery);
        $stmt->bind_param("i", $playerId);
        $stmt->execute();
    
        echo json_encode(['status' => 'success']);
    }
}

// Close the database connection
$conn->close();
?>
