<?php
session_start();
include("../../php/dbConn.php");

// // check if user is logged in
// include("../../php/sessionCheck.php");

if (!isset($conn)) {
    die("Database connection not found.");
}

date_default_timezone_set('Asia/Kuala_Lumpur');

function esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function queryAll(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_free_result($result);
    return $rows;
}

function queryOne(mysqli $conn, string $sql): ?array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row ?: null;
}

function formatDelay(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }

    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($mins === 0) {
        return $hours . ' hr';
    }

    return $hours . ' hr ' . $mins . ' min';
}

function buildAddress(...$parts): string
{
    $clean = [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part !== '') {
            $clean[] = $part;
        }
    }
    return implode(', ', $clean);
}

// Handover Required
$handoverSql = "
    SELECT
        j.jobID,
        r.requestID,
        u.fullname AS collectorName,
        r.pickupAddress,
        r.pickupState,
        r.pickupPostcode,
        COALESCE(
            CASE
                WHEN LOWER(al.description) LIKE '%vehicle full%' THEN 'Vehicle full'
                WHEN LOWER(al.description) LIKE '%end of shift%' THEN 'End of shift'
                WHEN LOWER(al.description) LIKE '%handover%' THEN 'Handover required'
                ELSE al.action
            END,
            'Handover required'
        ) AS handoverReason,
        DATE_FORMAT(al.dateTime, '%h:%i %p') AS handoverTime,
        v.plateNum,
        v.type AS vehicleType
    FROM tblactivity_log al
    INNER JOIN tbljob j ON j.jobID = al.jobID
    INNER JOIN tblcollection_request r ON r.requestID = j.requestID
    INNER JOIN tblusers u ON u.userID = j.collectorID
    LEFT JOIN tblvehicle v ON v.vehicleID = j.vehicleID
    WHERE al.type = 'Job'
      AND (
            LOWER(al.action) LIKE '%handover%'
         OR LOWER(al.description) LIKE '%handover%'
         OR LOWER(al.description) LIKE '%vehicle full%'
         OR LOWER(al.description) LIKE '%end of shift%'
      )
      AND j.status NOT IN ('Completed', 'Cancelled', 'Rejected')
    ORDER BY al.dateTime DESC
";
$handoverRows = queryAll($conn, $handoverSql);

$handoverJobs = [];
foreach ($handoverRows as $row) {
    $jobIdFormatted = 'JOB' . str_pad((string)$row['jobID'], 3, '0', STR_PAD_LEFT);

    $handoverJobs[] = [
        'id' => $jobIdFormatted,
        'jobID' => (int)$row['jobID'],
        'requestID' => (int)$row['requestID'],
        'collector' => $row['collectorName'],
        'location' => buildAddress($row['pickupAddress'], $row['pickupState'], $row['pickupPostcode']),
        'reason' => $row['handoverReason'],
        'time' => $row['handoverTime'],
        'status' => 'Handover Required',
        'vehicle' => trim(($row['vehicleType'] ?? '') . ' ' . ($row['plateNum'] ?? ''))
    ];
}

// Delayed Jobs
$delayedSql = "
    SELECT
        j.jobID,
        j.requestID,
        j.status AS jobStatus,
        j.scheduledDate,
        j.scheduledTime,
        TIMESTAMPDIFF(
            MINUTE,
            CONCAT(j.scheduledDate, ' ', j.scheduledTime),
            NOW()
        ) AS delayMinutes,
        u.fullname AS collectorName,
        v.plateNum,
        v.type AS vehicleType,
        r.pickupAddress,
        r.pickupState,
        r.pickupPostcode,
        COALESCE(
            (
                SELECT i.subject
                FROM tblissue i
                WHERE i.jobID = j.jobID
                ORDER BY i.reportedAt DESC
                LIMIT 1
            ),
            CASE
                WHEN j.status = 'Pending' THEN 'Collector has not started yet'
                WHEN j.status = 'Scheduled' THEN 'Scheduled time exceeded'
                WHEN j.status = 'Ongoing' THEN 'Still in progress beyond schedule'
                ELSE 'Delayed'
            END
        ) AS delayReason
    FROM tbljob j
    INNER JOIN tblcollection_request r ON r.requestID = j.requestID
    INNER JOIN tblusers u ON u.userID = j.collectorID
    LEFT JOIN tblvehicle v ON v.vehicleID = j.vehicleID
    WHERE j.status IN ('Pending', 'Scheduled', 'Ongoing')
      AND CONCAT(j.scheduledDate, ' ', j.scheduledTime) < NOW()
    ORDER BY delayMinutes DESC, j.scheduledDate ASC, j.scheduledTime ASC
";
$delayedRows = queryAll($conn, $delayedSql);

$delayedJobs = [];
foreach ($delayedRows as $row) {
    $jobIdFormatted = 'JOB' . str_pad((string)$row['jobID'], 3, '0', STR_PAD_LEFT);

    $delayedJobs[] = [
        'id' => $jobIdFormatted,
        'jobID' => (int)$row['jobID'],
        'requestID' => (int)$row['requestID'],
        'collector' => $row['collectorName'],
        'location' => buildAddress($row['pickupAddress'], $row['pickupState'], $row['pickupPostcode']),
        'delay' => formatDelay(max(0, (int)$row['delayMinutes'])),
        'reason' => $row['delayReason'],
        'time' => date('h:i A', strtotime($row['scheduledTime'])),
        'status' => 'Delayed',
        'vehicle' => trim(($row['vehicleType'] ?? '') . ' ' . ($row['plateNum'] ?? ''))
    ];
}

// Failed Drop-Off
$failedDropoffSql = "
    SELECT
        j.jobID,
        j.requestID,
        u.fullname AS collectorName,
        COALESCE(c.name, 'Not Assigned') AS originalCentre,
        COUNT(DISTINCT i.itemID) AS itemCount,
        DATE_FORMAT(MAX(al.dateTime), '%h:%i %p') AS failTime,
        COALESCE(
            CASE
                WHEN LOWER(al.description) LIKE '%centre closed%' THEN 'Centre closed'
                WHEN LOWER(al.description) LIKE '%no parking%' THEN 'No parking'
                WHEN LOWER(al.description) LIKE '%failed%' THEN al.description
                ELSE al.action
            END,
            'Drop-off issue'
        ) AS failReason
    FROM tblactivity_log al
    INNER JOIN tbljob j ON j.jobID = al.jobID
    INNER JOIN tblusers u ON u.userID = j.collectorID
    LEFT JOIN tblitem i ON i.requestID = j.requestID
    LEFT JOIN tblcentre c ON c.centreID = i.centreID
    WHERE (
            LOWER(al.action) LIKE '%dropoff failed%'
         OR LOWER(al.action) LIKE '%drop-off failed%'
         OR LOWER(al.description) LIKE '%centre closed%'
         OR LOWER(al.description) LIKE '%no parking%'
         OR LOWER(al.description) LIKE '%failed drop%'
         OR LOWER(al.description) LIKE '%drop-off failed%'
    )
      AND j.status NOT IN ('Completed', 'Cancelled', 'Rejected')
    GROUP BY j.jobID, j.requestID, u.fullname, c.name, al.action, al.description
    ORDER BY MAX(al.dateTime) DESC
";
$failedDropoffRows = queryAll($conn, $failedDropoffSql);

$pendingDropoffJobs = [];
foreach ($failedDropoffRows as $row) {
    $jobIdFormatted = 'JOB' . str_pad((string)$row['jobID'], 3, '0', STR_PAD_LEFT);

    $pendingDropoffJobs[] = [
        'id' => $jobIdFormatted,
        'jobID' => (int)$row['jobID'],
        'requestID' => (int)$row['requestID'],
        'collector' => $row['collectorName'],
        'items' => ((int)$row['itemCount']) . ' items',
        'originalCentre' => $row['originalCentre'],
        'failReason' => $row['failReason'],
        'time' => $row['failTime'] ?: '-'
    ];
}

// Active Collectors 
$activeCollectorsSql = "
    SELECT
        c.collectorID,
        u.fullname,
        c.status AS collectorStatus,

        j.jobID,
        j.requestID,
        j.status AS jobStatus,
        j.scheduledDate,
        j.scheduledTime,

        v.plateNum,
        v.type AS vehicleType,

        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,

        centreData.centreID,
        centreData.centreName,
        centreData.centreAddress,
        centreData.centreState,
        centreData.centrePostcode

    FROM tblcollector c
    INNER JOIN tblusers u
        ON u.userID = c.collectorID

    LEFT JOIN (
        SELECT
            j1.collectorID,
            j1.jobID,
            j1.requestID,
            j1.vehicleID,
            j1.status,
            j1.scheduledDate,
            j1.scheduledTime
        FROM tbljob j1
        INNER JOIN (
            SELECT collectorID, MAX(jobID) AS latestJobID
            FROM tbljob
            GROUP BY collectorID
        ) latest
            ON latest.latestJobID = j1.jobID
    ) j
        ON j.collectorID = c.collectorID

    LEFT JOIN tblvehicle v
        ON v.vehicleID = j.vehicleID

    LEFT JOIN tblcollection_request cr
        ON cr.requestID = j.requestID

    LEFT JOIN (
        SELECT
            i.requestID,
            c2.centreID,
            c2.name AS centreName,
            c2.address AS centreAddress,
            c2.state AS centreState,
            c2.postcode AS centrePostcode
        FROM tblitem i
        INNER JOIN tblcentre c2
            ON c2.centreID = i.centreID
        INNER JOIN (
            SELECT requestID, MIN(itemID) AS firstItemID
            FROM tblitem
            WHERE centreID IS NOT NULL
            GROUP BY requestID
        ) firstItem
            ON firstItem.firstItemID = i.itemID
    ) centreData
        ON centreData.requestID = j.requestID

    WHERE c.status IN ('active', 'on duty')
    ORDER BY
        CASE
            WHEN j.status IN ('Pending', 'Scheduled', 'Ongoing') THEN 0
            ELSE 1
        END,
        u.fullname ASC
";
$activeCollectorRows = queryAll($conn, $activeCollectorsSql);

$activeCollectors = [];
$collectorSelectOptions = [];

foreach ($activeCollectorRows as $row) {
    $jobIdFormatted = !empty($row['jobID'])
        ? 'JOB' . str_pad((string)$row['jobID'], 3, '0', STR_PAD_LEFT)
        : null;

    $hasActiveJob = in_array((string)$row['jobStatus'], ['Pending', 'Scheduled', 'Ongoing'], true);

    $pickupAddressFull = buildAddress(
        $row['pickupAddress'] ?? '',
        $row['pickupState'] ?? '',
        $row['pickupPostcode'] ?? '',
        'Malaysia'
    );

    $centreAddressFull = buildAddress(
        $row['centreAddress'] ?? '',
        $row['centreState'] ?? '',
        $row['centrePostcode'] ?? '',
        'Malaysia'
    );

    $activeCollectors[] = [
        'id' => 'C' . str_pad((string)$row['collectorID'], 3, '0', STR_PAD_LEFT),
        'collectorID' => (int)$row['collectorID'],
        'name' => $row['fullname'],
        'vehicle' => !empty($row['plateNum'])
            ? trim(($row['vehicleType'] ?? '') . ' ' . ($row['plateNum'] ?? ''))
            : 'No vehicle assigned',
        'status' => $hasActiveJob ? 'busy' : 'online',
        'jobId' => $hasActiveJob ? $jobIdFormatted : null,
        'requestID' => !empty($row['requestID']) ? (int)$row['requestID'] : null,
        'jobStatus' => $row['jobStatus'] ?? '',
        'scheduledDate' => $row['scheduledDate'] ?? '',
        'scheduledTime' => $row['scheduledTime'] ?? '',
        'pickupAddress' => $pickupAddressFull,
        'pickupLabel' => buildAddress($row['pickupAddress'] ?? '', $row['pickupState'] ?? '', $row['pickupPostcode'] ?? ''),
        'centreName' => $row['centreName'] ?? '',
        'centreAddress' => $centreAddressFull,
        'centreLabel' => buildAddress($row['centreAddress'] ?? '', $row['centreState'] ?? '', $row['centrePostcode'] ?? ''),
        'currentRoad' => $hasActiveJob
            ? ('Pickup: ' . buildAddress($row['pickupAddress'] ?? '', $row['pickupState'] ?? '', $row['pickupPostcode'] ?? ''))
            : 'Waiting for assignment'
    ];

    $collectorSelectOptions[] = [
        'collectorID' => (int)$row['collectorID'],
        'name' => $row['fullname']
    ];
}

// Vehicles, centres, quick stats
$vehicleRows = queryAll($conn, "
    SELECT vehicleID, plateNum, type, status
    FROM tblvehicle
    WHERE status IN ('Available', 'In Use')
    ORDER BY
        CASE WHEN status = 'Available' THEN 0 ELSE 1 END,
        plateNum ASC
");

$centreRows = queryAll($conn, "
    SELECT centreID, name, state, status
    FROM tblcentre
    WHERE status = 'Active'
    ORDER BY name ASC
");

$completedTodayRow = queryOne($conn, "
    SELECT COUNT(*) AS totalCompleted
    FROM tbljob
    WHERE status = 'Completed'
      AND DATE(completedAt) = CURDATE()
");
$completedToday = (int)($completedTodayRow['totalCompleted'] ?? 0);

$avgResponseRow = queryOne($conn, "
    SELECT
        ROUND(AVG(TIMESTAMPDIFF(
            MINUTE,
            r.createdAt,
            CONCAT(j.scheduledDate, ' ', j.scheduledTime)
        ))) AS avgMinutes
    FROM tbljob j
    INNER JOIN tblcollection_request r ON r.requestID = j.requestID
");
$avgResponseMinutes = (int)($avgResponseRow['avgMinutes'] ?? 0);
$avgResponseText = $avgResponseMinutes > 0 ? $avgResponseMinutes . 'min' : '0min';

$totalDistance = 0;

$handoverLookup = [];
foreach ($handoverJobs as $job) {
    $handoverLookup[$job['id']] = $job;
}

$delayedLookup = [];
foreach ($delayedJobs as $job) {
    $delayedLookup[$job['id']] = $job;
}

$pendingDropoffLookup = [];
foreach ($pendingDropoffJobs as $job) {
    $pendingDropoffLookup[$job['id']] = $job;
}

$jsData = [
    'handoverJobs' => $handoverJobs,
    'delayedJobs' => $delayedJobs,
    'pendingDropoffJobs' => $pendingDropoffJobs,
    'activeCollectors' => $activeCollectors,
    'quickStats' => [
        'completedToday' => $completedToday,
        'avgResponse' => $avgResponseText,
        'totalDistance' => $totalDistance
    ],
    'handoverLookup' => $handoverLookup,
    'delayedLookup' => $delayedLookup,
    'pendingDropoffLookup' => $pendingDropoffLookup,
    'centresAvailable' => count($centreRows)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Jobs - AfterVolt</title>

    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/aCollectionJobs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">
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
        <div class="page-container">
            <div class="ops-header">
                <h1>Collection Jobs</h1>
            </div>

            <div class="dashboard-grid">
                <div class="jobs-column">
                    <div class="handover-panel" id="handoverPanel">
                        <div class="panel-header danger">
                            <i class="fas fa-exchange-alt"></i>
                            <h3>Handover Required</h3>
                            <span class="panel-badge" id="panelHandoverCount">0</span>
                        </div>
                        <div class="panel-content" id="handoverList"></div>
                    </div>

                    <div class="delayed-panel" id="delayedPanel">
                        <div class="panel-header warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Delayed Jobs</h3>
                            <span class="panel-badge" id="panelDelayedCount">0</span>
                        </div>
                        <div class="panel-content" id="delayedList"></div>
                    </div>

                    <div class="pickup-failed-section">
                        <div class="section-header">
                            <h2><i class="fas fa-exclamation-triangle" style="color: #ffa502;"></i>Failed Drop-Off</h2>
                            <span class="section-badge" id="pendingDropoffCount">0</span>
                        </div>

                        <div class="info-message" style="background: #fff3cd; border-left: 4px solid #ffa502; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem;">
                            <i class="fas fa-info-circle" style="color: #b55f0e;"></i>
                            <span style="color: #b55f0e; font-size: 0.9rem;"> Need centre reassignment</span>
                        </div>

                        <div class="pending-dropoff-list" id="pendingDropoffList"></div>

                        <div class="dropoff-stats" style="display: flex; gap: 1rem; margin-top: 1rem; padding: 0.8rem; background: var(--sec-bg-color); border-radius: 12px;">
                            <div style="flex: 1; text-align: center;">
                                <span style="font-size: 1.3rem; font-weight: 700; color: #ffa502;" id="itemsInTransit">0</span>
                                <span style="display: block; font-size: 0.7rem; color: var(--Gray);">Items in Transit</span>
                            </div>
                            <div style="flex: 1; text-align: center;">
                                <span style="font-size: 1.3rem; font-weight: 700; color: var(--MainBlue);" id="affectedCollectors">0</span>
                                <span style="display: block; font-size: 0.7rem; color: var(--Gray);">Affected Collectors</span>
                            </div>
                            <div style="flex: 1; text-align: center;">
                                <span style="font-size: 1.3rem; font-weight: 700; color: #2ecc71;" id="centresAvailable">0</span>
                                <span style="display: block; font-size: 0.7rem; color: var(--Gray);">Centres Available</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="map-column">
                    <div class="map-container" id="mapContainer">
                        <div class="map-placeholder" id="mapPlaceholder">
                            <i class="fas fa-map-marked-alt"></i>
                            <p>Loading map...</p>
                        </div>

                        <div id="actualMap" style="height: 100%; width: 100%; display: none;"></div>

                        <div class="map-controls">
                            <button class="map-control-btn" onclick="centerMapOnAll()">
                                <i class="fas fa-location-arrow"></i>
                            </button>
                            <button class="map-control-btn" onclick="toggleMapLayers()">
                                <i class="fas fa-layer-group"></i>
                            </button>
                            <button class="map-control-btn" onclick="zoomToFit()">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>

                        <div class="route-info-box" id="routeInfoBox" style="display: none;">
                            <div><strong id="routeCollectorName">Collector</strong></div>
                            <div id="routeCurrentLocation">Current location: -</div>
                            <div id="routeEta">ETA to collection centre: -</div>
                        </div>

                        <div id="etaBubble" class="eta-bubble" style="display: none;">
                            <span class="eta-dot"></span>
                            <span id="etaBubbleText">ETA: -</span>
                        </div>
                    </div>

                    <div class="quick-stats">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value" id="completedToday">0</span>
                                <span class="stat-label">Completed Today</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value" id="avgResponse">0</span>
                                <span class="stat-label">Avg Response</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-route"></i>
                            </div>
                            <div class="stat-details">
                                <span class="stat-value" id="totalDistance">0</span>
                                <span class="stat-label">Total KM</span>
                            </div>
                        </div>
                    </div>

                    <div class="active-collectors-box">
                        <div class="box-header">
                            <h3><i class="fas fa-users"></i> Active Collections</h3>
                            <span class="collector-count" id="activeCollectorCount">0</span>
                        </div>
                        <div class="collector-list" id="activeCollectorList"></div>
                    </div>
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
                <a href="../../html/admin/aCollectionRequests.php">Collection Requests</a><br>
                <a href="../../html/admin/aCollectionJobs.php">Collection Jobs</a><br>
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

    <!-- Assign Handover Modal -->
    <div class="modal-overlay" id="assignHandoverModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Assign Handover</h3>
                <button class="modal-close" onclick="closeAssignHandoverModal()">&times;</button>
            </div>
            <form id="assignHandoverForm" class="admin-form">
                <div class="form-group">
                    <label>Job ID</label>
                    <input type="text" id="handoverJobId" readonly>
                </div>

                <div class="form-group">
                    <label>Current Collector</label>
                    <input type="text" id="handoverCurrentCollector" readonly>
                </div>

                <div class="form-group">
                    <label>Reason for Handover</label>
                    <input type="text" id="handoverReason" readonly>
                </div>

                <div class="form-group">
                    <label>Assign To Collector</label>
                    <select id="handoverNewCollector" required>
                        <option value="">Select collector</option>
                        <option value="Ahmad Bin Yusof">Ahmad Bin Yusof</option>
                        <option value="Mei Ling">Mei Ling</option>
                        <option value="Mike Wilson">Mike Wilson</option>
                        <option value="Jane Smith">Jane Smith</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vehicle</label>
                    <select id="handoverVehicle" required>
                        <option value="">Select vehicle</option>
                        <option value="Van ABC 123">Van ABC 123</option>
                        <option value="Truck XYZ 789">Truck XYZ 789</option>
                        <option value="Van DEF 456">Van DEF 456</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <select id="handoverPriority">
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Admin Notes</label>
                    <textarea id="handoverAdminNotes" rows="3" placeholder="Add handover notes"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeAssignHandoverModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Assign Handover</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Job Details Modal -->
    <div class="modal-overlay" id="jobDetailsModal">
        <div class="modal-box modal-box-lg">
            <div class="modal-header">
                <h3>Job Details</h3>
                <button class="modal-close" onclick="closeJobDetailsModal()">&times;</button>
            </div>

            <div class="job-details-grid">
                <div class="detail-card">
                    <h4>Job Information</h4>
                    <p><strong>Job ID:</strong> <span id="detailsJobId"></span></p>
                    <p><strong>Status:</strong> <span id="detailsStatus"></span></p>
                    <p><strong>Scheduled Time:</strong> <span id="detailsTime"></span></p>
                    <p><strong>Location:</strong> <span id="detailsLocation"></span></p>
                </div>

                <div class="detail-card">
                    <h4>Collector Information</h4>
                    <p><strong>Collector:</strong> <span id="detailsCollector"></span></p>
                    <p><strong>Vehicle:</strong> <span id="detailsVehicle"></span></p>
                    <p><strong>Reason:</strong> <span id="detailsReason"></span></p>
                </div>

                <div class="detail-card detail-card-full">
                    <h4>Admin Action Summary</h4>
                    <textarea id="detailsAdminNotes" rows="4" placeholder="Add internal admin notes here"></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeJobDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Reassign Job Modal -->
    <div class="modal-overlay" id="reassignJobModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Reassign Job</h3>
                <button class="modal-close" onclick="closeReassignJobModal()">&times;</button>
            </div>
            <form id="reassignJobForm" class="admin-form">
                <div class="form-group">
                    <label>Job ID</label>
                    <input type="text" id="reassignJobId" readonly>
                </div>

                <div class="form-group">
                    <label>Current Collector</label>
                    <input type="text" id="reassignCurrentCollector" readonly>
                </div>

                <div class="form-group">
                    <label>Delay Reason</label>
                    <input type="text" id="reassignDelayReason" readonly>
                </div>

                <div class="form-group">
                    <label>New Collector</label>
                    <select id="reassignNewCollector" required>
                        <option value="">Select collector</option>
                        <option value="Ahmad Bin Yusof">Ahmad Bin Yusof</option>
                        <option value="Mei Ling">Mei Ling</option>
                        <option value="Mike Wilson">Mike Wilson</option>
                        <option value="Jane Smith">Jane Smith</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>New Vehicle</label>
                    <select id="reassignNewVehicle" required>
                        <option value="">Select vehicle</option>
                        <option value="Van ABC 123">Van ABC 123</option>
                        <option value="Truck XYZ 789">Truck XYZ 789</option>
                        <option value="Van DEF 456">Van DEF 456</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Updated ETA</label>
                    <input type="text" id="reassignEta" placeholder="e.g. 20 min">
                </div>

                <div class="form-group">
                    <label>Admin Remarks</label>
                    <textarea id="reassignRemarks" rows="3" placeholder="Reason for reassignment / instructions"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeReassignJobModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Confirm Reassign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reassign Centre Modal -->
    <div class="modal-overlay" id="reassignCentreModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Reassign Collection Centre</h3>
                <button class="modal-close" onclick="closeReassignCentreModal()">&times;</button>
            </div>

            <form id="reassignCentreForm" class="admin-form">
                <div class="form-group">
                    <label>Job ID</label>
                    <input type="text" id="reassignCentreJobId" readonly>
                </div>

                <div class="form-group">
                    <label>Collector</label>
                    <input type="text" id="reassignCentreCollector" readonly>
                </div>

                <div class="form-group">
                    <label>Original Centre</label>
                    <input type="text" id="reassignCentreOriginal" readonly>
                </div>

                <div class="form-group">
                    <label>Failure Reason</label>
                    <input type="text" id="reassignCentreReason" readonly>
                </div>

                <div class="form-group">
                    <label>New Collection Centre</label>
                    <select id="reassignCentreNew" required>
                        <option value="">Select centre</option>
                        <option value="Centre A">Centre A</option>
                        <option value="Centre B">Centre B</option>
                        <option value="Centre C">Centre C</option>
                        <option value="Centre D">Centre D</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <select id="reassignCentrePriority">
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Instructions for Collector</label>
                    <textarea id="reassignCentreInstructions" rows="3" placeholder="Example: Proceed to Centre C and confirm arrival with admin."></textarea>
                </div>

                <div class="form-group">
                    <label>Admin Remarks</label>
                    <textarea id="reassignCentreRemarks" rows="3" placeholder="Internal note for reassignment"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeReassignCentreModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Confirm Reassign</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script src="/main/javascript/mainScript.js"></script>
    <script src="/main/javascript/aCollectionJobs.js"></script>
</body>
</html>