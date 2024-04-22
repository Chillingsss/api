<?php

session_unset();
// Destroy the entire session
session_destroy();

// Send a response indicating successful logout
$response = array('status' => 'success');
header('Content-Type: application/json');
echo json_encode($response);
?>