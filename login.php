<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include "connection.php";

$username = $_GET['username'];
$password = $_GET['password'];

$sql = "SELECT * FROM tbl_users WHERE username=:username AND password=:password";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':password', $password);
$stmt->execute();
$returnValue = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($returnValue);
?>