<?php
$servername = "127.0.0.1";
$username = "root";
$password = "pelino";
$dbname = "db_socialmedia";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$postId = $_GET['postId'];

$sql = "SELECT 
        posts.id,
        posts.filename,
        posts.upload_date,
        posts.caption,
        posts.userID,
        tbl_users.firstname
        FROM posts
        INNER JOIN tbl_users ON tbl_users.id = posts.userID
        WHERE posts.id = :postId";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':postId', $postId, PDO::PARAM_INT);
$stmt->execute();

// Fetch the post details
$postDetails = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if postDetails is not empty (post with given ID exists)
if ($postDetails) {
    header('Content-Type: application/json');
    echo json_encode($postDetails);
} else {
    // Return an error message if post with given ID was not found
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'Post not found']);
}

$conn = null;
