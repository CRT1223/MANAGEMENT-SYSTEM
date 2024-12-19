<?php
header('Content-Type: application/json');

// Database connection setup
$host = 'localhost';
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch data for each round
    $query = "
        SELECT bm.MATCHES_ID, bm.ROUND_STATUS, 
               bm.TEAM1, t1.TEAM_NAME AS TEAM1_NAME,
               bm.TEAM2, t2.TEAM_NAME AS TEAM2_NAME,
               bm.WINNER, w.TEAM_NAME AS WINNER_TEAM_NAME
        FROM BRACKETING_MATCHES bm
        LEFT JOIN TEAM t1 ON bm.TEAM1 = t1.TEAM_ID
        LEFT JOIN TEAM t2 ON bm.TEAM2 = t2.TEAM_ID
        LEFT JOIN TEAM w ON bm.WINNER = w.TEAM_ID
        ORDER BY bm.MATCHES_ID;
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($matches);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>