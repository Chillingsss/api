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
    die("Connection failed: " . $e->getMessage());
}


$sql = "SELECT 
        uploads.id,
        uploads.filename,
        uploads.upload_date,
        uploads.caption,
        uploads.userID,
        tbl_users.firstname,
        tbl_users.lastname,
        tbl_users.prof_pic,
        COUNT(DISTINCT tbl_points.point_Id) AS likes,
        COUNT(DISTINCT tbl_comment.comment_id) AS countComment
        FROM uploads
        INNER JOIN tbl_users ON tbl_users.id = uploads.userID
        LEFT JOIN tbl_points ON tbl_points.point_postId = uploads.id
        LEFT JOIN tbl_comment ON tbl_comment.comment_uploadId = uploads.id
        GROUP BY uploads.id
        ORDER BY uploads.upload_date DESC

        ";
$stmt = $conn->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);


echo json_encode($posts);

$conn = null;
