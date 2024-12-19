<?php
// Start the session
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "SPORTS_MANAGEMENT";

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Decode JSON input for login requests
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
        exit;
    }

    // Login request handling
    if (isset($data['action']) && $data['action'] === 'login') {
        if (isset($data['username']) && isset($data['password'])) {
            $username = $data['username'];
            $password = $data['password'];

            // Prepare and execute the query
            $stmt = $conn->prepare("SELECT * FROM USER WHERE username = :username AND password = :password");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Set session data
                $_SESSION['logged_in'] = true;

                // Redirect logged-in users to the admin page
                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful",
                    "redirectUrl" => "/Project/admin" // Redirect to admin route after login
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Username or password not provided"]);
        }
        exit;
    }

    // Handle logout

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action']) && $data['action'] === 'logout') {
        // Unset all session variables and destroy the session
        session_unset();  
        session_destroy(); 

        // Send a JSON response indicating the logout status
        echo json_encode([
            "status" => "success",
            "message" => "Logged out successfully",
            "redirectUrl" => "/Project/"  // Redirect URL after logout
        ]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $e->getMessage()]);
}
?>
