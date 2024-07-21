<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

$servername = "localhost";
$username = "root";
$password = "pelino";
$database = "db_socialmedia";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the logged-in user's ID from the request
    $data = json_decode(file_get_contents("php://input"), true);
    $loggedInUserId = isset($data['userId']) ? $data['userId'] : 0;

    // SQL query to fetch all users except the logged-in user
    $sql = "SELECT id, firstname, lastname, prof_pic FROM tbl_users WHERE id != :loggedInUserId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':loggedInUserId', $loggedInUserId, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode(array('error' => 'Database error: ' . $e->getMessage()));
}

$conn = null;
