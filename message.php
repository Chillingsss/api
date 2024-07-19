<?php
include "connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operation = $_POST['operation'] ?? '';

    switch ($operation) {
        case 'sendChat':
            echo sendChat($_POST['json']);
            break;
        default:
            echo json_encode(["status" => -1, "message" => "Invalid operation."]);
            break;
    }
} else {
    echo json_encode(["status" => -1, "message" => "Invalid request method."]);
}

function sendChat($json)
{
    include "connection.php";
    $json = json_decode($json, true);

    $userId = $json["userId"];
    $chat_message = $json["chat_message"];
    $usersID = $json["usersID"];

    $sql = "INSERT INTO tbl_chat (chat_userID, chat_message, chat_usersID, chat_date_created)
        VALUES (:userId, :chat_message, :usersID, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":chat_message", $chat_message);
    $stmt->bindParam(":userId", $userId);
    $stmt->bindParam(":usersID", $usersID);
    $stmt->execute();

    return json_encode($stmt->rowCount() > 0 ? ["status" => 1, "message" => "Message sent successfully."] : ["status" => 0, "message" => "Failed to send message."]);
}
