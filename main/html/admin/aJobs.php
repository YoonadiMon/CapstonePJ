<?php
session_start();
include("../../php/dbConn.php");

// // check if user is logged in
include("../../php/sessionCheck.php"); 

function sanitize($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function mapJobStatus($jobStatus) {
    $jobStatus = strtolower(trim((string)$jobStatus));

    if ($jobStatus === 'pending') return 'Pending';
    if ($jobStatus === 'scheduled') return 'Scheduled';
    if ($jobStatus === 'ongoing') return 'Ongoing';
    if ($jobStatus === 'completed') return 'Completed';
    if ($jobStatus === 'rejected') return 'Rejected';
    if ($jobStatus === 'cancelled') return 'Cancelled';

    return 'Pending';
}

function mapStage($status) {
    if (in_array($status, ['Pending', 'Scheduled', 'Rejected'], true)) return 'pre-execution';
    if ($status === 'Ongoing') return 'execution';
    if (in_array($status, ['Completed', 'Cancelled'], true)) return 'resolution';
    return 'pre-execution';
}

function mapTimelineIcon($type, $action, $description = '') {
    $type = strtolower(trim((string)$type));
    $action = strtolower(trim((string)$action));
    $description = strtolower(trim((string)$description));

    if ($type === 'issue') return 'fas fa-triangle-exclamation';
    if (strpos($action, 'create') !== false || strpos($description, 'create') !== false) {
        return 'fas fa-file-circle-plus';
    }
    if (strpos($action, 'assign') !== false || strpos($description, 'assign') !== false) {
        return 'fas fa-user-check';
    }
    if (strpos($action, 'accept') !== false || strpos($description, 'accept') !== false) {
        return 'fas fa-check-circle';
    }
    if (strpos($action, 'reject') !== false || strpos($description, 'reject') !== false) {
        return 'fas fa-times-circle';
    }
    if (strpos($action, 'delay') !== false || strpos($description, 'delay') !== false) {
        return 'fas fa-clock';
    }
    if (strpos($action, 'pickup') !== false || strpos($description, 'pickup') !== false || strpos($description, 'picked up') !== false) {
        return 'fas fa-box';
    }
    if (strpos($action, 'dropoff') !== false || strpos($description, 'drop off') !== false || strpos($description, 'dropoff') !== false) {
        return 'fas fa-location-dot';
    }
    if (strpos($action, 'depart') !== false || strpos($description, 'depart') !== false) {
        return 'fas fa-truck';
    }
    if (strpos($action, 'arrive') !== false || strpos($description, 'arrive') !== false) {
        return 'fas fa-map-marker-alt';
    }
    if (strpos($action, 'complete') !== false || strpos($description, 'complete') !== false) {
        return 'fas fa-check-double';
    }
    if (strpos($action, 'cancel') !== false || strpos($description, 'cancel') !== false) {
        return 'fas fa-ban';
    }
    return 'fas fa-circle';
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$checkJobs = "SELECT COUNT(*) as total FROM tbljob";
$checkResult = $conn->query($checkJobs);
if ($checkResult) {
    $jobCount = $checkResult->fetch_assoc()['total'];
    error_log("Total jobs in database: " . $jobCount);
}

// Fetch jobs 
$jobs = [];
$sql = "
SELECT
    j.jobID,
    j.requestID,
    j.collectorID,
    j.vehicleID,
    j.scheduledDate,
    j.scheduledTime,
    j.estimatedEndTime,
    j.status AS jobStatus,
    j.rejectionReason AS jobRejectionReason,
    j.startedAt,
    j.completedAt,
    r.status AS requestStatus,
    r.rejectionReason AS requestRejectionReason,
    r.pickupAddress,
    r.pickupState,
    r.pickupPostcode,
    r.preferredDateTime,
    r.createdAt AS requestCreatedAt,
    up.fullname AS providerName,
    up.userID AS providerUserID,
    uc.fullname AS collectorName,
    uc.userID AS collectorUserID,
    v.model AS vehicleModel,
    v.plateNum,
    v.type AS vehicleType,
    COUNT(DISTINCT i.itemID) AS itemCount,
    COALESCE(SUM(i.weight), 0) AS totalWeight
FROM tbljob j
INNER JOIN tblcollection_request r ON r.requestID = j.requestID
INNER JOIN tblusers up ON up.userID = r.providerID
INNER JOIN tblusers uc ON uc.userID = j.collectorID
INNER JOIN tblvehicle v ON v.vehicleID = j.vehicleID
LEFT JOIN tblitem i ON i.requestID = j.requestID
GROUP BY j.jobID, j.requestID, j.collectorID, j.vehicleID, j.scheduledDate, 
         j.scheduledTime, j.estimatedEndTime, j.status, j.rejectionReason,
         j.startedAt, j.completedAt, r.status, r.rejectionReason, r.pickupAddress,
         r.pickupState, r.pickupPostcode, r.preferredDateTime, r.createdAt,
         up.fullname, up.userID, uc.fullname, uc.userID, v.model, v.plateNum, v.type
ORDER BY j.jobID DESC
";

error_log("Executing SQL: " . $sql);

$result = $conn->query($sql);
if ($result) {
    error_log("Query executed successfully. Number of rows: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $displayStatus = mapJobStatus($row['jobStatus']);
            $reasonText = $row['jobRejectionReason'] ?: $row['requestRejectionReason'] ?: '';
            $requestID = (int)$row['requestID'];
            $jobID = (int)$row['jobID'];

            // Fetch items for this request
            $items = [];
            $itemStmt = $conn->prepare("
                SELECT 
                    i.itemID, 
                    i.description, 
                    i.model, 
                    i.brand, 
                    i.weight, 
                    i.status AS itemStatus,
                    i.image,
                    it.name AS itemTypeName,
                    c.name AS centreName, 
                    c.address AS centreAddress,
                    c.centreID
                FROM tblitem i 
                INNER JOIN tblitem_type it ON it.itemTypeID = i.itemTypeID 
                LEFT JOIN tblcentre c ON c.centreID = i.centreID 
                WHERE i.requestID = ? 
                ORDER BY i.itemID ASC
            ");
            $itemStmt->bind_param('i', $requestID);
            $itemStmt->execute();
            $itemRes = $itemStmt->get_result();
            while ($item = $itemRes->fetch_assoc()) {
                $dropoff = 'Not assigned yet';
                if ($item['centreName']) {
                    $dropoff = $item['centreName'];
                    if (!empty($item['centreAddress'])) {
                        $dropoff .= ' - ' . $item['centreAddress'];
                    }
                }
                
                $items[] = [
                    'id' => 'ITEM' . str_pad((string)$item['itemID'], 3, '0', STR_PAD_LEFT),
                    'itemID' => $item['itemID'],
                    'name' => $item['itemTypeName'],
                    'brand' => trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? '')) ?: 'N/A',
                    'weight' => number_format((float)$item['weight'], 2),
                    'dropoff' => $dropoff,
                    'description' => $item['description'] ?: 'No description provided',
                    'status' => $item['itemStatus'],
                    'image' => $item['image'] ?: '',
                    'imagePath' => !empty($item['image']) ? '../../uploads/' . $item['image'] : ''
                ];
            }
            $itemStmt->close();

            $timeline = [];
            $timelineStmt = $conn->prepare("
                SELECT type, action, description, dateTime 
                FROM tblactivity_log 
                WHERE (jobID = ? OR (requestID = ? AND jobID IS NULL))
                ORDER BY dateTime ASC, logID ASC
            ");

            $timelineStmt->bind_param('ii', $jobID, $requestID);
            $timelineStmt->execute();
            $timelineRes = $timelineStmt->get_result();

            while ($log = $timelineRes->fetch_assoc()) {
                $text = !empty($log['description'])
                ? trim($log['description'])
                : ucwords(str_replace('_', ' ', (string)$log['action']));
                $text = preg_replace('/\s*\(ID:\s*\d+\)/i', '', $text);
                $text = preg_replace('/\bcentre\s+/i', '', $text);
                $text = preg_replace('/\bvehicle\b/i', 'Vehicle', $text);
                $timeline[] = [
                    'time' => $log['dateTime'],
                    'icon' => mapTimelineIcon($log['type'], $log['action'], $log['description']),
                    'text' => $text
                ];
            }

            $timelineStmt->close();

            $scheduledDateTime = $row['scheduledDate'] . 'T' . substr($row['scheduledTime'], 0, 5);
            
            $preferredDate = date('d/m/Y', strtotime($row['preferredDateTime']));
            
            $scheduledDisplay = date('d/m/Y h:i A', strtotime($row['scheduledDate'] . ' ' . $row['scheduledTime']));

            $jobs[] = [
                'id' => 'JOB' . str_pad((string)$jobID, 3, '0', STR_PAD_LEFT),
                'jobID' => $jobID,
                'requestID' => 'REQ' . str_pad((string)$requestID, 3, '0', STR_PAD_LEFT),
                'requestIDRaw' => $requestID,
                'status' => $displayStatus,
                'jobStatus' => $row['jobStatus'],
                'requestStatus' => $row['requestStatus'],
                'providerName' => $row['providerName'],
                'address' => $row['pickupAddress'] . ', ' . $row['pickupPostcode'] . ', ' . $row['pickupState'],
                'collector' => $row['collectorName'],
                'vehicle' => $row['vehicleModel'] . ' (' . $row['plateNum'] . ')',
                'datetime' => $scheduledDateTime,
                'itemCount' => (int)$row['itemCount'],
                'totalWeight' => number_format((float)$row['totalWeight'], 2),
                'stage' => mapStage($displayStatus),
                'reasonText' => $reasonText,
                'fullData' => [
                    'provider' => [
                        'name' => $row['providerName'],
                        'address' => $row['pickupAddress'] . ', ' . $row['pickupPostcode'] . ', ' . $row['pickupState'],
                        'date' => $preferredDate
                    ],
                    'assignment' => [
                        'collector' => $row['collectorName'],
                        'vehicle' => $row['vehicleModel'] . ' (' . $row['plateNum'] . ')',
                        'scheduled' => $scheduledDisplay
                    ],
                    'items' => $items,
                    'timeline' => $timeline
                ]
            ];
        }
    } else {
        error_log("No jobs found in database");
    }
} else {
    error_log("Query failed: " . $conn->error);
}

$jobs = empty($jobs) ? [] : $jobs;

$debug_info = [
    'total_jobs_in_db' => $jobCount ?? 0,
    'jobs_fetched' => count($jobs),
    'query_executed' => $sql,
    'query_error' => $conn->error ?? null
];

$jobsJson = json_encode($jobs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$debugJson = json_encode($debug_info);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/aJobs.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                        <button id="themeToggleMobile"><img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon"></button>
                        <a href="../../html/common/Setting.php"><img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM"></a>
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
            <button id="themeToggleDesktop"><img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon"></button>
            <a href="../../html/common/Setting.php"><img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg"></a>
        </section>
    </header>
    <hr>

    <main class="jobs-main">
        <div class="page-container">
            <div class="jobs-header">
                <h1 class="page-title" id="pageTitle">Jobs</h1>
                <button class="back-btn" id="backToListBtn" style="display: none;"><i class="fas fa-arrow-left"></i> Back to Jobs</button>
            </div>

            <div id="jobsListContainer" class="jobs-list-container">
                <div class="jobs-stats-grid" id="statsContainer"></div>
                <div class="filter-bar">
                    <div class="filter-group">
                        <div class="filter-dropdown">
                            <button class="filter-dropdown-btn" id="filterDropdownBtn">
                                <i class="fas fa-filter"></i> <span id="selectedFilter">All Jobs</span> <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-dropdown-content" id="filterDropdownContent">
                                <a href="#" data-filter="all" class="active-filter"><i class="fas fa-list"></i> All Jobs</a>
                                <a href="#" data-filter="Pending"><i class="far fa-clock"></i> Pending</a>
                                <a href="#" data-filter="Scheduled"><i class="fas fa-calendar-check"></i> Scheduled</a>
                                <a href="#" data-filter="Rejected"><i class="fas fa-times-circle"></i> Rejected</a>
                                <a href="#" data-filter="Ongoing"><i class="fas fa-sync-alt"></i> Ongoing</a>
                                <a href="#" data-filter="Completed"><i class="fas fa-check-double"></i> Completed</a>
                                <a href="#" data-filter="Cancelled"><i class="fas fa-ban"></i> Cancelled</a>
                            </div>
                        </div>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search jobs by ID, provider, collector..." id="searchInput">
                        </div>
                    </div>
                </div>
                <div class="actions-row">
                    <div class="sort-container">
                        <div class="sort-slider">
                            <button class="sort-slider-btn" id="sortDescBtn"><i class="fas fa-sort-amount-down"></i> Newest</button>
                            <button class="sort-slider-btn" id="sortAscBtn"><i class="fas fa-sort-amount-up"></i> Oldest</button>
                        </div>
                    </div>
                    <button class="collections-nav-btn" id="goToCollectionsBtn">
    <i class="fas fa-truck"></i> View Ongoing Jobs
</button>
                </div>
                <div class="jobs-timeline-container" id="timelineContainer"></div>
            </div>

            <div id="jobDetailContainer" class="job-detail-container" style="display: none;">
                <div class="job-detail-container-modern">
                    <div class="detail-header-modern">
                        <div class="detail-title-section-modern">
                            <h2 id="detailJobId"></h2>
                            <span class="detail-status-modern" id="detailJobStatus"></span>
                        </div>
                    </div>
                    <div class="info-grid-modern">
                        <div class="info-card-modern">
                            <h3><i class="fas fa-store"></i> Provider Information</h3>
                            <div class="info-row-modern"><span class="info-label-modern">Name</span><span class="info-value-modern" id="detailProviderName"></span></div>
                            <div class="info-row-modern"><span class="info-label-modern">Address</span><span class="info-value-modern" id="detailProviderAddress"></span></div>
                            <div class="info-row-modern"><span class="info-label-modern">Date</span><span class="info-value-modern" id="detailProviderDate"></span></div>
                        </div>
                        <div class="info-card-modern">
                            <h3><i class="fas fa-truck"></i> Assignment Details</h3>
                            <div class="info-row-modern"><span class="info-label-modern">Collector</span><span class="info-value-modern" id="detailCollector"></span></div>
                            <div class="info-row-modern"><span class="info-label-modern">Vehicle</span><span class="info-value-modern" id="detailVehicle"></span></div>
                            <div class="info-row-modern"><span class="info-label-modern">Scheduled</span><span class="info-value-modern" id="detailScheduled"></span></div>
                            <div class="info-row-modern"><span class="info-label-modern">Total Weight</span><span class="info-value-modern" id="detailTotalWeight"></span></div>
                        </div>
                    </div>
                    <div class="items-section-modern">
                        <div class="items-header-modern">
                            <h3><i class="fas fa-boxes"></i> Items</h3>
                            <span class="items-count-modern" id="detailItemsCount">0</span>
                        </div>
                        <div id="detailItemsList"></div>
                    </div>
                    <div class="timeline-container-modern">
                        <h3><i class="fas fa-history"></i> Job Timeline</h3>
                        <div class="timeline-modern" id="detailTimeline"></div>
                    </div>
                    <div class="action-buttons-modern" id="detailActionButtons"></div>
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

    <div class="toast" id="toast"></div>

    <script src="../../javascript/mainScript.js?v=<?php echo time(); ?>"></script>
    <script>
       window.jobsData = <?php echo $jobsJson ?: '[]'; ?>;
    </script>
    <script src="../../javascript/aJobs.js?v=<?php echo time(); ?>"></script>

</body>
</html>