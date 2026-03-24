<?php
session_start();
include("../../php/dbConn.php");

// ── TEMP: hardcoded session for testing (remove once login is done) ──
$_SESSION['userID']   = 9;
$_SESSION['userType'] = 'collector';

// ─── AJAX / ACTION HANDLER ───────────────────────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $userID = (int)$_SESSION['userID'];
    $action = $_GET['action'];

    /* helper: insert activity log */
    function logActivity($conn, $requestID, $jobID, $userID, $type, $action, $description) {
        $stmt = $conn->prepare(
            "INSERT INTO tblactivity_log (requestID, jobID, userID, type, action, description)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iiisss', $requestID, $jobID, $userID, $type, $action, $description);
        $stmt->execute();
        $stmt->close();
    }

    // ── action: start_journey ────────────────────────────────────────────────
    if ($action === 'start_journey') {
        $jobID = (int)$_POST['jobID'];

        // Verify this job belongs to this collector and is Pending/Scheduled
        $chk = $conn->prepare(
            "SELECT j.jobID, j.requestID, j.status FROM tbljob j
             WHERE j.jobID = ? AND j.collectorID = ?
             AND j.status IN ('Pending','Scheduled')"
        );
        $chk->bind_param('ii', $jobID, $userID);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row) { echo json_encode(['success'=>false,'message'=>'Job not found or not accepted']); exit; }

        $requestID = $row['requestID'];
        $now = date('Y-m-d H:i:s');

        // Update job status → Ongoing, set startedAt
        $upd = $conn->prepare("UPDATE tbljob SET status='Ongoing', startedAt=? WHERE jobID=?");
        $upd->bind_param('si', $now, $jobID);
        $upd->execute();
        $upd->close();

        // Update request status → Ongoing
        $upd2 = $conn->prepare("UPDATE tblcollection_request SET status='Ongoing' WHERE requestID=?");
        $upd2->bind_param('i', $requestID);
        $upd2->execute();
        $upd2->close();

        // Update collector status → on duty
        $upd3 = $conn->prepare("UPDATE tblcollector SET status='on duty' WHERE collectorID=?");
        $upd3->bind_param('i', $userID);
        $upd3->execute();
        $upd3->close();

        logActivity($conn, $requestID, $jobID, $userID, 'Job', 'Departed', 'Collector started journey from base');
        logActivity($conn, $requestID, $jobID, $userID, 'Job', 'Status Change', 'Changed from Scheduled to Ongoing');
        logActivity($conn, $requestID, $jobID, $userID, 'Request', 'Status Change', 'Changed from Approved to Ongoing');

        echo json_encode(['success'=>true]);
        exit;
    }

    // ── action: complete_pickup ──────────────────────────────────────────────
    if ($action === 'complete_pickup') {
        $jobID     = (int)$_POST['jobID'];
        $requestID = (int)$_POST['requestID'];

        // Mark all Pending items on this request → Collected
        $upd = $conn->prepare("UPDATE tblitem SET status='Collected' WHERE requestID=? AND status='Pending'");
        $upd->bind_param('i', $requestID);
        $upd->execute();
        $upd->close();

        $cnt = $conn->prepare("SELECT COUNT(*) as c FROM tblitem WHERE requestID=?");
        $cnt->bind_param('i', $requestID);
        $cnt->execute();
        $count = $cnt->get_result()->fetch_assoc()['c'];
        $cnt->close();

        logActivity($conn, $requestID, $jobID, $userID, 'Job', 'Items Collected',
            "All $count item(s) collected from provider location");

        echo json_encode(['success'=>true]);
        exit;
    }

    // ── action: complete_dropoff ─────────────────────────────────────────────
    if ($action === 'complete_dropoff') {
        $jobID     = (int)$_POST['jobID'];
        $requestID = (int)$_POST['requestID'];
        $centreID  = (int)$_POST['centreID'];

        // Mark items for this centre → Received
        $upd = $conn->prepare(
            "UPDATE tblitem SET status='Received'
             WHERE requestID=? AND centreID=? AND status='Collected'"
        );
        $upd->bind_param('ii', $requestID, $centreID);
        $upd->execute();
        $upd->close();

        // Check if ALL items are now Received+
        $chk = $conn->prepare(
            "SELECT COUNT(*) as c FROM tblitem
             WHERE requestID=? AND status NOT IN ('Received','Processed','Recycled','Cancelled')"
        );
        $chk->bind_param('i', $requestID);
        $chk->execute();
        $remaining = $chk->get_result()->fetch_assoc()['c'];
        $chk->close();

        // Get centre name for log
        $cname = $conn->prepare("SELECT name FROM tblcentre WHERE centreID=?");
        $cname->bind_param('i', $centreID);
        $cname->execute();
        $centreName = $cname->get_result()->fetch_assoc()['name'] ?? '';
        $cname->close();

        logActivity($conn, $requestID, $jobID, $userID, 'Job', 'All Items Dropped',
            "Items delivered to $centreName (ID: $centreID)");

        if ($remaining == 0) {
            $upd2 = $conn->prepare("UPDATE tblcollection_request SET status='Collected' WHERE requestID=?");
            $upd2->bind_param('i', $requestID);
            $upd2->execute();
            $upd2->close();

            $upd3 = $conn->prepare("UPDATE tbljob SET status='Picked Up' WHERE jobID=?");
            $upd3->bind_param('i', $jobID);
            $upd3->execute();
            $upd3->close();

            logActivity($conn, $requestID, $jobID, $userID, 'Request', 'Status Change', 'Changed from Ongoing to Collected');
            logActivity($conn, $requestID, $jobID, $userID, 'Job', 'Status Change', 'Changed from Ongoing to Picked Up');
        }

        echo json_encode(['success'=>true, 'allDelivered'=>($remaining==0)]);
        exit;
    }

    // ── action: complete_return ──────────────────────────────────────────────
    if ($action === 'complete_return') {
        $jobID     = (int)$_POST['jobID'];
        $requestID = (int)$_POST['requestID'];
        $now       = date('Y-m-d H:i:s');

        $upd = $conn->prepare("UPDATE tbljob SET status='Completed', completedAt=? WHERE jobID=?");
        $upd->bind_param('si', $now, $jobID);
        $upd->execute();
        $upd->close();

        $upd2 = $conn->prepare("UPDATE tblcollector SET status='active' WHERE collectorID=?");
        $upd2->bind_param('i', $userID);
        $upd2->execute();
        $upd2->close();

        logActivity($conn, $requestID, $jobID, $userID, 'Job', 'Returned', 'Returned to base, journey completed');
        logActivity($conn, $requestID, $jobID, $userID, 'Job', 'Status Change', 'Changed from Picked Up to Completed');
        logActivity($conn, $requestID, $jobID, $userID, 'Job', 'Completed', null);

        echo json_encode(['success'=>true]);
        exit;
    }

    // ── action: report_issue ─────────────────────────────────────────────────
    if ($action === 'report_issue') {
        $jobID       = (int)$_POST['jobID'];
        $requestID   = (int)$_POST['requestID'];
        $issueType   = $_POST['issueType']   ?? 'other';
        $severity    = $_POST['severity']    ?? 'Medium';
        $subject     = $_POST['subject']     ?? 'Issue reported';
        $description = $_POST['description'] ?? '';

        $typeMap = ['vehicle_breakdown'=>'Vehicle','accident'=>'Safety','other'=>'Other'];
        $issueTypeMapped = $typeMap[$issueType] ?? 'Other';

        $ins = $conn->prepare(
            "INSERT INTO tblissue
             (requestID, jobID, reportedBy, issueType, severity, subject, description, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Open')"
        );
        $ins->bind_param('iiissss', $requestID, $jobID, $userID, $issueTypeMapped, $severity, $subject, $description);
        $ins->execute();
        $issueID = $conn->insert_id;
        $ins->close();

        logActivity($conn, $requestID, $jobID, $userID, 'Issue', 'Create',
            "Issue (ID: $issueID) - $issueTypeMapped - $subject");

        echo json_encode(['success'=>true, 'issueID'=>$issueID]);
        exit;
    }

    echo json_encode(['success'=>false, 'message'=>'Unknown action']);
    exit;
}

// ─── FETCH JOB DATA FOR PAGE RENDER ─────────────────────────────────────────
$collectorID = (int)$_SESSION['userID'];

/*
  Fetch the latest job for this collector that is accepted (Pending/Scheduled)
  OR currently active (Ongoing/Picked Up).
  "Latest" = highest scheduledDate.
  Job must NOT be Completed/Rejected/Cancelled.
*/
$jobQuery = $conn->prepare(
    "SELECT j.jobID, j.requestID, j.collectorID, j.vehicleID,
            j.scheduledDate, j.scheduledTime, j.estimatedEndTime,
            j.status AS jobStatus, j.startedAt, j.completedAt,
            cr.pickupAddress, cr.pickupState, cr.pickupPostcode,
            cr.preferredDateTime, cr.status AS requestStatus,
            u.fullname AS providerName,
            p.address AS providerAddress, p.state AS providerState, p.postcode AS providerPostcode,
            v.plateNum, v.model AS vehicleModel, v.type AS vehicleType, v.capacityWeight,
            v.status AS vehicleStatus
     FROM tbljob j
     INNER JOIN tblcollection_request cr ON cr.requestID = j.requestID
     INNER JOIN tblprovider p            ON p.providerID  = cr.providerID
     INNER JOIN tblusers u               ON u.userID       = p.providerID
     INNER JOIN tblvehicle v             ON v.vehicleID    = j.vehicleID
     WHERE j.collectorID = ?
       AND j.status IN ('Pending','Scheduled','Ongoing','Picked Up')
     ORDER BY j.scheduledDate DESC
     LIMIT 1"
);
$jobQuery->bind_param('i', $collectorID);
$jobQuery->execute();
$job = $jobQuery->get_result()->fetch_assoc();
$jobQuery->close();

$hasJob = !empty($job);

// ── Fetch items grouped by centreID ─────────────────────────────────────────
$itemsByCentre = [];
$itemsNoCentre = [];

if ($hasJob) {
    $itemQuery = $conn->prepare(
        "SELECT i.itemID, i.centreID, i.itemTypeID, i.description,
                i.model, i.brand, i.weight, i.status,
                it.name AS typeName,
                c.name AS centreName, c.address AS centreAddress,
                c.state AS centreState, c.postcode AS centrePostcode
         FROM tblitem i
         INNER JOIN tblitem_type it ON it.itemTypeID = i.itemTypeID
         LEFT  JOIN tblcentre c    ON c.centreID = i.centreID
         WHERE i.requestID = ?
           AND i.status != 'Cancelled'
         ORDER BY i.centreID ASC, i.itemID ASC"
    );
    $itemQuery->bind_param('i', $job['requestID']);
    $itemQuery->execute();
    $itemResult = $itemQuery->get_result();
    $itemQuery->close();

    while ($item = $itemResult->fetch_assoc()) {
        if ($item['centreID']) {
            $cid = $item['centreID'];
            if (!isset($itemsByCentre[$cid])) {
                $itemsByCentre[$cid] = [
                    'centreID'       => $cid,
                    'centreName'     => $item['centreName'],
                    'centreAddress'  => $item['centreAddress'],
                    'centreState'    => $item['centreState'],
                    'centrePostcode' => $item['centrePostcode'],
                    'items'          => [],
                ];
            }
            $itemsByCentre[$cid]['items'][] = $item;
        } else {
            $itemsNoCentre[] = $item;
        }
    }
    $itemsByCentre = array_values($itemsByCentre);
}

// ── Build route steps ────────────────────────────────────────────────────────
$routeSteps = [];
if ($hasJob) {
    $routeSteps[] = ['label'=>'Start Journey', 'sublabel'=>'Collector Base'];
    $routeSteps[] = [
        'label'    => 'Items Collected at Provider Location',
        'sublabel' => $job['providerName'] . ' – ' . $job['pickupAddress'],
    ];
    foreach ($itemsByCentre as $group) {
        $names = implode(', ', array_map(fn($i) => $i['typeName'], $group['items']));
        $routeSteps[] = ['label'=>'Delivered to ' . $group['centreName'], 'sublabel'=>$names];
    }
    $routeSteps[] = ['label'=>'Returned to Base', 'sublabel'=>'Journey complete'];
}

// ── Determine initial UI state ───────────────────────────────────────────────
$journeyStarted = $hasJob && in_array($job['jobStatus'], ['Ongoing','Picked Up','Completed']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ongoing Job</title>
    <link rel="icon" type="image/png" href="../../assets//images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/clnProgress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
<body>

<!-- ─── POPUP: Start Journey ─── -->
<div class="popup-overlay" id="startPopup">
    <div class="popup">
        <div class="popup-icon">🚗</div>
        <h3>Start Journey</h3>
        <p>Drive safe! Your journey has begun. Follow the route and complete each session in order.</p>
        <div class="popup-actions">
            <button class="btn btn-primary" onclick="confirmStartJourney()">Let's Go!</button>
        </div>
    </div>
</div>

<!-- ─── POPUP: Complete Session ─── -->
<div class="popup-overlay" id="completePopup">
    <div class="popup">
        <div class="popup-icon">✅</div>
        <h3>Confirm Completion</h3>
        <p id="completePopupMsg">Are you sure you want to mark this session as completed?</p>
        <div class="popup-actions">
            <button class="btn btn-ghost" onclick="closePopup('completePopup')">Cancel</button>
            <button class="btn btn-complete" onclick="confirmComplete()">Yes, Complete</button>
        </div>
    </div>
</div>

<!-- ─── POPUP: Report Issue ─── -->
<div class="popup-overlay" id="reportPopup">
    <div class="popup" style="text-align:left; max-width:480px;">
        <h3 style="text-align:center; margin-bottom:6px;">Report an Issue</h3>
        <p style="text-align:center; margin-bottom:18px;">Describe the issue so an admin can assist you promptly.</p>
        <div class="form-group">
            <label>Reason</label>
            <select id="issueReason" onchange="toggleBreakdownAddress()">
                <option value="">Select a reason…</option>
                <option value="vehicle_breakdown">Vehicle Breakdown</option>
                <option value="accident">Accident</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="form-group" id="breakdownAddressGroup" style="display:none;">
            <label>Breakdown Address</label>
            <input type="text" id="breakdownAddress" placeholder="e.g. Jalan Ampang, KL">
        </div>
        <div class="form-group">
            <label>Note (optional)</label>
            <textarea id="issueNote" placeholder="Add any extra details…"></textarea>
        </div>
        <div class="popup-actions">
            <button class="btn btn-ghost" onclick="closePopup('reportPopup')">Cancel</button>
            <button class="btn btn-danger" onclick="submitReport()">Submit Report</button>
        </div>
    </div>
</div>

<!-- ─── TOAST ─── -->
<div class="toast" id="toast"></div>

<div id="cover" class="" onclick="hideMenu()"></div>

<header>
    <section class="c-logo-section">
        <a href="../../html/collector/cHome.html" class="c-logo-link">
            <img src="../../assets//images/logo.png" alt="Logo" class="c-logo">
            <div class="c-text">AfterVolt</div>
        </a>
    </section>
    <nav class="c-navbar-side">
        <img src="../../assets//images/icon-menu.svg" alt="icon-menu" onclick="showMenu()" class="c-icon-btn" id="menuBtn">
        <div id="sidebarNav" class="c-navbar-side-menu">
            <img src="../../assets//images/icon-menu-close.svg" alt="icon-menu-close" onclick="hideMenu()" class="close-btn" id="closeBtn">
            <div class="c-navbar-side-items">
                <section class="c-navbar-side-more">
                    <button id="themeToggleMobile"><img src="../../assets//images/light-mode-icon.svg" alt="Light Mode Icon"></button>
                    <a href="../../html/common/Setting.html"><img src="../../assets//images/setting-light.svg" alt="Settings" id="settingImgM"></a>
                </section>
                <a href="../../html/collector/cHome.html">Home</a>
                <a href="../../html/collector/cMyJobs.html">My Jobs</a><br>
                <a href="../../html/collector/cInProgress.html">Ongoing Jobs</a><br>
                <a href="../../html/collector/cCompletedJobs.html">History</a>
                <a href="../../html/common/About.html">About</a><br>
            </div>
        </div>
    </nav>
    <nav class="c-navbar-desktop">
        <a href="../../html/collector/cHome.html">Home</a>
        <a href="../../html/collector/cMyJobs.html">My Jobs</a><br>
        <a href="../../html/collector/cInProgress.html">Ongoing Jobs</a><br>
        <a href="../../html/collector/cCompletedJobs.html">History</a>
        <a href="../../html/common/About.html">About</a><br>
    </nav>
    <section class="c-navbar-more">
        <button id="themeToggleDesktop"><img src="../../assets//images/light-mode-icon.svg" alt="Light Mode Icon"></button>
        <a href="../../html/common/Setting.html"><img src="../../assets//images/setting-light.svg" alt="Settings" id="settingImg"></a>
    </section>
</header>
<hr>

<main>
<?php if (!$hasJob): ?>
    <div class="page-header">
        <button class="back-btn" onclick="history.back()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <h1 class="page-title">Ongoing Job</h1>
    </div>
    <div style="text-align:center; padding:60px 20px; color:var(--text-muted);">
        <div style="font-size:48px; margin-bottom:16px;">📭</div>
        <h3 style="font-size:18px; font-weight:700; color:var(--text-color); margin-bottom:8px;">No Active Job</h3>
        <p>You don't have any accepted job to start. Check <a href="../../html/collector/cMyJobs.html" style="color:var(--MainBlue);">My Jobs</a> for pending assignments.</p>
    </div>

<?php else:
    $jobID      = (int)$job['jobID'];
    $requestID  = (int)$job['requestID'];
    $jobLabel   = 'JOB' . str_pad($jobID, 3, '0', STR_PAD_LEFT);
    $jobStatus  = $job['jobStatus'];
    $centreCount = count($itemsByCentre);

    $globalBadgeClass = 'badge-accepted';
    $globalBadgeText  = 'Accepted';
    if (in_array($jobStatus, ['Ongoing','Picked Up'])) { $globalBadgeClass = 'badge-ongoing';   $globalBadgeText = 'Ongoing'; }
    if ($jobStatus === 'Completed')                    { $globalBadgeClass = 'badge-completed'; $globalBadgeText = 'Completed'; }

    // All items flat
    $allItems = [];
    $totalWeight = 0;
    foreach ($itemsByCentre as $group) {
        foreach ($group['items'] as $it) { $allItems[] = $it; $totalWeight += (float)$it['weight']; }
    }
    foreach ($itemsNoCentre as $it) { $allItems[] = $it; $totalWeight += (float)$it['weight']; }

    // Pickup done = no item is still Pending
    $pickupDone = $journeyStarted;
    foreach ($allItems as $it) {
        if ($it['status'] === 'Pending') { $pickupDone = false; break; }
    }
    // All centres done
    $allCentresDone = true;
    foreach ($itemsByCentre as $group) {
        foreach ($group['items'] as $git) {
            if (!in_array($git['status'], ['Received','Processed','Recycled'])) {
                $allCentresDone = false; break 2;
            }
        }
    }
    $returnCompleted = ($jobStatus === 'Completed');
?>
    <div class="page-header">
        <button class="back-btn" onclick="history.back()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <h1 class="page-title">Ongoing Job</h1>
        <span class="badge <?= $globalBadgeClass ?>" id="globalStatus"><?= htmlspecialchars($globalBadgeText) ?></span>
    </div>

    <div class="journey-layout">

        <div class="cards-column" id="cardsColumn">

            <!-- CARD 1: Overview -->
            <div class="job-card" id="card-overview">
                <div class="card-header">
                    <div class="card-header-left">
                        <span class="job-id-label"><?= htmlspecialchars($jobLabel) ?></span>
                        <span class="badge <?= $globalBadgeClass ?>" id="badge-overview"><?= htmlspecialchars($globalBadgeText) ?></span>
                    </div>
                    <?php if (!$journeyStarted): ?>
                    <button class="btn btn-primary" id="startJourneyBtn" onclick="openStartPopup()">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Start Journey
                    </button>
                    <?php endif; ?>
                </div>
                <p style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;">Details Overview</p>
                <div class="overview-grid">
                    <div class="overview-cell">
                        <div class="overview-cell-title">Provider</div>
                        <p><span>Name :</span> <?= htmlspecialchars($job['providerName']) ?></p>
                        <p><span>Address :</span> <?= htmlspecialchars($job['pickupAddress'] . ', ' . $job['pickupPostcode'] . ', ' . $job['pickupState']) ?></p>
                        <p><span>Date :</span> <?= date('d/m/Y', strtotime($job['scheduledDate'])) ?></p>
                    </div>
                    <div class="overview-cell">
                        <div class="overview-cell-title">Item</div>
                        <ul class="item-list">
                            <?php foreach ($allItems as $idx => $it): ?>
                            <li><?= ($idx+1) . '. ' . htmlspecialchars($it['typeName']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <span class="weight-tag">Total weight: <?= number_format($totalWeight, 1) ?> kg</span>
                    </div>
                    <div class="overview-cell">
                        <div class="overview-cell-title">Brand &amp; Model</div>
                        <ul class="item-list">
                            <?php foreach ($allItems as $idx => $it): ?>
                            <li><?= ($idx+1) . '. ' . htmlspecialchars(($it['brand'] ?? '-') . ', ' . ($it['model'] ?? '-')) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Pickup session -->
            <?php
            $s1Locked    = !$journeyStarted;
            $s1CardClass = $s1Locked ? 'locked' : ($pickupDone ? 'completed-card' : 'active-card');
            ?>
            <div class="job-card <?= $s1CardClass ?>" id="card-session1">
                <div class="card-header">
                    <div class="card-header-left">
                        <span class="job-id-label"><?= htmlspecialchars($jobLabel) ?></span>
                        <span class="badge <?= $pickupDone ? 'badge-completed' : 'badge-pickup' ?>" id="badge-session1">
                            <?= $pickupDone ? 'Completed' : 'Pick Up' ?>
                        </span>
                    </div>
                </div>
                <hr class="card-divider">
                <div class="session-route">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    Base → <?= htmlspecialchars($job['pickupAddress'] . ', ' . $job['pickupPostcode'] . ', ' . $job['pickupState']) ?>
                </div>
                <div class="session-footer">
                    <span></span>
                    <button class="btn btn-complete" id="completeBtn-session1"
                            onclick="openCompletePopup('session1', null)"
                            <?= ($s1Locked || $pickupDone) ? 'disabled' : '' ?>>
                        <?= $pickupDone ? '✓ Done' : 'Complete' ?>
                    </button>
                </div>
            </div>

            <!-- CARDS: One per dropoff centre -->
            <?php foreach ($itemsByCentre as $ci => $group):
                $sessionKey = 'session' . ($ci + 2);

                // Previous step done?
                if ($ci === 0) {
                    $prevDone = $pickupDone;
                } else {
                    $prevGroup = $itemsByCentre[$ci - 1];
                    $prevDone  = true;
                    foreach ($prevGroup['items'] as $pit) {
                        if (!in_array($pit['status'], ['Received','Processed','Recycled'])) { $prevDone = false; break; }
                    }
                }

                $thisDone = true;
                foreach ($group['items'] as $git) {
                    if (!in_array($git['status'], ['Received','Processed','Recycled'])) { $thisDone = false; break; }
                }

                $dLocked    = !$prevDone;
                $dCardClass = $dLocked ? 'locked' : ($thisDone ? 'completed-card' : 'active-card');
            ?>
            <div class="job-card <?= $dCardClass ?>" id="card-<?= $sessionKey ?>">
                <div class="card-header">
                    <div class="card-header-left">
                        <span class="job-id-label"><?= htmlspecialchars($jobLabel) ?></span>
                        <span class="badge <?= $thisDone ? 'badge-completed' : 'badge-pickup' ?>" id="badge-<?= $sessionKey ?>">
                            <?= $thisDone ? 'Completed' : 'Drop Off' ?>
                        </span>
                    </div>
                    <div class="card-header-actions">
                        <button class="btn btn-danger" id="reportBtn-<?= $sessionKey ?>"
                                onclick="openReport('<?= $sessionKey ?>', <?= $group['centreID'] ?>)"
                                <?= $dLocked ? 'disabled' : '' ?>>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            Report Issue
                        </button>
                    </div>
                </div>
                <div class="session-items-grid">
                    <div class="session-items-cell">
                        <div class="cell-title">Item Dropoff</div>
                        <ul>
                            <?php foreach ($group['items'] as $ii => $git): ?>
                            <li><?= ($ii+1) . '. ' . htmlspecialchars($git['typeName']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="session-items-cell">
                        <div class="cell-title">Brand &amp; Model</div>
                        <ul>
                            <?php foreach ($group['items'] as $ii => $git): ?>
                            <li><?= ($ii+1) . '. ' . htmlspecialchars(($git['brand'] ?? '-') . ', ' . ($git['model'] ?? '-')) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <hr class="card-divider">
                <div class="session-route">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    <?= htmlspecialchars($job['pickupAddress']) ?> → <?= htmlspecialchars($group['centreName'] . ', ' . $group['centreState']) ?>
                </div>
                <div class="session-footer">
                    <span></span>
                    <button class="btn btn-complete" id="completeBtn-<?= $sessionKey ?>"
                            onclick="openCompletePopup('<?= $sessionKey ?>', <?= $group['centreID'] ?>)"
                            <?= ($dLocked || $thisDone) ? 'disabled' : '' ?>>
                        <?= $thisDone ? '✓ Done' : 'Complete' ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- CARD: Return to Base -->
            <?php
            $returnSessionKey = 'session' . ($centreCount + 2);
            $rLocked    = !$allCentresDone;
            $rCardClass = $rLocked ? 'locked' : ($returnCompleted ? 'completed-card' : 'active-card');
            $lastCentreName = !empty($itemsByCentre) ? end($itemsByCentre)['centreName'] : $job['pickupAddress'];
            ?>
            <div class="job-card <?= $rCardClass ?>" id="card-<?= $returnSessionKey ?>">
                <div class="card-header">
                    <div class="card-header-left">
                        <span class="job-id-label"><?= htmlspecialchars($jobLabel) ?></span>
                        <span class="badge <?= $returnCompleted ? 'badge-completed' : 'badge-pickup' ?>" id="badge-<?= $returnSessionKey ?>">
                            <?= $returnCompleted ? 'Completed' : 'Return' ?>
                        </span>
                    </div>
                    <div class="card-header-actions">
                        <button class="btn btn-danger" id="reportBtn-<?= $returnSessionKey ?>"
                                onclick="openReport('<?= $returnSessionKey ?>', null)"
                                <?= $rLocked ? 'disabled' : '' ?>>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            Report Issue
                        </button>
                    </div>
                </div>
                <hr class="card-divider">
                <div class="session-route">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    <?= htmlspecialchars($lastCentreName) ?> → Collector Base
                </div>
                <div class="session-footer">
                    <span></span>
                    <button class="btn btn-complete" id="completeBtn-<?= $returnSessionKey ?>"
                            onclick="openCompletePopup('<?= $returnSessionKey ?>', null)"
                            <?= ($rLocked || $returnCompleted) ? 'disabled' : '' ?>>
                        <?= $returnCompleted ? '✓ Done' : 'Complete' ?>
                    </button>
                </div>
            </div>

        </div><!-- /cards-column -->

        <!-- ─── ROUTE SIDEBAR ─── -->
        <aside class="route-sidebar">
            <div class="route-title">Route</div>
            <div class="route-steps">
                <?php foreach ($routeSteps as $ri => $step):
                    $isLast = ($ri === count($routeSteps) - 1);

                    // Determine dot state
                    if ($ri === 0) {
                        $dotState = $journeyStarted ? 'done' : 'active';
                    } elseif ($ri === 1) {
                        $dotState = $pickupDone ? 'done' : ($journeyStarted ? 'active' : '');
                    } elseif ($ri <= $centreCount) {
                        $ci = $ri - 2;
                        $groupDone = true;
                        if (isset($itemsByCentre[$ci])) {
                            foreach ($itemsByCentre[$ci]['items'] as $git) {
                                if (!in_array($git['status'], ['Received','Processed','Recycled'])) { $groupDone = false; break; }
                            }
                        }
                        $prevGroupDone = $pickupDone;
                        if ($ci > 0 && isset($itemsByCentre[$ci-1])) {
                            $prevGroupDone = true;
                            foreach ($itemsByCentre[$ci-1]['items'] as $pit) {
                                if (!in_array($pit['status'], ['Received','Processed','Recycled'])) { $prevGroupDone = false; break; }
                            }
                        }
                        $dotState = $groupDone ? 'done' : ($prevGroupDone ? 'active' : '');
                    } else {
                        $dotState = $returnCompleted ? 'done' : ($allCentresDone ? 'active' : '');
                    }

                    $lineState = $dotState;
                ?>
                <div class="route-step">
                    <div class="step-indicator">
                        <div class="step-dot <?= $dotState ?>" id="dot-<?= $ri ?>"></div>
                        <?php if (!$isLast): ?>
                        <div class="step-line <?= $lineState ?>" id="line-<?= $ri ?>"></div>
                        <?php endif; ?>
                    </div>
                    <div class="step-content">
                        <div class="step-label <?= $dotState ?>" id="label-<?= $ri ?>"><?= htmlspecialchars($step['label']) ?></div>
                        <div class="step-sublabel"><?= htmlspecialchars($step['sublabel']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

    </div><!-- /journey-layout -->
<?php endif; ?>
</main>

<hr>
<footer>
    <section class="c-footer-info-section">
        <a href="../../html/collector/cHome.html">
            <img src="../../assets//images/logo.png" alt="Logo" class="c-logo">
        </a>
        <div class="c-text">AfterVolt</div>
        <div class="c-text c-text-center">Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.</div>
        <div class="c-text c-text-label">+60 12 345 6789</div>
        <div class="c-text">abc@gmail.com</div>
    </section>
    <section class="c-footer-links-section">
        <div>
            <b>My Jobs</b><br>
            <a href="../../html/collector/cMyJobs.html">My Jobs</a><br>
            <a href="../../html/collector/cInProgress.html">In Progress</a><br>
            <a href="../../html/collector/cCompletedJobs.html">Completed Jobs</a>
        </div>
        <div>
            <b>Support</b><br>
            <a href="../../html/collector/cReportIssues.html">Report Issue</a>
        </div>
        <div>
            <b>Proxy</b><br>
            <a href="../../html/common/About.html">About</a><br>
            <a href="../../html/common/Profile.html">Edit Profile</a><br>
            <a href="../../html/common/Setting.html">Setting</a>
        </div>
    </section>
</footer>

<script src="../../javascript/mainScript.js"></script>

<script>
/* ─── PHP → JS BRIDGE ───────────────────────────────────────────── */
const JOB_ID      = <?= $hasJob ? (int)$job['jobID']      : 'null' ?>;
const REQUEST_ID  = <?= $hasJob ? (int)$job['requestID']  : 'null' ?>;
const CENTRE_IDS  = <?= $hasJob ? json_encode(array_column($itemsByCentre, 'centreID')) : '[]' ?>;
const RETURN_KEY  = <?= $hasJob ? json_encode('session' . ($centreCount + 2)) : 'null' ?>;

/* ─── STATE ─────────────────────────────────────────────────────── */
const state = {
    journeyStarted : <?= ($hasJob && $journeyStarted) ? 'true' : 'false' ?>,
    pendingSession : null,
    pendingCentreID: null,
};

/* ─── POPUP HELPERS ─────────────────────────────────────────────── */
function openPopup(id)  { document.getElementById(id).classList.add('visible'); }
function closePopup(id) { document.getElementById(id).classList.remove('visible'); }

/* ─── TOAST ─────────────────────────────────────────────────────── */
function showToast(msg, duration = 2500) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), duration);
}

/* ─── AJAX HELPER ───────────────────────────────────────────────── */
async function postAction(action, extraData = {}) {
    const fd = new FormData();
    fd.append('jobID',     JOB_ID);
    fd.append('requestID', REQUEST_ID);
    for (const [k, v] of Object.entries(extraData)) fd.append(k, v);
    const res = await fetch(`?action=${action}`, { method: 'POST', body: fd });
    return res.json();
}

/* ─── START JOURNEY ─────────────────────────────────────────────── */
function openStartPopup() { openPopup('startPopup'); }

async function confirmStartJourney() {
    closePopup('startPopup');
    const data = await postAction('start_journey');
    if (!data.success) { showToast('❌ Failed to start journey. Try again.'); return; }

    state.journeyStarted = true;
    const btn = document.getElementById('startJourneyBtn');
    if (btn) btn.style.display = 'none';

    setBadge('badge-overview', 'ongoing', 'Ongoing');
    setBadge('globalStatus',   'ongoing', 'Ongoing');

    unlockCard('card-session1');
    const cb = document.getElementById('completeBtn-session1');
    if (cb) cb.disabled = false;

    updateRouteStep(0, 'done');
    updateRouteLine(0, 'done');
    updateRouteStep(1, 'active');

    showToast('🚗 Journey started, drive safe!');
}

/* ─── COMPLETE SESSION ──────────────────────────────────────────── */
function openCompletePopup(sessionKey, centreID) {
    state.pendingSession  = sessionKey;
    state.pendingCentreID = centreID;

    const msgs = { 'session1': 'Confirm you have collected all items from the provider location.' };
    for (let i = 0; i < CENTRE_IDS.length; i++) {
        msgs['session' + (i + 2)] = 'Confirm you have delivered all items to this recycling centre.';
    }
    if (RETURN_KEY) msgs[RETURN_KEY] = 'Confirm you have returned safely to the base facility.';

    document.getElementById('completePopupMsg').textContent =
        msgs[sessionKey] || 'Confirm completion of this session.';
    openPopup('completePopup');
}

async function confirmComplete() {
    closePopup('completePopup');
    const key      = state.pendingSession;
    const centreID = state.pendingCentreID;

    let data;
    if (key === 'session1') {
        data = await postAction('complete_pickup');
    } else if (key === RETURN_KEY) {
        data = await postAction('complete_return');
    } else {
        data = await postAction('complete_dropoff', { centreID });
    }

    if (!data.success) { showToast('❌ Failed to complete session. Try again.'); return; }

    // Mark this card done
    const card = document.getElementById('card-' + key);
    if (card) { card.classList.remove('active-card','locked'); card.classList.add('completed-card'); }
    setBadge('badge-' + key, 'completed', 'Completed');
    const btn = document.getElementById('completeBtn-' + key);
    if (btn) { btn.disabled = true; btn.textContent = '✓ Done'; }

    // Build ordered session keys
    const allKeys = ['session1'];
    for (let i = 0; i < CENTRE_IDS.length; i++) allKeys.push('session' + (i + 2));
    if (RETURN_KEY) allKeys.push(RETURN_KEY);

    const idx     = allKeys.indexOf(key);
    const dotIdx  = idx + 1;   // dot 0 = start base, dot 1 = session1, …
    const nextKey = allKeys[idx + 1];

    updateRouteStep(dotIdx, 'done');
    updateRouteLine(dotIdx, 'done');

    if (key === RETURN_KEY) {
        setBadge('globalStatus',   'completed', 'Completed');
        setBadge('badge-overview', 'completed', 'Completed');
        updateRouteStep(dotIdx + 1, 'done');
        showToast('🎉 Job completed! Well done!', 4000);
        return;
    }

    // Unlock next
    if (nextKey) {
        unlockCard('card-' + nextKey);
        const nb = document.getElementById('completeBtn-' + nextKey);
        if (nb) nb.disabled = false;
        const rb = document.getElementById('reportBtn-' + nextKey);
        if (rb) rb.disabled = false;
        updateRouteStep(dotIdx + 1, 'active');
    }

    showToast('✅ Session completed!');
}

/* ─── REPORT ISSUE ──────────────────────────────────────────────── */
let pendingReportSession  = null;
let pendingReportCentreID = null;

function openReport(sessionKey, centreID) {
    pendingReportSession  = sessionKey;
    pendingReportCentreID = centreID;
    openPopup('reportPopup');
}

function toggleBreakdownAddress() {
    document.getElementById('breakdownAddressGroup').style.display =
        document.getElementById('issueReason').value === 'vehicle_breakdown' ? 'block' : 'none';
}

async function submitReport() {
    const reason = document.getElementById('issueReason').value;
    if (!reason) { showToast('⚠️ Please select a reason.'); return; }

    const note    = document.getElementById('issueNote').value;
    const address = document.getElementById('breakdownAddress').value;
    const subjectMap = {
        vehicle_breakdown : 'Vehicle Breakdown',
        accident          : 'Accident reported',
        other             : 'Issue reported by collector',
    };
    const desc = address ? `Breakdown at: ${address}. ${note}` : (note || subjectMap[reason]);

    const data = await postAction('report_issue', {
        issueType   : reason,
        severity    : reason === 'accident' ? 'High' : 'Medium',
        subject     : subjectMap[reason] || 'Issue',
        description : desc,
    });

    closePopup('reportPopup');
    if (!data.success) { showToast('❌ Failed to submit report.'); return; }

    const card = document.getElementById('card-' + pendingReportSession);
    if (card) { card.classList.add('locked'); card.classList.remove('active-card'); }
    setBadge('badge-' + pendingReportSession, 'interrupted', 'Interrupted');
    setBadge('globalStatus', 'interrupted', 'Interrupted');

    // Reset form
    document.getElementById('issueReason').value = '';
    document.getElementById('issueNote').value   = '';
    document.getElementById('breakdownAddress').value = '';
    document.getElementById('breakdownAddressGroup').style.display = 'none';

    showToast('⚠️ Issue reported. Admin has been notified.', 4000);
}

/* ─── DOM HELPERS ───────────────────────────────────────────────── */
function unlockCard(cardId) {
    const c = document.getElementById(cardId);
    if (c) { c.classList.remove('locked'); c.classList.add('active-card'); }
}
function setBadge(elemId, type, label) {
    const el = document.getElementById(elemId);
    if (!el) return;
    el.className   = 'badge badge-' + type;
    el.textContent = label;
}
function updateRouteStep(dotIdx, st) {
    const dot   = document.getElementById('dot-'   + dotIdx);
    const label = document.getElementById('label-' + dotIdx);
    const cls   = st === 'done' ? 'done' : st === 'active' ? 'active' : '';
    if (dot)   dot.className   = 'step-dot '   + cls;
    if (label) label.className = 'step-label ' + cls;
}
function updateRouteLine(lineIdx, st) {
    const line = document.getElementById('line-' + lineIdx);
    if (line) line.className = 'step-line ' + (st === 'done' ? 'done' : st === 'active' ? 'active' : '');
}
</script>
</body>
</html>