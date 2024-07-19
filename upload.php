<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

$servername = "localhost";
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


if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileError = $_FILES['file']['error'];
    $fileType = $_FILES['file']['type'];

    $caption = isset($_POST['caption']) ? $_POST['caption'] : '';

    $userID = isset($_POST['userID']) ? $_POST['userID'] : '';

    $fileExt = explode('.', $fileName);
    $fileActualExt = strtolower(end($fileExt));

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($fileActualExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 100000000) {
                $fileNameNew = uniqid('', true) . "." . $fileActualExt;
                // $fileDestination = 'uploads/' . $fileNameNew;
                $fileDestination = '/var/www/html/sync/uploads/' . $fileNameNew;
                // $fileDestination = './../sync/uploads/' . $fileNameNew;

                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $sql = "INSERT INTO uploads (filename, caption, userID) VALUES (:filename, :caption, :userID)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':filename', $fileNameNew);
                    $stmt->bindParam(':caption', $caption);
                    $stmt->bindParam(':userID', $userID);

                    if ($stmt->execute()) {
                        echo json_encode(["message" => "File uploaded and inserted into the database successfully!"]);
                    } else {
                        echo json_encode(["error" => "Error inserting data into the database."]);
                    }
                } else {
                    echo json_encode(["error" => "Failed to move uploaded file."]);
                }
            } else {
                echo json_encode(["error" => "Your file is too large!"]);
            }
        } else {
            echo json_encode(["error" => "There was an error uploading your file!"]);
        }
    } else {
        echo json_encode(["error" => "You cannot upload files of this type!"]);
    }
} else {

    $caption = isset($_POST['caption']) ? $_POST['caption'] : '';

    $userID = isset($_POST['userID']) ? $_POST['userID'] : '';

    $sql = "INSERT INTO uploads (caption, userID) VALUES (:caption, :userID)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':caption', $caption);
    $stmt->bindParam(':userID', $userID);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Data inserted into the database successfully!"]);
    } else {
        echo json_encode(["error" => "Error inserting data into the database."]);
    }
}

$conn = null;
