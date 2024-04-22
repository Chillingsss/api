<?php
header('Content-Type: application/json');

$servername = "127.0.0.1";
$username = "root";
$password = "pelino";
$dbname = "db_socialmedia";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



    $sql = "SELECT * FROM tbl_users";

    $stmt = $pdo->query($sql);


    if ($stmt->rowCount() > 0) {

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


        echo json_encode($users);
    } else {

        echo json_encode(array("status" => 0, "message" => "No users found."));
    }
} catch (PDOException $e) {
    // Handle database errors
    echo json_encode(array("status" => -1, "message" => "Database error: " . $e->getMessage()));
}
