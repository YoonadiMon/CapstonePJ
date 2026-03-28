<?php
session_start();
include("../../php/dbConn.php");
include("../../php/sessionCheck.php");

function jsonResponse($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_request') {
    mysqli_begin_transaction($conn);

    try {
        $requestID = isset($_POST['requestID']) ? (int)$_POST['requestID'] : 0;
        $collectorID = isset($_POST['collectorID']) ? (int)$_POST['collectorID'] : 0;
        $vehicleID = isset($_POST['vehicleID']) ? (int)$_POST['vehicleID'] : 0;
        $centreID = isset($_POST['centreID']) ? (int)$_POST['centreID'] : 0;
        $scheduledDateTime = trim($_POST['scheduledDateTime'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $adminUserID = isset($_SESSION['userID']) ? (int)$_SESSION['userID'] : 1;

        if ($requestID <= 0 || $collectorID <= 0 || $vehicleID <= 0 || $centreID <= 0 || $scheduledDateTime === '') {
            throw new Exception('Missing required assignment data.');
        }

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $scheduledDateTime);
        if (!$dt) {
            throw new Exception('Invalid schedule date/time.');
        }

        $scheduledDate = $dt->format('Y-m-d');
        $scheduledTime = $dt->format('H:i:s');

        $checkRequestSql = "
            SELECT requestID, status
            FROM tblcollection_request
            WHERE requestID = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkRequestSql);
        mysqli_stmt_bind_param($stmt, "i", $requestID);
        mysqli_stmt_execute($stmt);
        $requestResult = mysqli_stmt_get_result($stmt);
        $requestRow = mysqli_fetch_assoc($requestResult);

        if (!$requestRow) {
            throw new Exception('Request not found.');
        }

        if ($requestRow['status'] !== 'Approved') {
            throw new Exception('Only approved requests can be assigned.');
        }

        $checkJobSql = "
            SELECT jobID
            FROM tbljob
            WHERE requestID = ?
            AND status NOT IN ('Cancelled', 'Rejected')
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkJobSql);
        mysqli_stmt_bind_param($stmt, "i", $requestID);
        mysqli_stmt_execute($stmt);
        $jobResult = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($jobResult)) {
            throw new Exception('This request already has a job assigned.');
        }

        $requestItemTypes = [];
        $requestItemNames = [];

        $itemTypeSql = "
            SELECT i.itemTypeID, it.name
            FROM tblitem i
            INNER JOIN tblitem_type it
                ON it.itemTypeID = i.itemTypeID
            WHERE i.requestID = ?
        ";
        $stmt = mysqli_prepare($conn, $itemTypeSql);
        mysqli_stmt_bind_param($stmt, "i", $requestID);
        mysqli_stmt_execute($stmt);
        $itemTypeResult = mysqli_stmt_get_result($stmt);

        while ($itemRow = mysqli_fetch_assoc($itemTypeResult)) {
            $requestItemTypes[] = (int)$itemRow['itemTypeID'];
            $requestItemNames[] = strtolower(trim($itemRow['name']));
        }

        if (empty($requestItemTypes)) {
            throw new Exception('This request has no items to assign.');
        }

        // Collector validation
        $checkCollectorStatusSql = "
            SELECT status
            FROM tblcollector
            WHERE collectorID = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkCollectorStatusSql);
        mysqli_stmt_bind_param($stmt, "i", $collectorID);
        mysqli_stmt_execute($stmt);
        $collectorStatusResult = mysqli_stmt_get_result($stmt);
        $collectorStatusRow = mysqli_fetch_assoc($collectorStatusResult);

        if (!$collectorStatusRow) {
            throw new Exception('Collector not found.');
        }

        if ($collectorStatusRow['status'] === 'suspended' || $collectorStatusRow['status'] === 'inactive') {
            throw new Exception('Collector is suspended or inactive and cannot be assigned.');
        }

        $checkCollectorJobsSql = "
            SELECT jobID, scheduledDate
            FROM tbljob
            WHERE collectorID = ?
              AND status IN ('Scheduled', 'Pending', 'Ongoing')
              AND ABS(DATEDIFF(scheduledDate, ?)) <= 1
        ";
        $stmt = mysqli_prepare($conn, $checkCollectorJobsSql);
        mysqli_stmt_bind_param($stmt, "is", $collectorID, $scheduledDate);
        mysqli_stmt_execute($stmt);
        $collectorJobsResult = mysqli_stmt_get_result($stmt);
        $conflictingJobs = [];

        while ($conflictJob = mysqli_fetch_assoc($collectorJobsResult)) {
            $conflictingJobs[] = $conflictJob['scheduledDate'];
        }

        if (!empty($conflictingJobs)) {
            $conflictDates = implode(', ', $conflictingJobs);
            throw new Exception("Collector already has a scheduled job on or within 1 day of the selected date (conflicts with: {$conflictDates}).");
        }

        // Vehicle validation
        $checkVehicleStatusSql = "
            SELECT status
            FROM tblvehicle
            WHERE vehicleID = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkVehicleStatusSql);
        mysqli_stmt_bind_param($stmt, "i", $vehicleID);
        mysqli_stmt_execute($stmt);
        $vehicleStatusResult = mysqli_stmt_get_result($stmt);
        $vehicleStatusRow = mysqli_fetch_assoc($vehicleStatusResult);

        if (!$vehicleStatusRow) {
            throw new Exception('Vehicle not found.');
        }

        if ($vehicleStatusRow['status'] === 'Maintenance' || $vehicleStatusRow['status'] === 'Inactive') {
            throw new Exception('Vehicle is under maintenance or inactive and cannot be assigned.');
        }

        $checkMaintenanceSql = "
            SELECT maintenanceID, startDate, status
            FROM tblmaintenance
            WHERE vehicleID = ?
              AND status IN ('Scheduled', 'In Progress')
              AND startDate <= ?
        ";
        $stmt = mysqli_prepare($conn, $checkMaintenanceSql);
        mysqli_stmt_bind_param($stmt, "is", $vehicleID, $scheduledDate);
        mysqli_stmt_execute($stmt);
        $maintenanceResult = mysqli_stmt_get_result($stmt);
        $conflictingMaintenance = mysqli_fetch_assoc($maintenanceResult);

        if ($conflictingMaintenance) {
            throw new Exception("Vehicle has scheduled or in-progress maintenance on or before the selected date.");
        }

        $checkVehicleJobsSql = "
            SELECT jobID, scheduledDate
            FROM tbljob
            WHERE vehicleID = ?
              AND status IN ('Scheduled', 'Pending', 'Ongoing')
              AND ABS(DATEDIFF(scheduledDate, ?)) <= 1
        ";
        $stmt = mysqli_prepare($conn, $checkVehicleJobsSql);
        mysqli_stmt_bind_param($stmt, "is", $vehicleID, $scheduledDate);
        mysqli_stmt_execute($stmt);
        $vehicleJobsResult = mysqli_stmt_get_result($stmt);
        $conflictingVehicleJobs = [];

        while ($conflictJob = mysqli_fetch_assoc($vehicleJobsResult)) {
            $conflictingVehicleJobs[] = $conflictJob['scheduledDate'];
        }

        if (!empty($conflictingVehicleJobs)) {
            $conflictDates = implode(', ', $conflictingVehicleJobs);
            throw new Exception("Vehicle already has a scheduled job on or within 1 day of the selected date (conflicts with: {$conflictDates}).");
        }

        // Centre validation
        $checkCentreSql = "
            SELECT centreID, name, status
            FROM tblcentre
            WHERE centreID = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkCentreSql);
        mysqli_stmt_bind_param($stmt, "i", $centreID);
        mysqli_stmt_execute($stmt);
        $centreResult = mysqli_stmt_get_result($stmt);
        $centreRow = mysqli_fetch_assoc($centreResult);

        if (!$centreRow) {
            throw new Exception('Collection centre not found.');
        }

        if ($centreRow['status'] !== 'Active') {
            throw new Exception('Collection centre is not active and cannot accept items.');
        }

        $centreAcceptedTypes = [];
        $acceptedTypeSql = "
            SELECT itemTypeID
            FROM tblcentre_accepted_type
            WHERE centreID = ?
        ";
        $stmt = mysqli_prepare($conn, $acceptedTypeSql);
        mysqli_stmt_bind_param($stmt, "i", $centreID);
        mysqli_stmt_execute($stmt);
        $acceptedTypeResult = mysqli_stmt_get_result($stmt);

        while ($acceptedRow = mysqli_fetch_assoc($acceptedTypeResult)) {
            $centreAcceptedTypes[] = (int)$acceptedRow['itemTypeID'];
        }

        foreach ($requestItemTypes as $index => $typeID) {
            $itemName = $requestItemNames[$index] ?? '';

            if ($itemName === 'other electronics') {
                continue;
            }

            if (!in_array($typeID, $centreAcceptedTypes, true)) {
                throw new Exception("Selected collection centre does not accept '{$itemName}'.");
            }
        }

        $collectorName = 'Collector ID ' . $collectorID;
        $collectorInfoSql = "
            SELECT u.fullname
            FROM tblusers u
            WHERE u.userID = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $collectorInfoSql);
        mysqli_stmt_bind_param($stmt, "i", $collectorID);
        mysqli_stmt_execute($stmt);
        $collectorInfoResult = mysqli_stmt_get_result($stmt);
        $collectorInfoRow = mysqli_fetch_assoc($collectorInfoResult);
        if ($collectorInfoRow && !empty($collectorInfoRow['fullname'])) {
            $collectorName = $collectorInfoRow['fullname'];
        }

        $plateNum = 'Vehicle ID ' . $vehicleID;
        $vehicleInfoSql = "
            SELECT plateNum
            FROM tblvehicle
            WHERE vehicleID = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $vehicleInfoSql);
        mysqli_stmt_bind_param($stmt, "i", $vehicleID);
        mysqli_stmt_execute($stmt);
        $vehicleInfoResult = mysqli_stmt_get_result($stmt);
        $vehicleInfoRow = mysqli_fetch_assoc($vehicleInfoResult);
        if ($vehicleInfoRow && !empty($vehicleInfoRow['plateNum'])) {
            $plateNum = $vehicleInfoRow['plateNum'];
        }

        $centreName = $centreRow['name'];

        $insertJobSql = "
            INSERT INTO tbljob (
                requestID,
                collectorID,
                vehicleID,
                scheduledDate,
                scheduledTime,
                status
            ) VALUES (?, ?, ?, ?, ?, 'Pending')
        ";
        $stmt = mysqli_prepare($conn, $insertJobSql);
        mysqli_stmt_bind_param($stmt, "iiiss", $requestID, $collectorID, $vehicleID, $scheduledDate, $scheduledTime);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create job.');
        }

        $jobID = mysqli_insert_id($conn);

        // $updateRequestSql = "
        //     UPDATE tblcollection_request
        //     SET status = 'Scheduled'
        //     WHERE requestID = ?
        // ";
        // $stmt = mysqli_prepare($conn, $updateRequestSql);
        // mysqli_stmt_bind_param($stmt, "i", $requestID);

        // if (!mysqli_stmt_execute($stmt)) {
        //     throw new Exception('Failed to update request status.');
        // }

        $updateItemsSql = "
            UPDATE tblitem
            SET centreID = ?
            WHERE requestID = ?
        ";
        $stmt = mysqli_prepare($conn, $updateItemsSql);
        mysqli_stmt_bind_param($stmt, "ii", $centreID, $requestID);
        mysqli_stmt_execute($stmt);

        $assignmentDescription = "Assigned to {$collectorName}, Vehicle {$plateNum}, {$centreName}";
        if ($notes !== '') {
            $assignmentDescription .= " | Notes: " . $notes;
        }

        $logRequestAssignmentSql = "
            INSERT INTO tblactivity_log (
                requestID,
                jobID,
                userID,
                type,
                action,
                description
            ) VALUES (?, NULL, ?, 'Request', 'Assignment', ?)
        ";
        $stmt = mysqli_prepare($conn, $logRequestAssignmentSql);
        mysqli_stmt_bind_param($stmt, "iis", $requestID, $adminUserID, $assignmentDescription);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to log request assignment.');
        }

        // $requestStatusDescription = "Changed from Approved to Scheduled";
        // $logRequestStatusSql = "
        //     INSERT INTO tblactivity_log (
        //         requestID,
        //         jobID,
        //         userID,
        //         type,
        //         action,
        //         description
        //     ) VALUES (?, ?, ?, 'Request', 'Status Change', ?)
        // ";
        // $stmt = mysqli_prepare($conn, $logRequestStatusSql);
        // mysqli_stmt_bind_param($stmt, "iiis", $requestID, $jobID, $adminUserID, $requestStatusDescription);

        // if (!mysqli_stmt_execute($stmt)) {
        //     throw new Exception('Failed to log request status change.');
        // }

        $jobCreateDescription = "Job awaiting collector acceptance";
        $logJobCreateSql = "
            INSERT INTO tblactivity_log (
                requestID,
                jobID,
                userID,
                type,
                action,
                description
            ) VALUES (?, ?, ?, 'Job', 'Create', ?)
        ";
        $stmt = mysqli_prepare($conn, $logJobCreateSql);
        mysqli_stmt_bind_param($stmt, "iiis", $requestID, $jobID, $adminUserID, $jobCreateDescription);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to log job creation.');
        }

        mysqli_commit($conn);

        jsonResponse(true, 'Assignment saved successfully.', [
            'jobID' => $jobID,
            'requestID' => $requestID
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        jsonResponse(false, $e->getMessage());
    }
}

$requests = [];
$requestSql = "
    SELECT 
        cr.requestID,
        u.fullname AS providerName,
        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,
        cr.preferredDateTime,
        cr.status,
        COALESCE(SUM(i.weight), 0) AS totalWeight
    FROM tblcollection_request cr
    INNER JOIN tblusers u
        ON u.userID = cr.providerID
    LEFT JOIN tblitem i
        ON i.requestID = cr.requestID
    LEFT JOIN tbljob j
        ON j.requestID = cr.requestID
        AND j.status NOT IN ('Cancelled', 'Rejected')
    WHERE cr.status = 'Approved'
    AND j.jobID IS NULL
      AND j.jobID IS NULL
    GROUP BY 
        cr.requestID,
        u.fullname,
        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,
        cr.preferredDateTime,
        cr.status
    ORDER BY cr.requestID DESC
";
$requestResult = mysqli_query($conn, $requestSql);

if ($requestResult) {
    while ($row = mysqli_fetch_assoc($requestResult)) {
        $requestId = (int)$row['requestID'];

        $items = [];
        $itemDetails = [];

        $itemTypeSql = "
            SELECT it.itemTypeID, it.name
            FROM tblitem i
            INNER JOIN tblitem_type it
                ON it.itemTypeID = i.itemTypeID
            WHERE i.requestID = {$requestId}
            ORDER BY i.itemID ASC
        ";
        $itemTypeResult = mysqli_query($conn, $itemTypeSql);

        if ($itemTypeResult) {
            while ($itemRow = mysqli_fetch_assoc($itemTypeResult)) {
                $items[] = $itemRow['name'];
                $itemDetails[] = [
                    'itemTypeID' => (int)$itemRow['itemTypeID'],
                    'name' => $itemRow['name']
                ];
            }
        }

        $joinedItems = strtolower(implode(' ', $items));
        $type = 'electronics';

        if (strpos($joinedItems, 'battery') !== false || strpos($joinedItems, 'power bank') !== false) {
            $type = 'batteries';
        } elseif (
            strpos($joinedItems, 'television') !== false ||
            strpos($joinedItems, 'tv') !== false ||
            strpos($joinedItems, 'electric kitchen appliances') !== false ||
            strpos($joinedItems, 'electric home appliances') !== false ||
            strpos($joinedItems, 'refrigerator') !== false ||
            strpos($joinedItems, 'fridge') !== false ||
            strpos($joinedItems, 'washing machine') !== false
        ) {
            $type = 'appliances';
        }

        $requests[] = [
            'id' => (string)$row['requestID'],
            'provider' => $row['providerName'],
            'items' => $items,
            'itemDetails' => $itemDetails,
            'address' => $row['pickupAddress'] . ', ' . $row['pickupState'] . ' ' . $row['pickupPostcode'],
            'postcode' => $row['pickupPostcode'],
            'preferredDate' => date('Y-m-d\TH:i', strtotime($row['preferredDateTime'])),
            'weight' => number_format((float)$row['totalWeight'], 2) . ' kg',
            'status' => strtolower($row['status']),
            'type' => $type
        ];
    }
}

$collectors = [];
$collectorSql = "
    SELECT 
        c.collectorID,
        u.fullname,
        c.status,
        CASE
            WHEN c.status IN ('suspended', 'inactive') THEN 0
            ELSE 1
        END AS available
    FROM tblcollector c
    INNER JOIN tblusers u
        ON u.userID = c.collectorID
    WHERE c.status IN ('active', 'on duty', 'suspended', 'inactive')
    ORDER BY u.fullname ASC
";
$collectorResult = mysqli_query($conn, $collectorSql);

if ($collectorResult) {
    while ($row = mysqli_fetch_assoc($collectorResult)) {
        $collectors[] = [
            'id' => (string)$row['collectorID'],
            'name' => $row['fullname'],
            'available' => (bool)$row['available'],
            'status' => $row['status']
        ];
    }
}

$collectorScheduledJobs = [];
$collectorJobSql = "
    SELECT jobID, collectorID, scheduledDate, status
    FROM tbljob
    WHERE collectorID IS NOT NULL
      AND status IN ('Scheduled', 'Pending')
";
$collectorJobResult = mysqli_query($conn, $collectorJobSql);

if ($collectorJobResult) {
    while ($row = mysqli_fetch_assoc($collectorJobResult)) {
        $collectorIdKey = (string)$row['collectorID'];
        if (!isset($collectorScheduledJobs[$collectorIdKey])) {
            $collectorScheduledJobs[$collectorIdKey] = [];
        }
        $collectorScheduledJobs[$collectorIdKey][] = [
            'jobID' => (string)$row['jobID'],
            'scheduledDate' => $row['scheduledDate'],
            'status' => $row['status']
        ];
    }
}

$vehicles = [];
$vehicleSql = "
    SELECT
        v.vehicleID,
        v.model,
        v.plateNum,
        v.capacityWeight,
        v.status,
        CASE
            WHEN v.status IN ('Maintenance', 'Inactive') THEN 0
            ELSE 1
        END AS available
    FROM tblvehicle v
    ORDER BY v.plateNum ASC
";
$vehicleResult = mysqli_query($conn, $vehicleSql);

if ($vehicleResult) {
    while ($row = mysqli_fetch_assoc($vehicleResult)) {
        $vehicles[] = [
            'id' => (string)$row['vehicleID'],
            'model' => $row['model'] . ' - ' . $row['plateNum'],
            'status' => $row['status'],
            'available' => (bool)$row['available'],
            'capacity' => number_format((float)$row['capacityWeight'], 0) . ' kg'
        ];
    }
}

$vehicleMaintenance = [];
$maintenanceSql = "
    SELECT
        m.maintenanceID,
        m.vehicleID,
        m.startDate,
        m.endDate,
        m.status,
        m.description
    FROM tblmaintenance m
    WHERE m.status IN ('Scheduled', 'In Progress', 'Completed')
    ORDER BY m.startDate ASC
";
$maintenanceResult = mysqli_query($conn, $maintenanceSql);

if ($maintenanceResult) {
    while ($row = mysqli_fetch_assoc($maintenanceResult)) {
        $vehicleIdKey = (string)$row['vehicleID'];
        if (!isset($vehicleMaintenance[$vehicleIdKey])) {
            $vehicleMaintenance[$vehicleIdKey] = [];
        }

        $vehicleMaintenance[$vehicleIdKey][] = [
            'maintenanceID' => (string)$row['maintenanceID'],
            'startDate' => $row['startDate'],
            'endDate' => $row['endDate'],
            'status' => $row['status'],
            'notes' => $row['description'] ?? ''
        ];
    }
}

$vehicleScheduledJobs = [];
$vehicleJobSql = "
    SELECT jobID, vehicleID, scheduledDate, status
    FROM tbljob
    WHERE vehicleID IS NOT NULL
      AND status IN ('Scheduled', 'Pending', 'Ongoing')
";
$vehicleJobResult = mysqli_query($conn, $vehicleJobSql);

if ($vehicleJobResult) {
    while ($row = mysqli_fetch_assoc($vehicleJobResult)) {
        $vehicleIdKey = (string)$row['vehicleID'];
        if (!isset($vehicleScheduledJobs[$vehicleIdKey])) {
            $vehicleScheduledJobs[$vehicleIdKey] = [];
        }
        $vehicleScheduledJobs[$vehicleIdKey][] = [
            'jobID' => (string)$row['jobID'],
            'scheduledDate' => $row['scheduledDate'],
            'status' => $row['status']
        ];
    }
}

$centres = [];
$centreSql = "
    SELECT
        c.centreID,
        c.name,
        c.address,
        c.state,
        c.postcode,
        c.status,
        COUNT(i.itemID) AS itemCount
    FROM tblcentre c
    LEFT JOIN tblitem i
        ON i.centreID = c.centreID
       AND i.status IN ('Received','Processed','Collected')
    GROUP BY c.centreID, c.name, c.address, c.state, c.postcode, c.status
    ORDER BY c.name ASC
";
$centreResult = mysqli_query($conn, $centreSql);

if ($centreResult) {
    while ($row = mysqli_fetch_assoc($centreResult)) {
        $centres[] = [
            'id' => (string)$row['centreID'],
            'name' => $row['name'],
            'status' => $row['status'],
            'address' => $row['address'] . ', ' . $row['state'] . ' ' . $row['postcode']
        ];
    }
}

$centreAcceptedTypesData = [];
$centreAcceptedSql = "
    SELECT centreID, itemTypeID
    FROM tblcentre_accepted_type
";
$centreAcceptedResult = mysqli_query($conn, $centreAcceptedSql);

if ($centreAcceptedResult) {
    while ($row = mysqli_fetch_assoc($centreAcceptedResult)) {
        $centreIdKey = (string)$row['centreID'];
        if (!isset($centreAcceptedTypesData[$centreIdKey])) {
            $centreAcceptedTypesData[$centreIdKey] = [];
        }
        $centreAcceptedTypesData[$centreIdKey][] = (int)$row['itemTypeID'];
    }
}

$recentAssignments = [];
$timelineSql = "
    SELECT
        al.dateTime,
        al.requestID,
        al.description
    FROM tblactivity_log al
    WHERE al.type = 'Request'
      AND al.action = 'Assignment'
    ORDER BY al.dateTime DESC
    LIMIT 5
";
$timelineResult = mysqli_query($conn, $timelineSql);

if ($timelineResult) {
    while ($row = mysqli_fetch_assoc($timelineResult)) {
        $recentAssignments[] = [
            'time' => date('H:i', strtotime($row['dateTime'])),
            'date' => date('d M Y', strtotime($row['dateTime'])),
            'event' => ($row['description'] ?? 'Assignment recorded'),
            'requestID' => $row['requestID']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations - Aftervolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/aOperations.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

<main class="operations-main">
    <div class="ops-page-header">
        <div>
            <h1 class="ops-title">Operations</h1>
        </div>
    </div>

    <div class="ops-schedule-container">
        <div class="ops-requests-queue">
            <div class="queue-header">
                <div class="queue-actions">
                    <select class="ops-filter-select" id="requestFilter">
                        <option value="all">All e-waste types</option>
                        <option value="electronics">Electronics</option>
                        <option value="batteries">Batteries</option>
                        <option value="appliances">Appliances</option>
                    </select>
                    <input type="text" placeholder="🔍 Search requests..." class="ops-search-input" id="requestSearch">
                </div>
            </div>

            <div class="request-cards-container" id="requestCardsContainer"></div>
        </div>

        <div class="ops-assignment-panel" id="assignmentPanel">
            <div class="panel-header">
                <h2>Assignment</h2>
                <span class="request-id-badge" id="selectedRequestId" style="display: none;"></span>
            </div>

            <div class="selected-request-summary" id="selectedRequestSummary"></div>

            <div class="assignment-form">
                <div class="form-section">
                    <h3>Assign Collector</h3>
                    <div class="assign-item">
                        <div class="custom-dropdown popup-only-field" id="collectorDropdown" data-selected-value="">
                            <div class="custom-dropdown-select no-arrow-field">
                                <span id="selectedCollectorText">Select a collector</span>
                            </div>
                        </div>
                        <button class="c-btn-small view-btn popup-action-btn" id="viewCollectorAvailability" title="Choose collector">
                            <i class="far fa-calendar-alt"></i>
                        </button>
                    </div>
                    <div class="collector-availability-hint" id="collectorHint"></div>
                </div>

                <div class="form-section">
                    <h3>Assign Vehicle</h3>
                    <div class="assign-item">
                        <div class="custom-dropdown popup-only-field" id="vehicleDropdown" data-selected-value="">
                            <div class="custom-dropdown-select no-arrow-field">
                                <span id="selectedVehicleText">Select a vehicle</span>
                            </div>
                        </div>
                        <button class="c-btn-small view-btn popup-action-btn" id="viewVehicleStatus" title="Choose vehicle">
                            <i class="fas fa-truck"></i>
                        </button>
                    </div>
                    <div class="vehicle-status-hint" id="vehicleHint"></div>
                </div>

                <div class="form-section">
                    <h3>Collection Centre</h3>
                    <div class="assign-item">
                        <div class="custom-dropdown" id="centreDropdown">
                            <div class="custom-dropdown-select centre-select-field">
                                <span id="selectedCentreText">Select a collection centre</span>
                                <span class="arrow"><i class="fas fa-chevron-down"></i></span>
                            </div>
                            <div class="custom-dropdown-menu" id="centreMenu"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Schedule Date & Time</h3>
                    <div class="datetime-picker-container">
                        <input type="datetime-local" id="scheduledDateTime" class="ops-datetime-input" min="">
                    </div>
                </div>

                <div class="form-section">
                    <h3>Notes</h3>
                    <textarea id="assignmentNotes" class="ops-textarea"></textarea>
                </div>

                <div class="assignment-actions">
                    <button class="c-btn-primary c-btn-big" id="confirmAssignmentBtn" disabled>✓ Confirm</button>
                    <button class="c-btn-secondary" id="resetAssignmentBtn">↺ Reset</button>
                </div>

                <div class="quick-actions"></div>
            </div>

            <div class="recent-assignments">
                <h3>Recent assignments</h3>
                <div class="timeline-mini" id="recentTimeline"></div>
            </div>
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
        <div class="c-text c-text-label">
            +60 12 345 6789
        </div>
        <div class="c-text">
            abc@gmail.com
        </div>
    </section>

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

<div class="ops-modal-overlay" id="vehicleMaintenanceModal" style="display:none;">
    <div class="ops-modal ops-modal-large nicer-modal">
        <div class="ops-modal-header nicer-modal-header">
            <div>
                <h3>Vehicle Availability</h3>
                <p class="ops-modal-subtitle" id="selectedVehicleDateDisplay">No date selected</p>
            </div>
            <button type="button" class="ops-modal-close plain-close-btn" id="closeVehicleMaintenanceModal">&times;</button>
        </div>

        <div class="ops-modal-body">
            <div class="maintenance-toolbar cleaner-toolbar">
                <input type="date" id="vehicleAvailabilityDatePicker" class="date-picker-small">
                <div class="available-count-badge" id="availableVehicleCountBadge"></div>
            </div>

            <div id="vehicleMaintenanceCalendar" class="maintenance-vehicle-grid">
                <div class="maintenance-empty">No vehicles found.</div>
            </div>
        </div>
    </div>
</div>

<script src="../../javascript/mainScript.js"></script>

<script>
window.requestsData = <?php echo json_encode($requests, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.collectorsData = <?php echo json_encode($collectors, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.collectorScheduledJobsData = <?php echo json_encode($collectorScheduledJobs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.vehiclesData = <?php echo json_encode($vehicles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.vehicleMaintenanceData = <?php echo json_encode($vehicleMaintenance, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.vehicleScheduledJobsData = <?php echo json_encode($vehicleScheduledJobs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.centresData = <?php echo json_encode($centres, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.centreAcceptedTypesData = <?php echo json_encode($centreAcceptedTypesData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.recentAssignmentsData = <?php echo json_encode($recentAssignments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<script src="../../javascript/aOperations.js?v=<?php echo time(); ?>"></script>
</body>
</html>