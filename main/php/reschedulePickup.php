<?php
include("dbConn.php");
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$requestId = mysqli_real_escape_string($conn, $_POST['requestId']);
$newDateTime = mysqli_real_escape_string($conn, $_POST['newDateTime']);
$providerId = mysqli_real_escape_string($conn, $_POST['providerId']);

// Validate datetime format
if (!strtotime($newDateTime)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date/time format']);
    exit();
}

// Verify that this request belongs to the provider and is in Pending status
$checkSql = "SELECT requestID, status FROM tblcollection_request WHERE requestID = '$requestId' AND providerID = '$providerId'";
$checkResult = mysqli_query($conn, $checkSql);

if (mysqli_num_rows($checkResult) === 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or request not found']);
    exit();
}

$row = mysqli_fetch_assoc($checkResult);
if ($row['status'] !== 'Pending') {
    echo json_encode(['success' => false, 'message' => 'Only pending requests can be rescheduled']);
    exit();
}

// Update the preferred date and time
$sql = "UPDATE tblcollection_request SET preferredDateTime = '$newDateTime' WHERE requestID = '$requestId'";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Pickup rescheduled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>