<?php
include("dbConn.php");
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert into tblcollection_request
    $sql = "INSERT INTO tblcollection_request (
        providerID, 
        pickupAddress, 
        pickupState, 
        pickupPostcode, 
        preferredDateTime, 
        status,
        createdAt
    ) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issss", 
        $data['providerID'],
        $data['pickupAddress'],
        $data['pickupState'],
        $data['pickupPostcode'],
        $data['preferredDateTime']
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to create collection request');
    }
    
    $requestID = mysqli_insert_id($conn);
    
    // Insert items into tblitem
    foreach ($data['items'] as $item) {
        if ($item['itemTypeID'] && $item['weight'] > 0) {
            $sql = "INSERT INTO tblitem (
                requestID,
                itemTypeID,
                description,
                model,
                brand,
                weight,
                length,
                width,
                height,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisssdddd", 
                $requestID,
                $item['itemTypeID'],
                $item['description'],
                $item['model'],
                $item['brand'],
                $item['weight'],
                $item['length'],
                $item['width'],
                $item['height']
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to insert item');
            }
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'requestID' => $requestID,
        'message' => 'Pickup request submitted successfully'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>