<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow requests from any origin
header("Access-Control-Allow-Methods: POST"); // Allow POST requests
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"); // Allow specified headers

$servername = "localhost";
$username = "root";
$password = "";
$database = "db_flutter";


$conn = new mysqli($servername, $username, $password, $database);


if ($conn->connect_error) {
    die ("Connection failed: " . $conn->connect_error);
}

$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST["reg-firstname"];
    $middlename = $_POST["reg-middlename"];
    $lastname = $_POST["reg-lastname"];
    $address = $_POST["reg-address"];
    $email = $_POST["reg-email"];
    $cpnumber = $_POST["reg-cpnumber"];
    $username = $_POST["reg-username"];
    $password = $_POST["reg-password"];
    $retypePassword = $_POST['reg-retype-password'];

    $sql = "INSERT INTO tbl_users (firstname, middlename, lastname, address, email, cpnumber, username, password)
            VALUES ('$firstname', '$middlename', '$lastname', '$address', '$email', '$cpnumber', '$username', '$password')";

    $result = $conn->query($sql);

    if ($result === TRUE) {
        $response["status"] = "success";
        $response["message"] = "New record created successfully";
    } else {
        $response["status"] = "error";
        $response["message"] = "Error: " . $sql . "<br>" . $conn->error;
        $response["query"] = $sql;
    }
}

echo json_encode($response);

$conn->close();
