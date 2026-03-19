<?php
include("../../php/dbConn.php");
if(!isset($_SESSION)) {
    session_start();
}
include("../../php/sessionCheck.php");

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is provider
if ($_SESSION['userType'] !== 'provider') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$providerID = mysqli_real_escape_string($conn, $_POST['providerID'] ?? '');
$pickupAddress = mysqli_real_escape_string($conn, $_POST['pickupAddress'] ?? '');
$pickupState = mysqli_real_escape_string($conn, $_POST['pickupState'] ?? '');
$pickupPostcode = mysqli_real_escape_string($conn, $_POST['pickupPostcode'] ?? '');
$preferredDateTime = mysqli_real_escape_string($conn, $_POST['preferredDateTime'] ?? '');
$specialInstructions = mysqli_real_escape_string($conn, $_POST['specialInstructions'] ?? '');
$items = json_decode($_POST['items'] ?? '[]', true);

// Validate required fields
if (empty($providerID) || empty($pickupAddress) || empty($pickupState) || 
    empty($pickupPostcode) || empty($preferredDateTime) || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

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
                rejectionReason
            ) VALUES (
                '$providerID',
                '$pickupAddress',
                '$pickupState',
                '$pickupPostcode',
                '$preferredDateTime',
                'Pending',
                NULL
            )";
    
    if (!mysqli_query($conn, $sql)) {
        throw new Exception('Error inserting collection request: ' . mysqli_error($conn));
    }
    
    $requestID = mysqli_insert_id($conn);
    
    // Calculate total points for this request
    $totalPoints = 0;
    
    // Insert items into tblitem
    foreach ($items as $item) {
        $itemTypeID = intval($item['itemTypeID'] ?? 0);
        $quantity = intval($item['quantity'] ?? 1);
        $pointsPerItem = intval($item['points'] ?? 0);
        $description = mysqli_real_escape_string($conn, $item['description'] ?? '');
        $model = mysqli_real_escape_string($conn, $item['model'] ?? '');
        $brand = mysqli_real_escape_string($conn, $item['brand'] ?? '');
        $weight = floatval($item['weight'] ?? 0);
        
        // Parse dimensions if provided (format: "LxWxH")
        $length = 0;
        $width = 0;
        $height = 0;
        if (!empty($item['dimensions'])) {
            $dims = explode('x', strtolower($item['dimensions']));
            if (count($dims) == 3) {
                $length = floatval(trim($dims[0]));
                $width = floatval(trim($dims[1]));
                $height = floatval(trim($dims[2]));
            }
        }
        
        $itemStatus = mysqli_real_escape_string($conn, $item['status'] ?? 'Pending');
        
        // Calculate points: itemRecyclePoints × quantity
        $itemPoints = $pointsPerItem * $quantity;
        $totalPoints += $itemPoints;
        
        // Insert multiple items based on quantity
        for ($i = 0; $i < $quantity; $i++) {
            $sql = "INSERT INTO tblitem (
                        requestID,
                        centreID,
                        itemTypeID,
                        description,
                        model,
                        brand,
                        weight,
                        length,
                        width,
                        height,
                        image,
                        status
                    ) VALUES (
                        '$requestID',
                        NULL,
                        '$itemTypeID',
                        '$description',
                        '$model',
                        '$brand',
                        '$weight',
                        '$length',
                        '$width',
                        '$height',
                        NULL,
                        '$itemStatus'
                    )";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception('Error inserting item: ' . mysqli_error($conn));
            }
        }
    }
    
    // Handle image uploads (separate from item insertion)
    if (isset($_FILES['item_images']) && !empty($_FILES['item_images']['name'][0])) {
        // Create directory if it doesn't exist
        $uploadDir = '../../uploads/items/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($_FILES['item_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['item_images']['error'][$key] == 0) {
                $fileName = time() . '_' . $requestID . '_' . $key . '_' . $_FILES['item_images']['name'][$key];
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmp_name, $filePath)) {
                    // You might want to store image path in a separate table
                    // For now, we'll just log it
                    error_log("Image uploaded for request $requestID: $fileName");
                }
            }
        }
    }
    
    // Update provider points (add points to provider's account)
    $updatePointsSql = "UPDATE tblprovider SET point = point + $totalPoints WHERE providerID = '$providerID'";
    if (!mysqli_query($conn, $updatePointsSql)) {
        throw new Exception('Error updating provider points: ' . mysqli_error($conn));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Pickup request submitted successfully',
        'requestID' => 'REQ' . str_pad($requestID, 6, '0', STR_PAD_LEFT),
        'estimatedPoints' => $totalPoints
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>