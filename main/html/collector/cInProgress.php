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

        // Verify this job belongs to this collector and is Scheduled
        $chk = $conn->prepare(
            "SELECT j.jobID, j.requestID, j.status FROM tbljob j
             WHERE j.jobID = ? AND j.collectorID = ?
             AND j.status = 'Scheduled'"
        );
        $chk->bind_param('ii', $jobID, $userID);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$row) { echo json_encode(['success'=>false,'message'=>'Job not found or not scheduled']); exit; }

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

        $cnt = $conn->prepare("SELECT COUNT(*) as c FROM tblitem WHERE requestID=? AND status != 'Cancelled'");
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
        $issueType   = $_POST['issueType']   ?? 'Other';
        $severity    = $_POST['severity']    ?? 'Medium';
        $subject     = trim($_POST['subject']     ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validate issueType against DB enum
        $validTypes = ['Operational', 'Vehicle', 'Safety', 'Technical', 'Other'];
        if (!in_array($issueType, $validTypes)) $issueType = 'Other';

        // Validate severity against DB enum
        $validSeverities = ['Low', 'Medium', 'High', 'Critical'];
        if (!in_array($severity, $validSeverities)) $severity = 'Medium';

        if (empty($subject))     { echo json_encode(['success'=>false,'message'=>'Subject is required']); exit; }
        if (empty($description)) { echo json_encode(['success'=>false,'message'=>'Description is required']); exit; }

        $ins = $conn->prepare(
            "INSERT INTO tblissue
             (requestID, jobID, reportedBy, issueType, severity, subject, description, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Open')"
        );
        $ins->bind_param('iiissss', $requestID, $jobID, $userID, $issueType, $severity, $subject, $description);
        $ins->execute();
        $issueID = $conn->insert_id;
        $ins->close();

        logActivity($conn, $requestID, $jobID, $userID, 'Issue', 'Create',
            "Issue (ID: $issueID) - $issueType - $subject");

        echo json_encode(['success'=>true, 'issueID'=>$issueID]);
        exit;
    }

    echo json_encode(['success'=>false, 'message'=>'Unknown action']);
    exit;
}

// ─── FETCH JOB DATA FOR PAGE RENDER ─────────────────────────────────────────
$collectorID = (int)$_SESSION['userID'];

/*
  Fetch the latest SCHEDULED or ONGOING job for this collector.
  Priority: Ongoing jobs first, then Scheduled
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
       AND j.status IN ('Scheduled', 'Ongoing', 'Picked Up')
     ORDER BY FIELD(j.status, 'Ongoing', 'Picked Up', 'Scheduled'), j.scheduledDate ASC
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
                    'allCollected'   => true
                ];
            }
            $itemsByCentre[$cid]['items'][] = $item;
            if ($item['status'] !== 'Collected' && $item['status'] !== 'Received') {
                $itemsByCentre[$cid]['allCollected'] = false;
            }
        } else {
            $itemsNoCentre[] = $item;
        }
    }
    $itemsByCentre = array_values($itemsByCentre);

    // ── Fetch issues for this job ────────────────────────────────────────────
    $issueQuery = $conn->prepare(
        "SELECT iss.issueID, iss.issueType, iss.severity, iss.subject,
                iss.description, iss.status, iss.reportedAt, iss.resolvedAt, iss.notes,
                u.fullname AS assignedAdminName
         FROM tblissue iss
         LEFT JOIN tblusers u ON u.userID = iss.assignedAdminID
         WHERE iss.jobID = ? AND iss.requestID = ?
         ORDER BY iss.reportedAt DESC"
    );
    $issueQuery->bind_param('ii', $job['jobID'], $job['requestID']);
    $issueQuery->execute();
    $issueResult = $issueQuery->get_result();
    $issueQuery->close();

    $jobIssues = [];
    while ($iss = $issueResult->fetch_assoc()) {
        $jobIssues[] = $iss;
    }
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

// ── Determine current progress state ────────────────────────────────────────
$journeyStarted = false;
$pickupCompleted = false;
$completedCentres = [];
$returnCompleted = false;

if ($hasJob) {
    $journeyStarted = ($job['jobStatus'] !== 'Scheduled');
    $pickupCompleted = true;
    
    // Check if all items are collected (not pending)
    foreach ($itemsByCentre as $group) {
        foreach ($group['items'] as $item) {
            if ($item['status'] === 'Pending') {
                $pickupCompleted = false;
                break;
            }
        }
    }
    
    // Check which centres have been delivered
    foreach ($itemsByCentre as $group) {
        $allReceived = true;
        foreach ($group['items'] as $item) {
            if ($item['status'] !== 'Received' && $item['status'] !== 'Processed' && $item['status'] !== 'Recycled') {
                $allReceived = false;
                break;
            }
        }
        if ($allReceived) {
            $completedCentres[] = $group['centreID'];
        }
    }
    
    $returnCompleted = ($job['jobStatus'] === 'Completed');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $journeyStarted ? 'Ongoing Job' : 'Scheduled Job' ?></title>
    <link rel="icon" type="image/png" href="../../assets//images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/clnProgress.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

<style>
/* Your existing CSS styles remain the same */
.issues-section {
    margin-top: 32px;
}

.issues-section-title {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.issues-section-title .issues-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--StatusRedLight);
    color: var(--StatusRed);
    font-size: 12px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
}

.dark-mode .issues-section-title .issues-count-badge {
    background: hsl(0, 50%, 18%);
    color: hsl(0, 72%, 65%);
}

.issues-empty {
    background: var(--card-bg);
    border: 1.5px solid var(--border-color);
    border-radius: 14px;
    padding: 32px 24px;
    text-align: center;
    color: var(--text-muted);
    font-size: 13.5px;
    box-shadow: var(--shadow);
}

.issues-empty .issues-empty-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.issues-empty p {
    color: var(--text-muted);
    font-size: 13px;
}

.issue-card {
    background: var(--card-bg);
    border: 1.5px solid var(--border-color);
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: var(--shadow);
    margin-bottom: 14px;
    transition: box-shadow 0.25s, border-color 0.25s;
}

.issue-card:last-child {
    margin-bottom: 0;
}

.issue-card:hover {
    box-shadow: var(--shadow-hover);
}

.issue-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.issue-card-left {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.issue-card-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-color);
    line-height: 1.3;
}

.issue-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}

.issue-type-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.2px;
    background: var(--sec-bg-color);
    color: var(--text-muted);
    border: 1px solid var(--border-color);
}

.severity-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.2px;
}

.severity-badge::before {
    content: '';
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}

.severity-low {
    background: var(--StatusGreenLight);
    color: var(--StatusGreen);
}

.severity-medium {
    background: var(--StatusYellowLight);
    color: hsl(42, 80%, 30%);
}

.severity-high {
    background: hsl(25, 100%, 93%);
    color: hsl(25, 90%, 40%);
}

.severity-critical {
    background: var(--StatusRedLight);
    color: var(--StatusRed);
}

.dark-mode .severity-low {
    background: hsl(142, 40%, 15%);
    color: hsl(142, 60%, 55%);
}

.dark-mode .severity-medium {
    background: hsl(42, 60%, 18%);
    color: hsl(42, 90%, 65%);
}

.dark-mode .severity-high {
    background: hsl(25, 50%, 18%);
    color: hsl(25, 80%, 65%);
}

.dark-mode .severity-critical {
    background: hsl(0, 50%, 18%);
    color: hsl(0, 72%, 65%);
}

.issue-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.issue-status-badge::before {
    content: '';
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}

.issue-status-open {
    background: var(--StatusRedLight);
    color: var(--StatusRed);
}

.issue-status-assigned {
    background: var(--StatusYellowLight);
    color: hsl(42, 80%, 30%);
}

.issue-status-resolved {
    background: var(--StatusGreenLight);
    color: var(--StatusGreen);
}

.dark-mode .issue-status-open {
    background: hsl(0, 50%, 18%);
    color: hsl(0, 72%, 65%);
}

.dark-mode .issue-status-assigned {
    background: hsl(42, 60%, 18%);
    color: hsl(42, 90%, 65%);
}

.dark-mode .issue-status-resolved {
    background: hsl(142, 40%, 15%);
    color: hsl(142, 60%, 55%);
}

.issue-card-body {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.6;
    margin-bottom: 14px;
}

.issue-card-divider {
    border: none;
    border-top: 1px solid var(--border-color);
    margin: 12px 0;
}

.issue-card-footer {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
}

.issue-info-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.issue-info-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
}

.issue-info-value {
    font-size: 12.5px;
    color: var(--text-color);
    font-weight: 500;
}

.issue-notes-block {
    background: var(--sec-bg-color);
    border-radius: 8px;
    padding: 10px 14px;
    margin-top: 12px;
    font-size: 12.5px;
    color: var(--text-color);
    line-height: 1.55;
    border-left: 3px solid var(--MainBlue);
}

.issue-notes-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--MainBlue);
    margin-bottom: 4px;
}

@media (max-width: 640px) {
    .issue-card {
        padding: 16px;
    }

    .issue-card-footer {
        grid-template-columns: 1fr 1fr;
    }
}


/* Issue Alert Banner */
.issue-alert {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Disabled button styles for interrupted state */
.btn-complete:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: var(--text-muted);
}

.btn-danger:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
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
    <div class="popup" style="text-align:left; max-width:500px;">
        <h3 style="text-align:center; margin-bottom:6px;">Report an Issue</h3>
        <p style="text-align:center; margin-bottom:18px;">Describe the issue so an admin can assist you promptly.</p>

        <div class="form-group">
            <label>Issue Type <span style="color:var(--StatusRed);">*</span></label>
            <select id="issueType">
                <option value="">Select type…</option>
                <option value="Operational">Operational</option>
                <option value="Vehicle">Vehicle</option>
                <option value="Safety">Safety</option>
                <option value="Technical">Technical</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label>Severity <span style="color:var(--StatusRed);">*</span></label>
            <select id="issueSeverity">
                <option value="">Select severity…</option>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
            </select>
        </div>

        <div class="form-group">
            <label>Subject <span style="color:var(--StatusRed);">*</span></label>
            <input type="text" id="issueSubject" placeholder="e.g. Vehicle tyre pressure dropped" maxlength="150">
        </div>

        <div class="form-group">
            <label>Description <span style="color:var(--StatusRed);">*</span></label>
            <textarea id="issueDescription" placeholder="Provide details about the issue…" rows="4"></textarea>
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
        <a href="../../html/collector/cHome.php" class="c-logo-link">
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
                <a href="../../html/collector/cHome.php">Home</a>
                <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
                <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a><br>
                <a href="../../html/collector/cCompletedJobs.php">History</a>
                <a href="../../html/common/About.html">About</a><br>
            </div>
        </div>
    </nav>
    <nav class="c-navbar-desktop">
        <a href="../../html/collector/cHome.php">Home</a>
        <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
        <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a><br>
        <a href="../../html/collector/cCompletedJobs.php">History</a>
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
        <div style="font-size:48px; margin-bottom:16px;">📋</div>
        <h3 style="font-size:18px; font-weight:700; color:var(--text-color); margin-bottom:8px;">No Scheduled Job</h3>
        <p style="font-size:13.5px; line-height:1.6;">You don't have any scheduled job at the moment.<br>Check <a href="../../html/collector/cMyJobs.php" style="color:var(--MainBlue); font-weight:600;">My Jobs</a> for pending assignments that are awaiting acceptance.</p>
    </div>

<?php else:
    $jobID       = (int)$job['jobID'];
    $requestID   = (int)$job['requestID'];
    $jobLabel    = 'JOB' . str_pad($jobID, 3, '0', STR_PAD_LEFT);
    $jobStatus   = $job['jobStatus'];
    $centreCount = count($itemsByCentre);

    // Determine global badge
    if ($returnCompleted) {
        $globalBadgeClass = 'badge-completed';
        $globalBadgeText  = 'Completed';
    } elseif ($journeyStarted) {
        $globalBadgeClass = 'badge-ongoing';
        $globalBadgeText  = 'Ongoing';
    } else {
        $globalBadgeClass = 'badge-accepted';
        $globalBadgeText  = 'Scheduled';
    }

    // All items flat
    $allItems    = [];
    $totalWeight = 0;
    foreach ($itemsByCentre as $group) {
        foreach ($group['items'] as $it) { $allItems[] = $it; $totalWeight += (float)$it['weight']; }
    }
    foreach ($itemsNoCentre as $it) { $allItems[] = $it; $totalWeight += (float)$it['weight']; }

    $issueCount = count($jobIssues ?? []);
?>
    <div class="page-header">
        <button class="back-btn" onclick="history.back()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <h1 class="page-title"><?= $journeyStarted ? 'Ongoing Job' : 'Scheduled Job' ?></h1>
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
                    <?php if (!$journeyStarted && !$returnCompleted): ?>
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
                        <p><span>Time :</span> <?= date('h:i A', strtotime($job['scheduledTime'])) ?></p>
                    </div>
                    <div class="overview-cell">
                        <div class="overview-cell-title">Items</div>
                        <ul class="item-list">
                            <?php foreach ($allItems as $idx => $it): ?>
                            <li><?= ($idx+1) . '. ' . htmlspecialchars($it['typeName']) . ' (' . htmlspecialchars($it['status']) . ')' ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <span class="weight-tag">Total weight: <?= number_format($totalWeight, 1) ?> kg</span>
                    </div>
                    <div class="overview-cell">
                        <div class="overview-cell-title">Vehicle</div>
                        <p><span>Plate :</span> <?= htmlspecialchars($job['plateNum']) ?></p>
                        <p><span>Model :</span> <?= htmlspecialchars($job['vehicleModel']) ?></p>
                        <p><span>Type :</span> <?= htmlspecialchars($job['vehicleType']) ?></p>
                        <p><span>Capacity :</span> <?= number_format($job['capacityWeight'], 0) ?> kg</p>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Pickup session -->
            <?php
            $pickupCompletedStatus = $pickupCompleted ? 'completed' : ($journeyStarted ? 'active' : 'locked');
            $pickupDisabled = ($pickupCompleted || $returnCompleted) ? true : !$journeyStarted;
            ?>
            <div class="job-card <?= $pickupCompletedStatus ?>" id="card-session1">
                <div class="card-header">
                    <div class="card-header-left">
                        <span class="job-id-label"><?= htmlspecialchars($jobLabel) ?></span>
                        <span class="badge badge-pickup" id="badge-session1">Pick Up</span>
                    </div>
                </div>
                <hr class="card-divider">
                <div class="session-route">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    Base → <?= htmlspecialchars($job['pickupAddress'] . ', ' . $job['pickupPostcode'] . ', ' . $job['pickupState']) ?>
                </div>
                <div class="session-footer">
                    <span style="font-size:12px; color:var(--text-muted);">
                        <?= $pickupCompleted ? '✓ Items collected' : ($journeyStarted ? 'Ready to collect items' : 'Start journey to unlock') ?>
                    </span>
                    <button class="btn btn-complete" id="completeBtn-session1" 
                            onclick="openCompletePopup('session1', null)"
                            <?= $pickupDisabled ? 'disabled' : '' ?>>
                        <?= $pickupCompleted ? '✓ Done' : 'Complete' ?>
                    </button>
                </div>
            </div>

            <!-- CARDS: One per dropoff centre -->
            <?php 
            $activeCentreIndex = -1;
            if ($pickupCompleted && !$returnCompleted) {
                // Find first centre not fully delivered
                foreach ($itemsByCentre as $ci => $group) {
                    if (!in_array($group['centreID'], $completedCentres)) {
                        $activeCentreIndex = $ci;
                        break;
                    }
                }
            }
            
            foreach ($itemsByCentre as $ci => $group):
                $sessionKey = 'session' . ($ci + 2);
                $isCompleted = in_array($group['centreID'], $completedCentres);
                $isActive = ($activeCentreIndex === $ci && !$isCompleted && $pickupCompleted && !$returnCompleted);
                $cardStatus = $isCompleted ? 'completed-card' : ($isActive ? 'active-card' : 'locked');
                $btnDisabled = !$isActive;
            ?>
            <div class="job-card <?= $cardStatus ?>" id="card-<?= $sessionKey ?>">
                <div class="card-header">
                    <div class="card-header-left">
                        <span class="job-id-label"><?= htmlspecialchars($jobLabel) ?></span>
                        <span class="badge badge-pickup" id="badge-<?= $sessionKey ?>">Drop Off</span>
                    </div>
                    <div class="card-header-actions">
                        <button class="btn btn-danger" id="reportBtn-<?= $sessionKey ?>"
                                onclick="openReport('<?= $sessionKey ?>', <?= $group['centreID'] ?>)"
                                <?= $btnDisabled ? 'disabled' : '' ?>>
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
                    <span style="font-size:12px; color:var(--text-muted);">
                        <?= $isCompleted ? '✓ Delivered' : ($isActive ? 'Ready to deliver' : 'Complete previous step to unlock') ?>
                    </span>
                    <button class="btn btn-complete" id="completeBtn-<?= $sessionKey ?>" 
                            onclick="openCompletePopup('<?= $sessionKey ?>', <?= $group['centreID'] ?>)"
                            <?= $btnDisabled ? 'disabled' : '' ?>>
                        <?= $isCompleted ? '✓ Done' : 'Complete' ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- CARD: Return to Base -->
            <?php
            $returnSessionKey = 'session' . ($centreCount + 2);
            $lastCentreName   = !empty($itemsByCentre) ? end($itemsByCentre)['centreName'] : $job['pickupAddress'];
            $allCentresCompleted = count($completedCentres) === count($itemsByCentre);
            $returnActive = ($pickupCompleted && $allCentresCompleted && !$returnCompleted);
            $returnCardStatus = $returnCompleted ? 'completed-card' : ($returnActive ? 'active-card' : 'locked');
            ?>
            <div class="job-card <?= $returnCardStatus ?>" id="card-<?= $returnSessionKey ?>">
                <div class="card-header">
                    <div class="card-header-left">
                        <span class="job-id-label"><?= htmlspecialchars($jobLabel) ?></span>
                        <span class="badge badge-pickup" id="badge-<?= $returnSessionKey ?>">Return</span>
                    </div>
                    <div class="card-header-actions">
                        <button class="btn btn-danger" id="reportBtn-<?= $returnSessionKey ?>"
                                onclick="openReport('<?= $returnSessionKey ?>', null)"
                                <?= !$returnActive || $returnCompleted ? 'disabled' : '' ?>>
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
                    <span style="font-size:12px; color:var(--text-muted);">
                        <?= $returnCompleted ? '✓ Journey completed' : ($returnActive ? 'Ready to return to base' : 'Complete all dropoffs to unlock') ?>
                    </span>
                    <button class="btn btn-complete" id="completeBtn-<?= $returnSessionKey ?>" 
                            onclick="openCompletePopup('<?= $returnSessionKey ?>', null)"
                            <?= !$returnActive || $returnCompleted ? 'disabled' : '' ?>>
                        <?= $returnCompleted ? '✓ Done' : 'Complete' ?>
                    </button>
                </div>
            </div>

        </div><!-- /cards-column -->

        <!-- ─── ROUTE SIDEBAR ─── -->
        <aside class="route-sidebar">
            <div class="route-title">Route</div>
            <div class="route-steps">
                <?php 
                $stepStatuses = [];
                $stepStatuses[0] = $journeyStarted ? 'active' : '';
                $stepStatuses[1] = $pickupCompleted ? 'done' : ($journeyStarted ? 'active' : '');
                $stepStatuses[2] = '';
                $stepStatuses[3] = '';
                $stepStatuses[4] = '';
                
                if ($pickupCompleted) {
                    $completedCount = count($completedCentres);
                    for ($i = 0; $i < $completedCount && $i < count($itemsByCentre); $i++) {
                        $stepStatuses[2 + $i] = 'done';
                    }
                    if ($completedCount < count($itemsByCentre)) {
                        $stepStatuses[2 + $completedCount] = 'active';
                    } elseif ($allCentresCompleted && !$returnCompleted) {
                        $stepStatuses[2 + count($itemsByCentre)] = 'active';
                    } elseif ($returnCompleted) {
                        $stepStatuses[2 + count($itemsByCentre)] = 'done';
                    }
                }
                
                foreach ($routeSteps as $ri => $step):
                    $isLast   = ($ri === count($routeSteps) - 1);
                    $dotState = $stepStatuses[$ri] ?? '';
                ?>
                <div class="route-step">
                    <div class="step-indicator">
                        <div class="step-dot <?= $dotState ?>" id="dot-<?= $ri ?>"></div>
                        <?php if (!$isLast): ?>
                        <div class="step-line <?= $stepStatuses[$ri] === 'done' ? 'done' : '' ?>" id="line-<?= $ri ?>"></div>
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

    <!-- ─── ISSUE HISTORY SECTION ─── -->
    <div class="issues-section">
        <div class="issues-section-title">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" style="color:var(--StatusRed);">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Issue Reports
            <?php if ($issueCount > 0): ?>
            <span class="issues-count-badge"><?= $issueCount ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($jobIssues)): ?>
        <div class="issues-empty">
            <div class="issues-empty-icon">🛡️</div>
            <p style="font-weight:600; color:var(--text-color); margin-bottom:4px;">No issues reported</p>
            <p>No issues have been raised for this job. Use the Report Issue button during your journey if something comes up.</p>
        </div>

        <?php else: ?>
            <?php foreach ($jobIssues as $iss):
                $issStatusClass = match($iss['status']) {
                    'Open'     => 'issue-status-open',
                    'Assigned' => 'issue-status-assigned',
                    'Resolved' => 'issue-status-resolved',
                    default    => 'issue-status-open',
                };
                $sevClass = match($iss['severity']) {
                    'Low'      => 'severity-low',
                    'Medium'   => 'severity-medium',
                    'High'     => 'severity-high',
                    'Critical' => 'severity-critical',
                    default    => 'severity-medium',
                };
                $reportedAt  = date('d M Y, h:i A', strtotime($iss['reportedAt']));
                $resolvedAt  = $iss['resolvedAt'] ? date('d M Y, h:i A', strtotime($iss['resolvedAt'])) : '—';
            ?>
            <div class="issue-card">
                <div class="issue-card-header">
                    <div class="issue-card-left">
                        <div class="issue-card-title"><?= htmlspecialchars($iss['subject']) ?></div>
                        <div class="issue-card-meta">
                            <span class="issue-type-badge"><?= htmlspecialchars($iss['issueType']) ?></span>
                            <span class="severity-badge <?= $sevClass ?>"><?= htmlspecialchars($iss['severity']) ?></span>
                        </div>
                    </div>
                    <span class="issue-status-badge <?= $issStatusClass ?>"><?= htmlspecialchars($iss['status']) ?></span>
                </div>

                <div class="issue-card-body">
                    <?= htmlspecialchars($iss['description']) ?>
                </div>

                <hr class="issue-card-divider">

                <div class="issue-card-footer">
                    <div class="issue-info-cell">
                        <span class="issue-info-label">Issue ID</span>
                        <span class="issue-info-value">#<?= str_pad($iss['issueID'], 3, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="issue-info-cell">
                        <span class="issue-info-label">Reported At</span>
                        <span class="issue-info-value"><?= $reportedAt ?></span>
                    </div>
                    <div class="issue-info-cell">
                        <span class="issue-info-label">Assigned Admin</span>
                        <span class="issue-info-value"><?= htmlspecialchars($iss['assignedAdminName'] ?? '—') ?></span>
                    </div>
                    <div class="issue-info-cell">
                        <span class="issue-info-label">Resolved At</span>
                        <span class="issue-info-value"><?= $resolvedAt ?></span>
                    </div>
                </div>

                <?php if (!empty($iss['notes'])): ?>
                <div class="issue-notes-block">
                    <div class="issue-notes-label">Admin Notes</div>
                    <?= htmlspecialchars($iss['notes']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php endif; ?>
</main>

<hr>
<footer>
    <section class="c-footer-info-section">
        <a href="../../html/collector/cHome.php">
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
            <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
            <a href="../../html/collector/cInProgress.php">In Progress</a><br>
            <a href="../../html/collector/cCompletedJobs.php">Completed Jobs</a>
        </div>
        <div>
            <b>Support</b><br>
            <a href="../../html/collector/cReportIssues.html">Report Issue</a>
        </div>
        <div>
            <b>Proxy</b><br>
            <a href="../../html/common/About.html">About</a><br>
            <a href="../../html/common/Profile.php">Edit Profile</a><br>
            <a href="../../html/common/Setting.html">Setting</a>
        </div>
    </section>
</footer>

<script src="../../javascript/mainScript.js"></script>

<script>
// /* ─── PHP → JS BRIDGE ───────────────────────────────────────────── */
// const JOB_ID      = <?= $hasJob ? (int)$job['jobID']      : 'null' ?>;
// const REQUEST_ID  = <?= $hasJob ? (int)$job['requestID']  : 'null' ?>;
// const CENTRE_IDS  = <?= $hasJob ? json_encode(array_column($itemsByCentre, 'centreID')) : '[]' ?>;
// const RETURN_KEY  = <?= $hasJob ? json_encode('session' . ($centreCount + 2)) : 'null' ?>;
// const JOURNEY_STARTED = <?= $journeyStarted ? 'true' : 'false' ?>;
// const PICKUP_COMPLETED = <?= $pickupCompleted ? 'true' : 'false' ?>;
// const RETURN_COMPLETED = <?= $returnCompleted ? 'true' : 'false' ?>;

// /* ─── STATE ─────────────────────────────────────────────────────── */
// let state = {
//     journeyStarted: JOURNEY_STARTED,
//     pickupCompleted: PICKUP_COMPLETED,
//     completedCentres: <?= json_encode($completedCentres) ?>,
//     returnCompleted: RETURN_COMPLETED,
//     pendingSession: null,
//     pendingCentreID: null,
// };

// /* ─── POPUP HELPERS ─────────────────────────────────────────────── */
// function openPopup(id)  { document.getElementById(id).classList.add('visible'); }
// function closePopup(id) { document.getElementById(id).classList.remove('visible'); }

// /* ─── TOAST ─────────────────────────────────────────────────────── */
// function showToast(msg, duration = 2500) {
//     const t = document.getElementById('toast');
//     t.textContent = msg;
//     t.classList.add('show');
//     setTimeout(() => t.classList.remove('show'), duration);
// }

// /* ─── AJAX HELPER ───────────────────────────────────────────────── */
// async function postAction(action, extraData = {}) {
//     const fd = new FormData();
//     fd.append('jobID',     JOB_ID);
//     fd.append('requestID', REQUEST_ID);
//     for (const [k, v] of Object.entries(extraData)) fd.append(k, v);
//     const res = await fetch(`?action=${action}`, { method: 'POST', body: fd });
//     return res.json();
// }

// /* ─── START JOURNEY ─────────────────────────────────────────────── */
// function openStartPopup() { 
//     if (!state.journeyStarted && !state.returnCompleted) {
//         openPopup('startPopup');
//     }
// }

// async function confirmStartJourney() {
//     closePopup('startPopup');
//     const data = await postAction('start_journey');
//     if (!data.success) { showToast('❌ Failed to start journey. Try again.'); return; }

//     // Update state
//     state.journeyStarted = true;
    
//     // Update UI
//     const btn = document.getElementById('startJourneyBtn');
//     if (btn) btn.style.display = 'none';

//     setBadge('badge-overview', 'ongoing', 'Ongoing');
//     setBadge('globalStatus', 'ongoing', 'Ongoing');

//     // Update page title
//     document.querySelector('.page-title').textContent = 'Ongoing Job';

//     // Unlock session 1
//     updateCardState('card-session1', 'active-card');
//     const cb = document.getElementById('completeBtn-session1');
//     if (cb) {
//         cb.disabled = false;
//         cb.onclick = () => openCompletePopup('session1', null);
//     }

//     // Enable report button for session 1
//     const rb = document.getElementById('reportBtn-session1');
//     if (rb) rb.disabled = false;

//     // Remove lock hint text
//     const lockHints = document.querySelectorAll('#card-session1 .session-footer span');
//     lockHints.forEach(el => el.textContent = 'Ready to collect items');

//     // Update route steps
//     updateRouteStep(0, 'active');
//     updateRouteStep(1, 'active');
//     updateRouteLine(0, 'active');

//     showToast('🚗 Journey started, drive safe!');
// }

// /* ─── COMPLETE SESSION ──────────────────────────────────────────── */
// function openCompletePopup(sessionKey, centreID) {
//     state.pendingSession = sessionKey;
//     state.pendingCentreID = centreID;

//     const msgs = { 
//         'session1': 'Confirm you have collected all items from the provider location.'
//     };
//     for (let i = 0; i < CENTRE_IDS.length; i++) {
//         msgs['session' + (i + 2)] = 'Confirm you have delivered all items to this recycling centre.';
//     }
//     if (RETURN_KEY) msgs[RETURN_KEY] = 'Confirm you have returned safely to the base facility.';

//     document.getElementById('completePopupMsg').textContent =
//         msgs[sessionKey] || 'Confirm completion of this session.';
//     openPopup('completePopup');
// }

// async function confirmComplete() {
//     closePopup('completePopup');
//     const key = state.pendingSession;
//     const centreID = state.pendingCentreID;

//     let data;
//     if (key === 'session1') {
//         data = await postAction('complete_pickup');
//     } else if (key === RETURN_KEY) {
//         data = await postAction('complete_return');
//     } else {
//         data = await postAction('complete_dropoff', { centreID });
//     }

//     if (!data.success) { 
//         showToast('❌ Failed to complete session. Try again.'); 
//         return; 
//     }

//     // Mark this card as completed
//     updateCardState('card-' + key, 'completed-card');
//     setBadge('badge-' + key, 'completed', 'Completed');
//     const btn = document.getElementById('completeBtn-' + key);
//     if (btn) { 
//         btn.disabled = true; 
//         btn.textContent = '✓ Done';
//         btn.onclick = null;
//     }
    
//     // Disable report button
//     const reportBtn = document.getElementById('reportBtn-' + key);
//     if (reportBtn) reportBtn.disabled = true;

//     // Build ordered session keys
//     const allKeys = ['session1'];
//     for (let i = 0; i < CENTRE_IDS.length; i++) allKeys.push('session' + (i + 2));
//     if (RETURN_KEY) allKeys.push(RETURN_KEY);

//     const idx = allKeys.indexOf(key);
//     const dotIdx = idx + 1;
//     const nextKey = allKeys[idx + 1];

//     // Update route step
//     updateRouteStep(dotIdx, 'done');
//     updateRouteLine(dotIdx, 'done');

//     // Update state based on completion
//     if (key === 'session1') {
//         state.pickupCompleted = true;
//     } else if (key === RETURN_KEY) {
//         state.returnCompleted = true;
//         setBadge('globalStatus', 'completed', 'Completed');
//         setBadge('badge-overview', 'completed', 'Completed');
//         updateRouteStep(dotIdx + 1, 'done');
//         showToast('🎉 Job completed! Well done!', 4000);
//         return;
//     } else {
//         // It's a centre dropoff
//         if (centreID && !state.completedCentres.includes(centreID)) {
//             state.completedCentres.push(centreID);
//         }
//     }

//     // Unlock next step if available
//     if (nextKey) {
//         updateCardState('card-' + nextKey, 'active-card');
//         const nextBtn = document.getElementById('completeBtn-' + nextKey);
//         if (nextBtn) {
//             nextBtn.disabled = false;
//             nextBtn.onclick = () => {
//                 const centreId = nextKey === RETURN_KEY ? null : CENTRE_IDS[parseInt(nextKey.split('session')[1]) - 2];
//                 openCompletePopup(nextKey, centreId);
//             };
//         }
//         const nextReportBtn = document.getElementById('reportBtn-' + nextKey);
//         if (nextReportBtn) nextReportBtn.disabled = false;
//         updateRouteStep(dotIdx + 1, 'active');
        
//         // Update footer text
//         const footerSpan = document.querySelector('#card-' + nextKey + ' .session-footer span');
//         if (footerSpan) {
//             if (nextKey === RETURN_KEY) {
//                 footerSpan.textContent = 'Ready to return to base';
//             } else {
//                 footerSpan.textContent = 'Ready to deliver';
//             }
//         }
//     }

//     showToast('✅ Session completed!');
// }

// /* ─── REPORT ISSUE ──────────────────────────────────────────────── */
// let pendingReportSession = null;
// let pendingReportCentreID = null;

// function openReport(sessionKey, centreID) {
//     pendingReportSession = sessionKey;
//     pendingReportCentreID = centreID;
//     openPopup('reportPopup');
// }

// async function submitReport() {
//     const issueType = document.getElementById('issueType').value;
//     const severity = document.getElementById('issueSeverity').value;
//     const subject = document.getElementById('issueSubject').value.trim();
//     const description = document.getElementById('issueDescription').value.trim();

//     if (!issueType)   { showToast('⚠️ Please select an issue type.'); return; }
//     if (!severity)    { showToast('⚠️ Please select a severity level.'); return; }
//     if (!subject)     { showToast('⚠️ Please enter a subject.'); return; }
//     if (!description) { showToast('⚠️ Please provide a description.'); return; }

//     const data = await postAction('report_issue', {
//         issueType,
//         severity,
//         subject,
//         description,
//     });

//     closePopup('reportPopup');
//     if (!data.success) { showToast('❌ Failed to submit report. ' + (data.message || '')); return; }

//     // Lock the current session card
//     updateCardState('card-' + pendingReportSession, 'locked');
//     setBadge('badge-' + pendingReportSession, 'interrupted', 'Interrupted');
//     setBadge('globalStatus', 'interrupted', 'Interrupted');

//     // Disable complete button
//     const btn = document.getElementById('completeBtn-' + pendingReportSession);
//     if (btn) btn.disabled = true;
    
//     // Disable report button
//     const reportBtn = document.getElementById('reportBtn-' + pendingReportSession);
//     if (reportBtn) reportBtn.disabled = true;

//     // Reset form
//     document.getElementById('issueType').value = '';
//     document.getElementById('issueSeverity').value = '';
//     document.getElementById('issueSubject').value = '';
//     document.getElementById('issueDescription').value = '';

//     showToast('⚠️ Issue reported. Admin has been notified.', 4000);

//     // Reload after brief delay
//     setTimeout(() => location.reload(), 3500);
// }

// /* ─── DOM HELPERS ───────────────────────────────────────────────── */
// function updateCardState(cardId, className) {
//     const card = document.getElementById(cardId);
//     if (card) {
//         card.classList.remove('locked', 'active-card', 'completed-card');
//         card.classList.add(className);
//     }
// }

// function setBadge(elemId, type, label) {
//     const el = document.getElementById(elemId);
//     if (!el) return;
//     el.className = 'badge badge-' + type;
//     el.textContent = label;
// }

// function updateRouteStep(dotIdx, state) {
//     const dot = document.getElementById('dot-' + dotIdx);
//     const label = document.getElementById('label-' + dotIdx);
//     if (dot) dot.className = 'step-dot ' + state;
//     if (label) label.className = 'step-label ' + state;
// }

// function updateRouteLine(lineIdx, state) {
//     const line = document.getElementById('line-' + lineIdx);
//     if (line) line.className = 'step-line ' + (state === 'done' ? 'done' : '');
// }

// // Initialize page state based on PHP data
// document.addEventListener('DOMContentLoaded', function() {
//     // Set initial button states
//     if (!state.journeyStarted && !state.returnCompleted) {
//         const startBtn = document.getElementById('startJourneyBtn');
//         if (startBtn) startBtn.style.display = 'flex';
//     }
    
//     // Set initial route step states
//     if (state.journeyStarted) {
//         updateRouteStep(0, 'active');
//         if (state.pickupCompleted) {
//             updateRouteStep(1, 'done');
//             updateRouteLine(1, 'done');
//         } else {
//             updateRouteStep(1, 'active');
//             updateRouteLine(1, 'active');
//         }
        
//         let completedCount = state.completedCentres.length;
//         for (let i = 0; i < completedCount && i < CENTRE_IDS.length; i++) {
//             updateRouteStep(2 + i, 'done');
//             updateRouteLine(2 + i, 'done');
//         }
        
//         if (completedCount < CENTRE_IDS.length && state.pickupCompleted && !state.returnCompleted) {
//             updateRouteStep(2 + completedCount, 'active');
//         } else if (completedCount === CENTRE_IDS.length && state.pickupCompleted && !state.returnCompleted) {
//             updateRouteStep(2 + CENTRE_IDS.length, 'active');
//         } else if (state.returnCompleted) {
//             updateRouteStep(2 + CENTRE_IDS.length, 'done');
//         }
//     }
    
//     // If all completed, show final message
//     if (state.returnCompleted) {
//         showToast('🎉 This job has been completed!', 3000);
//     }
// });


/* ─── PHP → JS BRIDGE ───────────────────────────────────────────── */
const JOB_ID      = <?= $hasJob ? (int)$job['jobID']      : 'null' ?>;
const REQUEST_ID  = <?= $hasJob ? (int)$job['requestID']  : 'null' ?>;
const CENTRE_IDS  = <?= $hasJob ? json_encode(array_column($itemsByCentre, 'centreID')) : '[]' ?>;
const RETURN_KEY  = <?= $hasJob ? json_encode('session' . ($centreCount + 2)) : 'null' ?>;
const JOURNEY_STARTED = <?= $journeyStarted ? 'true' : 'false' ?>;
const PICKUP_COMPLETED = <?= $pickupCompleted ? 'true' : 'false' ?>;
const RETURN_COMPLETED = <?= $returnCompleted ? 'true' : 'false' ?>;

/* ─── STATE ─────────────────────────────────────────────────────── */
let state = {
    journeyStarted: JOURNEY_STARTED,
    pickupCompleted: PICKUP_COMPLETED,
    completedCentres: <?= json_encode($completedCentres) ?>,
    returnCompleted: RETURN_COMPLETED,
    pendingSession: null,
    pendingCentreID: null,
    hasActiveIssue: false, // Track if there's an active issue that blocks progress
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

/* ─── CHECK FOR ACTIVE ISSUES ───────────────────────────────────── */
function checkForActiveIssues() {
    // Check if there are any open or assigned issues for this job
    const openIssues = document.querySelectorAll('.issue-status-open, .issue-status-assigned');
    if (openIssues.length > 0) {
        state.hasActiveIssue = true;
        lockAllSteps();
        showToast('⚠️ There are unresolved issues. Please wait for admin resolution before continuing.', 5000);
        return true;
    }
    return false;
}

/* ─── LOCK ALL FUTURE STEPS ─────────────────────────────────────── */
function lockAllSteps() {
    // Get all session cards
    const allCards = document.querySelectorAll('[id^="card-session"]');
    
    allCards.forEach(card => {
        // Don't lock already completed cards
        if (!card.classList.contains('completed-card')) {
            card.classList.remove('active-card', 'completed-card');
            card.classList.add('locked');
            
            // Disable complete button
            const completeBtn = card.querySelector('.btn-complete');
            if (completeBtn) {
                completeBtn.disabled = true;
                completeBtn.onclick = null;
            }
            
            // Disable report button
            const reportBtn = card.querySelector('.btn-danger');
            if (reportBtn) {
                reportBtn.disabled = true;
            }
            
            // Update footer text
            const footerSpan = card.querySelector('.session-footer span');
            if (footerSpan) {
                footerSpan.textContent = '🔒 Journey paused due to reported issue';
                footerSpan.style.color = 'var(--StatusRed)';
            }
        }
    });
    
    // Update global status badge
    setBadge('globalStatus', 'interrupted', 'Interrupted');
    setBadge('badge-overview', 'interrupted', 'Interrupted');
    
    // Show notification if there are active issues
    const issueCount = document.querySelectorAll('.issue-status-open, .issue-status-assigned').length;
    if (issueCount > 0) {
        const issueMessage = document.createElement('div');
        issueMessage.className = 'issue-alert';
        issueMessage.innerHTML = `
            <div style="background: var(--StatusRedLight); border-left: 4px solid var(--StatusRed); padding: 12px 16px; margin: 16px 0; border-radius: 8px;">
                <strong style="color: var(--StatusRed);">⚠️ Journey Interrupted</strong><br>
                You have ${issueCount} unresolved issue(s) that require admin attention. 
                Please wait for resolution before continuing your journey.
            </div>
        `;
        
        const cardsColumn = document.getElementById('cardsColumn');
        const existingAlert = document.querySelector('.issue-alert');
        if (!existingAlert && cardsColumn) {
            cardsColumn.insertBefore(issueMessage, cardsColumn.firstChild);
        }
    }
}

/* ─── UNLOCK STEPS AFTER ISSUE RESOLUTION ───────────────────────── */
function unlockSteps() {
    state.hasActiveIssue = false;
    
    // Remove any alert messages
    const alerts = document.querySelectorAll('.issue-alert');
    alerts.forEach(alert => alert.remove());
    
    // Find the current active step based on progress
    if (!state.pickupCompleted && state.journeyStarted) {
        // Pickup not completed yet
        updateCardState('card-session1', 'active-card');
        const cb = document.getElementById('completeBtn-session1');
        if (cb) {
            cb.disabled = false;
            cb.onclick = () => openCompletePopup('session1', null);
        }
        const rb = document.getElementById('reportBtn-session1');
        if (rb) rb.disabled = false;
        
        const footerSpan = document.querySelector('#card-session1 .session-footer span');
        if (footerSpan) {
            footerSpan.textContent = 'Ready to collect items';
            footerSpan.style.color = '';
        }
    } 
    else if (state.pickupCompleted && !state.returnCompleted) {
        // Pickup completed, find next centre to deliver
        let nextCentreIndex = -1;
        for (let i = 0; i < CENTRE_IDS.length; i++) {
            if (!state.completedCentres.includes(CENTRE_IDS[i])) {
                nextCentreIndex = i;
                break;
            }
        }
        
        if (nextCentreIndex !== -1) {
            // Unlock the next centre
            const sessionKey = 'session' + (nextCentreIndex + 2);
            updateCardState('card-' + sessionKey, 'active-card');
            const cb = document.getElementById('completeBtn-' + sessionKey);
            if (cb) {
                cb.disabled = false;
                cb.onclick = () => openCompletePopup(sessionKey, CENTRE_IDS[nextCentreIndex]);
            }
            const rb = document.getElementById('reportBtn-' + sessionKey);
            if (rb) rb.disabled = false;
            
            const footerSpan = document.querySelector('#card-' + sessionKey + ' .session-footer span');
            if (footerSpan) {
                footerSpan.textContent = 'Ready to deliver';
                footerSpan.style.color = '';
            }
        } 
        else if (state.completedCentres.length === CENTRE_IDS.length) {
            // All centres completed, unlock return step
            updateCardState('card-' + RETURN_KEY, 'active-card');
            const cb = document.getElementById('completeBtn-' + RETURN_KEY);
            if (cb) {
                cb.disabled = false;
                cb.onclick = () => openCompletePopup(RETURN_KEY, null);
            }
            const rb = document.getElementById('reportBtn-' + RETURN_KEY);
            if (rb) rb.disabled = false;
            
            const footerSpan = document.querySelector('#card-' + RETURN_KEY + ' .session-footer span');
            if (footerSpan) {
                footerSpan.textContent = 'Ready to return to base';
                footerSpan.style.color = '';
            }
        }
    }
    
    // Update global badge back to ongoing
    setBadge('globalStatus', 'ongoing', 'Ongoing');
    setBadge('badge-overview', 'ongoing', 'Ongoing');
    
    showToast('✅ Issues resolved! You can now continue your journey.', 4000);
}

/* ─── START JOURNEY ─────────────────────────────────────────────── */
function openStartPopup() { 
    if (!state.journeyStarted && !state.returnCompleted && !state.hasActiveIssue) {
        openPopup('startPopup');
    } else if (state.hasActiveIssue) {
        showToast('⚠️ Cannot start journey. There are unresolved issues that need admin attention.', 4000);
    }
}

async function confirmStartJourney() {
    closePopup('startPopup');
    const data = await postAction('start_journey');
    if (!data.success) { showToast('❌ Failed to start journey. Try again.'); return; }

    // Update state
    state.journeyStarted = true;
    
    // Update UI
    const btn = document.getElementById('startJourneyBtn');
    if (btn) btn.style.display = 'none';

    setBadge('badge-overview', 'ongoing', 'Ongoing');
    setBadge('globalStatus', 'ongoing', 'Ongoing');

    // Update page title
    document.querySelector('.page-title').textContent = 'Ongoing Job';

    // Unlock session 1
    updateCardState('card-session1', 'active-card');
    const cb = document.getElementById('completeBtn-session1');
    if (cb) {
        cb.disabled = false;
        cb.onclick = () => openCompletePopup('session1', null);
    }

    // Enable report button for session 1
    const rb = document.getElementById('reportBtn-session1');
    if (rb) rb.disabled = false;

    // Remove lock hint text
    const lockHints = document.querySelectorAll('#card-session1 .session-footer span');
    lockHints.forEach(el => {
        el.textContent = 'Ready to collect items';
        el.style.color = '';
    });

    // Update route steps
    updateRouteStep(0, 'active');
    updateRouteStep(1, 'active');
    updateRouteLine(0, 'active');

    showToast('🚗 Journey started, drive safe!');
}

/* ─── COMPLETE SESSION ──────────────────────────────────────────── */
function openCompletePopup(sessionKey, centreID) {
    if (state.hasActiveIssue) {
        showToast('⚠️ Cannot complete session. There are unresolved issues that need admin attention.', 4000);
        return;
    }
    
    state.pendingSession = sessionKey;
    state.pendingCentreID = centreID;

    const msgs = { 
        'session1': 'Confirm you have collected all items from the provider location.'
    };
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
    const key = state.pendingSession;
    const centreID = state.pendingCentreID;

    let data;
    if (key === 'session1') {
        data = await postAction('complete_pickup');
    } else if (key === RETURN_KEY) {
        data = await postAction('complete_return');
    } else {
        data = await postAction('complete_dropoff', { centreID });
    }

    if (!data.success) { 
        showToast('❌ Failed to complete session. Try again.'); 
        return; 
    }

    // Mark this card as completed
    updateCardState('card-' + key, 'completed-card');
    setBadge('badge-' + key, 'completed', 'Completed');
    const btn = document.getElementById('completeBtn-' + key);
    if (btn) { 
        btn.disabled = true; 
        btn.textContent = '✓ Done';
        btn.onclick = null;
    }
    
    // Disable report button
    const reportBtn = document.getElementById('reportBtn-' + key);
    if (reportBtn) reportBtn.disabled = true;

    // Build ordered session keys
    const allKeys = ['session1'];
    for (let i = 0; i < CENTRE_IDS.length; i++) allKeys.push('session' + (i + 2));
    if (RETURN_KEY) allKeys.push(RETURN_KEY);

    const idx = allKeys.indexOf(key);
    const dotIdx = idx + 1;
    const nextKey = allKeys[idx + 1];

    // Update route step
    updateRouteStep(dotIdx, 'done');
    updateRouteLine(dotIdx, 'done');

    // Update state based on completion
    if (key === 'session1') {
        state.pickupCompleted = true;
    } else if (key === RETURN_KEY) {
        state.returnCompleted = true;
        setBadge('globalStatus', 'completed', 'Completed');
        setBadge('badge-overview', 'completed', 'Completed');
        updateRouteStep(dotIdx + 1, 'done');
        showToast('🎉 Job completed! Well done!', 4000);
        return;
    } else {
        // It's a centre dropoff
        if (centreID && !state.completedCentres.includes(centreID)) {
            state.completedCentres.push(centreID);
        }
    }

    // Unlock next step if available and no active issues
    if (nextKey && !state.hasActiveIssue) {
        updateCardState('card-' + nextKey, 'active-card');
        const nextBtn = document.getElementById('completeBtn-' + nextKey);
        if (nextBtn) {
            nextBtn.disabled = false;
            nextBtn.onclick = () => {
                const centreId = nextKey === RETURN_KEY ? null : CENTRE_IDS[parseInt(nextKey.split('session')[1]) - 2];
                openCompletePopup(nextKey, centreId);
            };
        }
        const nextReportBtn = document.getElementById('reportBtn-' + nextKey);
        if (nextReportBtn) nextReportBtn.disabled = false;
        updateRouteStep(dotIdx + 1, 'active');
        
        // Update footer text
        const footerSpan = document.querySelector('#card-' + nextKey + ' .session-footer span');
        if (footerSpan) {
            if (nextKey === RETURN_KEY) {
                footerSpan.textContent = 'Ready to return to base';
            } else {
                footerSpan.textContent = 'Ready to deliver';
            }
            footerSpan.style.color = '';
        }
    } else if (nextKey && state.hasActiveIssue) {
        // If there's an active issue, keep the next step locked
        updateCardState('card-' + nextKey, 'locked');
    }

    showToast('✅ Session completed!');
}

/* ─── REPORT ISSUE ──────────────────────────────────────────────── */
let pendingReportSession = null;
let pendingReportCentreID = null;

function openReport(sessionKey, centreID) {
    // Don't allow reporting if journey hasn't started or is completed
    if (!state.journeyStarted || state.returnCompleted) {
        showToast('⚠️ Cannot report issues at this stage.', 3000);
        return;
    }
    
    pendingReportSession = sessionKey;
    pendingReportCentreID = centreID;
    openPopup('reportPopup');
}

async function submitReport() {
    const issueType = document.getElementById('issueType').value;
    const severity = document.getElementById('issueSeverity').value;
    const subject = document.getElementById('issueSubject').value.trim();
    const description = document.getElementById('issueDescription').value.trim();

    if (!issueType)   { showToast('⚠️ Please select an issue type.'); return; }
    if (!severity)    { showToast('⚠️ Please select a severity level.'); return; }
    if (!subject)     { showToast('⚠️ Please enter a subject.'); return; }
    if (!description) { showToast('⚠️ Please provide a description.'); return; }

    const data = await postAction('report_issue', {
        issueType,
        severity,
        subject,
        description,
    });

    closePopup('reportPopup');
    if (!data.success) { showToast('❌ Failed to submit report. ' + (data.message || '')); return; }

    // Mark that there's an active issue
    state.hasActiveIssue = true;
    
    // Lock the current session card
    updateCardState('card-' + pendingReportSession, 'locked');
    setBadge('badge-' + pendingReportSession, 'interrupted', 'Interrupted');
    
    // Disable all future steps
    lockAllSteps();

    // Reset form
    document.getElementById('issueType').value = '';
    document.getElementById('issueSeverity').value = '';
    document.getElementById('issueSubject').value = '';
    document.getElementById('issueDescription').value = '';

    showToast('⚠️ Issue reported. Journey paused. Admin has been notified and will review shortly.', 5000);

    // Instead of reloading, we'll keep the page state and just lock everything
    // Reload after 5 seconds to show the new issue in the history
    setTimeout(() => location.reload(), 5000);
}

/* ─── DOM HELPERS ───────────────────────────────────────────────── */
function updateCardState(cardId, className) {
    const card = document.getElementById(cardId);
    if (card) {
        card.classList.remove('locked', 'active-card', 'completed-card');
        card.classList.add(className);
    }
}

function setBadge(elemId, type, label) {
    const el = document.getElementById(elemId);
    if (!el) return;
    el.className = 'badge badge-' + type;
    el.textContent = label;
}

function updateRouteStep(dotIdx, state) {
    const dot = document.getElementById('dot-' + dotIdx);
    const label = document.getElementById('label-' + dotIdx);
    if (dot) dot.className = 'step-dot ' + state;
    if (label) label.className = 'step-label ' + state;
}

function updateRouteLine(lineIdx, state) {
    const line = document.getElementById('line-' + lineIdx);
    if (line) line.className = 'step-line ' + (state === 'done' ? 'done' : '');
}

// Initialize page state based on PHP data
document.addEventListener('DOMContentLoaded', function() {
    // Check for any active issues on page load
    checkForActiveIssues();
    
    // Set initial button states
    if (!state.journeyStarted && !state.returnCompleted && !state.hasActiveIssue) {
        const startBtn = document.getElementById('startJourneyBtn');
        if (startBtn) startBtn.style.display = 'flex';
    }
    
    // If there's an active issue, all steps should be locked
    if (state.hasActiveIssue) {
        lockAllSteps();
    } else {
        // Set initial route step states
        if (state.journeyStarted) {
            updateRouteStep(0, 'active');
            if (state.pickupCompleted) {
                updateRouteStep(1, 'done');
                updateRouteLine(1, 'done');
            } else {
                updateRouteStep(1, 'active');
                updateRouteLine(1, 'active');
            }
            
            let completedCount = state.completedCentres.length;
            for (let i = 0; i < completedCount && i < CENTRE_IDS.length; i++) {
                updateRouteStep(2 + i, 'done');
                updateRouteLine(2 + i, 'done');
            }
            
            if (completedCount < CENTRE_IDS.length && state.pickupCompleted && !state.returnCompleted) {
                updateRouteStep(2 + completedCount, 'active');
            } else if (completedCount === CENTRE_IDS.length && state.pickupCompleted && !state.returnCompleted) {
                updateRouteStep(2 + CENTRE_IDS.length, 'active');
            } else if (state.returnCompleted) {
                updateRouteStep(2 + CENTRE_IDS.length, 'done');
            }
        }
        
        // If all completed, show final message
        if (state.returnCompleted) {
            showToast('🎉 This job has been completed!', 3000);
        }
    }
});

</script>
</body>
</html>