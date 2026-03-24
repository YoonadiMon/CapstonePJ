<?php
include("dbConn.php");
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$requestId = mysqli_real_escape_string($conn, $_POST['requestId']);
$providerId = mysqli_real_escape_string($conn, $_POST['providerId']);

// Verify that this request belongs to the provider
$checkSql = "SELECT requestID FROM tblcollection_request WHERE requestID = '$requestId' AND providerID = '$providerId'";
$checkResult = mysqli_query($conn, $checkSql);

if (mysqli_num_rows($checkResult) === 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or request not found']);
    exit();
}

// Update the request status to Cancelled
$sql = "UPDATE tblcollection_request SET status = 'Cancelled', rejectionReason = 'Cancelled by provider' WHERE requestID = '$requestId'";

if (mysqli_query($conn, $sql)) {
    // Also update any associated items if needed
    $updateItemsSql = "UPDATE tblitem SET status = 'Cancelled' WHERE requestID = '$requestId'";
    mysqli_query($conn, $updateItemsSql);
    
    echo json_encode(['success' => true, 'message' => 'Pickup cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>