<?php
// logActivity.php - Handles activity logging

include("dbConn.php");
session_start();

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$requestID = $input['requestID'] ?? null;
$userID = $input['userID'] ?? null;
$userType = $input['userType'] ?? 'provider';
$action = $input['action'] ?? 'Create Request';
$description = $input['description'] ?? '';

if (!$requestID || !$userID) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Determine type based on userType
$type = 'Request';

// Insert into activity log
$sql = "INSERT INTO tblactivity_log (requestID, userID, type, action, description, dateTime) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "siiss", $requestID, $userID, $type, $action, $description);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert activity log: ' . mysqli_error($conn)]);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>