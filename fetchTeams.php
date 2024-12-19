<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch the currently playing teams with details from the TEAM table
    $stmt = $conn->prepare("
        SELECT 
            t1.TEAM_NAME AS TEAM_1_NAME, 
            t1.TEAM_LOGO_PATH AS TEAM_1_LOGO,
            t2.TEAM_NAME AS TEAM_2_NAME,
            t2.TEAM_LOGO_PATH AS TEAM_2_LOGO
        FROM 
            CURRENTLY_PLAYING cp
        JOIN 
            TEAM t1 ON cp.TEAM_1 = t1.TEAM_ID
        JOIN 
            TEAM t2 ON cp.TEAM_2 = t2.TEAM_ID
        LIMIT 1
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(["status" => "success", "data" => $result]);
    } else {
        echo json_encode(["status" => "error", "message" => "No data found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
