<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

session_start();


$servername = "127.0.0.1";
$username = "root";
$password = "pelino";
$database = "db_socialmedia";

// Create connection
$connection = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the user is logged in
if (isset($_SESSION['userId'])) {
    $loggedInUserId = $_SESSION['userId'];

    // Query the database to get user details
    $query = "SELECT * FROM tbl_users WHERE id = $loggedInUserId"; // Adjust the query based on your database schema

    $result = mysqli_query($connection, $query);

    if ($result) {
        $userData = mysqli_fetch_assoc($result);

        // Return the user details as JSON
        echo json_encode($userData);
    } else {
        // Handle database error
        echo json_encode(['error' => 'Unable to fetch user details']);
    }
} else {
    // User is not logged in
    echo json_encode(['error' => 'User not logged in']);
}

// Close the database connection
mysqli_close($connection);
?>