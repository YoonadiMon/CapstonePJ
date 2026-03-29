<?php
session_start();
include("../../php/dbConn.php");

// check if user is logged in
include("../../php/sessionCheck.php");  

// Check if user is admin
if ($_SESSION['userType'] !== 'admin') {
    header("Location: ../../../index.html");
    exit();
}

function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

$validStates  = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang',
                 'Perak','Perlis','Penang','Selangor','Terengganu','Kuala Lumpur','Putrajaya'];
$validStatuses = ['Active', 'Inactive'];

// Remove 'Other Electronics' from item types
$itemTypesResult = $conn->query("SELECT itemTypeID, name FROM tblitem_type WHERE name != 'Other Electronics' ORDER BY itemTypeID");
$allItemTypes = [];
while ($row = $itemTypesResult->fetch_assoc()) {
    $allItemTypes[] = $row;
}

$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg   = $_SESSION['errorMsg']   ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check database connection
    if (!$conn) {
        $_SESSION['errorMsg'] = 'Database connection failed.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $action = $_POST['action'] ?? '';

        // add
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $postcode = trim($_POST['postcode'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $items = $_POST['items'] ?? [];

            // Validation
            $errors = [];
            if ($name === '') { 
                $errors[] = 'Centre name is required.';
            }
            if (strlen($name) > 100) { 
                $errors[] = 'Centre name must be less than 100 characters'; 
            }
            if ($address === '') { 
                $errors[] = 'Address is required.'; 
            }
            if (strlen($address) < 20) { 
                $errors[] = 'Address must be at least 20 characters long.'; 
            }
            if (strlen($address) > 255) { 
                $errors[] = 'Address must be less than 255 characters.'; 
            }
            if (!in_array($state, $validStates)) { 
                $errors[] = 'Please select a valid state.';
            }
            if ($postcode === '') { 
                $errors[] = 'Postcode is required.'; 
            }
            if (!preg_match('/^\d{5}$/', $postcode)) { 
                $errors[] = 'Postcode must be exactly 5 digits.'; 
            }
            if ($contact === '') { 
                $errors[] = 'Contact number is required.'; 
            }
            if (!preg_match('/^0[0-9]{9,10}$/', $contact)) { 
                $errors[] = 'Enter a valid contact number.'; 
            }
            if (!in_array($status, $validStatuses)) { 
                $errors[] = 'Please select a valid status.'; 
            }
            if (empty($items)) { 
                $errors[] = 'Select at least one accepted item type.'; 
            }

            // Check for duplicates
            if (empty($errors)) {
                // Check if centre with same name already exists
                $checkStmt = $conn->prepare("SELECT centreID FROM tblcentre WHERE name = ?");
                $checkStmt->bind_param('s', $name);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $errors[] = 'A centre with this name already exists. Please use a different name.';
                }
                
                // Check if centre with same contact number already exists
                $checkContactStmt = $conn->prepare("SELECT centreID FROM tblcentre WHERE contact = ?");
                $checkContactStmt->bind_param('s', $contact);
                $checkContactStmt->execute();
                $checkContactResult = $checkContactStmt->get_result();
                
                if ($checkContactResult->num_rows > 0) {
                    $errors[] = 'A centre with this contact number already exists. Please use a different contact number.';
                }
            }

            if (empty($errors)) {
                // Start transaction
                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO tblcentre (name, address, state, postcode, contact, status) 
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param('ssssss', $name, $address, $state, $postcode, $contact, $status);
                    $stmt->execute();
                    $newID = $conn->insert_id;
                    
                    // Insert accepted item types
                    if (!empty($items)) {
                        $stmtType = $conn->prepare("INSERT INTO tblcentre_accepted_type 
                                                    (centreID, itemTypeID) VALUES (?, ?)");
                        foreach ($items as $typeID) {
                            $typeID = (int)$typeID;
                            $stmtType->bind_param('ii', $newID, $typeID);
                            $stmtType->execute();
                        }
                    }
                    
                    $conn->commit();
                    $_SESSION['successMsg'] = 'Centre added successfully.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            } else {
                $_SESSION['errorMsg'] = implode(' ', $errors);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

        // edit
        } elseif ($action === 'edit') {
            $centreID = (int)($_POST['centreID'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $postcode = trim($_POST['postcode'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $items = $_POST['items'] ?? [];

            // Validation
            $errors = [];
            if ($centreID <= 0) {
                $errors[] = 'Invalid centre ID.';
            }
            if ($name === '') {
                $errors[] = 'Centre name is required.';
            }
            if (strlen($name) > 100) {
                $errors[] = 'Centre name must be 100 characters or fewer.';
            }
            if ($address === '') {
                $errors[] = 'Address is required.';
            }
            if (strlen($address) < 20) { 
                $errors[] = 'Address must be at least 20 characters long.'; 
            }
            if (strlen($address) > 255) { 
                $errors[] = 'Address must be less than 255 characters.'; 
            }
            if (!in_array($state, $validStates)) {
                $errors[] = 'Please select a valid state.';
            }
            if ($postcode === '') {
                $errors[] = 'Postcode is required.';
            }
            if (!preg_match('/^\d{5}$/', $postcode)) {
                $errors[] = 'Postcode must be exactly 5 digits.';
            }
            if ($contact === '') {
                $errors[] = 'Contact number is required.';
            }
            if (!preg_match('/^0[0-9]{9,}$/', $contact)) {
                $errors[] = 'Enter a valid contact number.';
            }
            if (!in_array($status, $validStatuses)) {
                $errors[] = 'Please select a valid status.';
            }
            if (empty($items)) {
                $errors[] = 'Select at least one accepted item type.';
            }
            
            // Check for duplicates (excluding current centre)
            if (empty($errors)) {
                // Check if another centre with same name already exists
                $checkStmt = $conn->prepare("SELECT centreID FROM tblcentre WHERE name = ? AND centreID != ?");
                $checkStmt->bind_param('si', $name, $centreID);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $errors[] = 'Another centre with this name already exists. Please use a different name.';
                }
                
                // Check if another centre with same contact number already exists
                $checkContactStmt = $conn->prepare("SELECT centreID FROM tblcentre WHERE contact = ? AND centreID != ?");
                $checkContactStmt->bind_param('si', $contact, $centreID);
                $checkContactStmt->execute();
                $checkContactResult = $checkContactStmt->get_result();
                
                if ($checkContactResult->num_rows > 0) {
                    $errors[] = 'Another centre with this contact number already exists. Please use a different contact number.';
                }
            }

            if (empty($errors)) {
                // Block status change to Inactive if centre has active items
                if ($status === 'Inactive') {
                    $activeItemStmt = $conn->prepare("
                        SELECT COUNT(*) AS cnt FROM tblitem i
                        JOIN tblcollection_request r ON i.requestID = r.requestID
                        WHERE i.centreID = ?
                        AND i.status IN ('Pending', 'Collected', 'Received')
                    ");
                    $activeItemStmt->bind_param('i', $centreID);
                    $activeItemStmt->execute();
                    $activeItemRow = $activeItemStmt->get_result()->fetch_assoc();

                    if ($activeItemRow['cnt'] > 0) {
                        $_SESSION['errorMsg'] = 'Cannot set centre to Inactive — it has ' . $activeItemRow['cnt'] . ' active item(s) currently assigned to it.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }

                    // Block if an Ongoing job is dropping off to this centre
                    $ongoingJobStmt = $conn->prepare("
                        SELECT COUNT(*) AS cnt FROM tblitem i
                        JOIN tblcollection_request r ON i.requestID = r.requestID
                        JOIN tbljob j ON j.requestID = r.requestID
                        WHERE i.centreID = ?
                        AND j.status = 'Ongoing'
                    ");
                    $ongoingJobStmt->bind_param('i', $centreID);
                    $ongoingJobStmt->execute();
                    $ongoingJobRow = $ongoingJobStmt->get_result()->fetch_assoc();

                    if ($ongoingJobRow['cnt'] > 0) {
                        $_SESSION['errorMsg'] = 'Cannot set centre to Inactive — a collector is currently en route to drop off items at this centre.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                }

                // Start transaction
                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare(
                        "UPDATE tblcentre SET name=?, address=?, state=?, postcode=?, contact=?, 
                        status=? WHERE centreID=?"
                    );
                    $stmt->bind_param('ssssssi', $name, $address, $state, $postcode, $contact, $status, $centreID);
                    $stmt->execute();
                    
                    // Delete existing accepted types
                    $deleteStmt = $conn->prepare("DELETE FROM tblcentre_accepted_type WHERE centreID = ?");
                    $deleteStmt->bind_param('i', $centreID);
                    $deleteStmt->execute();
                    
                    // Insert new accepted item types
                    if (!empty($items)) {
                        $stmtType = $conn->prepare("INSERT INTO tblcentre_accepted_type 
                                                    (centreID, itemTypeID) VALUES (?, ?)");
                        foreach ($items as $typeID) {
                            $typeID = (int)$typeID;
                            $stmtType->bind_param('ii', $centreID, $typeID);
                            $stmtType->execute();
                        }
                    }
                    
                    $conn->commit();
                    $_SESSION['successMsg'] = 'Centre updated successfully.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            } else {
                $_SESSION['errorMsg'] = implode(' ', $errors);
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

        // delete
        } elseif ($action === 'delete') {
            $centreID = (int)($_POST['centreID'] ?? 0);
            if ($centreID <= 0) {
                $_SESSION['errorMsg'] = 'Invalid centre ID.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                // Block deletion if items are assigned to this centre
                $itemCheckStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tblitem WHERE centreID = ?");
                $itemCheckStmt->bind_param('i', $centreID);
                $itemCheckStmt->execute();
                $itemCheckRow = $itemCheckStmt->get_result()->fetch_assoc();
                if ($itemCheckRow['cnt'] > 0) {
                    $_SESSION['errorMsg'] = 'Cannot delete this centre with ' . $itemCheckRow['cnt'] . ' item record(s) linked to it.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                // Start transaction
                $conn->begin_transaction();
                try {
                    // Delete accepted types first
                    $deleteStmt = $conn->prepare("DELETE FROM tblcentre_accepted_type WHERE centreID = ?");
                    $deleteStmt->bind_param('i', $centreID);
                    $deleteStmt->execute();
                    
                    // Then delete centre
                    $stmt = $conn->prepare("DELETE FROM tblcentre WHERE centreID = ?");
                    $stmt->bind_param('i', $centreID);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $conn->commit();
                        $_SESSION['successMsg'] = 'Centre deleted successfully.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $conn->rollback();
                        $_SESSION['errorMsg'] = 'Centre not found or could not be deleted.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
                    header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                }
            }
        }
    }
}

// search
$search = trim($_GET['search'] ?? '');
$searchParam = '%' . $search . '%';

$sql = "SELECT c.*, GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR '||') AS acceptedItems,
        GROUP_CONCAT(t.itemTypeID ORDER BY t.name SEPARATOR '||') AS acceptedItemIDs,
        (SELECT COUNT(*) FROM tblitem i WHERE i.centreID = c.centreID) AS itemCount,
        (SELECT COUNT(*) FROM tblitem i WHERE i.centreID = c.centreID
            AND i.status IN ('Pending','Collected','Received','Processed')) AS activeItemCount,
        (SELECT COUNT(*) FROM tblitem i
            JOIN tbljob j ON j.requestID = i.requestID
            WHERE i.centreID = c.centreID AND j.status = 'Ongoing') AS ongoingJobCount
        FROM tblcentre c
        LEFT JOIN tblcentre_accepted_type cat ON c.centreID = cat.centreID
        LEFT JOIN tblitem_type t ON cat.itemTypeID = t.itemTypeID AND t.name != 'Other Electronics'
        WHERE c.name LIKE ? OR c.contact LIKE ? OR c.state LIKE ? OR c.postcode LIKE ?
        GROUP BY c.centreID
        ORDER BY c.centreID";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $searchParam, $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result  = $stmt->get_result();
$centres = [];
while ($row = $result->fetch_assoc()) {
    $row['items'] = $row['acceptedItems'] ? explode('||', $row['acceptedItems']) : [];
    $row['itemIDs'] = $row['acceptedItemIDs'] ? explode('||', $row['acceptedItemIDs']) : [];
    $row['id'] = (int)$row['centreID'];
    $centres[] = $row;
}

$totalCentres = count($centres);

$centresJson = json_encode($centres, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$itemTypesJson = json_encode($allItemTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Centres - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .back-button:hover {
            background: var(--sec-bg-color);
            border-color: var(--MainBlue);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .centre-count {
            color: var(--Gray);
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .dark-mode .centre-count {
            color: var(--BlueGray);
        }

        .search-add-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            flex: 1;
        }

        .search-input {
            display: block;
            width: 100%;
            border: 1px solid var(--Gray);
            border-radius: 6px;
            font-size: 1rem;
            color: var(--text-color);
            background-color: var(--bg-color);
            flex: 1;
            padding: 0.875rem 1.25rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border: 1px solid var(--MainBlue);
            box-shadow: 0 0 0 3px var(--LightBlue);
            outline: none;
        }

        .dark-mode .search-input:focus {
            box-shadow: 0 0 0 3px var(--DarkerBlue);
        }

        .search-input::placeholder {
            color: var(--Gray);
        }

        .search-btn {
            padding: 0.5rem 1.5rem;
            background: var(--MainBlue);
            color: var(--White);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .clear-btn {
            padding: 0.5rem 1.5rem;
            background: var(--Gray);
            color: var(--White);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }

        .clear-btn:hover {
            background: var(--BlueGray);
        }
        
        .add-btn{
            padding: 0.5rem 1.5rem;
            background: var(--MainBlue);
            color: var(--White);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            align-self: stretch;
        }

        .centres-table-container {
            background: var(--bg-color);
            border-radius: 16px;
            box-shadow: 0 4px 12px var(--shadow-color);
            overflow: hidden;
        }

        .dark-mode .centres-table-container {
            box-shadow: 0 4px 8px var(--BlueGray);
        }

        .centres-table {
            width: 100%;
            border-collapse: collapse;
        }

        .centres-table thead {
            background: var(--LightBlue);
            color: var(--text-color);
        }

        .dark-mode .centres-table thead {
            background: var(--LowMainBlue);
        }

        .centres-table th {
            padding: 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .centres-table td {
            text-align: center;
            padding: 1.25rem;
            border-bottom: 1px solid var(--BlueGray);
            color: var(--text-color);
        }

        .centres-table .left {
            text-align: left;
        }

        .centres-table tbody tr {
            transition: all 0.2s ease;
        }

        .centres-table tbody tr:hover {
            background: var(--shadow-color);
        }

        .dark-mode .centres-table tbody tr:hover {
            background: var(--Gray);
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-btn {
            width: 2.25rem;
            height: 2.25rem;
            background: var(--MainBlue);
            color: var(--White);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
        }

        .action-btn:hover {
            background: var(--DarkBlue);
            transform: translateY(-2px);
        }

        .action-btn img {
            width: 1rem;
            height: 1rem;
        }

        .dark-mode .search-btn,
        .dark-mode .add-btn, 
        .dark-mode .action-btn {
            background-color: var(--DarkerMainBlue);
        }

        .search-btn:hover,
        .add-btn:hover, 
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--BlueGray);
            transition: all 0.3s ease;
        }

        .dark-mode .search-btn:hover,
        .dark-mode .add-btn:hover, 
        .dark-mode .action-btn:hover {
            box-shadow: 0 4px 12px var(--Gray);
        }

        .action-btn.delete-btn {
            background: red;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: var(--DarkBlue);
            color: var(--White);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            z-index: 3000;
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.25s, transform 0.25s;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: none;
        }

        .toast.success {
            background: #28a745;
            color: var(--White);
        }

        .toast.error {
            background: #dc3545;
            color: var(--White);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--Gray);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 20px;
            padding: 2.5rem;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .modal h2 {
            font-size: 1.6rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-right: 2rem;
        }

        .modal h2.danger { 
            color: red; 
        }

        .modal-small { 
            max-width: 480px; 
        }

        .view-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .view-field label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--BlueGray);
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .view-field span {
            font-size: 1rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .view-field.full { 
            grid-column: 1 / -1; 
        }

        .view-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.25rem;
        }

        .view-tag {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            background: var(--LightBlue);
            color: var(--MainBlue);
        }

        .dark-mode .view-tag {
            background: var(--LowMainBlue);
            color: var(--White);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-field { 
            display: flex; 
            flex-direction: column; 
            gap: 0.4rem; 
        }

        .form-field.full { 
            grid-column: 1 / -1; 
        }

        .form-field label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--BlueGray);
        }

        .form-field input,
        .form-field select,
        .form-field textarea {
            padding: 0.75rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text-color);
            background: var(--bg-color);
            transition: border 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 3px var(--LightBlue);
            outline: none;
        }

        .form-field textarea { 
            resize: vertical; 
            min-height: 80px; 
        }

        .form-field select { 
            padding-left: 0.5rem; 
        }

        .field-error { 
            font-size: 0.8rem; 
            color: red; 
            margin-top: 0.2rem; 
            display: none; 
        }

        .field-error.show { 
            display: block; 
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem 1rem;
            padding: 0.75rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            background: var(--bg-color);
            overflow-y: auto;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-color);
            cursor: pointer;
            padding: 0.25rem 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: var(--MainBlue);
            cursor: pointer;
            flex-shrink: 0;
            padding: 0;
            border: none;
            border-radius: 0;
            box-shadow: none;
        }

        .form-field .select-all-label {
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            cursor: pointer;
            color: var(--text-color);
        }

        .select-all-label input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            accent-color: var(--MainBlue);
            cursor: pointer;
            flex-shrink: 0;
        }

        .checkbox-group input[type="checkbox"]:focus,
        .select-all-label input[type="checkbox"]:focus {
            box-shadow: none;
            outline: 2px solid var(--MainBlue);
            border-color: transparent;
        }

        .delete-info-box {
            background: var(--LightBlue);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-top: 1rem;
            margin-bottom: 1.25rem;
        }

        .dark-mode .delete-info-box { 
            background: var(--LowMainBlue); 
        }

        .delete-info-box strong { 
            color: var(--MainBlue); 
        }

        .delete-info-box .info-name { 
            color: var(--text-color); 
            font-weight: 600; 
        }

        .delete-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none; 
            box-shadow: none;
        }

        .modal-buttons {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }

        .btn-modal {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn-cancel {
            background: var(--BlueGray);
            color: var(--text-color);
        }

        .btn-cancel:hover { 
            background: var(--Gray); 
        }

        .btn-confirm-delete {
            background: red;
            color: var(--White);
        }

        .btn-confirm-delete:hover {
            background: red;
            transform: translateY(-2px);
        }

        .btn-save {
            background: var(--MainBlue);
            color: var(--White);
        }

        .btn-save:hover {
            background: var(--DarkerBlue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--BlueGray);
        }

        .dark-mode .btn-save { 
            background: var(--DarkerMainBlue); 
        }

        .modal-close-btn {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            width: 2rem;
            height: 2rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .modal-close-btn:hover {
            opacity: 0.5;
        }

        .modal-close-btn img {
            width: 1.25rem;
            height: 1.25rem;
        }

        .dark-icon { 
            display: none; 
        }

        .dark-mode .light-icon {
             display: none; 
        }

        .dark-mode .dark-icon { 
            display: inline; 
        }

        /* Status badge styles */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-active {
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .status-inactive {
            background: hsl(0,   70%, 90%); 
            color: hsl(0,   70%, 35%); 
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .centres-table {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }
            
            .search-add-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                flex-wrap: wrap;
            }
            
            .search-form .search-input {
                flex: 1 1 100%;
            }

            .search-form .search-btn,
            .search-form .clear-btn {
                flex: 1;
                text-align: center;
            }
            
            .clear-btn {
                display: block;
            }

            .add-btn {
                width: 100%;
            }

            .centres-table-container {
                overflow-x: auto;
            }

            .centres-table {
                min-width: 800px;
            }

            .view-grid, .form-grid { 
                grid-template-columns: 1fr; 
            }

            .view-field.full, .form-field.full { 
                grid-column: 1; 
            }

            .modal { 
                padding: 1.5rem; 
            }
        }
    </style>

</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <!-- Logo + Name & Navbar -->
    <header>
        <!-- Logo + Name -->
        <section class="c-logo-section">
            <a href="../../html/admin/aHome.php" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <!-- Menu Links -->

        <!-- Menu Links Mobile -->
        <nav class="c-navbar-side">
            <img src="../../assets/images/icon-menu.svg" alt="icon-menu" onclick="showMenu()" class="c-icon-btn" id="menuBtn">
            <div id="sidebarNav" class="c-navbar-side-menu">
                
                <img src="../../assets/images/icon-menu-close.svg" alt="icon-menu-close" onclick="hideMenu()" class="close-btn"  id="closeBtn">
                <div class="c-navbar-side-items">
                    <section class="c-navbar-side-more">
                        <button id="themeToggleMobile">
                            <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon" >
                        </button>
                        <a href="../../html/common/Setting.php">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>

                    <a href="../../html/admin/aHome.php">Home</a>
                    <a href="../../html/admin/aRequests.php">Requests</a><br>
                    <a href="../../html/admin/aJobs.php">Jobs</a><br>
                    <a href="../../html/admin/aIssue.php">Issue</a><br>
                    <a href="../../html/admin/aOperations.php">Operations</a><br>
                    <a href="../../html/admin/aReport.php">Report</a>
                </div>
            </div>

        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <a href="../../html/admin/aHome.php">Home</a>
            <a href="../../html/admin/aRequests.php">Requests</a><br>
            <a href="../../html/admin/aJobs.php">Jobs</a><br>
            <a href="../../html/admin/aIssue.php">Issue</a><br>
            <a href="../../html/admin/aOperations.php">Operations</a><br>
            <a href="../../html/admin/aReport.php">Report</a>
        </nav>          
        <section class="c-navbar-more">
            <button id="themeToggleDesktop">
                <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon" >
            </button>
            <a href="../../html/common/Setting.php">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
        
    </header>
    <hr>

    <!-- Main Content -->
    <main>
        <div class="page-container">
            <a href="../../html/admin/aHome.php" class="back-button">
                ← Back to Home
            </a>

            <div class="page-header">
                <h1>Manage Collection Centres</h1>
                <p class="centre-count">Total Centres: <?php echo $totalCentres; ?></p>
            </div>

            <div class="search-add-section">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" 
                        placeholder="Search by name, contact, state..." 
                        value="<?php echo sanitize($search); ?>" >
                    <button type="submit" class="search-btn">Search</button>
                    <?php if ($search): ?>
                        <a href="?" class="clear-btn">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
                <button class="add-btn" onclick="openAddModal()">
                    + Add New Centre
                </button>
            </div>

            <div class="centres-table-container">
                <table class="centres-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="left">Name</th>
                            <th>Contact</th>
                            <th>State</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                         <?php if (empty($centres)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <h3>No Centres Found</h3>
                                        <p><?php echo $search ? 'Try a different search term.' : 'Add a new centre to get started.'; ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($centres as $i => $c): ?>
                                <tr>
                                    <td>#<?php echo sanitize($c['centreID']); ?></td>
                                    <td class="left"><?php echo sanitize($c['name']) ?></td>
                                    <td><?php echo sanitize($c['contact']) ?></td>
                                    <td><?php echo sanitize($c['state']) ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $c['status'] === 'Active' ? 'status-active' : 'status-inactive' ?>">
                                            <?php echo sanitize($c['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn" onclick="openViewModal(<?php echo $i ?>)" title="View">
                                                <img src="../../assets/images/view-icon-white.svg" alt="View">
                                            </button>
                                            <button class="action-btn" onclick="openEditModal(<?php echo $i ?>)" title="Edit">
                                                <img src="../../assets/images/edit-icon-white.svg" alt="Edit">
                                            </button>
                                            <?php if ((int)$c['itemCount'] > 0): ?>
                                                <button class="action-btn delete-btn delete-disabled" disabled
                                                    title="Cannot delete: this centre has <?php echo (int)$c['itemCount'] ?> linked item(s)">
                                                    <img src="../../assets/images/delete-icon-white.svg" alt="Delete">
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn delete-btn" onclick="openDeleteModal(<?php echo $i ?>)" title="Delete">
                                                    <img src="../../assets/images/delete-icon-white.svg" alt="Delete">
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <hr>
    <!-- Footer -->
    <footer>
        <!-- Column 1 -->
        <section class="c-footer-info-section">
            <a href="../../html/admin/aHome.php">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
            </a>
            <div class="c-text">AfterVolt</div>
            

            <div class="c-text c-text-center">
                Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.
            </div>
            <div class="c-text c-text-label">
                +60 12 345 6789
            </div>
            <div class="c-text">
                abc@gmail.com
            </div>
        </section>
        
        <!-- Column 2 -->
        <section class="c-footer-links-section">
            <div>
                <b>Management</b><br>
                <a href="../../html/admin/aRequests.php">Collection Requests</a><br>
                <a href="../../html/admin/aJobs.php">Collection Jobs</a><br>
                <a href="../../html/admin/aIssue.php">Issue</a><br>
            </div>
            <div>
                <b>System Operation</b><br>
                <a href="../../html/admin/aProviders.php">Providers</a><br>
                <a href="../../html/admin/aCollectors.php">Collectors</a><br>
                <a href="../../html/admin/aVehicles.php">Vehicles</a><br>
                <a href="../../html/admin/aCentres.php">Collection Centres</a><br>
                <a href="../../html/admin/aItemProcessing.php">Item Processing</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/Profile.php">Edit Profile</a><br>
                <a href="../../html/common/Setting.php">Setting</a>
            </div>
        </section>
    </footer>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <!-- View Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal">
            <button class="modal-close-btn" onclick="closeModal('viewModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Centre Details</h2>
                <div class="view-grid">
                    <div class="view-field full">
                        <label>Centre Name</label>
                        <span id="view-name">—</span>
                    </div>
                    <div class="view-field full">
                        <label>Address</label>
                        <span id="view-address">—</span>
                    </div>
                    <div class="view-field">
                        <label>State</label>
                        <span id="view-state">—</span>
                    </div>
                    <div class="view-field">
                        <label>Postcode</label>
                        <span id="view-postcode">—</span>
                    </div>
                    <div class="view-field">
                        <label>Contact (Phone)</label>
                        <span id="view-contact">—</span>
                    </div>
                    <div class="view-field">
                        <label>Status</label>
                        <span id="view-status">—</span>
                    </div>
                    <div class="view-field">
                        <label>Collected Items</label>
                        <span id="view-item-count">—</span>
                    </div>
                    <div class="view-field">
                        <label>Current Accepted Item Types</label>
                        <span id="view-accepted-count">—</span>
                    </div>
                    <div class="view-field full">
                        <label>Accepted Item Types</label>
                        <div class="view-tags" id="view-items">—</div>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button class="btn-modal btn-cancel" onclick="closeModal('viewModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <button class="modal-close-btn" onclick="closeModal('editModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Edit Centre</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit-id" name="centreID">
                <div class="form-grid">
                    <div class="form-field full">
                        <label for="edit-name">Centre Name</label>
                        <input type="text" id="edit-name" name="name" required>
                        <span class="field-error" id="err-edit-name"></span>
                    </div>
                    <div class="form-field full">
                        <label for="edit-address">Address</label>
                        <textarea id="edit-address" name="address"></textarea>
                        <span class="field-error" id="err-edit-address"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-state">State</label>
                        <select id="edit-state" name="state">
                            <option value="">-- Select State --</option>
                            <?php foreach ($validStates as $s): ?>
                                <option value="<?php echo $s ?>"><?php echo $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-error" id="err-edit-state"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-postcode">Postcode</label>
                        <input type="text" id="edit-postcode" name="postcode">
                        <span class="field-error" id="err-edit-postcode"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-contact">Contact (Phone)</label>
                        <input type="text" id="edit-contact" name="contact" required>
                        <span class="field-error" id="err-edit-contact"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-status">Status</label>
                        <select id="edit-status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-field full">
                        <label>Accepted Item Types</label>
                        <label class="select-all-label">
                            <input type="checkbox" id="edit-select-all">
                            Select All
                        </label>
                        <div class="checkbox-group" id="edit-items-group">
                            <?php foreach ($allItemTypes as $type): ?>
                                <label>
                                    <input type="checkbox" name="items[]" value="<?php echo $type['itemTypeID'] ?>">
                                    <?php echo sanitize($type['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="field-error" id="err-edit-items"></span>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal modal-small">
            <button class="modal-close-btn" onclick="closeModal('deleteModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2 class="danger">Confirm Deletion</h2>
            <p>Are you sure you want to delete this centre? This action <strong>cannot be undone</strong>.</p>
            <div class="delete-info-box">
                <strong>Centre: </strong><span class="info-name" id="delete-name">—</span><br>
                <strong>State: </strong><span class="info-name" id="delete-state">—</span>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete-id" name="centreID">
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-confirm-delete">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <button class="modal-close-btn" onclick="closeModal('addModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Add New Centre</h2>
            <form id="addForm" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-field full">
                        <label for="add-name">Centre Name</label>
                        <input type="text" id="add-name" name="name" placeholder="Enter centre name..." required>
                        <span class="field-error" id="err-add-name"></span>
                    </div>
                    <div class="form-field full">
                        <label for="add-address">Address</label>
                        <textarea id="add-address" name="address" placeholder="Enter full address..."></textarea>
                        <span class="field-error" id="err-add-address"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-state">State</label>
                        <select id="add-state" name="state">
                            <option value="">-- Select State --</option>
                            <?php foreach ($validStates as $s): ?>
                                <option value="<?php echo $s ?>"><?php echo $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-error" id="err-add-state"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-postcode">Postcode</label>
                        <input type="text" id="add-postcode" name="postcode" placeholder="e.g. 50450">
                        <span class="field-error" id="err-add-postcode"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-contact">Contact (Phone)</label>
                        <input type="text" id="add-contact" name="contact" placeholder="e.g. 0123456789" required>
                        <span class="field-error" id="err-add-contact"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-status">Status</label>
                        <select id="add-status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-field full">
                        <label>Accepted Item Types</label>
                        <label class="select-all-label">
                            <input type="checkbox" id="add-select-all">
                            Select All
                        </label>
                        <div class="checkbox-group" id="add-items-group">
                            <?php foreach ($allItemTypes as $type): ?>
                                <label>
                                    <input type="checkbox" name="items[]" value="<?php echo $type['itemTypeID'] ?>">
                                    <?php echo sanitize($type['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="field-error" id="err-add-items"></span>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Add Centre</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        // data from php
        const centres = <?php echo $centresJson ?>;
        const itemTypes = <?php echo $itemTypesJson ?>;
        const successMsg = <?php echo json_encode($successMsg) ?>;
        const errorMsg = <?php echo json_encode($errorMsg) ?>;
        
        // Toast
        function showToast(msg,type) {
            var t = document.getElementById("toast");
            t.className = "toast " + type;
            t.textContent = msg;
            t.classList.add("show");
            setTimeout(function() { t.classList.remove("show"); }, 3000);
        }

        if (successMsg) showToast(successMsg, 'success');
        if (errorMsg) showToast(errorMsg, 'error');

        // modal functions
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('active');
            document.body.classList.add('stopScroll');
            modal.querySelector('.modal').scrollTop = 0;
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.classList.remove('stopScroll');
        }

        // view
        function openViewModal(index) {
            const c = centres[index];
            document.getElementById('view-name').textContent = c.name;
            document.getElementById('view-address').textContent = c.address;
            document.getElementById('view-state').textContent = c.state;
            document.getElementById('view-postcode').textContent = c.postcode;
            document.getElementById('view-contact').textContent = c.contact;
            document.getElementById('view-status').textContent = c.status;
            document.getElementById('view-accepted-count').textContent = c.items.length + ' type(s)';
            document.getElementById('view-item-count').textContent = parseInt(c.itemCount) + ' item(s)';

            // Render item tags
            const tagsContainer = document.getElementById('view-items');
            tagsContainer.innerHTML = c.items.length
                ? c.items.map(item => `<span class="view-tag">${item}</span>`).join('')
                : '—';

            openModal('viewModal');
        }

        // edit
        function openEditModal(index) {
            const c = centres[index];
            document.getElementById('edit-id').value = c.id;
            document.getElementById('edit-name').value = c.name;
            document.getElementById('edit-address').value = c.address;
            document.getElementById('edit-state').value = c.state;
            document.getElementById('edit-postcode').value = c.postcode;
            document.getElementById('edit-contact').value = c.contact;
            document.getElementById('edit-status').value = c.status;

            // Disable Inactive option if centre has active items or an ongoing job
            const inactiveOption = document.querySelector('#edit-status option[value="Inactive"]');
            const isLocked = parseInt(c.activeItemCount) > 0 || parseInt(c.ongoingJobCount) > 0;
            inactiveOption.disabled = isLocked;
            if (isLocked) {
                inactiveOption.text = 'Inactive (unavailable — active items assigned)';
                // If somehow it's already set to Inactive (edge case), force back to Active
                if (document.getElementById('edit-status').value === 'Inactive') {
                    document.getElementById('edit-status').value = 'Active';
                }
            } else {
                inactiveOption.text = 'Inactive';
            }

            // Get the accepted item IDs directly from the data
            const acceptedItemIds = c.itemIDs ? c.itemIDs.map(id => parseInt(id)) : [];

            // Check the corresponding checkboxes
            document.querySelectorAll('#edit-items-group input[type="checkbox"]').forEach(cb => {
                cb.checked = acceptedItemIds.includes(parseInt(cb.value));
            });

            const editAllBoxes = [...document.querySelectorAll('#edit-items-group input[type="checkbox"]')];
            const editSelectAll = document.getElementById('edit-select-all');
            editSelectAll.checked = editAllBoxes.every(c => c.checked);
            editSelectAll.indeterminate = !editSelectAll.checked && editAllBoxes.some(c => c.checked);
            
            clearErrors('edit');
            openModal('editModal');
        }

        // delete
        function openDeleteModal(index) {
            const c = centres[index];
            document.getElementById('delete-id').value = c.id;
            document.getElementById('delete-name').textContent = c.name;
            document.getElementById('delete-state').textContent = c.state;
            openModal('deleteModal');
        }

        // add
        function openAddModal() {
            document.getElementById('addForm').reset();
            document.querySelectorAll('#add-items-group input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.getElementById('add-select-all').checked = false;
            document.getElementById('add-select-all').indeterminate = false;
            clearErrors('add');
            openModal('addModal');
        }

        // validation
        function setError(fieldId, errorId, message) {
            const field = document.getElementById(fieldId);
            const err = document.getElementById(errorId);
            if (field) field.classList.add('invalid');
            if (err) { 
                err.textContent = message; err.classList.add('show'); 
            }
        }

        function clearErrors(prefix) {
            document.querySelectorAll(`#${prefix}Form .invalid`).forEach(el => el.classList.remove('invalid'));
            document.querySelectorAll(`#${prefix}Form .field-error`).forEach(el => { 
                el.textContent = ''; 
                el.classList.remove('show'); 
            });
            
            // Remove invalid class from checkbox groups
            const addGroup = document.getElementById(`${prefix}-items-group`);
            if (addGroup) addGroup.classList.remove('invalid');
            
            const editGroup = document.getElementById(`edit-items-group`);
            if (editGroup) editGroup.classList.remove('invalid');
        }

        function validateForm(prefix) {
            clearErrors(prefix);
            let valid = true;

            const name = document.getElementById(`${prefix}-name`).value.trim();
            const address = document.getElementById(`${prefix}-address`).value.trim();
            const state = document.getElementById(`${prefix}-state`).value;
            const postcode = document.getElementById(`${prefix}-postcode`).value.trim();
            const contact = document.getElementById(`${prefix}-contact`).value.trim();

            if (!name) {
                setError(`${prefix}-name`, `err-${prefix}-name`, 'Centre name is required.');
                valid = false;
            } else if (name.length > 100) {
                setError(`${prefix}-name`, `err-${prefix}-name`, 'Centre name must be 100 characters or fewer.');
                valid = false;
            }

            if (!address) {
                setError(`${prefix}-address`, `err-${prefix}-address`, 'Address is required.');
                valid = false;
            } else if (address.length < 20) {
                setError(`${prefix}-address`, `err-${prefix}-address`, 'Address must be at least 20 characters.');
                valid = false;
            } else if (address.length > 255) {
                setError(`${prefix}-address`, `err-${prefix}-address`, 'Address must be less than 255 characters.');
                valid = false;
            }

            if (!state) {
                setError(`${prefix}-state`, `err-${prefix}-state`, 'Please select a state.');
                valid = false;
            }

            if (!postcode) {
                setError(`${prefix}-postcode`, `err-${prefix}-postcode`, 'Postcode is required.');
                valid = false;
            } else if (!/^\d{5}$/.test(postcode)) {
                setError(`${prefix}-postcode`, `err-${prefix}-postcode`, 'Postcode must be exactly 5 digits.');
                valid = false;
            }

            if (!contact) {
                setError(`${prefix}-contact`, `err-${prefix}-contact`, 'Contact number is required.');
                valid = false;
            } else if (!/^0[0-9]{9,10}$/.test(contact)) {
                setError(`${prefix}-contact`, `err-${prefix}-contact`, 'Enter a valid Malaysian mobile number.');
                valid = false;
            }

            const checkedItems = document.querySelectorAll(`#${prefix}-items-group input[type="checkbox"]:checked`);
            if (checkedItems.length === 0) {
                const err = document.getElementById(`err-${prefix}-items`);
                if (err) { 
                    err.textContent = 'Select at least one accepted item type.'; 
                    err.classList.add('show'); 
                }
                valid = false;
            }

            return valid;
        }

        // Clear inline errors on input change
        document.querySelectorAll('.form-field input, .form-field select, .form-field textarea').forEach(el => {
            el.addEventListener('input', function () {
                this.classList.remove('invalid');
                const errEl = document.getElementById('err-' + this.id);
                if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
            });
            el.addEventListener('change', function () {
                this.dispatchEvent(new Event('input'));
            });
        });

        // Form submission handlers
        document.getElementById('addForm').addEventListener('submit', function (e) {
            if (!validateForm('add')) e.preventDefault();
        });

        document.getElementById('editForm').addEventListener('submit', function (e) {
            if (!validateForm('edit')) e.preventDefault();
        });

        // Select all functionality for item checkboxes
        function setupSelectAll(selectAllId, groupId) {
            const selectAll = document.getElementById(selectAllId);
            const checkboxes = document.querySelectorAll(`#${groupId} input[type="checkbox"]`);

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = this.checked);
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function () {
                    selectAll.checked = [...checkboxes].every(c => c.checked);
                    selectAll.indeterminate = !selectAll.checked && [...checkboxes].some(c => c.checked);
                });
            });
        }

        setupSelectAll('add-select-all', 'add-items-group');
        setupSelectAll('edit-select-all', 'edit-items-group');


    </script>
</body>
</html>