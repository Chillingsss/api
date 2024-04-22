<?php
header('Content-Type: application/json');


$uploadDir = '/var/www/html/sync/uploads/';

// Check if file data is received
if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileError = $_FILES['file']['error'];
    $fileType = $_FILES['file']['type'];

    $userID = isset($_POST['userID']) ? $_POST['userID'] : '';
    $caption = isset($_POST['caption']) ? $_POST['caption'] : '';


    // Process file upload
    $fileExt = explode('.', $fileName);
    $fileActualExt = strtolower(end($fileExt));

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($fileActualExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 25000000) {
                $fileNameNew = uniqid('', true) . "." . $fileActualExt;
                $fileDestination = $uploadDir . $fileNameNew;

                if (move_uploaded_file($fileTmpName, $fileDestination)) {

                    try {
                        $servername = "127.0.0.1";
                        $username = "root";
                        $password = "pelino";
                        $dbname = "db_socialmedia";


                        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

                        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        $sql = "INSERT INTO uploads (filename, caption, userID) VALUES (:filename, :caption, :userID)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':filename', $fileNameNew);
                        $stmt->bindParam(':caption', $caption);
                        $stmt->bindParam(':userID', $userID);


                        $stmt->execute();

                        $conn = null;


                        echo json_encode(["message" => "File uploaded and inserted into the database successfully!"]);
                    } catch (PDOException $e) {

                        echo json_encode(["error" => "Error inserting data into the database: " . $e->getMessage()]);
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
    echo json_encode(["error" => "No file uploaded!"]);
}
