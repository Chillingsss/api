<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (isset($_GET['query'])) {
    $query = $_GET['query'];

    // Example of database connection (you should use your own)
    $servername = "localhost";
    $username = "root";
    $password = "pelino";
    $database = "db_socialmedia";

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = $conn->prepare("SELECT id, firstname, lastname, prof_pic FROM tbl_users WHERE firstname LIKE ? OR lastname LIKE ?");
    $searchTerm = "%{$query}%";
    $sql->bind_param("ss", $searchTerm, $searchTerm);
    $sql->execute();
    $result = $sql->get_result();

    $users = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    $sql->close();
    $conn->close();

    echo json_encode($users);
} else {
    echo json_encode(["error" => "No query parameter provided."]);
}
