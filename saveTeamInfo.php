<?php
$servername = "localhost"; 
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

// Get the action type from POST data
$actionType = $_POST['actionType'];

if ($actionType === 'saveTeamInfo') {
    // Get POST data for saveTeamInfo
    $teamId = $_POST['teamId'];
    $teamName = $_POST['teamName'];
    $coachName = $_POST['coachName'];
    $selectedPlayers = json_decode($_POST['selectedPlayers'], true);

    $logoPath = null;

    // Check if an image was uploaded
    if (isset($_FILES['teamLogo']) && $_FILES['teamLogo']['error'] === 0) {
        // Define upload directory and file name
        $uploadDir = 'logo/';
        $imageExtension = pathinfo($_FILES['teamLogo']['name'], PATHINFO_EXTENSION);
        $imageName = $teamName . '.' . $imageExtension;
        $logoPath = $uploadDir . $imageName;

        // Delete existing logo if it exists
        $existingLogoQuery = "SELECT TEAM_LOGO_PATH FROM TEAM WHERE TEAM_ID = ?";
        $stmt = $conn->prepare($existingLogoQuery);
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingLogo = $result->fetch_assoc()['TEAM_LOGO_PATH'];

        if ($existingLogo && file_exists($existingLogo)) {
            unlink($existingLogo);
        }

        // Move the uploaded file to the 'logo' directory
        if (!move_uploaded_file($_FILES['teamLogo']['tmp_name'], $logoPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded image.']);
            exit;
        }
    }

    // Start SQL transaction
    $conn->begin_transaction();

    try {
        // Update team name and coach
        $updateQuery = "UPDATE TEAM SET TEAM_NAME = ?, TEAM_COACH_NAME = ? WHERE TEAM_ID = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $teamName, $coachName, $teamId);
        $stmt->execute();

        // Update logo path if an image was uploaded
        if ($logoPath) {
            $updateLogoQuery = "UPDATE TEAM SET TEAM_LOGO_PATH = ? WHERE TEAM_ID = ?";
            $stmt = $conn->prepare($updateLogoQuery);
            $stmt->bind_param("si", $logoPath, $teamId);
            $stmt->execute();
        }

        // Fetch all existing players for the team
        $existingPlayersQuery = "SELECT PLAYER_ID FROM TEAM_PLAYER WHERE TEAM_ID = ?";
        $stmt = $conn->prepare($existingPlayersQuery);
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $result = $stmt->get_result();

        $existingPlayers = [];
        while ($row = $result->fetch_assoc()) {
            $existingPlayers[] = $row['PLAYER_ID'];
        }

        // Identify players to delete (in database but not in selectedPlayers)
        $playersToDelete = array_diff($existingPlayers, $selectedPlayers);
        if (!empty($playersToDelete)) {
            $deleteQuery = "DELETE FROM TEAM_PLAYER WHERE TEAM_ID = ? AND PLAYER_ID = ?";
            $stmt = $conn->prepare($deleteQuery);
            foreach ($playersToDelete as $playerIdToDelete) {
                $stmt->bind_param("ii", $teamId, $playerIdToDelete);
                $stmt->execute();
            }
        }

        // Insert new players (in selectedPlayers but not in database)
        $playersToAdd = array_diff($selectedPlayers, $existingPlayers);
        if (!empty($playersToAdd)) {
            $insertQuery = "INSERT INTO TEAM_PLAYER (TEAM_ID, PLAYER_ID) VALUES (?, ?)";
            $stmt = $conn->prepare($insertQuery);
            foreach ($playersToAdd as $playerIdToAdd) {
                $stmt->bind_param("ii", $teamId, $playerIdToAdd);
                $stmt->execute();
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} elseif ($actionType === 'savePlayerPositions') {
    // Get POST data for savePlayerPositions
    $teamId = $_POST['teamId'];
    $playerPositions = json_decode($_POST['playerPositions'], true);

    try {
        // Update player positions in TEAM_PLAYER table
        foreach ($playerPositions as $player) {
            $playerId = $player['player_id'];
            $position = $player['position'];

            $updatePositionQuery = "UPDATE TEAM_PLAYER SET PLAYER_POSITION = ? WHERE TEAM_ID = ? AND PLAYER_ID = ?";
            $stmt = $conn->prepare($updatePositionQuery);
            $stmt->bind_param("sii", $position, $teamId, $playerId);
            $stmt->execute();
        }

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action type']);
}
?>
