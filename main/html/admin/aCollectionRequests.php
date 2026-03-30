<?php
session_start();
include("../../php/dbConn.php");

// // check if user is logged in
include("../../php/sessionCheck.php");

if (!isset($conn)) {
    die("Database connection not found.");
}

$collectionRequests = [];

$sql = "
    SELECT
        r.requestID,
        r.pickupAddress,
        r.pickupState,
        r.pickupPostcode,
        r.preferredDateTime,
        r.status AS requestStatus,
        r.createdAt,
        r.rejectionReason,

        u.fullname AS providerName,
        u.phone AS providerPhone,

        j.jobID,
        j.scheduledDate,
        j.scheduledTime,
        j.status AS jobStatus,
        j.completedAt,

        collectorUser.fullname AS collectorName,

        v.plateNum,
        v.type AS vehicleType,

        COUNT(i.itemID) AS itemCount,
        COALESCE(SUM(i.weight), 0) AS totalWeight,
        GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ', ') AS itemTypes,
        GROUP_CONCAT(DISTINCT i.description ORDER BY i.itemID SEPARATOR ', ') AS itemDescriptions,
        GROUP_CONCAT(DISTINCT i.brand ORDER BY i.brand SEPARATOR ', ') AS brands,
        GROUP_CONCAT(DISTINCT i.status ORDER BY i.status SEPARATOR ', ') AS itemStatuses

    FROM tblcollection_request r
    LEFT JOIN tblprovider p
        ON p.providerID = r.providerID
    LEFT JOIN tblusers u
        ON u.userID = p.providerID
    LEFT JOIN tbljob j
        ON j.requestID = r.requestID
    LEFT JOIN tblusers collectorUser
        ON collectorUser.userID = j.collectorID
    LEFT JOIN tblvehicle v
        ON v.vehicleID = j.vehicleID
    LEFT JOIN tblitem i
        ON i.requestID = r.requestID
    LEFT JOIN tblitem_type it
        ON it.itemTypeID = i.itemTypeID

    GROUP BY
        r.requestID,
        r.pickupAddress,
        r.pickupState,
        r.pickupPostcode,
        r.preferredDateTime,
        r.status,
        r.createdAt,
        r.rejectionReason,
        u.fullname,
        u.phone,
        j.jobID,
        j.scheduledDate,
        j.scheduledTime,
        j.status,
        j.completedAt,
        collectorUser.fullname,
        v.plateNum,
        v.type

    ORDER BY r.requestID DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($result)) {
    $status = strtolower($row['requestStatus']);

    if (!empty($row['jobStatus'])) {
        $jobStatus = strtolower($row['jobStatus']);

        if ($jobStatus === 'ongoing') {
            $status = 'ongoing';
        } elseif ($jobStatus === 'completed') {
            $status = 'completed';
        } elseif ($jobStatus === 'cancelled') {
            $status = 'cancelled';
        } elseif ($jobStatus === 'rejected') {
            $status = 'rejected';
        } elseif (in_array($jobStatus, ['pending', 'scheduled'])) {
            $status = 'scheduled';
        }
    } else {
        if ($status === 'pending') {
            $status = 'pending';
        } elseif ($status === 'approved') {
            $status = 'approved';
        }
    }

    $collectionRequests[] = [
        'id' => 'REQ' . str_pad($row['requestID'], 3, '0', STR_PAD_LEFT),
    'requestID' => (int)$row['requestID'],
    'jobID' => !empty($row['jobID']) ? (int)$row['jobID'] : null,
        'title' => !empty($row['itemTypes']) ? $row['itemTypes'] : 'Collection Request',
        'items' => !empty($row['itemTypes']) ? explode(', ', $row['itemTypes']) : [],
        'itemDescriptions' => !empty($row['itemDescriptions']) ? explode(', ', $row['itemDescriptions']) : [],
        'status' => $status,
        'provider' => $row['providerName'] ?: 'Unknown Provider',
        'providerContact' => $row['providerPhone'] ?: '-',
        'date' => !empty($row['createdAt']) ? date('d M Y', strtotime($row['createdAt'])) : '-',
        'scheduledDate' => !empty($row['scheduledDate']) ? date('d M Y', strtotime($row['scheduledDate'])) : null,
        'scheduledTime' => !empty($row['scheduledTime']) ? date('g:i A', strtotime($row['scheduledTime'])) : null,
        'completedDate' => !empty($row['completedAt']) ? date('d M Y', strtotime($row['completedAt'])) : null,
        'completionTime' => !empty($row['completedAt']) ? date('H:i', strtotime($row['completedAt'])) : null,
        'weight' => number_format((float)$row['totalWeight'], 1, '.', ''),
        'address' => $row['pickupAddress'] . ', ' . $row['pickupPostcode'] . ' ' . $row['pickupState'],
        'description' => $row['itemDescriptions'] ?: 'No description provided',
        'brand' => $row['brands'] ?: '-',
        'condition' => $row['itemStatuses'] ?: '-',
        'assignedCollector' => $row['collectorName'] ?: null,
        'assignedVehicle' => !empty($row['plateNum']) ? (($row['vehicleType'] ?: 'Vehicle') . ' - ' . $row['plateNum']) : null,
        'rejectionReason' => $row['rejectionReason'] ?: null,
        'cancellationReason' => null,
        'completionNotes' => null,
        'timelineLogs' => []
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Requests - AfterVolt</title>
    
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/aCollectionRequests.css?v=<?php echo time(); ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
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
                    <a href="../../html/admin/aCollectionRequests.php" class="active">Collection Requests</a>
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
        <div class="ops-page-header">
        <h1 class="ops-title">Collection Requests</h1>
    </div>

    <div class="back-btn-container">
    <a href="../../html/admin/aRequests.php" class="page-back-btn">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>    
    </a>
</div>
</div>

        <div class="dashboard-container">
            <!-- Left Sidebar -->
            <div class="dashboard-sidebar">
                <div class="sidebar-header"></div>

                <div class="status-panels">
                    <div class="status-panel scheduled" onclick="filterByStatus('scheduled')">
                        <div class="panel-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="panel-info">
                            <span class="panel-label">Scheduled</span>
                            <span class="panel-count" id="scheduledCount">0</span>
                        </div>
                        <div class="panel-trend">
                            <i class="fas fa-arrow-up"></i> +2
                        </div>
                    </div>

                    <div class="status-panel ongoing" onclick="filterByStatus('ongoing')">
                        <div class="panel-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <div class="panel-info">
                            <span class="panel-label">Ongoing</span>
                            <span class="panel-count" id="ongoingCount">0</span>
                        </div>
                        <div class="panel-trend">
                            <i class="fas fa-clock"></i> In progress
                        </div>
                    </div>

                    <div class="status-panel completed" onclick="filterByStatus('completed')">
                        <div class="panel-icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="panel-info">
                            <span class="panel-label">Completed</span>
                            <span class="panel-count" id="completedCount">0</span>
                        </div>
                        <div class="panel-trend">
                            <i class="fas fa-check"></i> 12 this month
                        </div>
                    </div>

                    <div class="status-panel cancelled" onclick="filterByStatus('cancelled')">
                        <div class="panel-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="panel-info">
                            <span class="panel-label">Cancelled</span>
                            <span class="panel-count" id="cancelledCount">0</span>
                        </div>
                        <div class="panel-trend">
                            <i class="fas fa-exclamation"></i> 3 this week
                        </div>
                    </div>

                    <div class="status-panel rejected" onclick="filterByStatus('rejected')">
                        <div class="panel-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="panel-info">
                            <span class="panel-label">Rejected</span>
                            <span class="panel-count" id="rejectedCount">0</span>
                        </div>
                        <div class="panel-trend">
                            <i class="fas fa-info"></i> 1 pending review
                        </div>
                    </div>
                </div>

                <div class="quick-filters">
                    <h4>Quick Filters</h4>
                    <div class="filter-chips">
                        <span class="filter-chip" data-filter="this-week">This Week</span>
                        <span class="filter-chip" data-filter="this-month">This Month</span>
                        <span class="filter-chip" data-filter="high-weight">High Weight (&gt;20kg)</span>
                        <span class="filter-chip" data-filter="needs-review">Needs Review</span>
                    </div>
                    <button type="button" class="reset-filters-btn" id="resetFiltersBtn">Reset</button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="dashboard-main">
                <div class="main-header">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search by ID, provider, items..." id="searchInput">
                    </div>
                    <div class="header-actions">
                        <button class="action-btn" id="exportBtn">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <!-- List View -->
                <div id="collectionListView" class="view-section">
                    <div class="results-bar">
                        <div class="results-info">
                            <span id="resultCount">Showing 0 requests</span>
                        </div>

                        <div class="results-actions">
                            <button class="all-requests-btn" id="allRequestsBtn">
                                <i class="fas fa-list"></i> All Requests
                            </button>

                            <div class="results-sort">
                                <div class="sort-dropdown" id="sortDropdown">
                                    <button class="sort-dropdown-btn" id="sortDropdownBtn">
                                        <i class="fas fa-sort-amount-down"></i>
                                        <span id="selectedSort">Newest First</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>

                                    <div class="sort-dropdown-content" id="sortDropdownContent">
                                        <a href="#" data-sort="date-desc" class="active-sort">
                                            <i class="fas fa-calendar-alt"></i> Newest First
                                        </a>
                                        <a href="#" data-sort="date-asc">
                                            <i class="fas fa-calendar-alt"></i> Oldest First
                                        </a>
                                        <a href="#" data-sort="weight-desc">
                                            <i class="fas fa-weight-hanging"></i> Weight (High to Low)
                                        </a>
                                        <a href="#" data-sort="weight-asc">
                                            <i class="fas fa-weight-hanging"></i> Weight (Low to High)
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="timelineContainer" class="timeline-view"></div>

                    <div id="kanbanContainer" class="kanban-view hidden">
                        <div class="kanban-column" data-status="scheduled">
                            <div class="kanban-header scheduled">
                                <span>Scheduled</span>
                                <span class="column-count" id="kanbanScheduledCount">0</span>
                            </div>
                            <div class="kanban-cards" id="kanbanScheduled"></div>
                        </div>

                        <div class="kanban-column" data-status="ongoing">
                            <div class="kanban-header ongoing">
                                <span>Ongoing</span>
                                <span class="column-count" id="kanbanOngoingCount">0</span>
                            </div>
                            <div class="kanban-cards" id="kanbanOngoing"></div>
                        </div>

                        <div class="kanban-column" data-status="completed">
                            <div class="kanban-header completed">
                                <span>Completed</span>
                                <span class="column-count" id="kanbanCompletedCount">0</span>
                            </div>
                            <div class="kanban-cards" id="kanbanCompleted"></div>
                        </div>

                        <div class="kanban-column" data-status="cancelled">
                            <div class="kanban-header cancelled">
                                <span>Cancelled</span>
                                <span class="column-count" id="kanbanCancelledCount">0</span>
                            </div>
                            <div class="kanban-cards" id="kanbanCancelled"></div>
                        </div>

                        <div class="kanban-column" data-status="rejected">
                            <div class="kanban-header rejected">
                                <span>Rejected</span>
                                <span class="column-count" id="kanbanRejectedCount">0</span>
                            </div>
                            <div class="kanban-cards" id="kanbanRejected"></div>
                        </div>
                    </div>

                    <div id="emptyState" class="empty-state hidden">
                        <i class="fas fa-box-open"></i>
                        <h3>No requests found</h3>
                    </div>

                    <div class="view-toggle">
                        <button class="toggle-btn active" data-view="timeline">
                            <i class="fas fa-stream"></i> List
                        </button>
                        <button class="toggle-btn" data-view="kanban">
                            <i class="fas fa-columns"></i> Kanban
                        </button>
                    </div>
                </div>

                <!-- Detail View -->
                <div id="collectionDetailView" class="view-section hidden">
                    <div class="detail-container">
                        <div class="detail-nav">
                            <button class="back-btn" id="backToListBtn">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            </div>
                        </div>

                        <div class="detail-hero">
                            <div class="hero-badge" id="detailStatus">Scheduled</div>
                            <div class="hero-title">
                                <h1 id="detailRequestId">#REQ002</h1>
                                <p id="detailTitle">Desktop PC, Printer Setup</p>
                            </div>
                            <div class="hero-provider" id="detailProvider">
                                <i class="fas fa-user-circle"></i>
                                <div>
                                    <strong>Sarah Tan</strong>
                                    <span>+60 12-345 6789</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-stats">
                            <div class="stat-item">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <span class="stat-label">Request Date</span>
                                    <span class="stat-value" id="detailRequestDate">0</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <i class="fas fa-weight-hanging"></i>
                                <div>
                                    <span class="stat-label">Total Weight</span>
                                    <span class="stat-value" id="detailWeight">0</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <i class="fas fa-boxes"></i>
                                <div>
                                    <span class="stat-label">Items</span>
                                    <span class="stat-value" id="detailItemCount">0</span>
                                </div>
                            </div>

                            <div class="stat-item" id="statusSpecificStat"></div>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-left">
                                <div class="detail-card">
                                    <h3><i class="fas fa-box"></i> Items</h3>
                                    <div class="items-list" id="detailItemsList"></div>
                                </div>

                                <div class="detail-card">
                                    <h3><i class="fas fa-align-left"></i> Description</h3>
                                    <p id="detailDescription">
                                        Old desktop computer setup, printer needs repair. All items are in working condition but outdated.
                                    </p>
                                </div>

                                <div class="detail-card">
                                    <h3><i class="fas fa-tag"></i> Details</h3>
                                    <div class="details-grid">
                                        <div>
                                            <span class="details-label">Brand/Model</span>
                                            <span class="details-value" id="detailBrand">HP Pavilion, Canon Pixma</span>
                                        </div>
                                        <div>
                                            <span class="details-label">Condition</span>
                                            <span class="details-value" id="detailCondition">Working but outdated</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-right">
                                <div class="detail-card">
                                    <h3><i class="fas fa-map-marker-alt"></i> Pickup Address</h3>
                                    <p id="detailAddress">No 45, Jalan University, Petaling Jaya, 47300</p>
                                    <a href="#" class="map-link" id="mapLink">
                                        <i class="fas fa-external-link-alt"></i> View on map
                                    </a>
                                </div>

                                <div class="detail-card">
                                    <h3><i class="fas fa-history"></i> Timeline</h3>
                                    <div class="timeline-steps" id="timelineSteps"></div>
                                </div>

                                <div class="detail-card" id="assignmentCard">
                                    <h3><i class="fas fa-truck"></i> Assignment</h3>
                                    <div class="assignment-info" id="assignmentInfo"></div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-footer" id="detailFooter"></div>
                        <div class="detail-notes" id="detailNotes"></div>
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

    <script>
    window.collectionRequestsData = <?php echo json_encode($collectionRequests, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="../../javascript/mainScript.js"></script>
    <script src="../../javascript/aCollectionRequests.js?v=<?php echo time(); ?>"></script>
</body>
</html>