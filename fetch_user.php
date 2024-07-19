<?php
header('Content-Type: application/json');

$servername = "127.0.0.1";
$username = "root";
$password = "pelino";
$dbname = "db_socialmedia";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit;
}

$userId = isset($_GET['userId']) ? $_GET['userId'] : '';

$sql = "SELECT firstname FROM tbl_users WHERE id = :userId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':userId', $userId);
$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

$conn = null;

echo json_encode($user);
