<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

$actionType = $_GET['actiontype'] ?? $_POST['actiontype'] ?? null;

if ($actionType === 'fetchTeams') {
    $response = ['status' => 'success', 'matches' => [], 'message' => ''];

    $teamQuery = "SELECT TEAM_ID, TEAM_NAME, TEAM_LOGO_PATH, TEAM_COACH_NAME FROM TEAM";
    $teamResult = $conn->query($teamQuery);

    if ($teamResult && $teamResult->num_rows > 0) {
        $response['teams'] = $teamResult->fetch_all(MYSQLI_ASSOC);
    } else {
        $response['status'] = 'error';
        $response['message'] = 'No teams found.';
    }

    // Fetch Quarter Finals matches
    $matchQuery = "SELECT bm.MATCHES_ID, bm.ROUND_STATUS, 
                          bm.TEAM1, t1.TEAM_NAME AS TEAM1_NAME,
                          bm.TEAM2, t2.TEAM_NAME AS TEAM2_NAME,
                          bm.WINNER, w.TEAM_NAME AS WINNER_TEAM_NAME
                   FROM BRACKETING_MATCHES bm
                   LEFT JOIN TEAM t1 ON bm.TEAM1 = t1.TEAM_ID
                   LEFT JOIN TEAM t2 ON bm.TEAM2 = t2.TEAM_ID
                   LEFT JOIN TEAM w ON bm.WINNER = w.TEAM_ID
                   ORDER BY bm.MATCHES_ID";
    $matchResult = $conn->query($matchQuery);

    if ($matchResult && $matchResult->num_rows > 0) {
        $response['matches'] = $matchResult->fetch_all(MYSQLI_ASSOC);
    } else {
        $response['status'] = 'error';
        $response['message'] = 'No Quarter Finals matches found.';
    }

    // Output the response as JSON
    echo json_encode($response);
} elseif ($actionType === 'updateMatches') {
    try {
        $conn->begin_transaction();

        // Define round-to-MATCHES_ID mapping
        $roundMatchMapping = [
            'QUARTER FINALS' => [1, 2, 3, 4],
            'SEMI FINALS' => [5, 6],
            'FINALS' => [7],
            'CHAMPION' => [7], // CHAMPION also references MATCHES_ID 7
        ];

        // Ensure the round is valid
        if (!isset($_POST['round']) || !isset($roundMatchMapping[$_POST['round']])) {
            echo json_encode(['success' => false, 'message' => 'Invalid round specified.']);
            exit;
        }

        $round = $_POST['round'];
        $validMatches = $roundMatchMapping[$round];

        // Quarter validation: Check if TEAM1, TEAM2 already exist with a different MATCH_ID
        if ($round === 'QUARTER FINALS') {
            // Loop through POST data and update relevant matches
            foreach ($_POST as $key => $value) {
                if (preg_match('/(TEAM1|TEAM2)_(\d+)/', $key, $matches)) {
                    $matchId = (int)$matches[2];
                    $teamField = $matches[1];

                    // Skip if MATCHES_ID is not valid for the current round
                    if (!in_array($matchId, $validMatches)) {
                        continue;
                    }

                    // Update the specific field for the MATCHES_ID
                    $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET $teamField = ? WHERE MATCHES_ID = ?");
                    $stmt->bind_param("ii", $value, $matchId);
                    if (!$stmt->execute()) {
                        throw new Exception("Error updating MATCHES_ID $matchId: " . $stmt->error);
                    }
                }
            }

            // Clear WINNER for all matches
            $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = NULL");
            if (!$stmt->execute()) {
                throw new Exception("Error clearing WINNER fields: " . $stmt->error);
            }

            // Clear TEAM1 and TEAM2 for SEMI FINALS and FINALS
            $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET TEAM1 = NULL, TEAM2 = NULL WHERE ROUND_STATUS IN ('SEMI FINALS', 'FINALS')");
            if (!$stmt->execute()) {
                throw new Exception("Error clearing TEAM1 and TEAM2 for later rounds: " . $stmt->error);
            }
        }

        // Loop through POST data and update relevant matches
        foreach ($_POST as $key => $value) {
            if (preg_match('/(TEAM1|TEAM2)_(\d+)/', $key, $matches)) {
                $matchId = (int)$matches[2];
                $teamField = $matches[1];

                // Skip if MATCHES_ID is not valid for the current round
                if (!in_array($matchId, $validMatches)) {
                    continue;
                }

                // Update the specific field for the MATCHES_ID
                $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET $teamField = ? WHERE MATCHES_ID = ?");
                $stmt->bind_param("ii", $value, $matchId);
                if (!$stmt->execute()) {
                    throw new Exception("Error updating MATCHES_ID $matchId: " . $stmt->error);
                }
                
            }
        }

        // Handle SEMI FINALS updates (MATCHES_ID = 5, 6)
        if ($round === 'SEMI FINALS') {
            foreach ([5, 6] as $matchId) {
                $team1 = $_POST["TEAM1_$matchId"] ?? null;
                $team2 = $_POST["TEAM2_$matchId"] ?? null;
                $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET TEAM1 = ?, TEAM2 = ?, WINNER = NULL WHERE MATCHES_ID = ?");
                $stmt->bind_param("iii", $team1, $team2, $matchId);
                $stmt->execute();
            }
            $team1SF1 = $_POST['TEAM1_5'] ?? null;
            $team2SF1 = $_POST['TEAM2_5'] ?? null;
            $team1SF2 = $_POST['TEAM1_6'] ?? null;
            $team2SF2 = $_POST['TEAM2_6'] ?? null;

            if ($team1SF1) {
                // Update WINNER for SEMI FINALS MATCHES_ID: 1 and 2
                $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = ? WHERE MATCHES_ID = 1");
                $stmt->bind_param("i", $team1SF1);
                $stmt->execute();
            } 
            if ($team2SF1){
                $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = ? WHERE MATCHES_ID = 2");
                $stmt->bind_param("i", $team2SF1);
                $stmt->execute();
            }
                
            if ($team1SF2) {
                // Update WINNER for SEMI FINALS MATCHES_ID: 3 and 4
                $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = ? WHERE MATCHES_ID = 3");
                $stmt->bind_param("i", $team1SF2);
                $stmt->execute();
            }
            if ($team2SF2){
                $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = ? WHERE MATCHES_ID = 4");
                $stmt->bind_param("i", $team2SF2);
                $stmt->execute();
            }   
            
            $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET TEAM1 = NULL, TEAM2 = NULL, WINNER = NULL WHERE ROUND_STATUS = 'FINALS'");
            $stmt->execute();
        }

        // Handle FINALS updates (MATCHES_ID = 7)
        if ($round === 'FINALS') {
            $team1Final = $_POST['TEAM1_7'] ?? null;
            $team2Final = $_POST['TEAM2_7'] ?? null;
            
            // Update FINAL MATCHES_ID: 7
            $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET TEAM1 = ?, TEAM2 = ?, WINNER = NULL WHERE MATCHES_ID = 7");
            $stmt->bind_param("ii", $team1Final, $team2Final);
            $stmt->execute();
                if ($team1Final) {
                    // Update WINNER for MATCHES_ID: 5 and 6
                    $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = ? WHERE MATCHES_ID = 5");
                    $stmt->bind_param("i", $team1Final);
                    $stmt->execute();
                }
                if ($team2Final) {
                    $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = ? WHERE MATCHES_ID = 6");
                    $stmt->bind_param("i", $team2Final);
                    $stmt->execute();
                }
        }

        // Handle CHAMPION updates (MATCHES_ID = 7)
        if ($round === 'CHAMPION') {
            $champion = $_POST['WINNER_7'] ?? null;
            if ($champion) {
                $stmt = $conn->prepare("UPDATE BRACKETING_MATCHES SET WINNER = ? WHERE MATCHES_ID = 7");
                $stmt->bind_param("i", $champion);
                $stmt->execute();
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn->close();
?>
