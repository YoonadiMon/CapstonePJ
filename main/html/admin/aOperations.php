<?php
session_start();
include("../../php/dbConn.php");

// // check if user is logged in
// include("../../php/sessionCheck.php");

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

        // request must exist and be approved
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

        // prevent duplicate job
        $checkJobSql = "
            SELECT jobID
            FROM tbljob
            WHERE requestID = ?
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkJobSql);
        mysqli_stmt_bind_param($stmt, "i", $requestID);
        mysqli_stmt_execute($stmt);
        $jobResult = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($jobResult)) {
            throw new Exception('This request already has a job assigned.');
        }

        // collector must be available
        $checkCollectorSql = "
            SELECT c.collectorID
            FROM tblcollector c
            WHERE c.collectorID = ?
              AND c.status IN ('active', 'on duty')
              AND NOT EXISTS (
                    SELECT 1
                    FROM tbljob j
                    WHERE j.collectorID = c.collectorID
                      AND j.status IN ('Pending', 'Scheduled', 'Ongoing')
              )
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkCollectorSql);
        mysqli_stmt_bind_param($stmt, "i", $collectorID);
        mysqli_stmt_execute($stmt);
        $collectorResult = mysqli_stmt_get_result($stmt);

        if (!mysqli_fetch_assoc($collectorResult)) {
            throw new Exception('Collector is not available.');
        }

        // vehicle must be available
        $checkVehicleSql = "
            SELECT v.vehicleID
            FROM tblvehicle v
            WHERE v.vehicleID = ?
              AND v.status = 'Available'
              AND NOT EXISTS (
                    SELECT 1
                    FROM tbljob j
                    WHERE j.vehicleID = v.vehicleID
                      AND j.status IN ('Pending', 'Scheduled', 'Ongoing')
              )
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkVehicleSql);
        mysqli_stmt_bind_param($stmt, "i", $vehicleID);
        mysqli_stmt_execute($stmt);
        $vehicleResult = mysqli_stmt_get_result($stmt);

        if (!mysqli_fetch_assoc($vehicleResult)) {
            throw new Exception('Vehicle is not available.');
        }

        // centre must be active
        $checkCentreSql = "
            SELECT centreID, name
            FROM tblcentre
            WHERE centreID = ?
              AND status = 'Active'
            LIMIT 1
        ";
        $stmt = mysqli_prepare($conn, $checkCentreSql);
        mysqli_stmt_bind_param($stmt, "i", $centreID);
        mysqli_stmt_execute($stmt);
        $centreResult = mysqli_stmt_get_result($stmt);
        $centreRow = mysqli_fetch_assoc($centreResult);

        if (!$centreRow) {
            throw new Exception('Collection centre is not valid.');
        }

        // collector and vehicle display info
        $collectorName = 'Collector ID ' . $collectorID;
        $plateNum = 'Vehicle ID ' . $vehicleID;
        $centreName = $centreRow['name'];

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

        // insert job
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

        // update request status
        $updateRequestSql = "
            UPDATE tblcollection_request
            SET status = 'Scheduled'
            WHERE requestID = ?
        ";
        $stmt = mysqli_prepare($conn, $updateRequestSql);
        mysqli_stmt_bind_param($stmt, "i", $requestID);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update request status.');
        }

        $updateItemsSql = "
            UPDATE tblitem
            SET centreID = ?
            WHERE requestID = ?
        ";
        $stmt = mysqli_prepare($conn, $updateItemsSql);
        mysqli_stmt_bind_param($stmt, "ii", $centreID, $requestID);
        mysqli_stmt_execute($stmt);

        // activity log: request assignment
        $assignmentDescription = "Assigned to collector {$collectorName}, Vehicle {$plateNum}, {$centreName}";
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

        // activity log: request status
        $requestStatusDescription = "Changed from Approved to Scheduled";
        $logRequestStatusSql = "
            INSERT INTO tblactivity_log (
                requestID,
                jobID,
                userID,
                type,
                action,
                description
            ) VALUES (?, ?, ?, 'Request', 'Status Change', ?)
        ";
        $stmt = mysqli_prepare($conn, $logRequestStatusSql);
        mysqli_stmt_bind_param($stmt, "iiis", $requestID, $jobID, $adminUserID, $requestStatusDescription);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to log request status change.');
        }

        // activity log: job create
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

// Approved requests waiting for assignment

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
    WHERE cr.status = 'Approved'
      AND j.jobID IS NULL
    GROUP BY 
        cr.requestID,
        u.fullname,
        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,
        cr.preferredDateTime,
        cr.status
    ORDER BY cr.preferredDateTime ASC
";

$requestResult = mysqli_query($conn, $requestSql);

if ($requestResult) {
    while ($row = mysqli_fetch_assoc($requestResult)) {
        $requestId = (int)$row['requestID'];

        $items = [];
        $itemTypeSql = "
            SELECT it.name
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
            'address' => $row['pickupAddress'] . ', ' . $row['pickupState'] . ' ' . $row['pickupPostcode'],
            'postcode' => $row['pickupPostcode'],
            'preferredDate' => date('Y-m-d\TH:i', strtotime($row['preferredDateTime'])),
            'weight' => number_format((float)$row['totalWeight'], 2) . ' kg',
            'status' => strtolower($row['status']),
            'type' => $type
        ];
    }
}

// Collectors

$collectors = [];
$collectorSql = "
    SELECT 
        c.collectorID,
        u.fullname,
        c.status,
        CASE
            WHEN EXISTS (
                SELECT 1
                FROM tbljob j
                WHERE j.collectorID = c.collectorID
                  AND j.status IN ('Pending','Scheduled','Ongoing')
            ) THEN 0
            ELSE 1
        END AS available
    FROM tblcollector c
    INNER JOIN tblusers u
        ON u.userID = c.collectorID
    WHERE c.status IN ('active', 'on duty')
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

// Vehicles

$vehicles = [];
$vehicleSql = "
    SELECT
        v.vehicleID,
        v.model,
        v.plateNum,
        v.type,
        v.capacityWeight,
        v.status,
        CASE
            WHEN v.status = 'Available'
             AND NOT EXISTS (
                SELECT 1
                FROM tbljob j
                WHERE j.vehicleID = v.vehicleID
                  AND j.status IN ('Pending','Scheduled','Ongoing')
             )
            THEN 1
            ELSE 0
        END AS available
    FROM tblvehicle v
    WHERE v.status <> 'Inactive'
    ORDER BY v.plateNum ASC
";

$vehicleResult = mysqli_query($conn, $vehicleSql);

if ($vehicleResult) {
    while ($row = mysqli_fetch_assoc($vehicleResult)) {
        $vehicles[] = [
            'id' => (string)$row['vehicleID'],
            'model' => $row['model'] . ' - ' . $row['plateNum'],
            'status' => $row['available'] ? 'Available' : $row['status'],
            'capacity' => number_format((float)$row['capacityWeight'], 0) . ' kg'
        ];
    }
}

// Centres

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
    WHERE c.status = 'Active'
    GROUP BY c.centreID, c.name, c.address, c.state, c.postcode, c.status
    ORDER BY c.name ASC
";

$centreResult = mysqli_query($conn, $centreSql);

if ($centreResult) {
    while ($row = mysqli_fetch_assoc($centreResult)) {
        $itemCount = (int)$row['itemCount'];
        $capacityPercent = min(100, $itemCount * 10);

        $centres[] = [
            'id' => (string)$row['centreID'],
            'name' => $row['name'],
            'capacity' => $capacityPercent,
            'address' => $row['address'] . ', ' . $row['state'] . ' ' . $row['postcode']
        ];
    }
}

// Recent assignments timeline
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
    <link rel="stylesheet" href="../../style/aOperations.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <!-- Logo + Name & Navbar -->
    <header>
        <!-- Logo + Name -->
        <section class="c-logo-section">
            <a href="../../html/admin/aHome.html" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <!-- Menu Links Mobile -->
        <nav class="c-navbar-side">
            <img src="../../assets/images/icon-menu.svg" alt="icon-menu" onclick="showMenu()" class="c-icon-btn" id="menuBtn">
            <div id="sidebarNav" class="c-navbar-side-menu">
                
                <img src="../../assets/images/icon-menu-close.svg" alt="icon-menu-close" onclick="hideMenu()" class="close-btn" id="closeBtn">
                <div class="c-navbar-side-items">
                    <section class="c-navbar-side-more">
                        <button id="themeToggleMobile">
                            <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon">
                        </button>
                        <a href="../../html/common/Setting.html">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>

                    <a href="../../html/admin/aHome.html">Home</a>
                    <a href="../../html/admin/aRequests.php">Requests</a>
                    <a href="../../html/admin/aJobs.php">Jobs</a>
                    <a href="../../html/admin/aIssue.html">Issue</a>
                    <a href="../../html/admin/aOperations.php" class="active">Operations</a>
                    <a href="../../html/admin/aReport.html">Report</a>
                </div>
            </div>
        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <a href="../../html/admin/aHome.html">Home</a>
            <a href="../../html/admin/aRequests.php">Requests</a>
            <a href="../../html/admin/aJobs.php">Jobs</a>
            <a href="../../html/admin/aIssue.html">Issue</a>
            <a href="../../html/admin/aOperations.php" class="active">Operations</a>
            <a href="../../html/admin/aReport.html">Report</a>
        </nav>          
        <section class="c-navbar-more">
            <button id="themeToggleDesktop">
                <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
            <a href="../../html/common/Setting.html">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
    </header>
    <hr>

    <!-- Main Content -->
    <main class="operations-main">
        <!-- Page Header -->
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

                <!-- Request Cards Container -->
                <div class="request-cards-container" id="requestCardsContainer">
                </div>

                <div class="pagination-controls">
                    <button class="c-btn-small" disabled>◀ Previous</button>
                    <span class="page-indicator">Page 1 of 3</span>
                    <button class="c-btn-small">Next ▶</button>
                </div>
            </div>

            <div class="ops-assignment-panel" id="assignmentPanel">
                <div class="panel-header">
                    <h2>Assignment</h2>
                    <span class="request-id-badge" id="selectedRequestId" style="display: none;"></span>
                </div>


                <div class="selected-request-summary" id="selectedRequestSummary">
                </div>

                <!-- Assignment Form -->
                <div class="assignment-form">
                    <!-- Assign Collector Section -->
                    <div class="form-section">
                        <h3>Assign Collector</h3>
                        <div class="assign-item">
                            <div class="custom-dropdown" id="collectorDropdown">
                                <div class="custom-dropdown-select">
                                    <span id="selectedCollectorText">Select a collector</span>
                                    <span class="arrow"><i class="fas fa-chevron-down"></i></span>
                                </div>
                                <div class="custom-dropdown-menu" id="collectorMenu"></div>
                            </div>
                            <button class="c-btn-small view-btn" id="viewCollectorAvailability" title="View availability">
                                <i class="far fa-calendar-alt"></i>
                            </button>
                        </div>
                        <div class="collector-availability-hint" id="collectorHint"></div>
                    </div>

                    <!-- Assign Vehicle Section -->
                    <div class="form-section">
                        <h3>Assign Vehicle</h3>
                        <div class="assign-item">
                            <div class="custom-dropdown" id="vehicleDropdown">
                                <div class="custom-dropdown-select">
                                    <span id="selectedVehicleText">Select a vehicle</span>
                                    <span class="arrow"><i class="fas fa-chevron-down"></i></span>
                                </div>
                                <div class="custom-dropdown-menu" id="vehicleMenu"></div>
                            </div>
                            <button class="c-btn-small view-btn" id="viewVehicleStatus" title="View status">
                                <i class="fas fa-wrench"></i>
                            </button>
                        </div>
                        <div class="vehicle-status-hint" id="vehicleHint"></div>
                    </div>

                    <!-- Collection Centre Section -->
                    <div class="form-section">
                        <h3>Collection Centre</h3>
                        <div class="assign-item">
                            <div class="custom-dropdown" id="centreDropdown">
                                <div class="custom-dropdown-select">
                                    <span id="selectedCentreText">Select a collection centre</span>
                                    <span class="arrow"><i class="fas fa-chevron-down"></i></span>
                                </div>
                                <div class="custom-dropdown-menu" id="centreMenu"></div>
                            </div>
                            <div class="centre-capacity-container" id="centreCapacityContainer">
                                <div class="capacity-circle" id="capacityCircle">
                                    <span id="capacityPercentage">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Date & Time Section -->
                    <div class="form-section">
                        <h3>Schedule Date & Time</h3>
                        <div class="datetime-picker-container">
                            <input type="datetime-local" id="scheduledDateTime" class="ops-datetime-input" min="">
                            <div style="font-size: 0.85rem; color: var(--Gray); display: flex; align-items: center; gap: 0.3rem;"></div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="form-section">
                        <h3>Notes</h3>
                        <textarea id="assignmentNotes" class="ops-textarea"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="assignment-actions">
                        <button class="c-btn-primary c-btn-big" id="confirmAssignmentBtn" disabled>✓ Confirm</button>
                        <button class="c-btn-secondary" id="resetAssignmentBtn">↺ Reset</button>
                    </div>

                    <!-- Quick Actions -->
                    <div class="quick-actions"></div>
                </div>

                <!-- Recent Assignments Timeline -->
                <div class="recent-assignments">
                    <h3>Recent assignments</h3>
                    <div class="timeline-mini" id="recentTimeline"></div>
                </div>
            </div>
        </div>

        <!-- Bulk Operations Section 
        <div class="bulk-ops-section">
            <h3>Bulk operations</h3>
            <div class="bulk-actions">
                <button class="c-btn-secondary" id="assignMultipleBtn">Assign multiple (2 selected)</button>
                <button class="c-btn-secondary" id="rescheduleSelectedBtn">Reschedule selected</button>
                <button class="c-btn-secondary" id="exportScheduleBtn">📊 Export schedule</button>
            </div>
        </div> -->
    </main> 
    <hr>

    
    <!-- Footer -->
    <footer>
        <!-- Column 1 -->
        <section class="c-footer-info-section">
            <a href="../../html/admin/aHome.html">
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
                <a href="../../html/admin/aCollectionRequests.html">Collection Requests</a><br>
                <a href="../../html/admin/aJobs.html">Collection Jobs</a><br>
                <a href="../../html/admin/aIssue.html">Issue</a><br>
            </div>
            <div>
                <b>System Operation</b><br>
                <a href="../../html/admin/aProviders.html">Providers</a><br>
                <a href="../../html/admin/aCollectors.html">Collectors</a><br>
                <a href="../../html/admin/aVehicles.html">Vehicles</a><br>
                <a href="../../html/admin/aCentres.html">Collection Centres</a><br>
                <a href="../../html/admin/aItemProcessing.html">Item Processing</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/Profile.html">Edit Profile</a><br>
                <a href="../../html/common/Setting.html">Setting</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>

    <script>
    window.requestsData = <?php echo json_encode($requests, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.collectorsData = <?php echo json_encode($collectors, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.vehiclesData = <?php echo json_encode($vehicles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.centresData = <?php echo json_encode($centres, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.recentAssignmentsData = <?php echo json_encode($recentAssignments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;


    console.log('requestsData:', window.requestsData);
    console.log('collectorsData:', window.collectorsData);
    console.log('vehiclesData:', window.vehiclesData);
    console.log('centresData:', window.centresData);
    console.log('recentAssignmentsData:', window.recentAssignmentsData);
    </script>

    <script src="../../javascript/aOperations.js"></script>
</body>
</html>