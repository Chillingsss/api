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


    function createPost($json)
    {
        include "connection.php";
        $json = json_decode($json, true);
        $date = getCurrentDate();

        $returnValueImage = uploadImage();
        switch ($returnValueImage) {
            case 2:
                // You cannot Upload files of this type!
                return 2;
            case 3:
                // There was an error uploading your file!
                return 3;
            case 4:
                // Your file is too big (25mb maximum)
                return 4;
            default:
                break;
        }
        $sql = "INSERT INTO uploads( id, caption, post_image, post_dateCreated, post_status) 
    VALUES(:userId, :title, :description, :image, :date, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":userId", $json["userId"]);
        $stmt->bindParam(":description", $json["caption"]);
        $stmt->bindParam(":image", $returnValueImage);
        $stmt->bindParam(":date", $date);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }




    // function heartpost($json)
    // {
    //     include "connection.php";
    //     $json = json_decode($json, true);

    //     // Retrieve post and user IDs from the request
    //     $postId = $json['postId'];
    //     $userId = $json['userId'];

    //     try {
    //         $sqlCheckLiked = "SELECT * FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
    //         $stmtCheckLiked = $conn->prepare($sqlCheckLiked);
    //         $stmtCheckLiked->bindParam(":postId", $postId);
    //         $stmtCheckLiked->bindParam(":userId", $userId);
    //         $stmtCheckLiked->execute();

    //         if ($stmtCheckLiked->rowCount() > 0) {
    //             $sqlUnlike = "DELETE FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
    //             $stmtUnlike = $conn->prepare($sqlUnlike);
    //             $stmtUnlike->bindParam(":postId", $postId);
    //             $stmtUnlike->bindParam(":userId", $userId);
    //             $stmtUnlike->execute();
    //             return -5;
    //         } else {
    //             $sqlLike = "INSERT INTO tbl_points (point_postId, point_userId) VALUES (:postId, :userId)";
    //             $stmtLike = $conn->prepare($sqlLike);
    //             $stmtLike->bindParam(":postId", $postId);
    //             $stmtLike->bindParam(":userId", $userId);
    //             $stmtLike->execute();
    //         }

    //         return $stmtLike->rowCount() > 0 || $stmtUnlike->rowCount() > 0 ? 1 : 0;
    //     } catch (PDOException $e) {
    //         error_log("Error in heartpost function: " . $e->getMessage(), 0);
    //         return 0;
    //     }
    // }

    function heartpost($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $postId = $json['postId'];
        $userId = $json['userId'];
        $reaction = $json['reaction'];

        try {
            // Check if the user already reacted
            $sqlCheckReaction = "SELECT * FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
            $stmtCheckReaction = $conn->prepare($sqlCheckReaction);
            $stmtCheckReaction->bindParam(":postId", $postId);
            $stmtCheckReaction->bindParam(":userId", $userId);
            $stmtCheckReaction->execute();

            if ($stmtCheckReaction->rowCount() > 0) {
                if ($reaction === 'remove') {

                    $sqlRemoveReaction = "DELETE FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
                    $stmtRemoveReaction = $conn->prepare($sqlRemoveReaction);
                    $stmtRemoveReaction->bindParam(":postId", $postId);
                    $stmtRemoveReaction->bindParam(":userId", $userId);
                    $stmtRemoveReaction->execute();

                    return -5;
                } else {

                    $sqlUpdateReaction = "UPDATE tbl_points SET point_reaction = :reaction WHERE point_postId = :postId AND point_userId = :userId";
                    $stmtUpdateReaction = $conn->prepare($sqlUpdateReaction);
                    $stmtUpdateReaction->bindParam(":postId", $postId);
                    $stmtUpdateReaction->bindParam(":userId", $userId);
                    $stmtUpdateReaction->bindParam(":reaction", $reaction);
                    $stmtUpdateReaction->execute();

                    return 2;
                }
            } else {

                $sqlAddReaction = "INSERT INTO tbl_points (point_postId, point_userId, point_reaction) VALUES (:postId, :userId, :reaction)";
                $stmtAddReaction = $conn->prepare($sqlAddReaction);
                $stmtAddReaction->bindParam(":postId", $postId);
                $stmtAddReaction->bindParam(":userId", $userId);
                $stmtAddReaction->bindParam(":reaction", $reaction);
                $stmtAddReaction->execute();

                return 1;
            }
        } catch (PDOException $e) {
            error_log("Error in heartpost function: " . $e->getMessage(), 0);
            return 0;
        }
    }









    function getLikes($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $sql = "SELECT 
        a.firstname AS post_creator_firstname,
        b.*,
        COUNT(c.point_Id) AS likes,
        GROUP_CONCAT(d.firstname) AS likers_firstnames,
        GROUP_CONCAT(d.lastname) AS likers_lastnames,
        GROUP_CONCAT(d.prof_pic) AS likers_profile_pics,
        GROUP_CONCAT(d.id) AS likers_ids,
        GROUP_CONCAT(c.point_reaction) AS likers_reactions
        FROM tbl_users AS a
        INNER JOIN uploads AS b ON a.id = b.userID
        LEFT JOIN tbl_points AS c ON c.point_postId = b.id
        LEFT JOIN tbl_users AS d ON c.point_userId = d.id
        WHERE b.id = :postId
        GROUP BY b.id
        ORDER BY b.upload_date DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":postId", $json["postId"]);
        $stmt->execute();

        return $stmt->rowCount() > 0 ? json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)) : 0;
    }


    function isUserReaction($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $postId = $json['postId'];
        $userId = $json['userId'];

        $sql = "SELECT point_reaction FROM tbl_points WHERE point_postId = :postId AND point_userId = :userId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":postId", $postId);
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return json_encode(['reaction' => $row['point_reaction']]);
        } else {
            return json_encode(['reaction' => null]);
        }
    }







    function updateDetails($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {

            if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {

                $file = $_FILES['file'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileError = $file['error'];


                if ($fileError !== UPLOAD_ERR_OK) {
                    throw new Exception("Error uploading profile picture.");
                }

                $uploadPath = "/var/www/html/api/profPic/" . $fileName;
                move_uploaded_file($fileTmpName, $uploadPath);


                $sql = "UPDATE tbl_users SET prof_pic=:profilePicture WHERE id=:userId";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":profilePicture", $fileName);
                $stmt->bindParam(":userId", $json["userId"]);
                if (!$stmt->execute()) {
                    throw new Exception("Error updating profile picture in the database.");
                }
            }

            // Update other user details
            $sql = "UPDATE tbl_users SET firstname=:updatedFirstname, middlename=:updatedMiddlename, lastname=:updatedLastname, email=:updatedEmail, cpnumber=:updatedCpnumber, username=:updatedUsername, password=:updatedPassword WHERE id=:userId";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":updatedFirstname", $json["updatedFirstname"]);
            $stmt->bindParam(":updatedMiddlename", $json["updatedMiddlename"]);
            $stmt->bindParam(":updatedLastname", $json["updatedLastname"]);
            $stmt->bindParam(":updatedEmail", $json["updatedEmail"]);
            $stmt->bindParam(":updatedCpnumber", $json["updatedCpnumber"]);
            $stmt->bindParam(":updatedUsername", $json["updatedUsername"]);
            $stmt->bindParam(":updatedPassword", $json["updatedPassword"]);
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
        $sql = "SELECT 
                a.firstname,
                a.lastname,
                a.prof_pic, 
                b.*, 
                COUNT(DISTINCT c.point_Id) AS likes,
                COUNT(DISTINCT d.comment_id) AS countComment
            FROM 
                tbl_users AS a
                INNER JOIN uploads AS b ON a.id = b.userID
                LEFT JOIN tbl_points AS c ON c.point_postId = b.id
                LEFT JOIN tbl_comment AS d ON d.comment_uploadId = b.id
            WHERE 
                a.id = :profID
            GROUP BY 
                b.id
            ORDER BY 
                b.upload_date DESC";

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

        $sql = "SELECT b.comment_id, b.comment_userID, a.firstname, a.prof_pic, b.comment_message, b.comment_date_created 
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

    function countComment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $sql = "SELECT 
            b.comment_id, 
            b.comment_userID, 
            GROUP_CONCAT(a.firstname) AS firstnames,
            GROUP_CONCAT(a.lastname) AS lastnames,
            GROUP_CONCAT(a.prof_pic) AS prof_pics,
            b.comment_message, 
            b.comment_date_created,
            COUNT(b.comment_id) AS comment_count
        FROM 
            tbl_users AS a 
        INNER JOIN 
            tbl_comment AS b 
        ON 
            b.comment_userID = a.id 
        WHERE 
            b.comment_uploadId = :postId
        GROUP BY
            b.comment_id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":postId", $json["uploadId"]);
        $stmt->execute();

        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $returnValue = [
            'comments' => $comments,
            'comment_count' => count($comments)
        ];

        return json_encode($returnValue);
    }


    function deleteComment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $postId = $json['comment_id'];

        $sql = "DELETE FROM tbl_comment WHERE comment_id = :postId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':postId', $postId);

        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }

    function editComment($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $sql = "UPDATE tbl_comment SET comment_message=:updatedComment WHERE comment_id=:comment_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":updatedComment", $json["updatedComment"]);
            $stmt->bindParam(":comment_id", $json["comment_id"]);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return json_encode(array("status" => 1, "message" => "Comment updated successfully"));
            } else {
                return json_encode(array("status" => 0, "message" => "No changes made to the comment"));
            }
        } catch (PDOException $e) {
            return json_encode(array("status" => 0, "message" => "Error executing SQL statement: " . $e->getMessage()));
        } finally {
            $stmt = null;
            $conn = null;
        }
    }


    // function chat($json)
    // {
    //     include "connection.php";
    //     $json = json_decode($json, true);

    //     $userId = $json["userId"];
    //     $chat_message = $json["chat_message"];
    //     $usersID = $json["usersID"];

    //     $sql = "INSERT INTO tbl_chat (chat_userID, chat_message, chat_usersID, chat_date_created)
    //         VALUES (:userId, :chat_message, :usersID, NOW())";

    //     $stmt = $conn->prepare($sql);
    //     $stmt->bindParam(":chat_message", $chat_message);
    //     $stmt->bindParam(":userId", $userId);
    //     $stmt->bindParam(":usersID", $usersID);
    //     $stmt->execute();

    //     return $stmt->rowCount() > 0 ? 1 : 0;
    // }

    function chat($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $userId = $json["userId"];
        $chat_message = $json["chat_message"];
        $usersID = $json["usersID"];

        try {
            $sql = "INSERT INTO tbl_message (chat_userID,  chat_usersID, chat_message, chat_date_created)
                VALUES (:userId, :usersID, :chat_message, NOW())";

            $stmt = $conn->prepare($sql);

            $stmt->bindParam(":userId", $userId);
            $stmt->bindParam(":usersID", $usersID);
            $stmt->bindParam(":chat_message", $chat_message);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return json_encode(["status" => 1, "message" => "Message sent successfully."]);
            } else {
                return json_encode(["status" => 0, "message" => "Failed to send message."]);
            }
        } catch (PDOException $e) {
            return json_encode(["status" => -1, "message" => "Database error: " . $e->getMessage()]);
        } catch (Exception $e) {
            return json_encode(["status" => -1, "message" => "General error: " . $e->getMessage()]);
        }
    }


    // function getMessages($json)
    // {
    //     include "connection.php";
    //     $json = json_decode($json, true);

    //     $sql = "SELECT m.chat_message, m.chat_userID, u.firstname, u.lastname, u.prof_pic, m.chat_date_created 
    //         FROM tbl_message as m 
    //         INNER JOIN tbl_users as u ON m.chat_userID = u.id 
    //         WHERE (m.chat_usersID = :userID1 AND m.chat_userID = :userID2) 
    //         OR (m.chat_usersID = :userID2 AND m.chat_userID = :userID1)
    //         ORDER BY m.chat_date_created";

    //     $stmt = $conn->prepare($sql);
    //     $stmt->bindParam(":userID1", $json["userId"]);
    //     $stmt->bindParam(":userID2", $json["usersID"]);

    //     $stmt->execute();

    //     $returnValue = 0;

    //     if ($stmt->rowCount() > 0) {
    //         $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //         $returnValue = json_encode($rs);
    //     }

    //     return $returnValue;
    // }

    function getMessages($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $sql = "SELECT m.chat_id, m.chat_message, m.chat_userID, u.firstname, u.lastname, u.prof_pic, m.chat_date_created 
            FROM tbl_message as m 
            INNER JOIN tbl_users as u ON m.chat_userID = u.id 
            WHERE (m.chat_usersID = :userID1 AND m.chat_userID = :userID2) 
            OR (m.chat_usersID = :userID2 AND m.chat_userID = :userID1)
            ORDER BY m.chat_date_created";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":userID1", $json["userId"]);
            $stmt->bindParam(":userID2", $json["usersID"]);

            $stmt->execute();

            $returnValue = ["status" => -1, "message" => "No messages found."];

            if ($stmt->rowCount() > 0) {
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $returnValue = ["status" => 1, "messages" => $rs];
            }
        } catch (Exception $e) {
            $returnValue = ["status" => -1, "message" => "Database error: " . $e->getMessage()];
        }

        return json_encode($returnValue);
    }

    function editMessage($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        try {
            $sql = "UPDATE tbl_message SET chat_message=:updatedMessage WHERE chat_id=:chat_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":updatedMessage", $json["message"]);
            $stmt->bindParam(":chat_id", $json["messageId"]);
            $stmt->execute();

            // Debugging logs
            error_log("SQL: " . $sql);
            error_log("Message: " . $json["message"]);
            error_log("Chat ID: " . $json["messageId"]);

            if ($stmt->rowCount() > 0) {
                return json_encode(array("status" => 1, "message" => "Comment updated successfully"));
            } else {
                return json_encode(array("status" => 0, "message" => "No changes made to the comment"));
            }
        } catch (PDOException $e) {
            return json_encode(array("status" => 0, "message" => "Error executing SQL statement: " . $e->getMessage()));
        } finally {
            $stmt = null;
            $conn = null;
        }
    }

    function deleteMessage($json)
    {
        include "connection.php";
        $json = json_decode($json, true);

        $messageId = $json['chat_id'];

        $sql = "DELETE FROM tbl_message WHERE chat_id = :messageId";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':messageId', $messageId);

        $stmt->execute();
        return $stmt->rowCount() > 0 ? 1 : 0;
    }
}


function uploadImage()
{
    if (isset($_FILES["file"])) {
        $file = $_FILES['file'];
        // print_r($file);
        $fileName = $_FILES['file']['name'];
        $fileTmpName = $_FILES['file']['tmp_name'];
        $fileSize = $_FILES['file']['size'];
        $fileError = $_FILES['file']['error'];
        // $fileType = $_FILES['file']['type'];

        $fileExt = explode(".", $fileName);
        $fileActualExt = strtolower(end($fileExt));

        $allowed = ["jpg", "jpeg", "png", "gif"];

        if (in_array($fileActualExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 25000000) {
                    $fileNameNew = uniqid("", true) . "." . $fileActualExt;
                    $fileDestination = 'images/' . $fileNameNew;
                    move_uploaded_file($fileTmpName, $fileDestination);
                    return $fileNameNew;
                } else {
                    return 4;
                }
            } else {
                return 3;
            }
        } else {
            return 2;
        }
    } else {
        return "";
    }

    // $returnValueImage = uploadImage();

    // switch ($returnValueImage) {
    //     case 2:
    //         // You cannot Upload files of this type!
    //         return 2;
    //     case 3:
    //         // There was an error uploading your file!
    //         return 3;
    //     case 4:
    //         // Your file is too big (25mb maximum)
    //         return 4;
    //     default:
    //         break;
    // }
}

function getCurrentDate()
{
    $today = new DateTime("now", new DateTimeZone('Asia/Manila'));
    return $today->format('Y-m-d H:i:s');
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
        echo $data->deletePost($json);
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
    case "createPost":
        echo $data->createPost($json);
        break;
    case "countComment":
        echo $data->countComment($json);
        break;
    case "getMessages";
        echo $data->getMessages($json);
        break;
    case "editMessage";
        echo $data->editMessage($json);
        break;
    case "deleteMessage";
        echo $data->deleteMessage($json);
        break;
    case "isUserReaction";
        echo $data->isUserReaction($json);
        break;
    default:
        echo json_encode(array("status" => -1, "message" => "Invalid operation."));
}
