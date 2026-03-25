<?php
session_start();
include("../../php/dbConn.php");

// check if user is logged in
include("../../php/sessionCheck.php");

// Check if user is admin
if ($_SESSION['userType'] !== 'admin') {
    header("Location: ../../index.html");
    exit();
}

$userID   = (int)$_SESSION['userID'];
$userType = $_SESSION['userType'];

function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function logActivity($conn, $userID, $type, $action, $description, $requestID = null, $jobID = null) {
    $stmt = $conn->prepare("INSERT INTO tblactivity_log (requestID, jobID, userID, type, action, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iiisss', $requestID, $jobID, $userID, $type, $action, $description);
    $stmt->execute();
    $stmt->close();
}

$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $itemID    = (int)$_POST['itemID'];
    $newStatus = sanitize($_POST['newStatus']);

    $adminAllowed = ['Processed', 'Recycled'];
    $final        = ['Processed', 'Recycled', 'Cancelled'];

    if (in_array($newStatus, $adminAllowed)) {
        $checkStmt = $conn->prepare("SELECT status, requestID FROM tblitem WHERE itemID = ?");
        $checkStmt->bind_param('i', $itemID);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($checkRes && $checkRes['status'] === 'Received') {
            $requestID = (int)$checkRes['requestID'];

            $updStmt = $conn->prepare("UPDATE tblitem SET status = ? WHERE itemID = ?");
            $updStmt->bind_param('si', $newStatus, $itemID);
            $updStmt->execute();
            $updStmt->close();

            $itemNameStmt = $conn->prepare("SELECT it.description, itype.name FROM tblitem it JOIN tblitem_type itype ON it.itemTypeID = itype.itemTypeID WHERE it.itemID = ?");
            $itemNameStmt->bind_param('i', $itemID);
            $itemNameStmt->execute();
            $itemInfo = $itemNameStmt->get_result()->fetch_assoc();
            $itemNameStmt->close();
            $itemLabel = $itemInfo ? $itemInfo['name'] . ' – ' . $itemInfo['description'] : "Item ID $itemID";

            $logDesc = "Item $itemLabel (ID: $itemID) - Status changed to $newStatus";
            logActivity($conn, $userID, 'Item', 'Status Change', $logDesc, $requestID);

            if (in_array($newStatus, $final)) {
                $pendingStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tblitem WHERE requestID = ? AND status NOT IN ('Processed','Recycled','Cancelled')");
                $pendingStmt->bind_param('i', $requestID);
                $pendingStmt->execute();
                $pendingRow = $pendingStmt->get_result()->fetch_assoc();
                $pendingStmt->close();

                if ((int)$pendingRow['cnt'] === 0) {
                    $reqUpd = $conn->prepare("UPDATE tblcollection_request SET status = 'Completed' WHERE requestID = ?");
                    $reqUpd->bind_param('i', $requestID);
                    $reqUpd->execute();
                    $reqUpd->close();

                    logActivity($conn, $userID, 'Request', 'Status Change', "Changed to Completed – all items processed/recycled/cancelled", $requestID);
                }
            }

            $message     = 'Item status updated successfully.';
            $messageType = 'success';
        } else {
            $message     = 'Status can only be changed when the item is Received.';
            $messageType = 'error';
        }
    } else {
        $message     = 'Invalid status. Admin can only set items to Processed or Recycled.';
        $messageType = 'error';
    }
}

$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$filterReq    = isset($_GET['requestID']) ? (int)$_GET['requestID'] : 0;
$search       = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT 
            i.itemID,
            i.requestID,
            i.description,
            i.model,
            i.brand,
            i.weight,
            i.length,
            i.width,
            i.height,
            i.image,
            i.status AS itemStatus,
            itype.name AS itemType,
            cr.status AS reqStatus,
            cr.pickupAddress,
            cr.pickupState,
            cr.createdAt AS reqCreated,
            u.fullname AS providerName,
            c.name AS centreName
        FROM tblitem i
        JOIN tblitem_type itype ON i.itemTypeID = itype.itemTypeID
        JOIN tblcollection_request cr ON i.requestID = cr.requestID
        JOIN tblprovider p ON cr.providerID = p.providerID
        JOIN tblusers u ON p.providerID = u.userID
        LEFT JOIN tblcentre c ON i.centreID = c.centreID
        WHERE 1=1";

$params = [];
$types  = '';

if ($filterStatus !== 'all' && $filterStatus !== '') {
    $sql     .= " AND i.status = ?";
    $params[] = $filterStatus;
    $types   .= 's';
}
if ($filterReq > 0) {
    $sql     .= " AND i.requestID = ?";
    $params[] = $filterReq;
    $types   .= 'i';
}
if ($search !== '') {
    $like     = "%$search%";
    $sql     .= " AND (i.description LIKE ? OR itype.name LIKE ? OR u.fullname LIKE ? OR i.brand LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ssss';
}

$sql .= " ORDER BY i.itemID DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$countStmt = $conn->query("SELECT status, COUNT(*) AS cnt FROM tblitem GROUP BY status");
$counts    = ['Pending' => 0, 'Collected' => 0, 'Received' => 0, 'Processed' => 0, 'Recycled' => 0, 'Cancelled' => 0];
$totalAll  = 0;
while ($row = $countStmt->fetch_assoc()) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = (int)$row['cnt'];
    }
    $totalAll += (int)$row['cnt'];
}

$statuses      = ['Pending', 'Collected', 'Received', 'Processed', 'Recycled', 'Cancelled'];
$finalStatuses = ['Processed', 'Recycled', 'Cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Processing – AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        .page-container {
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .page-header-left h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 3px;
        }

        .page-header-left p {
            font-size: 0.8rem;
            color: var(--Gray);
        }

        .dark-mode .page-header-left p {
            color: var(--BlueGray);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.85rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 12px;
            padding: 1rem 1.1rem;
            box-shadow: 0 1px 6px var(--shadow-color);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
            text-decoration: none;
        }

        .stat-card:hover,
        .stat-card.active {
            border-color: var(--MainBlue);
            box-shadow: 0 2px 12px var(--shadow-color);
        }

        .stat-card.active {
            background: hsla(225, 94%, 67%, 0.05);
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon img {
            width: 17px;
            height: 17px;
        }

        .stat-icon-blue {
            background: hsla(225, 94%, 67%, 0.7);
            color: var(--MainBlue);
        }

        .stat-icon-yellow {
            background: hsla(50, 90%, 55%, 0.7);
            color: hsl(45, 72%, 36%);
        }

        .stat-icon-orange {
            background: hsla(30, 90%, 55%, 0.7);
            color: hsl(30, 72%, 40%);
        }

        .stat-icon-teal {
            background: hsla(180, 55%, 45%, 0.7);
            color: hsl(180, 55%, 30%);
        }

        .stat-icon-green {
            background: hsla(145, 50%, 45%, 0.7);
            color: hsl(145, 50%, 34%);
        }

        .stat-icon-red {
            background: hsla(0, 65%, 52%, 0.7);
            color: hsl(0, 60%, 44%);
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-color);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.69rem;
            color: var(--Gray);
            margin-top: 3px;
        }

        .section-heading {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
    
        .section-heading h3 {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0;
        }

        .section-heading h3 img {
            width: 14px;
            height: 14px;
        }

        .dark-mode .section-heading h3 img {
            content: url("../../assets/images/box-icon-white.svg");
        }

        .filter-label-tag {
            font-size: 0.7rem;
            font-weight: 400;
            color: var(--Gray);
            margin-left: 4px;
        }

        .filters-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-wrap img {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 13px;
            height: 13px;
            opacity: 0.5;
            pointer-events: none;
        }

        .dark-mode .search-wrap img {
            content: url("../../assets/images/view-icon-white.svg");
        }

        .search-input {
            width: 100%;
            padding: 0.45rem 0.7rem 0.45rem 32px;
            border: 1.5px solid var(--LowMainBlue);
            border-radius: 8px;
            font-size: 0.83rem;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .search-input:focus {
            border-color: var(--MainBlue);
        }

        .filter-select {
            padding: 0.45rem 0.7rem;
            border: 1.5px solid var(--LowMainBlue);
            border-radius: 8px;
            font-size: 0.83rem;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .filter-select:focus {
            border-color: var(--MainBlue);
        }

        .filter-request {
            position: relative; 
            display:flex; 
            align-items: center;
        }

        .filter-request span {
            position: absolute; 
            left: 9px; 
            font-size: 0.82rem; 
            font-weight: 700; 
            color: var(--Gray); 
            pointer-events: none;
        }

        .data-card {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 6px var(--shadow-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--text-color);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 10px 12px 0;
            text-align: left;
            border-bottom: 2px solid var(--LowMainBlue);
            white-space: nowrap;
        }

        .data-table th:first-child {
            padding-left: 1.2rem;
        }

        .data-table td {
            font-size: 0.81rem;
            color: var(--text-color);
            padding: 10px 10px 10px 0;
            border-bottom: 1px solid var(--LowMainBlue);
            vertical-align: middle;
        }

        .data-table td:first-child {
            padding-left: 1.2rem;
        }

        .data-table td:last-child {
            padding-right: 1.2rem;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr {
            transition: background 0.12s;
        }

        .data-table tbody tr:hover {
            background: hsla(225, 94%, 67%, 0.04);
        }

        .td-muted {
            color: var(--Gray);
            font-size: 0.77rem;
        }

        .item-id {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--MainBlue);
            font-family: monospace;
            background: hsla(225, 94%, 67%, 0.09);
            border-radius: 6px;
            padding: 2px 6px;
        }

        .item-name-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-dot-pending {
            background: hsl(45, 72%, 50%);
        }

        .status-dot-collected {
            background: hsl(225, 94%, 60%);
        }

        .status-dot-received {
            background: hsl(180, 55%, 40%);
        }

        .status-dot-processed {
            background: hsl(30, 72%, 45%);
        }

        .status-dot-recycled {
            background: hsl(145, 50%, 42%);
        }

        .status-dot-cancelled {
            background: hsl(0, 60%, 52%);
        }

        .item-name {
            font-size: 0.83rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .item-type {
            font-size: 0.68rem;
            color: var(--Gray);
            margin-top: 1px;
        }

        .pill {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 20px;
            white-space: nowrap;
            display: inline-block;
        }

        .pill-pending {
            background: hsla(50, 90%, 55%, 0.14);
            color: hsl(45, 72%, 32%);
        }

        .pill-collected {
            background: hsla(225, 94%, 67%, 0.13);
            color: var(--DarkerMainBlue);
        }

        .pill-received {
            background: hsla(180, 55%, 45%, 0.13);
            color: hsl(180, 55%, 25%);
        }

        .pill-processed {
            background: hsla(30, 90%, 55%, 0.13);
            color: hsl(30, 72%, 36%);
        }

        .pill-recycled {
            background: hsla(145, 50%, 45%, 0.13);
            color: hsl(145, 50%, 30%);
        }

        .pill-cancelled {
            background: hsla(0, 60%, 50%, 0.10);
            color: hsl(0, 52%, 42%);
        }

        .action-btn {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 14px;
            border: 1.5px solid;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: background 0.15s;
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
            background: none;
        }

        .action-btn img {
            width: 10px;
            height: 10px;
        }

        .action-btn-blue {
            color: var(--MainBlue);
            background: hsla(225, 94%, 67%, 0.09);
            border-color: hsla(225, 94%, 67%, 0.25);
        }

        .action-btn-blue:hover {
            background: hsla(225, 94%, 67%, 0.18);
        }

        .action-btn-gray {
            color: var(--Gray);
            background: var(--sec-bg-color);
            border-color: var(--LowMainBlue);
            cursor: default;
        }

        .detail-row {
            display: none;
        }

        .detail-row.open {
            display: table-row;
        }

        .detail-panel {
            padding: 1.2rem 1.5rem;
            background: hsla(225, 94%, 67%, 0.03);
            border-bottom: 1px solid var(--LowMainBlue);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem 1.5rem;
            margin-bottom: 1.2rem;
        }

        .detail-item .d-label {
            font-size: 0.67rem;
            font-weight: 700;
            color: var(--Gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 3px;
        }

        .detail-item .d-val {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .detail-item .d-val-light {
            font-size: 0.82rem;
            font-weight: 400;
            color: var(--text-color);
        }

        .detail-actions-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-change-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .status-change-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--Gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }

        .status-select {
            padding: 0.38rem 0.65rem;
            border: 1.5px solid var(--LowMainBlue);
            border-radius: 8px;
            font-size: 0.83rem;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            outline: none;
            min-width: 160px;
            transition: border-color 0.2s;
        }

        .status-select:focus {
            border-color: var(--MainBlue);
        }

        .btn-save-status {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            border: none;
            background: var(--MainBlue);
            color: #fff;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-save-status img {
            width: 11px;
            height: 11px;
        }

        .btn-save-status:hover {
            background: var(--DarkerMainBlue);
        }

        .btn-view-img {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            border: 1.5px solid var(--LowMainBlue);
            background: var(--sec-bg-color);
            color: var(--text-color);
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }

        .btn-view-img img {
            width: 11px;
            height: 11px;
        }

        .dark-mode .btn-view-img img {
            content: url('../../assets/images/view-icon-white.svg');
        }

        .btn-view-img:hover {
            background: var(--LowMainBlue);
        }

        .finalised-note {
            font-size: 0.75rem;
            color: var(--Gray);
            font-style: italic;
            padding: 6px 10px;
            background: var(--sec-bg-color);
            border-radius: 8px;
            border: 1px solid var(--LowMainBlue);
        }

        .dark-mode .finalised-note {
            color: var(--BlueGray);
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
        }

        .empty-state img {
            width: 40px;
            height: 40px;
            opacity: 0.3;
            margin-bottom: 0.7rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .empty-state p {
            font-size: 0.85rem;
            color: var(--Gray);
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.83rem;
            font-weight: 600;
            border: 1.5px solid;
        }

        .alert-success {
            background: hsla(145, 50%, 45%, 0.10);
            color: hsl(145, 50%, 30%);
            border-color: hsla(145, 50%, 45%, 0.3);
        }

        .alert-error {
            background: hsla(0, 60%, 50%, 0.10);
            color: hsl(0, 52%, 42%);
            border-color: hsla(0, 60%, 50%, 0.3);
        }

        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 8000;
            align-items: center;
            justify-content: center;
        }

        .modal-backdrop.open {
            display: flex;
        }

        .modal-box {
            background: var(--bg-color);
            border-radius: 14px;
            border: 1px solid var(--LowMainBlue);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.25);
            padding: 1.5rem 1.6rem;
            max-width: 420px;
            width: 92%;
            animation: modalIn 0.2s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.75rem;
        }

        .modal-body {
            font-size: 0.83rem;
            color: var(--Gray);
            line-height: 1.55;
            margin-bottom: 1.1rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn-cancel-sm {
            background: var(--sec-bg-color);
            border: 1.5px solid var(--LowMainBlue);
            color: var(--text-color);
            border-radius: 24px;
            padding: 0.4rem 1rem;
            font-size: 0.83rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-cancel-sm:hover {
            background: var(--LowMainBlue);
        }

        .btn-confirm-sm {
            background: hsl(145, 50%, 38%);
            color: #fff;
            border: none;
            border-radius: 24px;
            padding: 0.4rem 1.1rem;
            font-size: 0.83rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-confirm-sm:hover {
            background: hsl(145, 50%, 30%);
        }

        .img-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 9000;
            align-items: center;
            justify-content: center;
        }

        .img-modal-backdrop.open {
            display: flex;
        }

        .img-modal-inner {
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .img-modal-inner img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 10px;
            object-fit: contain;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.5);
        }

        .img-modal-caption {
            font-size: 0.83rem;
            color: #fff;
            opacity: 0.8;
        }

        .btn-img-close {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            border-radius: 24px;
            padding: 0.4rem 1.1rem;
            font-size: 0.83rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-img-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-clear-filters {
            font-size: 0.83rem;
            font-weight: 600;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            border: 1.5px solid var(--LowMainBlue);
            background: var(--Gray);
            color: var(--White);
            border:none;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s;
        }

        .btn-clear-filters:hover {
            background: var(--BlueGray);
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes toastOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        @media (max-width: 1200px) {
            .stats-row {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 900px) {
            .stats-row {
                grid-template-columns: repeat(3, 1fr);
            }

            .detail-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .page-header {
                flex-direction: column;
            }

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-clear-filters {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .data-table th:nth-child(3),
            .data-table td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <header>
        <section class="c-logo-section">
            <a href="../../html/admin/aHome.php" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <nav class="c-navbar-side">
            <img src="../../assets/images/icon-menu.svg" alt="icon-menu" onclick="showMenu()" class="c-icon-btn" id="menuBtn">
            <div id="sidebarNav" class="c-navbar-side-menu">
                <img src="../../assets/images/icon-menu-close.svg" alt="icon-menu-close" onclick="hideMenu()" class="close-btn" id="closeBtn">
                <div class="c-navbar-side-items">
                    <section class="c-navbar-side-more">
                        <button id="themeToggleMobile">
                            <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon">
                        </button>
                        <a href="../../html/common/Setting.php">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>
                    <a href="../../html/admin/aHome.php">Home</a>
                    <a href="../../html/admin/aRequests.php">Requests</a>
                    <a href="../../html/admin/aJobs.php">Jobs</a>
                    <a href="../../html/admin/aIssue.php">Issue</a>
                    <a href="../../html/admin/aOperations.php">Operations</a>
                    <a href="../../html/admin/aReport.php">Report</a>
                </div>
            </div>
        </nav>

        <nav class="c-navbar-desktop">
            <a href="../../html/admin/aHome.php">Home</a>
            <a href="../../html/admin/aRequests.php">Requests</a>
            <a href="../../html/admin/aJobs.php">Jobs</a>
            <a href="../../html/admin/aIssue.php">Issue</a>
            <a href="../../html/admin/aOperations.php">Operations</a>
            <a href="../../html/admin/aReport.php">Report</a>
        </nav>

        <section class="c-navbar-more">
            <button id="themeToggleDesktop">
                <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
            <a href="../../html/common/Setting.php">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
    </header>

    <hr>

    <main>

        <div class="page-header">
            <div class="page-header-left">
                <h1>Item Processing</h1>
                <p>Total Items: <?php echo $totalAll; ?></p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= sanitize($message) ?>
        </div>
        <?php endif; ?>

        <div class="stats-row">
            <a href="?status=Pending" class="stat-card <?= ($filterStatus === 'Pending') ? 'active' : '' ?>">
                <div class="stat-icon stat-icon-yellow">
                    <img src="../../assets/images/clock-icon-white.svg" alt="">
                </div>
                <div>
                    <div class="stat-value"><?= $counts['Pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </a>
            <a href="?status=Collected" class="stat-card <?= ($filterStatus === 'Collected') ? 'active' : '' ?>">
                <div class="stat-icon stat-icon-blue">
                    <img src="../../assets/images/truck-icon-white.svg" alt="">
                </div>
                <div>
                    <div class="stat-value"><?= $counts['Collected'] ?></div>
                    <div class="stat-label">Collected</div>
                </div>
            </a>
            <a href="?status=Received" class="stat-card <?= ($filterStatus === 'Received') ? 'active' : '' ?>">
                <div class="stat-icon stat-icon-teal">
                    <img src="../../assets/images/inbox-icon-white.svg" alt="">
                </div>
                <div>
                    <div class="stat-value"><?= $counts['Received'] ?></div>
                    <div class="stat-label">Received</div>
                </div>
            </a>
            <a href="?status=Processed" class="stat-card <?= ($filterStatus === 'Processed') ? 'active' : '' ?>">
                <div class="stat-icon stat-icon-orange">
                    <img src="../../assets/images/refresh-icon-white.svg" alt="">
                </div>
                <div>
                    <div class="stat-value"><?= $counts['Processed'] ?></div>
                    <div class="stat-label">Processed</div>
                </div>
            </a>
            <a href="?status=Recycled" class="stat-card <?= ($filterStatus === 'Recycled') ? 'active' : '' ?>">
                <div class="stat-icon stat-icon-green">
                    <img src="../../assets/images/check-icon-white.svg" alt="">
                </div>
                <div>
                    <div class="stat-value"><?= $counts['Recycled'] ?></div>
                    <div class="stat-label">Recycled</div>
                </div>
            </a>
            <a href="?status=Cancelled" class="stat-card <?= ($filterStatus === 'Cancelled') ? 'active' : '' ?>">
                <div class="stat-icon stat-icon-red">
                    <img style="width: 12px; height: 12px;" src="../../assets/images/icon-menu-close-dark.png" alt="">
                </div>
                <div>
                    <div class="stat-value"><?= $counts['Cancelled'] ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </a>
        </div>

        <div>
            <div class="section-heading">
                <h3>
                    <img src="../../assets/images/box-icon-black.svg" alt="">
                    Items List
                    <?php if ($filterStatus !== 'all' && $filterStatus !== ''): ?>
                    <span class="filter-label-tag">· <?= sanitize($filterStatus) ?></span>
                    <?php endif; ?>
                </h3>
                <form method="GET" class="filters-bar" id="filterForm">
                    <input type="hidden" name="status" value="<?= sanitize($filterStatus) ?>">
                    <div class="search-wrap">
                        <img src="../../assets/images/view-icon-black.svg" alt="">
                        <input
                            type="text"
                            name="search"
                            class="search-input"
                            placeholder="Search items, providers…"
                            value="<?= sanitize($search) ?>"
                            oninput="this.form.submit()"
                        >
                    </div>
                    <div class="filter-request">
                        <span>#</span>
                        <input
                            type="number"
                            name="requestID"
                            class="filter-select"
                            placeholder="Req ID"
                            value="<?= $filterReq > 0 ? $filterReq : '' ?>"
                            min="1"
                            style="padding-left:22px; width:100px; -moz-appearance:textfield;"
                            oninput="this.form.submit()"
                        >
                    </div>
                    <?php if ($search !== '' || $filterStatus !== 'all' || $filterReq > 0): ?>
                        <a href="?" class="btn-clear-filters">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="data-card">
                <?php if (empty($items)): ?>
                <div class="empty-state">
                    <img src="../../assets/images/box-icon-white.svg" alt="">
                    <p>No items match your current filters.</p>
                </div>
                <?php else: ?>
                <table class="data-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Provider</th>
                            <th>Request</th>
                            <th>Centre</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                            $isFinal = in_array($item['itemStatus'], $finalStatuses);
                            $dotClass = 'status-dot-' . strtolower($item['itemStatus']);
                            $pillClass = 'pill-' . strtolower($item['itemStatus']);
                        ?>
                        <tr>
                            <td>
                                <span class="item-id">#<?= $item['itemID'] ?></span>
                            </td>
                            <td>
                                <div class="item-name-cell">
                                    <div class="status-dot <?= $dotClass ?>"></div>
                                    <div>
                                        <div class="item-name"><?= sanitize($item['description']) ?></div>
                                        <div class="item-type"><?= sanitize($item['itemType']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="td-muted"><?= sanitize($item['providerName']) ?></td>
                            <td class="td-muted">Req #<?= $item['requestID'] ?></td>
                            <td class="td-muted"><?= $item['centreName'] ? sanitize($item['centreName']) : '—' ?></td>
                            <td>
                                <span class="pill <?= $pillClass ?>"><?= sanitize($item['itemStatus']) ?></span>
                            </td>
                            <td>
                                <button
                                    class="action-btn action-btn-blue"
                                    onclick="toggleDetail(<?= $item['itemID'] ?>)">
                                    <img src="../../assets/images/setting-blue.svg" alt="">
                                    Manage
                                </button>
                            </td>
                        </tr>
                        <tr class="detail-row" id="detail-<?= $item['itemID'] ?>">
                            <td colspan="7" style="padding:0">
                                <div class="detail-panel">
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <div class="d-label">Item Type</div>
                                            <div class="d-val"><?= sanitize($item['itemType']) ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="d-label">Brand / Model</div>
                                            <div class="d-val">
                                                <?= $item['brand'] ? sanitize($item['brand']) : '—' ?>
                                                <?= $item['model'] ? ' · ' . sanitize($item['model']) : '' ?>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="d-label">Weight</div>
                                            <div class="d-val"><?= $item['weight'] ?> kg</div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="d-label">Dimensions (L×W×H)</div>
                                            <div class="d-val"><?= $item['length'] ?> × <?= $item['width'] ?> × <?= $item['height'] ?> cm</div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="d-label">Provider</div>
                                            <div class="d-val"><?= sanitize($item['providerName']) ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="d-label">Request Date</div>
                                            <div class="d-val"><?= date('d M Y', strtotime($item['reqCreated'])) ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="d-label">Pickup Address</div>
                                            <div class="d-val d-val-light"><?= sanitize($item['pickupAddress']) ?>, <?= sanitize($item['pickupState']) ?></div>
                                        </div>
                                        <div class="detail-item">
                                            <div class="d-label">Collection Centre</div>
                                            <div class="d-val"><?= $item['centreName'] ? sanitize($item['centreName']) : '—' ?></div>
                                        </div>
                                    </div>

                                    <div class="detail-actions-row">
                                        <?php if ($item['image']): ?>
                                        <button
                                            class="btn-view-img"
                                            onclick="openImgModal('../../uploads/<?= sanitize($item['image']) ?>', '<?= sanitize($item['description']) ?>')">
                                            <img src="../../assets/images/view-icon-black.svg" alt="">
                                            View Image
                                        </button>
                                        <?php endif; ?>

                                        <?php if ($isFinal): ?>
                                        <span class="finalised-note">
                                            This item is finalised (<?= sanitize($item['itemStatus']) ?>) and cannot be changed.
                                        </span>
                                        <?php elseif ($item['itemStatus'] === 'Received'): ?>
                                        <form method="POST" class="status-change-form" onsubmit="return handleStatusSubmit(event, this, <?= $item['itemID'] ?>, '<?= sanitize($item['description']) ?>')">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="itemID" value="<?= $item['itemID'] ?>">
                                            <span class="status-change-label">Change Status:</span>
                                            <select name="newStatus" class="status-select" id="sel-<?= $item['itemID'] ?>">
                                                <option value="Processed">Processed</option>
                                                <option value="Recycled">Recycled</option>
                                            </select>
                                            <button type="submit" class="btn-save-status">
                                                <img src="../../assets/images/check-icon.svg" alt="">
                                                Save
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="finalised-note">
                                            Status is <strong><?= sanitize($item['itemStatus']) ?></strong> - managed by the system. Available once item is Received.
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <hr>

    <footer>
        <section class="c-footer-info-section">
            <a href="../../html/admin/aHome.php">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
            </a>
            <div class="c-text">AfterVolt</div>
            <div class="c-text c-text-center">
                Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.
            </div>
            <div class="c-text c-text-label">+60 12 345 6789</div>
            <div class="c-text">abc@aftervolt.my</div>
        </section>
        <section class="c-footer-links-section">
            <div>
                <b>Management</b><br>
                <a href="../../html/admin/aRequests.php">Collection Requests</a><br>
                <a href="../../html/admin/aJobs.php">Collection Jobs</a><br>
                <a href="../../html/admin/aIssue.php">Issue</a>
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

    <div class="modal-backdrop" id="confirmModal">
        <div class="modal-box">
            <div class="modal-title">Confirm Status Change</div>
            <div class="modal-body" id="confirmModalBody"></div>
            <div class="modal-actions">
                <button class="btn-cancel-sm" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn-confirm-sm" id="confirmModalBtn">Confirm</button>
            </div>
        </div>
    </div>

    <div class="img-modal-backdrop" id="imgModal" onclick="closeImgModal()">
        <div class="img-modal-inner" onclick="event.stopPropagation()">
            <img src="" alt="" id="imgModalSrc">
            <div class="img-modal-caption" id="imgModalCaption"></div>
            <button class="btn-img-close" onclick="closeImgModal()">Close</button>
        </div>
    </div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        let pendingForm = null;

        function toggleDetail(id) {
            const row = document.getElementById('detail-' + id);
            if (!row) return;
            row.classList.toggle('open');
        }

        function handleStatusSubmit(event, form, itemID, itemName) {
            event.preventDefault();
            const sel = document.getElementById('sel-' + itemID);
            const newStatus = sel ? sel.value : '';
            const finalStatuses = ['Processed', 'Recycled', 'Cancelled'];

            if (finalStatuses.includes(newStatus)) {
                pendingForm = form;
                const body = document.getElementById('confirmModalBody');
                body.innerHTML = 'You are about to mark <strong>' + itemName + '</strong> as <strong>' + newStatus + '</strong>. This action is final and cannot be undone.';
                document.getElementById('confirmModal').classList.add('open');
                document.getElementById('confirmModalBtn').onclick = function () {
                    closeConfirmModal();
                    pendingForm.submit();
                };
            } else {
                form.submit();
            }
            return false;
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('open');
            pendingForm = null;
        }

        function openImgModal(src, caption) {
            document.getElementById('imgModalSrc').src = src;
            document.getElementById('imgModalCaption').textContent = caption;
            document.getElementById('imgModal').classList.add('open');
        }

        function closeImgModal() {
            document.getElementById('imgModal').classList.remove('open');
            document.getElementById('imgModalSrc').src = '';
        }

        <?php if ($message): ?>
        (function () {
            const colors = { success: 'hsl(145,50%,40%)', error: 'hsl(0,65%,52%)' };
            const type = '<?= $messageType ?>';
            const msg  = '<?= addslashes($message) ?>';
            const t = document.createElement('div');
            t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;background:' + (colors[type] || colors.success) + ';color:#fff;padding:10px 18px;border-radius:24px;font-size:0.82rem;font-family:Inter,sans-serif;box-shadow:0 4px 16px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px;animation:toastIn .25s ease;';
            t.innerHTML = '<span style="font-weight:700">' + (type === 'success' ? '✓' : '✕') + '</span> ' + msg;
            document.body.appendChild(t);
            setTimeout(function () {
                t.style.animation = 'toastOut .25s ease forwards';
                setTimeout(function () { t.remove(); }, 250);
            }, 3000);
        })();
        <?php endif; ?>
    </script>
</body>
</html>