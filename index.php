<?php
session_start(); // Start the session to check logged-in status

// Get the route from the URL
$route = isset($_GET['route']) ? $_GET['route'] : '';

// Routing logic
switch ($route) {
    case 'admin':
        // If the user is logged in, show the admin page
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            include('admin.html'); // Show the admin page
        } else {
            // Redirect to login page if not logged in
            header('Location: /Project/');
            exit;
        }
        break;

    case 'scanqr':
        // If the user is logged in, show the scanqr page
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            include('scanqr.html'); // Show the scan QR page
        } else {
            // Redirect to login page if not logged in
            header('Location: /Project/');
            exit;
        }
        break;

    case 'buyer':
    default:
        // Default route is 'buyer.html' (home page) when not logged in
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Redirect logged-in users to /admin page instead of /Project/
            header('Location: /Project/admin');
            exit;
        } else {
            include('buyer.html'); // Show the buyer page (this will be the default page)
        }
        break;
}
?>
