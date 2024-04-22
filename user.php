<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

class Data
{

    function isUserLiked($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT * FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":postId", $json["postId"]);
        $stmt->bindParam(":userId", $json["userId"]);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function loginUser($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $sql = "SELECT * FROM tbl_users WHERE username=:loginUsername AND password=:loginPassword";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':loginUsername', $json['loginUsername']);
            $stmt->bindParam(':loginPassword', $json['loginPassword']);

            if ($stmt->execute()) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($data)) {

                    $storedPassword = $data[0]['password'];


                    if ($json['loginPassword'] === $storedPassword) {

                        session_start();


                        $_SESSION["userDetails"] = [
                            "id" => $data[0]["id"],
                            "firstname" => $data[0]["firstname"],
                            "middlename" => $data[0]["middlename"],
                            "lastname" => $data[0]["lastname"],
                            "email" => $data[0]["email"],
                            "cpnumber" => $data[0]["cpnumber"],
                            "username" => $data[0]["username"],
                        ];

                        $_SESSION["isLoggedIn"] = true;

                        return json_encode(array("status" => 1, "data" => $data));
                    } else {
                        return json_encode(array("status" => -1, "data" => [], "message" => "Incorrect password."));
                    }
                } else {
                    return json_encode(array("status" => -1, "data" => [], "message" => "No data found."));
                }
            } else {
                throw new Exception("Error executing SQL statement.");
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log("PDOException: " . $errorMsg);
            return json_encode(array("status" => -1, "title" => "Database error.", "message" => $errorMsg));
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Exception: " . $errorMsg);
            return json_encode(array("status" => -1, "title" => "An error occurred.", "message" => $errorMsg));
        } finally {
            $stmt = null;
            $conn = null;
        }
    }


    function signup($json)
    {
        // {"username":"joe1","email":"joe1@gmailcom","password":"joejoejoe"}
        include "connection.php";
        $data = json_decode($json, true);
        if (recordExists($data["username"], "tbl_users", "username")) {
            return -1;
        } else if (recordExists($data["email"], "tbl_users", "email")) {
            return -2;
        }

        $sql = "INSERT INTO tbl_users(firstname, middlename, lastname, email, cpnumber, username, password) 
        VALUES(:firstname, :middlename, :lastname, :email, :cpnumber, :username, :password)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":firstname", $data["firstname"]);
        $stmt->bindParam(":middlename", $data["middlename"]);
        $stmt->bindParam(":lastname", $data["lastname"]);
        $stmt->bindParam(":email", $data["email"]);
        $stmt->bindParam(":cpnumber", $data["cpnumber"]);
        $stmt->bindParam(":username", $data["username"]);
        $stmt->bindParam(":password", $data["password"]);

        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }



    function heartpost($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        // Retrieve post and user IDs from the request
        $postId = $json['postId'];
        $userId = $json['userId'];

        try {
            $sqlCheckLiked = "SELECT * FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
            $stmtCheckLiked = $conn->prepare($sqlCheckLiked);
            $stmtCheckLiked->bindParam(":postId", $postId);
            $stmtCheckLiked->bindParam(":userId", $userId);
            $stmtCheckLiked->execute();

            if ($stmtCheckLiked->rowCount() > 0) {
                $sqlUnlike = "DELETE FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
                $stmtUnlike = $conn->prepare($sqlUnlike);
                $stmtUnlike->bindParam(":postId", $postId);
                $stmtUnlike->bindParam(":userId", $userId);
                $stmtUnlike->execute();
                return -5;
            } else {
                $sqlLike = "INSERT INTO tbl_points (point_postId, point_userId) VALUES (:postId, :userId)";
                $stmtLike = $conn->prepare($sqlLike);
                $stmtLike->bindParam(":postId", $postId);
                $stmtLike->bindParam(":userId", $userId);
                $stmtLike->execute();
            }

            return $stmtLike->rowCount() > 0 || $stmtUnlike->rowCount() > 0 ? 1 : 0;
        } catch (PDOException $e) {
            error_log("Error in heartpost function: " . $e->getMessage(), 0);
            return 0;
        }
    }





    function getLikes($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $sql = "SELECT a.firstname, b.*, COUNT(c.point_Id) AS likes
            FROM tbl_users as a
            INNER JOIN uploads as b ON a.id = b.userID
            LEFT JOIN tbl_points as c ON c.point_postId = b.id
            WHERE b.id = :postId
            GROUP BY b.id
            ORDER BY b.upload_date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":postId", $json["postId"]);
        $stmt->execute();

        return $stmt->rowCount() > 0 ? json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)) : 0;
    }




    function updateDetails($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $sql = "UPDATE tbl_users SET firstname=:updatedFirstname, middlename=:updatedMiddlename, lastname=:updatedLastname, email=:updatedEmail, cpnumber=:updatedCpnumber, username=:updatedUsername, password=:updatedPassword WHERE id=:userId";

            $stmt = $conn->prepare($sql);

            // Bind parameters
            $stmt->bindParam(":updatedFirstname", $json["updated-firstname"]);
            $stmt->bindParam(":updatedMiddlename", $json["updated-middlename"]);
            $stmt->bindParam(":updatedLastname", $json["updated-lastname"]);
            $stmt->bindParam(":updatedEmail", $json["updated-email"]);
            $stmt->bindParam(":updatedCpnumber", $json["updated-cpnumber"]);
            $stmt->bindParam(":updatedUsername", $json["updated-username"]);
            $stmt->bindParam(":updatedPassword", $json["updated-password"]);
            $stmt->bindParam(":userId", $json["userId"]);

            if ($stmt->execute()) {
                return json_encode(array("status" => 1, "message" => "Details updated successfully"));
            } else {
                throw new Exception("Error executing SQL statement.");
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log("PDOException: " . $errorMsg);
            return json_encode(array("status" => -1, "title" => "Database error.", "message" => $errorMsg));
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("Exception: " . $errorMsg);
            return json_encode(array("status" => -1, "title" => "An error occurred.", "message" => $errorMsg));
        } finally {
            $stmt = null;
            $conn = null;
        }
    }


    function getProfile($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $sql = "SELECT a.firstname, b.*, COUNT(c.point_Id) AS likes
            FROM tbl_users as a
            INNER JOIN uploads as b ON a.id = b.userID
            LEFT JOIN tbl_points as c ON c.point_postId = b.id
            WHERE a.id = :profID
            GROUP BY b.id
            ORDER BY b.upload_date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":profID", $json["profID"]);
        $stmt->execute();

        return $stmt->rowCount() > 0 ? json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)) : 0;
    }


    function deletePost($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $postId = $json['postId'];

        $sql = "DELETE FROM uploads WHERE id = :postId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function editPost($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $sql = "UPDATE uploads SET caption=:updatedCaption WHERE id=:postId";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":updatedCaption", $json["updatedCaption"]);

        $stmt->bindParam(":postId", $json["postId"]);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return json_encode(array("status" => 1, "message" => "Details updated successfully"));
        } else {
            throw new Exception("Error executing SQL statement.");
        }

        $stmt = null;
        $conn = null;
    }

    function commentPost($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $uploadId = $json["uploadId"];
        $userId = $json["userId"];
        $comment_message = $json["comment_message"];


        $sql = "INSERT INTO tbl_comment (comment_userID, comment_message, comment_uploadId, comment_date_created)
            VALUES (:userId, :comment_message, :uploadId, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":comment_message", $comment_message);
        $stmt->bindParam(":uploadId", $uploadId);
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();

        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function fetchComment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $sql = "SELECT b.comment_id, b.comment_userID, a.firstname, b.comment_message 
        FROM tbl_users as a 
        INNER JOIN tbl_comment as b ON b.comment_userID = a.id 
        WHERE b.comment_uploadId = :postId";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":postId", $json["uploadId"]);

        $stmt->execute();

        $returnValue = 0;

        if ($stmt->rowCount() > 0) {
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $returnValue = json_encode($rs);
        }

        return $returnValue;
    }


    function deleteComment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $postId = $json['comment_id'];

        $sql = "DELETE FROM tbl_comment WHERE comment_id = :postId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':postId', $postId, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function editComment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $sql = "UPDATE tbl_comment SET comment_message=:updatedComment WHERE comment_id=:comment_id";

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":updatedComment", $json["updatedComment"]);

        $stmt->bindParam(":comment_id", $json["comment_id"]);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return json_encode(array("status" => 1, "message" => "Comment updated successfully"));
        } else {
            throw new Exception("Error executing SQL statement.");
        }

        $stmt = null;
        $conn = null;
    }

    function chat($json)
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

        return $stmt->rowCount() > 0 ? 1 : 0;
    }
}

function recordExists($value, $table, $column)
{
    include "connection.php";
    $sql = "SELECT COUNT(*) FROM $table WHERE $column = :value";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":value", $value);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    return $count > 0;
}

$operation = isset($_POST["operation"]) ? $_POST["operation"] : "Invalid";
$json = isset($_POST["json"]) ? $_POST["json"] : "";

$data = new Data();
switch ($operation) {
    case "loginUser":
        echo $data->loginUser($json);
        break;
    case "heartpost":
        echo $data->heartpost($json);
        break;
    case "getLikes":
        echo $data->getLikes($json);
        break;
    case "updateDetails":
        echo $data->updateDetails($json);
        break;
    case "getProfile":
        echo $data->getProfile($json);
        break;
    case "deletePost":
        echo $data->deletePost($json); // Call the deletePost function
        break;
    case "commentPost":
        echo $data->commentPost($json);
        break;
    case "editPost":
        echo $data->editPost($json);
        break;
    case "fetchComment":
        echo $data->fetchComment($json);
        break;
    case "isUserLiked":
        echo $data->isUserLiked($json);
        break;
    case "deleteComment":
        echo $data->deleteComment($json);
        break;
    case "editComment":
        echo $data->editComment($json);
        break;
    case "chat":
        echo $data->chat($json);
        break;
    case "signup":
        echo $data->signup($json);
        break;
    default:
        echo json_encode(array("status" => -1, "message" => "Invalid operation."));
}
