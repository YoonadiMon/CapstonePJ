<?php
session_start();
include("../../php/dbConn.php");

// // check if user is logged in
// include("../../php/sessionCheck.php");

// Temporarily set session as admin ID 1 until login is implemented
if (!isset($_SESSION['userID'])) {
    $_SESSION['userID']   = 1;
    $_SESSION['userType'] = 'admin';
}

$userID   = (int)$_SESSION['userID'];
$userType = $_SESSION['userType'];

function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function fmtDateTime($dt) {
    if (!$dt) return '—';
    return date('d M Y, g:i A', strtotime($dt));
}

function logActivity($conn, $userID, $type, $action, $description, $requestID = null, $jobID = null) {
    $stmt = $conn->prepare("INSERT INTO tblactivity_log (requestID, jobID, userID, type, action, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iiisss', $requestID, $jobID, $userID, $type, $action, $description);
    $stmt->execute();
    $stmt->close();
}

// Get issue ID from URL 
$issueID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($issueID <= 0) {
    header('Location: aIssue.php'); exit;
}

// Handle POST actions 
$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg   = $_SESSION['errorMsg']   ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Take on issue
    if ($action === 'take_on') {
        $stmt = $conn->prepare("
            UPDATE tblissue
            SET assignedAdminID = ?, assignedAt = NOW(), status = 'Assigned'
            WHERE issueID = ? AND status = 'Open'
        ");
        $stmt->bind_param('ii', $userID, $issueID);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['successMsg'] = 'You have taken on this issue. Admin panel is now unlocked.';

            // Fetch requestID and jobID for log
            $iRow = $conn->query("SELECT requestID, jobID FROM tblissue WHERE issueID=$issueID")->fetch_assoc();
            logActivity($conn, $userID, 'Issue', 'Assigned', "Admin took on issue #$issueID", $iRow['requestID'] ?? null, $iRow['jobID'] ?? null);
        } else {
            $_SESSION['errorMsg'] = 'Issue could not be assigned (it may have already been taken).';
        }
        $stmt->close();
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Save admin notes 
    if ($action === 'save_notes') {
        $notes = trim($_POST['notes'] ?? '');
        $stmt  = $conn->prepare("UPDATE tblissue SET notes = ? WHERE issueID = ? AND assignedAdminID = ?");
        $stmt->bind_param('sii', $notes, $issueID, $userID);
        if ($stmt->execute()) {
            $_SESSION['successMsg'] = 'Notes saved successfully.';

            $iRow = $conn->query("SELECT requestID, jobID FROM tblissue WHERE issueID=$issueID")->fetch_assoc();
            logActivity($conn, $userID, 'Issue', 'Notes Updated', "Admin updated notes for issue #$issueID", $iRow['requestID'] ?? null, $iRow['jobID'] ?? null);
        } else {
            $_SESSION['errorMsg'] = 'Failed to save notes.';
        }
        $stmt->close();
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Mark as Resolved 
    if ($action === 'resolve') {
        $notes = trim($_POST['resolve_notes'] ?? '');
        if ($notes === '') {
            $_SESSION['errorMsg'] = 'Resolution notes are required.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }
        $stmt = $conn->prepare("
            UPDATE tblissue
            SET status = 'Resolved', notes = ?, resolvedAt = NOW()
            WHERE issueID = ? AND assignedAdminID = ?
        ");
        $stmt->bind_param('sii', $notes, $issueID, $userID);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['successMsg'] = 'Issue marked as Resolved.';

            $iRow = $conn->query("SELECT requestID, jobID FROM tblissue WHERE issueID=$issueID")->fetch_assoc();
            logActivity($conn, $userID, 'Issue', 'Resolved', "Issue #$issueID resolved. Notes: $notes", $iRow['requestID'] ?? null, $iRow['jobID'] ?? null);
        } else {
            $_SESSION['errorMsg'] = 'Failed to resolve issue.';
        }
        $stmt->close();
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Reassign Collector (pickup-not-done only)
    if ($action === 'reassign_collector') {
        $jobID = (int)($_POST['jobID']        ?? 0);
        $newCollectorID = (int)($_POST['collectorID'] ?? 0);

        if ($jobID <= 0 || $newCollectorID <= 0) {
            $_SESSION['errorMsg'] = 'Invalid data for collector reassignment.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        // Fetch job date
        $jRow = $conn->query("SELECT scheduledDate, scheduledTime, estimatedEndTime, vehicleID, requestID FROM tbljob WHERE jobID=$jobID")->fetch_assoc();
        if (!$jRow) {
            $_SESSION['errorMsg'] = 'Job not found.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $date      = $jRow['scheduledDate'];
        $time      = $jRow['scheduledTime'];
        $endTime   = $jRow['estimatedEndTime'];
        $vehicleID = (int)$jRow['vehicleID'];
        $requestID = (int)$jRow['requestID'];

        // Check collector availability (±0 day — same day block)
        $escapedDate = $conn->real_escape_string($date);
        $conflict = $conn->query("
            SELECT 1 FROM tbljob
            WHERE collectorID = $newCollectorID
              AND scheduledDate = '$escapedDate'
              AND status IN ('Pending','Scheduled','Ongoing')
              AND jobID != $jobID
            LIMIT 1
        ")->num_rows > 0;

        if ($conflict) {
            $_SESSION['errorMsg'] = 'Selected collector already has a job on ' . date('d M Y', strtotime($date)) . '.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        // Cancel old job, create new job
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Reassigned via issue #$issueID' WHERE jobID=$jobID");
            $ins = $conn->prepare("INSERT INTO tbljob (requestID, collectorID, vehicleID, scheduledDate, scheduledTime, estimatedEndTime, status) VALUES (?,?,?,?,?,?,'Pending')");
            $ins->bind_param('iiisss', $requestID, $newCollectorID, $vehicleID, $date, $time, $endTime);
            $ins->execute();
            $newJobID = (int)$conn->insert_id;
            $conn->commit();
            $_SESSION['successMsg'] = 'Collector reassigned and a new job created.';

            // Fetch new collector name for log
            $cName = $conn->query("SELECT fullname FROM tblusers WHERE userID=$newCollectorID")->fetch_assoc()['fullname'] ?? "ID: $newCollectorID";
            logActivity($conn, $userID, 'Issue', 'Collector Reassigned', "Issue #$issueID: collector reassigned to $cName (ID: $newCollectorID). Old job #$jobID cancelled, new job #$newJobID created.", $requestID, $newJobID);
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Reassign Vehicle (pickup-not-done only)
    if ($action === 'reassign_vehicle') {
        $jobID = (int)($_POST['jobID']      ?? 0);
        $newVehicleID = (int)($_POST['vehicleID'] ?? 0);

        if ($jobID <= 0 || $newVehicleID <= 0) {
            $_SESSION['errorMsg'] = 'Invalid data for vehicle reassignment.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $jRow = $conn->query("SELECT scheduledDate, scheduledTime, estimatedEndTime, collectorID, requestID FROM tbljob WHERE jobID=$jobID")->fetch_assoc();
        if (!$jRow) {
            $_SESSION['errorMsg'] = 'Job not found.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $date        = $jRow['scheduledDate'];
        $time        = $jRow['scheduledTime'];
        $endTime     = $jRow['estimatedEndTime'];
        $collectorID = (int)$jRow['collectorID'];
        $requestID   = (int)$jRow['requestID'];
        $escapedDate = $conn->real_escape_string($date);

        // Check vehicle availability
        $conflict = $conn->query("
            SELECT 1 FROM tbljob
            WHERE vehicleID = $newVehicleID
              AND scheduledDate = '$escapedDate'
              AND status IN ('Pending','Scheduled','Ongoing')
              AND jobID != $jobID
            LIMIT 1
        ")->num_rows > 0;

        // Also check vehicle not in maintenance on that date
        $maintConflict = $conn->query("
            SELECT 1 FROM tblmaintenance
            WHERE vehicleID = $newVehicleID
              AND status IN ('Scheduled','In Progress')
              AND startDate <= '$escapedDate'
              AND (endDate IS NULL OR endDate >= '$escapedDate')
            LIMIT 1
        ")->num_rows > 0;

        if ($conflict) {
            $_SESSION['errorMsg'] = 'Selected vehicle already has a job on ' . date('d M Y', strtotime($date)) . '.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }
        if ($maintConflict) {
            $_SESSION['errorMsg'] = 'Selected vehicle has maintenance scheduled on ' . date('d M Y', strtotime($date)) . '.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        // Cancel old job, create new job
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Reassigned via issue #$issueID' WHERE jobID=$jobID");
            $ins = $conn->prepare("INSERT INTO tbljob (requestID, collectorID, vehicleID, scheduledDate, scheduledTime, estimatedEndTime, status) VALUES (?,?,?,?,?,?,'Pending')");
            $ins->bind_param('iiisss', $requestID, $collectorID, $newVehicleID, $date, $time, $endTime);
            $ins->execute();
            $newJobID = (int)$conn->insert_id;
            $conn->commit();
            $_SESSION['successMsg'] = 'Vehicle reassigned and a new job created.';

            // Fetch new vehicle plate for log
            $vPlate = $conn->query("SELECT plateNum FROM tblvehicle WHERE vehicleID=$newVehicleID")->fetch_assoc()['plateNum'] ?? "ID: $newVehicleID";
            logActivity($conn, $userID, 'Issue', 'Vehicle Reassigned', "Issue #$issueID: vehicle reassigned to $vPlate (ID: $newVehicleID). Old job #$jobID cancelled, new job #$newJobID created.", $requestID, $newJobID);
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Reassign Collector + Vehicle together (pickup-done, dropoff-pending — emergency)
    if ($action === 'reassign_dropoff') {
        $jobID          = (int)($_POST['jobID']          ?? 0);
        $newCollectorID = (int)($_POST['collectorID']    ?? 0);
        $newVehicleID   = (int)($_POST['vehicleID']      ?? 0);
        $newDate        = trim($_POST['new_date']        ?? '');
        $cancelPrevID   = (int)($_POST['cancel_prev_id'] ?? 0);

        if ($jobID <= 0 || $newCollectorID <= 0 || $newVehicleID <= 0) {
            $_SESSION['errorMsg'] = 'A new collector and vehicle are required.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $jRow = $conn->query("SELECT scheduledDate, scheduledTime, estimatedEndTime, requestID FROM tbljob WHERE jobID=$jobID")->fetch_assoc();
        if (!$jRow) {
            $_SESSION['errorMsg'] = 'Job not found.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $requestID  = (int)$jRow['requestID'];
        $schedDate  = !empty($newDate) ? $newDate : date('Y-m-d');
        $schedTime  = date('H:i:s');
        $endTime    = $jRow['estimatedEndTime'];
        $escapedDate = $conn->real_escape_string($schedDate);

        // Check collector availability on new date
        $cConflict = $conn->query("
            SELECT 1 FROM tbljob
            WHERE collectorID = $newCollectorID
              AND scheduledDate = '$escapedDate'
              AND status IN ('Pending','Scheduled','Ongoing')
              AND jobID != $jobID
            LIMIT 1
        ")->num_rows > 0;

        // Check vehicle availability on new date
        $vConflict = $conn->query("
            SELECT 1 FROM tbljob
            WHERE vehicleID = $newVehicleID
              AND scheduledDate = '$escapedDate'
              AND status IN ('Pending','Scheduled','Ongoing')
              AND jobID != $jobID
            LIMIT 1
        ")->num_rows > 0;

        $vMaintConflict = $conn->query("
            SELECT 1 FROM tblmaintenance
            WHERE vehicleID = $newVehicleID
              AND status IN ('Scheduled','In Progress')
              AND startDate <= '$escapedDate'
              AND (endDate IS NULL OR endDate >= '$escapedDate')
            LIMIT 1
        ")->num_rows > 0;

        if ($cConflict) {
            $_SESSION['errorMsg'] = 'Selected collector already has a job on ' . date('d M Y', strtotime($schedDate)) . '.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }
        if ($vConflict) {
            $_SESSION['errorMsg'] = 'Selected vehicle already has a job on ' . date('d M Y', strtotime($schedDate)) . '.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }
        if ($vMaintConflict) {
            $_SESSION['errorMsg'] = 'Selected vehicle has maintenance scheduled on ' . date('d M Y', strtotime($schedDate)) . '.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $conn->begin_transaction();
        try {
            // If re-reassigning, cancel the previous reassigned job first
            if ($cancelPrevID > 0) {
                $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Superseded by re-reassignment via issue #$issueID' WHERE jobID=$cancelPrevID AND status IN ('Pending','Scheduled')");
                logActivity($conn, $userID, 'Issue', 'Dropoff Reassignment Cancelled', "Issue #$issueID: previous reassigned job #$cancelPrevID cancelled due to re-reassignment.", $requestID, $cancelPrevID);
            }

            // Create new Scheduled job (emergency — scheduled right away)
            $ins = $conn->prepare("INSERT INTO tbljob (requestID, collectorID, vehicleID, scheduledDate, scheduledTime, estimatedEndTime, status) VALUES (?,?,?,?,?,?,'Scheduled')");
            $ins->bind_param('iiisss', $requestID, $newCollectorID, $newVehicleID, $schedDate, $schedTime, $endTime);
            $ins->execute();
            $newJobID = (int)$conn->insert_id;
            $conn->commit();
            $_SESSION['successMsg'] = 'Drop-off collector and vehicle reassigned. New job scheduled immediately.';

            $cName  = $conn->query("SELECT fullname FROM tblusers WHERE userID=$newCollectorID")->fetch_assoc()['fullname'] ?? "ID: $newCollectorID";
            $vPlate = $conn->query("SELECT plateNum FROM tblvehicle WHERE vehicleID=$newVehicleID")->fetch_assoc()['plateNum'] ?? "ID: $newVehicleID";
            $formattedDate = date('d M Y', strtotime($schedDate));
            logActivity($conn, $userID, 'Issue', 'Dropoff Collector+Vehicle Reassigned', "Issue #$issueID: drop-off reassigned to collector $cName (ID: $newCollectorID) with vehicle $vPlate (ID: $newVehicleID) on $formattedDate. New job #$newJobID created (Scheduled).", $requestID, $newJobID);
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Reschedule Collection
    if ($action === 'reschedule') {
        $jobID          = (int)($_POST['jobID']      ?? 0);
        $newDate        = $_POST['new_date']         ?? '';
        $newTime        = $_POST['new_time']         ?? '';
        $newEndTime     = $_POST['new_end_time']     ?? '';
        $newCollectorID = (int)($_POST['collectorID'] ?? 0);
        $newVehicleID   = (int)($_POST['vehicleID']   ?? 0);
        $centreAssign   = $_POST['centre_assignment'] ?? [];

        if ($jobID <= 0 || !$newDate || !$newTime || $newCollectorID <= 0 || $newVehicleID <= 0) {
            $_SESSION['errorMsg'] = 'Date, time, collector, and vehicle are required.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $jRow = $conn->query("SELECT collectorID, vehicleID, requestID FROM tbljob WHERE jobID=$jobID")->fetch_assoc();
        if (!$jRow) {
            $_SESSION['errorMsg'] = 'Job not found.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $requestID   = (int)$jRow['requestID'];
        $origCollID  = (int)$jRow['collectorID'];
        $origVehID   = (int)$jRow['vehicleID'];
        $escapedDate = $conn->real_escape_string($newDate);

        // Check collector conflict on new date (only if changed)
        if ($newCollectorID !== $origCollID) {
            $cConflict = $conn->query("
                SELECT 1 FROM tbljob
                WHERE collectorID = $newCollectorID
                  AND scheduledDate = '$escapedDate'
                  AND status IN ('Pending','Scheduled','Ongoing')
                  AND jobID != $jobID
                LIMIT 1
            ")->num_rows > 0;
            if ($cConflict) {
                $_SESSION['errorMsg'] = 'Selected collector already has a job on ' . date('d M Y', strtotime($newDate)) . '.';
                header("Location: aIssueDetail.php?id=$issueID"); exit;
            }
        }

        // Check vehicle conflict on new date (only if changed)
        if ($newVehicleID !== $origVehID) {
            $vConflict = $conn->query("
                SELECT 1 FROM tbljob
                WHERE vehicleID = $newVehicleID
                  AND scheduledDate = '$escapedDate'
                  AND status IN ('Pending','Scheduled','Ongoing')
                  AND jobID != $jobID
                LIMIT 1
            ")->num_rows > 0;
            $vMaintConflict = $conn->query("
                SELECT 1 FROM tblmaintenance
                WHERE vehicleID = $newVehicleID
                  AND status IN ('Scheduled','In Progress')
                  AND startDate <= '$escapedDate'
                  AND (endDate IS NULL OR endDate >= '$escapedDate')
                LIMIT 1
            ")->num_rows > 0;
            if ($vConflict) {
                $_SESSION['errorMsg'] = 'Selected vehicle already has a job on ' . date('d M Y', strtotime($newDate)) . '.';
                header("Location: aIssueDetail.php?id=$issueID"); exit;
            }
            if ($vMaintConflict) {
                $_SESSION['errorMsg'] = 'Selected vehicle has maintenance on ' . date('d M Y', strtotime($newDate)) . '.';
                header("Location: aIssueDetail.php?id=$issueID"); exit;
            }
        }

        // Cancel old job, create new job, update item centres
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Collection rescheduled via issue #$issueID' WHERE jobID=$jobID");
            $ins = $conn->prepare("INSERT INTO tbljob (requestID, collectorID, vehicleID, scheduledDate, scheduledTime, estimatedEndTime, status) VALUES (?,?,?,?,?,?,'Pending')");
            $ins->bind_param('iiisss', $requestID, $newCollectorID, $newVehicleID, $newDate, $newTime, $newEndTime);
            $ins->execute();
            $newJobID = (int)$conn->insert_id;

            // Update item centre assignments if provided
            foreach ($centreAssign as $itemID => $centreID) {
                $itemID   = (int)$itemID;
                $centreID = (int)$centreID;
                if ($itemID > 0 && $centreID > 0) {
                    $conn->query("UPDATE tblitem SET centreID = $centreID WHERE itemID = $itemID AND status = 'Pending'");
                }
            }

            $conn->commit();
            $_SESSION['successMsg'] = 'Collection rescheduled and a new job created.';

            $formattedNewDate = date('d M Y', strtotime($newDate));
            $cName  = $conn->query("SELECT fullname FROM tblusers WHERE userID=$newCollectorID")->fetch_assoc()['fullname'] ?? "ID: $newCollectorID";
            $vPlate = $conn->query("SELECT plateNum FROM tblvehicle WHERE vehicleID=$newVehicleID")->fetch_assoc()['plateNum'] ?? "ID: $newVehicleID";
            logActivity($conn, $userID, 'Issue', 'Collection Rescheduled', "Issue #$issueID: collection rescheduled to $formattedNewDate at $newTime with collector $cName and vehicle $vPlate. Old job #$jobID cancelled, new job #$newJobID created.", $requestID, $newJobID);
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Reschedule Request (request-only, no job)
    if ($action === 'reschedule_request') {
        $requestID   = (int)($_POST['requestID'] ?? 0);
        $newDateTime = trim($_POST['new_datetime'] ?? '');

        if ($requestID <= 0 || !$newDateTime) {
            $_SESSION['errorMsg'] = 'A new date and time are required.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $iRow = $conn->query("SELECT requestID, jobID FROM tblissue WHERE issueID=$issueID")->fetch_assoc();

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE tblcollection_request SET preferredDateTime = ? WHERE requestID = ?");
            $stmt->bind_param('si', $newDateTime, $requestID);
            $stmt->execute();

            if ($stmt->affected_rows <= 0) {
                throw new Exception('No rows updated. The request may not exist.');
            }
            $stmt->close();

            $formattedDT = date('d M Y, g:i A', strtotime($newDateTime));
            logActivity($conn, $userID, 'Issue', 'Request Rescheduled', "Issue #$issueID: request #$requestID preferred date/time updated to $formattedDT.", $iRow['requestID'] ?? null, $iRow['jobID'] ?? null);

            $conn->commit();
            $_SESSION['successMsg'] = 'Request preferred date/time updated successfully.';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Failed to update the request date/time: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    if ($action === 'reschedule_request_with_job') {
        $requestID   = (int)($_POST['requestID']      ?? 0);
        $newDateTime = trim($_POST['new_datetime']     ?? '');
        $jobID       = (int)($_POST['jobID']           ?? 0);
        $newDate     = trim($_POST['new_date']         ?? '');
        $newTime     = trim($_POST['new_time']         ?? '');
        $newEndTime  = trim($_POST['new_end_time']     ?? '');
        $newCollectorID = (int)($_POST['collectorID']  ?? 0);
        $newVehicleID   = (int)($_POST['vehicleID']    ?? 0);

        if ($requestID <= 0 || !$newDateTime || $jobID <= 0 || !$newDate || !$newTime || $newCollectorID <= 0 || $newVehicleID <= 0) {
            $_SESSION['errorMsg'] = 'All fields are required.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $escapedDate = $conn->real_escape_string($newDate);

        $jRow = $conn->query("SELECT collectorID, vehicleID FROM tbljob WHERE jobID=$jobID")->fetch_assoc();
        $origCollID = (int)($jRow['collectorID'] ?? 0);
        $origVehID  = (int)($jRow['vehicleID']   ?? 0);

        if ($newCollectorID !== $origCollID) {
            $cConflict = $conn->query("
                SELECT 1 FROM tbljob
                WHERE collectorID = $newCollectorID
                AND scheduledDate = '$escapedDate'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jobID
                LIMIT 1
            ")->num_rows > 0;
            if ($cConflict) {
                $_SESSION['errorMsg'] = 'Selected collector already has a job on ' . date('d M Y', strtotime($newDate)) . '.';
                header("Location: aIssueDetail.php?id=$issueID"); exit;
            }
        }

        if ($newVehicleID !== $origVehID) {
            $vConflict = $conn->query("
                SELECT 1 FROM tbljob
                WHERE vehicleID = $newVehicleID
                AND scheduledDate = '$escapedDate'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jobID
                LIMIT 1
            ")->num_rows > 0;
            $vMaintConflict = $conn->query("
                SELECT 1 FROM tblmaintenance
                WHERE vehicleID = $newVehicleID
                AND status IN ('Scheduled','In Progress')
                AND startDate <= '$escapedDate'
                AND (endDate IS NULL OR endDate >= '$escapedDate')
                LIMIT 1
            ")->num_rows > 0;
            if ($vConflict) {
                $_SESSION['errorMsg'] = 'Selected vehicle already has a job on ' . date('d M Y', strtotime($newDate)) . '.';
                header("Location: aIssueDetail.php?id=$issueID"); exit;
            }
            if ($vMaintConflict) {
                $_SESSION['errorMsg'] = 'Selected vehicle has maintenance on ' . date('d M Y', strtotime($newDate)) . '.';
                header("Location: aIssueDetail.php?id=$issueID"); exit;
            }
        }

        $conn->begin_transaction();
        try {
            $conn->query("UPDATE tblcollection_request SET preferredDateTime = '$newDateTime' WHERE requestID = $requestID");

            $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Rescheduled via issue #$issueID' WHERE jobID=$jobID");

            $ins = $conn->prepare("INSERT INTO tbljob (requestID, collectorID, vehicleID, scheduledDate, scheduledTime, estimatedEndTime, status) VALUES (?,?,?,?,?,?,'Pending')");
            $ins->bind_param('iiisss', $requestID, $newCollectorID, $newVehicleID, $newDate, $newTime, $newEndTime);
            $ins->execute();
            $newJobID = (int)$conn->insert_id;
            $conn->commit();

            $formattedDT = date('d M Y, g:i A', strtotime($newDateTime));
            $cName  = $conn->query("SELECT fullname FROM tblusers WHERE userID=$newCollectorID")->fetch_assoc()['fullname'] ?? "ID: $newCollectorID";
            $vPlate = $conn->query("SELECT plateNum FROM tblvehicle WHERE vehicleID=$newVehicleID")->fetch_assoc()['plateNum'] ?? "ID: $newVehicleID";
            logActivity($conn, $userID, 'Issue', 'Request + Job Rescheduled', "Issue #$issueID: request #$requestID preferred datetime updated to $formattedDT. Old job #$jobID cancelled, new job #$newJobID created with collector $cName and vehicle $vPlate.", $requestID, $newJobID);

            $_SESSION['successMsg'] = 'Request rescheduled and job updated successfully.';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Reassign Item(s) to Centre
    if ($action === 'reassign_centre') {
        $assignments = $_POST['centre_assignment'] ?? [];

        if (empty($assignments)) {
            $_SESSION['errorMsg'] = 'No assignments submitted.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $iRow = $conn->query("SELECT requestID, jobID FROM tblissue WHERE issueID=$issueID")->fetch_assoc();
        $logRequestID = $iRow['requestID'] ?? null;
        $logJobID     = $iRow['jobID']     ?? null;

        $conn->begin_transaction();
        try {
            $logDetails = [];
            foreach ($assignments as $itemID => $centreID) {
                $itemID   = (int)$itemID;
                $centreID = (int)$centreID;
                if ($itemID <= 0 || $centreID <= 0) continue;
                $conn->query("UPDATE tblitem SET centreID = $centreID WHERE itemID = $itemID AND status = 'Collected'");

                // Fetch names for log
                $ctrName  = $conn->query("SELECT name FROM tblcentre WHERE centreID=$centreID")->fetch_assoc()['name'] ?? "ID: $centreID";
                $itemDesc = $conn->query("SELECT description FROM tblitem WHERE itemID=$itemID")->fetch_assoc()['description'] ?? "ID: $itemID";
                $logDetails[] = "Item #$itemID ($itemDesc) → $ctrName";

                logActivity($conn, $userID, 'Issue', 'Centre Reassigned', "Issue #$issueID: item #$itemID ($itemDesc) reassigned to centre $ctrName (ID: $centreID).", $logRequestID, $logJobID);
            }
            $conn->commit();
            $_SESSION['successMsg'] = 'Item drop-off centre assignments updated successfully.';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Cancel Request
    if ($action === 'cancel_request') {
        $jobID     = (int)($_POST['jobID']     ?? 0);
        $requestID = (int)($_POST['requestID'] ?? 0);
        $reason    = trim($_POST['reason']     ?? '');
        $escapedReason = $conn->real_escape_string($reason);

        if (!$reason) {
            $_SESSION['errorMsg'] = 'A reason is required to cancel the request.';
            header("Location: aIssueDetail.php?id=$issueID"); 
            exit;
        }

        $conn->begin_transaction();
        try {
            if ($jobID > 0) {
                $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='$escapedReason' WHERE jobID=$jobID");
            }
            $conn->query("UPDATE tblcollection_request SET status='Rejected', rejectionReason='$escapedReason' WHERE requestID=$requestID");
            $conn->commit();
            $_SESSION['successMsg'] = 'Request and job cancelled successfully.';

            logActivity($conn, $userID, 'Issue', 'Request Cancelled', "Issue #$issueID: request #$requestID cancelled via issue. Reason: $reason", $requestID, $jobID > 0 ? $jobID : null);
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Suspend Collector
    // The linked job MUST be reassigned, all other jobs cancelled.
    // Availability check: ±1 day of each job's scheduledDate.

    if ($action === 'suspend_collector') {
        $targetCollectorID  = (int)($_POST['targetCollectorID']  ?? 0);
        $linkedJobID        = (int)($_POST['linkedJobID']        ?? 0);
        $linkedNewCollector = (int)($_POST['linkedNewCollector'] ?? 0);
        $linkedNewVehicle   = (int)($_POST['linkedNewVehicle']   ?? 0);

        if ($targetCollectorID <= 0 || $linkedJobID <= 0 || $linkedNewCollector <= 0) {
            $_SESSION['errorMsg'] = 'A replacement collector must be selected before suspending.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        // Fetch collector name for logging
        $cName = $conn->query("SELECT fullname FROM tblusers WHERE userID=$targetCollectorID")->fetch_assoc()['fullname'] ?? "ID: $targetCollectorID";

        // Fetch all active jobs for this collector
        $allJobsRes = $conn->query("
            SELECT j.jobID, j.requestID, j.vehicleID, j.scheduledDate, j.scheduledTime, j.estimatedEndTime
            FROM tbljob j
            WHERE j.collectorID = $targetCollectorID
              AND j.status IN ('Pending','Scheduled','Ongoing')
        ");
        $allJobs = [];
        while ($r = $allJobsRes->fetch_assoc()) $allJobs[] = $r;

        $conn->begin_transaction();
        try {
            foreach ($allJobs as $job) {
                $jID  = (int)$job['jobID'];
                $jReq = (int)$job['requestID'];
                $jVeh = (int)$job['vehicleID'];
                $jDate = $job['scheduledDate'];
                $jTime = $job['scheduledTime'];
                $jEnd  = $job['estimatedEndTime'];

                if ($jID === $linkedJobID) {
                    $dateObj   = new DateTime($jDate);
                    $dayBefore = (clone $dateObj)->modify('-1 day')->format('Y-m-d');
                    $dayAfter  = (clone $dateObj)->modify('+1 day')->format('Y-m-d');
                    $esc1 = $conn->real_escape_string($dayBefore);
                    $esc2 = $conn->real_escape_string($dayAfter);

                    $cConflict = $conn->query("
                        SELECT 1 FROM tbljob
                        WHERE collectorID = $linkedNewCollector
                          AND scheduledDate BETWEEN '$esc1' AND '$esc2'
                          AND status IN ('Pending','Scheduled','Ongoing')
                        LIMIT 1
                    ")->num_rows > 0;

                    if ($cConflict) {
                        throw new Exception("Selected replacement collector is unavailable within ±1 day of job #$jID.");
                    }

                    // Determine vehicle: use new vehicle if provided, else keep original
                    $useVehicle = ($linkedNewVehicle > 0) ? $linkedNewVehicle : $jVeh;
                    $escapedJDate = $conn->real_escape_string($jDate);

                    if ($linkedNewVehicle > 0) {
                        $vConflict = $conn->query("
                            SELECT 1 FROM tbljob
                            WHERE vehicleID = $linkedNewVehicle
                              AND scheduledDate BETWEEN '$esc1' AND '$esc2'
                              AND status IN ('Pending','Scheduled','Ongoing')
                            LIMIT 1
                        ")->num_rows > 0;
                        $vMaint = $conn->query("
                            SELECT 1 FROM tblmaintenance
                            WHERE vehicleID = $linkedNewVehicle
                              AND status IN ('Scheduled','In Progress')
                              AND startDate <= '$escapedJDate'
                              AND (endDate IS NULL OR endDate >= '$escapedJDate')
                            LIMIT 1
                        ")->num_rows > 0;
                        if ($vConflict || $vMaint) {
                            throw new Exception("Selected replacement vehicle is unavailable for job #$jID.");
                        }
                    }

                    // Cancel old job, create reassigned job
                    $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Collector suspended via issue #$issueID' WHERE jobID=$jID");
                    $ins = $conn->prepare("INSERT INTO tbljob (requestID, collectorID, vehicleID, scheduledDate, scheduledTime, estimatedEndTime, status) VALUES (?,?,?,?,?,?,'Pending')");
                    $ins->bind_param('iiisss', $jReq, $linkedNewCollector, $useVehicle, $jDate, $jTime, $jEnd);
                    $ins->execute();
                    $newJID = (int)$conn->insert_id;
                    logActivity($conn, $userID, 'Issue', 'Collector Suspended - Job Reassigned', "Issue #$issueID: job #$jID reassigned due to collector suspension. Old job cancelled, new job #$newJID created.", $jReq, $newJID);

                } else {
                    // All other jobs — cancel
                    $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Collector suspended via issue #$issueID' WHERE jobID=$jID");
                    logActivity($conn, $userID, 'Issue', 'Collector Suspended - Job Cancelled', "Issue #$issueID: job #$jID cancelled due to collector suspension.", $jReq, $jID);
                }
            }

            // Suspend the collector
            $conn->query("UPDATE tblcollector SET status='suspended' WHERE collectorID=$targetCollectorID");
            $conn->commit();

            $_SESSION['successMsg'] = "Collector $cName has been suspended. The linked job was reassigned and all other active jobs were cancelled.";
            logActivity($conn, $userID, 'Issue', 'Collector Suspended', "Issue #$issueID: collector $cName (ID: $targetCollectorID) suspended.", null, null);

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Suspension failed: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }

    // Suspend Provider
    // Current request cancelled
    // pending -> rejected
    // approved/scheduled, cancelled with jobs.

    if ($action === 'suspend_provider') {
        $targetProviderID  = (int)($_POST['targetProviderID']  ?? 0);
        $linkedRequestID   = (int)($_POST['linkedRequestID']   ?? 0);
        $linkedJobIDForReq = (int)($_POST['linkedJobIDForReq'] ?? 0);

        if ($targetProviderID <= 0) {
            $_SESSION['errorMsg'] = 'Invalid provider data.';
            header("Location: aIssueDetail.php?id=$issueID"); exit;
        }

        $pName = $conn->query("SELECT fullname FROM tblusers WHERE userID=$targetProviderID")->fetch_assoc()['fullname'] ?? "ID: $targetProviderID";

        $conn->begin_transaction();
        try {
            // Cancel the current linked request and its job
            if ($linkedRequestID > 0) {
                if ($linkedJobIDForReq > 0) {
                    $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Provider suspended via issue #$issueID' WHERE jobID=$linkedJobIDForReq");
                    logActivity($conn, $userID, 'Issue', 'Provider Suspended - Job Cancelled', "Issue #$issueID: job #$linkedJobIDForReq cancelled due to provider suspension.", $linkedRequestID, $linkedJobIDForReq);
                }
                $conn->query("UPDATE tblcollection_request SET status='Rejected', rejectionReason='Provider suspended via issue #$issueID' WHERE requestID=$linkedRequestID");
                logActivity($conn, $userID, 'Issue', 'Provider Suspended - Request Cancelled', "Issue #$issueID: current request #$linkedRequestID cancelled due to provider suspension.", $linkedRequestID, null);
            }

            // Process all other non-terminal requests for this provider
            $otherReqRes = $conn->query("
                SELECT requestID, status FROM tblcollection_request
                WHERE providerID = $targetProviderID
                  AND requestID != $linkedRequestID
                  AND status NOT IN ('Completed','Rejected','Cancelled')
            ");
            while ($oReq = $otherReqRes->fetch_assoc()) {
                $oRID    = (int)$oReq['requestID'];
                $oStatus = $oReq['status'];

                if ($oStatus === 'Pending') {
                    // Pending → Rejected
                    $conn->query("UPDATE tblcollection_request SET status='Rejected', rejectionReason='Provider suspended' WHERE requestID=$oRID");
                    logActivity($conn, $userID, 'Issue', 'Provider Suspended - Request Rejected', "Issue #$issueID: pending request #$oRID rejected due to provider suspension.", $oRID, null);
                } else {
                    // Approved / Scheduled → Rejected; cancel their active jobs too
                    $conn->query("UPDATE tblcollection_request SET status='Rejected', rejectionReason='Provider suspended' WHERE requestID=$oRID");
                    $relJobRes = $conn->query("SELECT jobID FROM tbljob WHERE requestID=$oRID AND status IN ('Pending','Scheduled','Ongoing')");
                    while ($rj = $relJobRes->fetch_assoc()) {
                        $rjID = (int)$rj['jobID'];
                        $conn->query("UPDATE tbljob SET status='Cancelled', rejectionReason='Provider suspended via issue #$issueID' WHERE jobID=$rjID");
                        logActivity($conn, $userID, 'Issue', 'Provider Suspended - Job Cancelled', "Issue #$issueID: job #$rjID cancelled due to provider suspension.", $oRID, $rjID);
                    }
                    logActivity($conn, $userID, 'Issue', 'Provider Suspended - Request Cancelled', "Issue #$issueID: approved/scheduled request #$oRID cancelled due to provider suspension.", $oRID, null);
                }
            }

            // Suspend the provider
            $conn->query("UPDATE tblprovider SET suspended=1 WHERE providerID=$targetProviderID");
            $conn->commit();

            $_SESSION['successMsg'] = "Provider $pName has been suspended. All linked requests and jobs have been cancelled.";
            logActivity($conn, $userID, 'Issue', 'Provider Suspended', "Issue #$issueID: provider $pName (ID: $targetProviderID) suspended.", null, null);

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Suspension failed: ' . sanitize($e->getMessage());
        }
        header("Location: aIssueDetail.php?id=$issueID"); exit;
    }
}

// Fetch issue 
$issueRow = $conn->query("
    SELECT i.*,
           u.fullname  AS reportedByName,
           a.fullname  AS assignedAdminName
    FROM tblissue i
    JOIN tblusers u ON i.reportedBy       = u.userID
    LEFT JOIN tblusers a ON i.assignedAdminID = a.userID
    WHERE i.issueID = $issueID
")->fetch_assoc();

if (!$issueRow) {
    header('Location: aIssue.php'); exit;
}

// Fetch linked job (most recent non-cancelled) 
$jobRow = null;
if (!empty($issueRow['jobID'])) $jobRow = $conn->query("
    SELECT j.*,
           uc.fullname  AS collectorName,
           v.plateNum   AS vehiclePlate,
           v.model      AS vehicleModel,
           v.type       AS vehicleType,
           v.capacityWeight AS vehicleCapacity
    FROM tbljob j
    JOIN tblcollector c  ON j.collectorID = c.collectorID
    JOIN tblusers uc     ON c.collectorID = uc.userID
    JOIN tblvehicle v    ON j.vehicleID   = v.vehicleID
    WHERE j.jobID = {$issueRow['jobID']}
")->fetch_assoc();

// Fetch linked request (always, since requestID is NOT NULL)
$reqRow = $conn->query("SELECT * FROM tblcollection_request WHERE requestID={$issueRow['requestID']}")->fetch_assoc();

$pendingJobForReq = null;
if ($reqRow) {
    $pjRes = $conn->query("
        SELECT j.jobID, j.collectorID, j.vehicleID, j.scheduledDate, j.scheduledTime, j.estimatedEndTime,
               uc.fullname AS collectorName,
               v.plateNum AS vehiclePlate, v.model AS vehicleModel, v.type AS vehicleType
        FROM tbljob j
        JOIN tblcollector c ON j.collectorID = c.collectorID
        JOIN tblusers uc ON c.collectorID = uc.userID
        JOIN tblvehicle v ON j.vehicleID = v.vehicleID
        WHERE j.requestID = {$reqRow['requestID']}
          AND j.status IN ('Pending','Scheduled')
        ORDER BY j.jobID DESC
        LIMIT 1
    ");
    $pendingJobForReq = $pjRes ? $pjRes->fetch_assoc() : null;
}

// Determine pickup / dropoff state from tblitem 
// pickup collected = all items for this request have status 'Collected' or beyond
$pickupDone  = false;
$dropoffDone = false;
if ($reqRow) {
    $rid  = (int)$reqRow['requestID'];
    $total = (int)$conn->query("SELECT COUNT(*) AS n FROM tblitem WHERE requestID=$rid")->fetch_assoc()['n'];
    if ($total > 0) {
        $collected = (int)$conn->query("SELECT COUNT(*) AS n FROM tblitem WHERE requestID=$rid AND status IN ('Collected','Received','Processed','Recycled')")->fetch_assoc()['n'];
        $received  = (int)$conn->query("SELECT COUNT(*) AS n FROM tblitem WHERE requestID=$rid AND status IN ('Received','Processed','Recycled')")->fetch_assoc()['n'];
        $pickupDone  = ($collected >= $total);
        $dropoffDone = ($received  >= $total);
    }
}

// Fetch items with status 'Collected' (picked up, not yet received) for centre reassignment
$collectedItems = [];
if ($reqRow) {
    $rid = (int)$reqRow['requestID'];
    $res = $conn->query("
        SELECT i.itemID, i.description, i.centreID, i.itemTypeID,
               it.name AS itemTypeName
        FROM tblitem i
        JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID
        WHERE i.requestID = $rid
          AND i.status = 'Collected'
    ");
    while ($r = $res->fetch_assoc()) $collectedItems[] = $r;
}

// Fetch all active centres for reassignment dropdown
// Use pickup postcode to sort by nearest (simple numeric postcode proximity)
$activeCentres = [];
if ($reqRow) {
    $pickupPostcode = (int)$reqRow['pickupPostcode'];
    $res = $conn->query("SELECT centreID, name, address, state, postcode FROM tblcentre WHERE status = 'Active' ORDER BY name");
    while ($r = $res->fetch_assoc()) {
        $r['_distance'] = abs((int)$r['postcode'] - $pickupPostcode);
        // Fetch accepted item types for this centre
        $cID = (int)$r['centreID'];
        $typeRes = $conn->query("SELECT itemTypeID FROM tblcentre_accepted_type WHERE centreID = $cID");
        $r['_acceptedTypes'] = [];
        while ($tr = $typeRes->fetch_assoc()) {
            $r['_acceptedTypes'][] = (int)$tr['itemTypeID'];
        }
        $activeCentres[] = $r;
    }
    usort($activeCentres, fn($a, $b) => $a['_distance'] - $b['_distance']);
}

// Available collectors on job date (no job ±0 same day)
$availCollectors = [];
if ($jobRow) {
    $jDate = $conn->real_escape_string($jobRow['scheduledDate']);
    $jID   = (int)$jobRow['jobID'];
    $res   = $conn->query("
        SELECT c.collectorID, u.fullname, u.phone
        FROM tblcollector c
        JOIN tblusers u ON c.collectorID = u.userID
        WHERE c.status = 'active'
          AND c.collectorID != {$jobRow['collectorID']}
          AND c.collectorID NOT IN (
              SELECT collectorID FROM tbljob
              WHERE scheduledDate = '$jDate'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jID
          )
        ORDER BY u.fullname
    ");
    while ($r = $res->fetch_assoc()) $availCollectors[] = $r;
}

// Available vehicles on job date 
$availVehicles = [];
if ($jobRow) {
    $jDate = $conn->real_escape_string($jobRow['scheduledDate']);
    $jID   = (int)$jobRow['jobID'];
    $res   = $conn->query("
        SELECT v.vehicleID, v.plateNum, v.model, v.type, v.capacityWeight
        FROM tblvehicle v
        WHERE v.status = 'Available'
          AND v.vehicleID != {$jobRow['vehicleID']}
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tbljob
              WHERE scheduledDate = '$jDate'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jID
          )
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tblmaintenance
              WHERE status IN ('Scheduled','In Progress')
                AND startDate <= '$jDate'
                AND (endDate IS NULL OR endDate >= '$jDate')
          )
        ORDER BY v.plateNum
    ");
    while ($r = $res->fetch_assoc()) $availVehicles[] = $r;
}

$reqOnlyAvailCollectors = [];
$reqOnlyAvailVehicles   = [];
if (!$jobRow && $pendingJobForReq) {
    $pjDate = $conn->real_escape_string($pendingJobForReq['scheduledDate']);
    $pjID   = (int)$pendingJobForReq['jobID'];

    $res = $conn->query("
        SELECT c.collectorID, u.fullname, u.phone
        FROM tblcollector c
        JOIN tblusers u ON c.collectorID = u.userID
        WHERE c.status = 'active'
          AND c.collectorID != {$pendingJobForReq['collectorID']}
          AND c.collectorID NOT IN (
              SELECT collectorID FROM tbljob
              WHERE scheduledDate = '$pjDate'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $pjID
          )
        ORDER BY u.fullname
    ");
    while ($r = $res->fetch_assoc()) $reqOnlyAvailCollectors[] = $r;

    $res2 = $conn->query("
        SELECT v.vehicleID, v.plateNum, v.model, v.type, v.capacityWeight
        FROM tblvehicle v
        WHERE v.status = 'Available'
          AND v.vehicleID != {$pendingJobForReq['vehicleID']}
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tbljob
              WHERE scheduledDate = '$pjDate'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $pjID
          )
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tblmaintenance
              WHERE status IN ('Scheduled','In Progress')
                AND startDate <= '$pjDate'
                AND (endDate IS NULL OR endDate >= '$pjDate')
          )
        ORDER BY v.plateNum
    ");
    while ($r = $res2->fetch_assoc()) $reqOnlyAvailVehicles[] = $r;
}

// Available collectors for dropoff reassignment (today's date, excluding current)
$dropoffAvailCollectors = [];
$dropoffAvailVehicles   = [];
if ($jobRow && $pickupDone && !$dropoffDone) {
    $todayEsc = $conn->real_escape_string(date('Y-m-d'));
    $jID      = (int)$jobRow['jobID'];

    $res = $conn->query("
        SELECT c.collectorID, u.fullname, u.phone
        FROM tblcollector c
        JOIN tblusers u ON c.collectorID = u.userID
        WHERE c.status = 'active'
          AND c.collectorID != {$jobRow['collectorID']}
          AND c.collectorID NOT IN (
              SELECT collectorID FROM tbljob
              WHERE scheduledDate = '$todayEsc'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jID
          )
        ORDER BY u.fullname
    ");
    while ($r = $res->fetch_assoc()) $dropoffAvailCollectors[] = $r;

    $res2 = $conn->query("
        SELECT v.vehicleID, v.plateNum, v.model, v.type, v.capacityWeight
        FROM tblvehicle v
        WHERE v.status = 'Available'
          AND v.vehicleID != {$jobRow['vehicleID']}
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tbljob
              WHERE scheduledDate = '$todayEsc'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jID
          )
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tblmaintenance
              WHERE status IN ('Scheduled','In Progress')
                AND startDate <= '$todayEsc'
                AND (endDate IS NULL OR endDate >= '$todayEsc')
          )
        ORDER BY v.plateNum
    ");
    while ($r = $res2->fetch_assoc()) $dropoffAvailVehicles[] = $r;
}

// Available collectors for suspension reassignment (±1 day check)
$suspendAvailCollectors = [];
$suspendAvailVehicles   = [];
if ($jobRow) {
    $jDate   = $jobRow['scheduledDate'];
    $jID     = (int)$jobRow['jobID'];
    $dateObj = new DateTime($jDate);
    $esc1    = $conn->real_escape_string((clone $dateObj)->modify('-1 day')->format('Y-m-d'));
    $esc2    = $conn->real_escape_string((clone $dateObj)->modify('+1 day')->format('Y-m-d'));
    $escDate = $conn->real_escape_string($jDate);

    $res = $conn->query("
        SELECT c.collectorID, u.fullname, u.phone
        FROM tblcollector c
        JOIN tblusers u ON c.collectorID = u.userID
        WHERE c.status = 'active'
          AND c.collectorID != {$jobRow['collectorID']}
          AND c.collectorID NOT IN (
              SELECT collectorID FROM tbljob
              WHERE scheduledDate BETWEEN '$esc1' AND '$esc2'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jID
          )
        ORDER BY u.fullname
    ");
    while ($r = $res->fetch_assoc()) $suspendAvailCollectors[] = $r;

    $res2 = $conn->query("
        SELECT v.vehicleID, v.plateNum, v.model, v.type, v.capacityWeight
        FROM tblvehicle v
        WHERE v.status = 'Available'
          AND v.vehicleID != {$jobRow['vehicleID']}
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tbljob
              WHERE scheduledDate BETWEEN '$esc1' AND '$esc2'
                AND status IN ('Pending','Scheduled','Ongoing')
                AND jobID != $jID
          )
          AND v.vehicleID NOT IN (
              SELECT vehicleID FROM tblmaintenance
              WHERE status IN ('Scheduled','In Progress')
                AND startDate <= '$escDate'
                AND (endDate IS NULL OR endDate >= '$escDate')
          )
        ORDER BY v.plateNum
    ");
    while ($r = $res2->fetch_assoc()) $suspendAvailVehicles[] = $r;
}

// Fetch collector suspension status for the linked job's collector
$collectorSuspended = false;
$collectorInfo      = null;
if ($jobRow) {
    $collectorInfo = $conn->query("
        SELECT c.status AS collectorStatus, u.fullname, u.userID
        FROM tblcollector c
        JOIN tblusers u ON c.collectorID = u.userID
        WHERE c.collectorID = {$jobRow['collectorID']}
    ")->fetch_assoc();
    $collectorSuspended = ($collectorInfo && $collectorInfo['collectorStatus'] === 'suspended');
}

// Fetch provider suspension status for the linked request's provider
$providerSuspended = false;
$providerInfo      = null;
if ($reqRow) {
    $providerInfo = $conn->query("
        SELECT p.suspended, u.fullname, u.userID, p.providerID
        FROM tblprovider p
        JOIN tblusers u ON p.providerID = u.userID
        WHERE p.providerID = {$reqRow['providerID']}
    ")->fetch_assoc();
    $providerSuspended = ($providerInfo && (int)$providerInfo['suspended'] === 1);
}

// Fetch activity log entries for this issue (for "Actions Performed" section)
$activityLog = [];
if ($issueRow) {
    $rid = (int)$issueRow['requestID'];
    $jid = !empty($issueRow['jobID']) ? (int)$issueRow['jobID'] : 0;

    // Fetch log entries that reference this issue in description, ordered by time
    $escapedIssueRef = $conn->real_escape_string("issue #$issueID");
    $res = $conn->query("
        SELECT l.logID, l.action, l.description, l.dateTime, u.fullname AS adminName
        FROM tblactivity_log l
        JOIN tblusers u ON l.userID = u.userID
        WHERE l.description LIKE '%$escapedIssueRef%'
        ORDER BY l.dateTime ASC
    ");
    while ($r = $res->fetch_assoc()) $activityLog[] = $r;
}

// Check if a previous dropoff reassignment job exists (Pending or Scheduled, created after the issue)
// so we can offer re-reassignment with cancellation of the previous one
$prevDropoffJobID = 0;
if ($jobRow && $pickupDone && !$dropoffDone && $issueRow['jobID']) {
    $origJobID = (int)$issueRow['jobID'];
    $rid       = (int)$issueRow['requestID'];
    // Find most recent Pending/Scheduled job for this request that is NOT the original job
    $prevRow = $conn->query("
        SELECT jobID FROM tbljob
        WHERE requestID = $rid
          AND jobID != $origJobID
          AND status IN ('Pending','Scheduled')
        ORDER BY jobID DESC
        LIMIT 1
    ")->fetch_assoc();
    if ($prevRow) $prevDropoffJobID = (int)$prevRow['jobID'];
}

// Permission flags 
$isAssignedToMe = ((int)$issueRow['assignedAdminID'] === $userID);
$isOpen         = ($issueRow['status'] === 'Open');
$isAssigned     = ($issueRow['status'] === 'Assigned');
$isResolved     = ($issueRow['status'] === 'Resolved');
// Admin can act if: issue is Assigned AND assigned to current user
$canAct         = $isAssigned && $isAssignedToMe;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Issue #<?php echo $issueID; ?> - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
        .detail-container {
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: none;
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--Gray);
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.2s, color 0.2s;
        }

        .back-btn:hover {
            border-color: var(--MainBlue);
            color: var(--MainBlue);
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-color);
        }

        .page-title span {
            color: var(--Gray);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .status-banner {
            border-radius: 10px;
            padding: 0.75rem 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-banner {
            background: var(--sec-bg-color);
            color: var(--text-color);
            border: 1px solid var(--BlueGray);
        }

        .card {
            background-color: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 14px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .card-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--BlueGray);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark-mode .card-title {
            color: var(--Gray);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.5rem;
        }

        @media (max-width: 600px) {
            .info-grid { 
                grid-template-columns: 1fr; 
            }
        }

        .info-grid.full .info-field {
            grid-column: 1 / -1;
        }

        .info-field {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .info-field.span-full {
            grid-column: 1 / -1;
        }

        .info-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--Gray);
        }

        .dark-mode .info-label {
            color: var(--BlueGray);
        }

        .info-value {
            font-size: 0.9rem;
            color: var(--text-color);
            font-weight: 500;
            line-height: 1.5;
        }

        .info-value.muted {
            color: var(--Gray);
            font-style: italic;
            font-weight: 400;
        }

        .badge-severity, 
        .badge-status, 
        .badge-type, 
        .badge-yn {
            display: inline-block;
            border-radius: 6px;
            padding: 0.2rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .severity-low { 
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .severity-medium   { 
            background: hsl(45,  90%, 88%); 
            color: hsl(35,  80%, 30%); 
        }

        .severity-high { 
            background: hsl(25,  90%, 88%); 
            color: hsl(20,  80%, 30%); 
        }

        .severity-critical { 
            background: hsl(0,   70%, 90%); 
            color: hsl(0,   70%, 35%); 
        }

        .status-open { 
            background: hsl(225, 80%, 92%); 
            color: var(--DarkerMainBlue); 
        }

        .status-assigned { 
            background: hsl(30, 80%, 90%);  
            color: hsl(25, 80%, 30%); 
        }

        .status-resolved { 
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .badge-type {
            background-color: var(--DarkBlue);
            color: var(--White);
        }

        .badge-yes { 
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .badge-no { 
            background: hsl(0, 60%, 92%);
            color: red; 
        }

        .linked-id {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.85rem;
            border-radius: 6px;
            color: var(--MainBlue);
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s;
        }

        .linked-id:hover { 
            text-decoration: underline; 
        }

        .assign-banner {
            background: var(--bg-color);
            border: 1px solid var(--MainBlue);
            border-radius: 12px;
            padding: 1.1rem 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .assign-banner-text strong {
            display: block;
            font-size: 0.95rem;
            color: var(--MainBlue);
            font-weight: 700;
            margin-bottom: 0.15rem;
        }

        .assign-banner-text p {
            font-size: 0.8rem;
            color: var(--Gray);
        }

        .dark-mode .assign-banner-text p {
            color: var(--BlueGray);
        }

        .action-section-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--Gray);
            margin-bottom: 0.75rem;
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-bottom: 1.25rem;
        }

        .section-divider {
            height: 1px;
            background-color: var(--BlueGray);
            margin: 1.25rem 0;
            opacity: 0.5;
            border: none;
        }

        .locked-msg {
            text-align: center;
            padding: 1.5rem 1rem;
            color: var(--Gray);
        }

        .locked-msg p {
            font-size: 0.9rem;
        }

        .locked-msg img {
            width: 2.5rem;
            height: 2.5rem;
            margin-bottom: 0.75rem;
        }

        button {
            font-size: 0.9rem;
        }

        button:active { 
            transform: scale(0.97); 
        }

        .cancel-btn {
            background: var(--Gray);
            color: var(--White);
        }

        .cancel-btn:hover { 
            background: var(--BlueGray); 
        }

        .dark-mode .c-btn-primary {
            background: var(--DarkerMainBlue);
            color: var(--White);
        }

        .btn-danger { 
            background: hsl(0, 70%, 90%); 
            color: red; 
        }

        .btn-danger:hover { 
            background: hsl(0, 65%, 84%); 
        }

        .btn-warning { 
            background: hsl(35, 90%, 90%); 
            color: hsl(30, 80%, 30%); 
        }

        .btn-warning:hover { 
            background: hsl(35, 85%, 83%); 
        }

        .btn-success { 
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 55%, 28%); 
        }

        .btn-success:hover { 
            background: hsl(145, 48%, 80%); 
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1rem;
        }

        .form-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--Gray);
        }

        .form-select, 
        .form-textarea, 
        .form-input {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            transition: border-color 0.2s;
            font-family: inherit;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }

        .form-select:focus, 
        .form-textarea:focus, 
        .form-input:focus {
            outline: none;
            border-color: var(--MainBlue);
        }

        .form-textarea {
            resize: vertical;
            min-height: 90px;
        }

        .form-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: 0.25rem;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-overlay.active { 
            display: flex; 
        }

        .modal {
            background: var(--bg-color);
            border-radius: 14px;
            padding: 1.75rem;
            width: 100%;
            max-width: 440px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            animation: modalIn 0.18s ease;
            position: relative;
        }

        .modal.modal-wide {
            max-width: 520px;
        }

        @keyframes modalIn {
            from { transform: translateY(16px) scale(0.97); opacity: 0; }
            to   { transform: none; opacity: 1; }
        }

        .modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1.1rem;
        }

        .modal-desc {
            font-size: 0.85rem;
            color: var(--Gray);
            margin-bottom: 1rem;
        }

        .required-star {
            color: red;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1.25rem;
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

        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: var(--DarkBlue);
            color: var(--White);
            padding: 0.7rem 1.2rem;
            border-radius: 10px;
            font-size: 0.875rem;
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
        }

        .toast.error { 
            background: #dc3545; 
        }

        .no-options-note {
            font-size: 0.82rem;
            color: var(--Gray);
            font-style: italic;
            padding: 0.4rem 0;
        }

        .banner-error {
            background: hsl(0, 70%, 92%);
            border-color: red;
            color: hsl(0, 70%, 35%);
        }

        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .suspend-notice {
            background: hsl(0, 70%, 96%);
            border: 1px solid hsl(0, 60%, 80%);
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-size: 0.82rem;
            color: hsl(0, 60%, 35%);
            margin-bottom: 0.75rem;
        }

        .suspend-warning-list {
            font-size: 0.82rem;
            color: var(--Gray);
            margin: 0.5rem 0 1rem 1rem;
            padding: 0;
            line-height: 1.7;
        }

        .centre-item-row {
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.85rem;
            min-width: 0;
            overflow: hidden;
        }

        .centre-item-label {
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            color: var(--text-color);
        }

        .centre-item-current {
            font-size: 0.75rem;
            color: var(--Gray);
            margin-bottom: 0.5rem;
        }

        .centre-item-current span {
            font-size: 0.75rem;
            color: var(--MainBlue);
        }

        .centre-item-incompatible {
            font-size: 0.72rem;
            color: red;
            font-weight: 600;
            margin-top: 0.2rem;
        }

        .modal-scroll-body {
            max-height: 55vh;
            overflow-y: auto;
            padding-right: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .label-note {
            font-weight: 400;
            text-transform: none;
            font-size: 0.75rem;
        }

        .activity-log-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .activity-log-item {
            display: grid;
            grid-template-columns: auto 1fr;
            grid-template-rows: auto auto;
            gap: 0.15rem 0.65rem;
            font-size: 0.82rem;
            padding: 0.7rem 0.9rem;
            background: var(--LightBlue);
            border-radius: 8px;
            border: 1px solid var(--BlueGray);
        }

        .dark-mode .activity-log-item {
            background: var(--LowMainBlue);
        }

        .activity-log-action {
            grid-column: 1;
            grid-row: 1;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--MainBlue);
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .activity-log-time {
            grid-column: 1;
            grid-row: 2;
            font-size: 0.7rem;
            color: var(--Gray);
            white-space: nowrap;
        }

        .dark-mode .activity-log-time {
            color: var(--BlueGray);
        }

        .activity-log-desc {
            grid-column: 2;
            grid-row: 1 / span 2;
            color: var(--text-color);
            line-height: 1.55;
            font-size: 0.82rem;
            align-self: center;
            word-break: break-word;
        }

        .activity-log-admin {
            display: none;
        }

        .reassign-warning-notice {
            background: hsl(35, 90%, 93%);
            border: 1px solid hsl(35, 80%, 70%);
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-size: 0.82rem;
            color: hsl(30, 80%, 30%);
            margin-bottom: 0.75rem;
        }

    </style>
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
                        <a href="../../html/common/Setting.html">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>

                    <a href="../../html/admin/aHome.html">Home</a>
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
            <a href="../../html/admin/aHome.html">Home</a>
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
            <a href="../../html/common/Setting.html">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
        
    </header>
    <hr>

    <!-- Main Content -->
    <main>
        <div class="detail-container">

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-header-left">
                    <a href="aIssue.php" class="back-btn">← Back</a>
                    <div class="page-title">Issue <span>#<?php echo $issueID; ?></span></div>
                </div>
            </div>

            <?php if ($successMsg): ?>
                <div class="status-banner banner-resolved"><?php echo sanitize($successMsg); ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="status-banner banner-error"><?php echo sanitize($errorMsg); ?></div>
            <?php endif; ?>

            <!-- Status Banner -->
            <?php
                $statusCss   = strtolower($issueRow['status']);
            ?>
            <div class="status-banner">
                <?php if ($isOpen): ?>
                    This issue is <span class="badge-status status-open">Open</span> and has not been assigned to any admin yet.
                <?php elseif ($isResolved): ?>
                    This issue is <span class="badge-status status-resolved">Resolved</span><?php echo $issueRow['assignedAdminName'] ? ' by ' . sanitize($issueRow['assignedAdminName']) : ''; ?>.
                <?php else: ?>
                    This issue is <span class="badge-status status-assigned">Assigned</span> to <?php echo sanitize($issueRow['assignedAdminName'] ?? '—'); ?>.
                    <?php if (!$isAssignedToMe): ?>
                        You are viewing as a read-only observer.
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Take On Banner: only shown when Open -->
            <?php if ($isOpen): ?>
            <div class="assign-banner">
                <div class="assign-banner-text">
                    <strong>This issue is unassigned</strong>
                    <p>Take ownership to unlock actions and begin resolving.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="take_on">
                    <button type="submit" class="c-btn-primary">Take On This Issue</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Issue Information -->
            <div class="card">
                <div class="card-title">Issue Information</div>
                <div class="info-grid">
                    <div class="info-field">
                        <span class="info-label">Issue ID</span>
                        <span class="info-value">#<?php echo $issueRow['issueID']; ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Issue Type</span>
                        <span class="info-value"><span class="badge-type"><?php echo sanitize($issueRow['issueType']); ?></span></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Severity</span>
                        <span class="info-value"><span class="badge-severity severity-<?php echo strtolower($issueRow['severity']); ?>"><?php echo sanitize($issueRow['severity']); ?></span></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Status</span>
                        <span class="info-value"><span class="badge-status status-<?php echo $statusCss; ?>"><?php echo sanitize($issueRow['status']); ?></span></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Reported By</span>
                        <span class="info-value"><?php echo sanitize($issueRow['reportedByName']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Reported At</span>
                        <span class="info-value"><?php echo fmtDateTime($issueRow['reportedAt']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Assigned Admin</span>
                        <?php if ($issueRow['assignedAdminName']): ?>
                            <span class="info-value"><?php echo sanitize($issueRow['assignedAdminName']); ?></span>
                        <?php else: ?>
                            <span class="info-value muted">Unassigned</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Assigned At</span>
                        <?php if ($issueRow['assignedAt']): ?>
                            <span class="info-value"><?php echo fmtDateTime($issueRow['assignedAt']); ?></span>
                        <?php else: ?>
                            <span class="info-value muted">—</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-field span-full">
                        <span class="info-label">Subject</span>
                        <span class="info-value"><?php echo sanitize($issueRow['subject']); ?></span>
                    </div>
                    <div class="info-field span-full">
                        <span class="info-label">Description</span>
                        <span class="info-value"><?php echo sanitize($issueRow['description']); ?></span>
                    </div>
                    <div class="info-field span-full">
                        <span class="info-label">Admin Notes / Resolution Notes</span>
                        <?php if ($issueRow['notes']): ?>
                            <span class="info-value"><?php echo nl2br(sanitize($issueRow['notes'])); ?></span>
                        <?php else: ?>
                            <span class="info-value muted">No notes yet.</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Resolved At</span>
                        <?php if ($issueRow['resolvedAt']): ?>
                            <span class="info-value"><?php echo fmtDateTime($issueRow['resolvedAt']); ?></span>
                        <?php else: ?>
                            <span class="info-value muted">—</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Linked Job & Request Info -->
            <?php if ($jobRow && $reqRow): ?>
            <div class="card">
                <div class="card-title">Linked Job & Request Info</div>
                <div class="info-grid">
                    <div class="info-field">
                        <span class="info-label">Linked Job</span>
                        <span class="info-value">
                            <a class="linked-id" href="../../html/admin/aJobs.php?id=<?php echo $jobRow['jobID'];?>">
                                JOB #<?php echo $jobRow['jobID']; ?>
                            </a>
                        </span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Linked Request</span>
                        <span class="info-value">
                            <a class="linked-id" href="../../html/admin/aRequests.php?id=<?php echo $reqRow['requestID'];?>">
                                REQ #<?php echo $reqRow['requestID']; ?>
                            </a>
                        </span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Current Job Status</span>
                        <span class="info-value"><?php echo sanitize($jobRow['status']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Scheduled Date & Time</span>
                        <span class="info-value"><?php echo date('d M Y', strtotime($jobRow['scheduledDate'])) . ', ' . date('g:i A', strtotime($jobRow['scheduledTime'])); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Assigned Collector</span>
                        <span class="info-value"><?php echo sanitize($jobRow['collectorName']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Assigned Vehicle</span>
                        <span class="info-value"><?php echo sanitize($jobRow['vehiclePlate'] . ' — ' . $jobRow['vehicleModel'] . ' (' . $jobRow['vehicleType'] . ')'); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Pickup Address</span>
                        <span class="info-value"><?php echo sanitize($reqRow['pickupAddress'] . ', ' . $reqRow['pickupState']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Request Status</span>
                        <span class="info-value"><?php echo sanitize($reqRow['status']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Pickup Completed</span>
                        <span class="info-value"><span class="badge-yn <?php echo $pickupDone ? 'badge-yes' : 'badge-no'; ?>"><?php echo $pickupDone ? 'Yes' : 'No'; ?></span></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Drop-off Completed</span>
                        <span class="info-value"><span class="badge-yn <?php echo $dropoffDone ? 'badge-yes' : 'badge-no'; ?>"><?php echo $dropoffDone ? 'Yes' : 'No'; ?></span></span>
                    </div>
                </div>
            </div>
            <?php elseif ($reqRow): ?>
            <!-- Request only (no job linked) -->
            <div class="card">
                <div class="card-title">Linked Request Info</div>
                <div class="info-grid">
                    <div class="info-field">
                        <span class="info-label">Linked Request</span>
                        <span class="info-value">
                            <a class="linked-id" href="../../html/admin/aRequests.php?id=<?php echo $reqRow['requestID'];?>">
                                REQ #<?php echo $reqRow['requestID']; ?>
                            </a>
                        </span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Request Status</span>
                        <span class="info-value"><?php echo sanitize($reqRow['status']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Pickup Address</span>
                        <span class="info-value"><?php echo sanitize($reqRow['pickupAddress'] . ', ' . $reqRow['pickupState']); ?></span>
                    </div>
                    <div class="info-field">
                        <span class="info-label">Preferred Date & Time</span>
                        <span class="info-value"><?php echo fmtDateTime($reqRow['preferredDateTime']); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions Performed -->
            <?php if (!empty($activityLog)): ?>
            <div class="card">
                <div class="card-title">Actions Performed</div>
                <div class="activity-log-list">
                    <?php foreach ($activityLog as $log): ?>
                    <div class="activity-log-item">
                        <span class="activity-log-time"><?php echo fmtDateTime($log['dateTime']); ?></span>
                        <span class="activity-log-action"><?php echo sanitize($log['action']); ?></span>
                        <span class="activity-log-desc"><?php echo sanitize($log['description']); ?></span>
                        <span class="activity-log-admin"><?php echo sanitize($log['adminName']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Admin Action Panel -->
            <div class="card">
                <div class="card-title">Admin Action Panel</div>

                <?php if (!$canAct): ?>
                    <!-- Locked panel -->
                    <div class="locked-msg">
                        <img src="../../assets/images/lock-icon-gray.svg" alt="Lock">
                        <?php if ($isResolved): ?>
                            <p>This issue has been resolved. No further actions are available.</p>
                        <?php elseif ($isOpen): ?>
                            <p>Take on this issue to unlock admin actions.</p>
                        <?php else: ?>
                            <p>This issue is assigned to another admin. You can view details but cannot perform actions.</p>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- Unlocked panel — $canAct = true -->

                    <?php if ($jobRow && $reqRow): ?>

                        <?php if (!$pickupDone): ?>
                            <!-- Pickup NOT done: reschedule, reassign collector, reassign vehicle, cancel -->
                            <div class="action-section-label">Pickup Not Yet Completed — Available Actions</div>
                            <div class="action-group">
                                <button class="btn-warning" onclick="openModal('modalReschedule')">Reschedule Collection</button>
                                <button class="btn-danger"  onclick="openModal('modalCancelRequest')">Cancel Request</button>
                            </div>

                        <?php elseif ($pickupDone && !$dropoffDone): ?>
                            <!-- Pickup done, dropoff NOT done: reassign collector+vehicle together, and centre -->
                            <div class="action-section-label">Pickup Completed — Drop-off Pending</div>
                            <div class="action-group">
                                <?php if (!empty($dropoffAvailCollectors) && !empty($dropoffAvailVehicles)): ?>
                                    <?php if ($prevDropoffJobID > 0): ?>
                                        <button class="btn-warning" onclick="openModalReassignDropoff(true)">Re-Assign Collector & Vehicle</button>
                                    <?php else: ?>
                                        <button class="c-btn-secondary" onclick="openModalReassignDropoff(false)">Reassign Collector & Vehicle</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="c-btn-secondary btn-disabled" disabled title="No available collectors or vehicles for today">Reassign Collector & Vehicle</button>
                                <?php endif; ?>
                                <?php if (!empty($collectedItems)): ?>
                                    <button class="c-btn-secondary" onclick="openModal('modalReassignCentre')">Reassign Drop-off Centre</button>
                                <?php endif; ?>
                            </div>

                        <?php else: ?>
                            <!-- Both done -->
                            <div class="action-section-label">Pickup & Drop-off Completed</div>
                            <p class="no-options-note">All collections are complete. No reassignment actions are available.</p>
                            <hr class="section-divider">
                        <?php endif; ?>

                    <?php elseif ($reqRow): ?>
                        <!-- Request only — no job linked -->
                        <div class="action-section-label">Request-Only Issue — Available Actions</div>
                        <div class="action-group">
                            <button class="btn-warning" onclick="openModal('modalRescheduleRequest')">Reschedule Request</button>
                            <button class="btn-danger" onclick="openModal('modalCancelRequest')">Cancel Request</button>
                        </div>

                    <?php else: ?>
                        <p class="no-options-note">No linked job found for this issue.</p>
                        <hr class="section-divider">
                    <?php endif; ?>

                    <hr class="section-divider">

                    <!-- Suspension Actions -->
                    <?php if ($jobRow && $reqRow): ?>
                    <div class="action-section-label">Suspension Actions</div>
                    <div class="action-group">
                        <?php if ($collectorSuspended): ?>
                            <button class="btn-danger btn-disabled" disabled title="Collector is already suspended">Suspend Collector</button>
                        <?php elseif (empty($suspendAvailCollectors)): ?>
                            <button class="btn-danger btn-disabled" disabled title="No available replacement collectors within ±1 day">Suspend Collector</button>
                        <?php else: ?>
                            <button class="btn-danger" onclick="openModal('modalSuspendCollector')">Suspend Collector</button>
                        <?php endif; ?>

                        <?php if ($providerSuspended): ?>
                            <button class="btn-danger btn-disabled" disabled title="Provider is already suspended">Suspend Provider</button>
                        <?php else: ?>
                            <button class="btn-danger" onclick="openModal('modalSuspendProvider')">Suspend Provider</button>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($reqRow): ?>
                    <!-- Suspension actions for request-only issues -->
                    <div class="action-section-label">Suspension Actions</div>
                    <div class="action-group">
                        <?php if ($providerSuspended): ?>
                            <button class="btn-danger btn-disabled" disabled title="Provider is already suspended">Suspend Provider</button>
                        <?php else: ?>
                            <button class="btn-danger" onclick="openModal('modalSuspendProvider')">Suspend Provider</button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <hr class="section-divider">

                    <!-- Notes + Resolve -->
                    <form method="POST" id="notesForm">
                        <input type="hidden" name="action" value="save_notes">
                        <div class="form-group">
                            <label class="form-label">Admin Notes</label>
                            <textarea class="form-textarea" name="notes" id="adminNotes" placeholder="Add notes about actions taken or observations..."><?php echo sanitize($issueRow['notes'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="c-btn-primary">Save Notes</button>
                            <button type="button" class="btn-success" onclick="openModal('modalMarkResolved')">Mark as Resolved</button>
                        </div>
                    </form>

                <?php endif; ?>
            </div>

        </div>
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
                <a href="../../html/common/Profile.html">Edit Profile</a><br>
                <a href="../../html/common/Setting.html">Setting</a>
            </div>
        </section>
    </footer>

    <?php if ($canAct && $jobRow): ?>

    <!-- Reassign Collector (pickup not done only) -->
    <div class="modal-overlay" id="modalReassignCollector">
        <div class="modal">
            <div class="modal-title">Reassign Collector</div>
            <?php if (empty($availCollectors)): ?>
                <p class="modal-desc">No active collectors are available on <?php echo date('d M Y', strtotime($jobRow['scheduledDate'])); ?>.</p>
                <div class="modal-actions">
                    <button class="cancel-btn c-btn-small" onclick="closeModal('modalReassignCollector')">Close</button>
                </div>
            <?php else: ?>
                <p class="modal-desc">Select a collector who is free on <?php echo date('d M Y', strtotime($jobRow['scheduledDate'])); ?>. The current job will be cancelled and a new one created.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="reassign_collector">
                    <input type="hidden" name="jobID"  value="<?php echo $jobRow['jobID']; ?>">
                    <div class="form-group">
                        <label class="form-label">Select New Collector</label>
                        <select class="form-select" name="collectorID" required>
                            <option value="">-- Select Collector --</option>
                            <?php foreach ($availCollectors as $col): ?>
                                <option value="<?php echo $col['collectorID']; ?>"><?php echo sanitize($col['fullname']); ?> — <?php echo sanitize($col['phone']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalReassignCollector')">Cancel</button>
                        <button type="submit" class="c-btn-primary c-btn-small">Confirm</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reassign Vehicle (pickup not done only) -->
    <div class="modal-overlay" id="modalReassignVehicle">
        <div class="modal">
            <div class="modal-title">Reassign Vehicle</div>
            <?php if (empty($availVehicles)): ?>
                <p class="modal-desc">No available vehicles found for <?php echo date('d M Y', strtotime($jobRow['scheduledDate'])); ?>.</p>
                <div class="modal-actions">
                    <button class="cancel-btn c-btn-small" onclick="closeModal('modalReassignVehicle')">Close</button>
                </div>
            <?php else: ?>
                <p class="modal-desc">Select a vehicle that is free on <?php echo date('d M Y', strtotime($jobRow['scheduledDate'])); ?>. The current job will be cancelled and a new one created.</p>
                <form method="POST">
                    <input type="hidden" name="action"  value="reassign_vehicle">
                    <input type="hidden" name="jobID"   value="<?php echo $jobRow['jobID']; ?>">
                    <div class="form-group">
                        <label class="form-label">Select New Vehicle</label>
                        <select class="form-select" name="vehicleID" required>
                            <option value="">-- Select Vehicle --</option>
                            <?php foreach ($availVehicles as $veh): ?>
                                <option value="<?php echo $veh['vehicleID']; ?>"><?php echo sanitize($veh['plateNum'] . ' — ' . $veh['model'] . ' (' . $veh['type'] . ', ' . number_format($veh['capacityWeight'], 0) . ' kg)'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalReassignVehicle')">Cancel</button>
                        <button type="submit" class="c-btn-primary c-btn-small">Confirm</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$pickupDone): ?>

    <!-- Reschedule Collection -->
    <div class="modal-overlay" id="modalReschedule">
        <div class="modal modal-wide">
            <button class="modal-close-btn" onclick="closeModal('modalReschedule')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <div class="modal-title">Reschedule Collection</div>
            <p class="modal-desc">The current job will be cancelled and a new one created. You may keep the same collector, vehicle, and item centres or change them.</p>
            <form method="POST">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="jobID"  value="<?php echo $jobRow['jobID']; ?>">
                <div class="form-group">
                    <label class="form-label">New Date <span class="required-star">*</span></label>
                    <input type="date" class="form-input" name="new_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">New Start Time <span class="required-star">*</span></label>
                    <input type="time" class="form-input" name="new_time" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estimated End Time</label>
                    <input type="time" class="form-input" name="new_end_time">
                </div>
                <div class="form-group">
                    <label class="form-label">Collector <span class="required-star">*</span></label>
                    <select class="form-select" name="collectorID" required>
                        <option value="<?php echo $jobRow['collectorID']; ?>" selected>
                            <?php echo sanitize($jobRow['collectorName']); ?> (current)
                        </option>
                        <?php foreach ($availCollectors as $col): ?>
                            <option value="<?php echo $col['collectorID']; ?>"><?php echo sanitize($col['fullname']); ?> — <?php echo sanitize($col['phone']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vehicle <span class="required-star">*</span></label>
                    <select class="form-select" name="vehicleID" required>
                        <option value="<?php echo $jobRow['vehicleID']; ?>" selected>
                            <?php echo sanitize($jobRow['vehiclePlate'] . ' — ' . $jobRow['vehicleModel'] . ' (' . $jobRow['vehicleType'] . ')'); ?> (current)
                        </option>
                        <?php foreach ($availVehicles as $veh): ?>
                            <option value="<?php echo $veh['vehicleID']; ?>"><?php echo sanitize($veh['plateNum'] . ' — ' . $veh['model'] . ' (' . $veh['type'] . ', ' . number_format($veh['capacityWeight'], 0) . ' kg)'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php
                    $pendingItems = [];
                    if ($reqRow) {
                        $rid = (int)$reqRow['requestID'];
                        $res = $conn->query("
                            SELECT i.itemID, i.description, i.centreID, i.itemTypeID,
                                   it.name AS itemTypeName
                            FROM tblitem i
                            JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID
                            WHERE i.requestID = $rid AND i.status = 'Pending'
                        ");
                        while ($r = $res->fetch_assoc()) $pendingItems[] = $r;
                    }
                ?>
                <?php if (!empty($pendingItems)): ?>
                <div class="form-group">
                    <label class="form-label">Item Drop-off Centres</label>
                    <div class="modal-scroll-body">
                        <?php foreach ($pendingItems as $item): ?>
                        <?php
                            $currentCentreName = '—';
                            if (!empty($item['centreID'])) {
                                foreach ($activeCentres as $ac) {
                                    if ((int)$ac['centreID'] === (int)$item['centreID']) {
                                        $currentCentreName = $ac['name'] . ' (' . $ac['state'] . ', ' . $ac['postcode'] . ')';
                                        break;
                                    }
                                }
                            }
                        ?>
                        <div class="centre-item-row">
                            <div class="centre-item-label">
                                Item #<?php echo $item['itemID']; ?>: <?php echo sanitize($item['itemTypeName']); ?>
                            </div>
                            <div class="centre-item-current">
                                Current centre: <span><?php echo sanitize($currentCentreName); ?></span>
                            </div>
                            <select class="form-select" name="centre_assignment[<?php echo $item['itemID']; ?>]">
                                <option value="<?php echo (int)$item['centreID']; ?>">
                                    <?php echo sanitize($currentCentreName); ?> (current)
                                </option>
                                <?php foreach ($activeCentres as $centre): ?>
                                    <?php if ((int)$centre['centreID'] === (int)$item['centreID']) continue; ?>
                                    <?php $accepted = in_array((int)$item['itemTypeID'], $centre['_acceptedTypes']); ?>
                                    <option value="<?php echo $centre['centreID']; ?>"
                                        <?php echo !$accepted ? 'disabled' : ''; ?>>
                                        <?php echo sanitize($centre['name']); ?>
                                        (<?php echo sanitize($centre['state']); ?>, <?php echo sanitize($centre['postcode']); ?>)
                                        <?php if ($centre['_distance'] === 0): ?> * Nearest<?php endif; ?>
                                        <?php if (!$accepted): ?>- Does not accept this item type<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalReschedule')">Cancel</button>
                    <button type="submit" class="c-btn-primary c-btn-small">Confirm Reschedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancel Request -->
    <div class="modal-overlay" id="modalCancelRequest">
        <div class="modal">
            <div class="modal-title">Cancel Request #<?php echo $reqRow ? $reqRow['requestID'] : ''; ?></div>
            <p class="modal-desc">This will cancel the linked job and reject the collection request. This action cannot be undone.</p>
            <form method="POST">
                <input type="hidden" name="action"    value="cancel_request">
                <input type="hidden" name="jobID"     value="<?php echo $jobRow['jobID']; ?>">
                <input type="hidden" name="requestID" value="<?php echo $reqRow ? $reqRow['requestID'] : 0; ?>">
                <div class="form-group">
                    <label class="form-label">Reason for Cancellation <span class="required-star">*</span></label>
                    <textarea class="form-textarea" name="reason" placeholder="Describe why this request is being cancelled..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalCancelRequest')">Back</button>
                    <button type="submit" class="btn-danger c-btn-small">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; // !$pickupDone ?>

    <!-- Reassign Collector + Vehicle for Drop-off (pickup done, dropoff pending) -->
    <?php if ($pickupDone && !$dropoffDone && (!empty($dropoffAvailCollectors) && !empty($dropoffAvailVehicles))): ?>
    <div class="modal-overlay" id="modalReassignDropoff">
        <div class="modal modal-wide">
            <div class="modal-title">Reassign Collector & Vehicle for Drop-off</div>

            <?php if ($prevDropoffJobID > 0): ?>
            <div class="reassign-warning-notice" id="reAssignWarning">
                ⚠ A previous reassignment already exists (JOB #<?php echo $prevDropoffJobID; ?>). Confirming will cancel that job and create a new one.
            </div>
            <?php endif; ?>

            <p class="modal-desc">Select a new collector and vehicle available today. The new job will be set to <strong>Scheduled</strong> immediately. You may also choose a different date if needed.</p>
            <form method="POST">
                <input type="hidden" name="action"          value="reassign_dropoff">
                <input type="hidden" name="jobID"           value="<?php echo $jobRow['jobID']; ?>">
                <input type="hidden" name="cancel_prev_id"  value="<?php echo $prevDropoffJobID; ?>">
                <div class="form-group">
                    <label class="form-label">New Collector <span class="required-star">*</span></label>
                    <select class="form-select" name="collectorID" required>
                        <option value="">-- Select Collector --</option>
                        <?php foreach ($dropoffAvailCollectors as $col): ?>
                            <option value="<?php echo $col['collectorID']; ?>"><?php echo sanitize($col['fullname']); ?> — <?php echo sanitize($col['phone']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">New Vehicle <span class="required-star">*</span></label>
                    <select class="form-select" name="vehicleID" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($dropoffAvailVehicles as $veh): ?>
                            <option value="<?php echo $veh['vehicleID']; ?>"><?php echo sanitize($veh['plateNum'] . ' — ' . $veh['model'] . ' (' . $veh['type'] . ', ' . number_format($veh['capacityWeight'], 0) . ' kg)'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Scheduled Date <span class="label-note">(leave blank for today — <?php echo date('d M Y'); ?>)</span></label>
                    <input type="date" class="form-input" name="new_date" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalReassignDropoff')">Cancel</button>
                    <button type="submit" class="c-btn-primary c-btn-small"><?php echo $prevDropoffJobID > 0 ? 'Confirm Re-Reassignment' : 'Confirm Reassignment'; ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reassign Drop-off Centre -->
    <?php if (!empty($collectedItems)): ?>
    <div class="modal-overlay" id="modalReassignCentre">
        <div class="modal modal-wide">
            <div class="modal-title">Reassign Drop-off Centre</div>
            <p class="modal-desc">
                Centres are sorted by proximity to the pickup postcode
                (<?php echo sanitize($reqRow['pickupPostcode']); ?>).
                Assign a new drop-off centre for each collected item below.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="reassign_centre">
                <div class="modal-scroll-body">
                    <?php foreach ($collectedItems as $item): ?>
                    <?php
                        $currentCentreName = '—';
                        if (!empty($item['centreID'])) {
                            foreach ($activeCentres as $ac) {
                                if ((int)$ac['centreID'] === (int)$item['centreID']) {
                                    $currentCentreName = $ac['name'] . ' (' . $ac['state'] . ', ' . $ac['postcode'] . ')';
                                    break;
                                }
                            }
                        }
                    ?>
                    <div class="centre-item-row">
                        <div class="centre-item-label">
                            Item#<?php echo $item['itemID']; ?>: <?php echo sanitize($item['itemTypeName']); ?>
                        </div>
                        <div class="centre-item-current">
                            Current centre: <span><?php echo sanitize($currentCentreName); ?></span>
                        </div>
                        <select class="form-select" name="centre_assignment[<?php echo $item['itemID']; ?>]" required>
                            <option value="">-- Select Centre --</option>
                            <?php foreach ($activeCentres as $centre): ?>
                                <?php $accepted = in_array((int)$item['itemTypeID'], $centre['_acceptedTypes']); ?>
                                <option value="<?php echo $centre['centreID']; ?>"
                                    <?php echo ((int)$item['centreID'] === (int)$centre['centreID']) ? 'selected' : ''; ?>
                                    <?php echo !$accepted ? 'disabled' : ''; ?>>
                                    <?php echo sanitize($centre['name']); ?>
                                    (<?php echo sanitize($centre['state']); ?>, <?php echo sanitize($centre['postcode']); ?>)
                                    <?php if ($centre['_distance'] === 0): ?>⭐ Nearest<?php endif; ?>
                                    <?php if (!$accepted): ?>— ✗ Does not accept this item type<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php
                            $anyAccepted = false;
                            foreach ($activeCentres as $centre) {
                                if (in_array((int)$item['itemTypeID'], $centre['_acceptedTypes'])) {
                                    $anyAccepted = true; break;
                                }
                            }
                        ?>
                        <?php if (!$anyAccepted): ?>
                            <div class="centre-item-incompatible">⚠ No active centres accept this item type.</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalReassignCentre')">Cancel</button>
                    <button type="submit" class="c-btn-primary c-btn-small">Confirm Reassignment</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Suspend Collector -->
    <?php if ($jobRow && $reqRow && !$collectorSuspended && !empty($suspendAvailCollectors)): ?>
    <div class="modal-overlay" id="modalSuspendCollector">
        <div class="modal modal-wide">
            <div class="modal-title">Suspend Collector — <?php echo sanitize($jobRow['collectorName']); ?></div>
            <div class="suspend-notice">
                ⚠ This action will suspend the collector. The linked job (JOB #<?php echo $jobRow['jobID']; ?>) <strong>must be reassigned</strong> before suspension. All other active jobs for this collector will be <strong>cancelled</strong>.
            </div>
            <ul class="suspend-warning-list">
                <li>The linked job will be reassigned to the replacement collector you select below.</li>
                <li>All other Pending / Scheduled jobs for this collector will be cancelled automatically.</li>
                <li>Replacement collector availability is checked within <strong>±1 day</strong> of each job's scheduled date.</li>
            </ul>
            <form method="POST">
                <input type="hidden" name="action"            value="suspend_collector">
                <input type="hidden" name="targetCollectorID" value="<?php echo $jobRow['collectorID']; ?>">
                <input type="hidden" name="linkedJobID"       value="<?php echo $jobRow['jobID']; ?>">
                <div class="form-group">
                    <label class="form-label">Replacement Collector for JOB #<?php echo $jobRow['jobID']; ?> <span class="required-star">*</span></label>
                    <select class="form-select" name="linkedNewCollector" required>
                        <option value="">-- Select Replacement Collector --</option>
                        <?php foreach ($suspendAvailCollectors as $col): ?>
                            <option value="<?php echo $col['collectorID']; ?>"><?php echo sanitize($col['fullname']); ?> — <?php echo sanitize($col['phone']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($suspendAvailVehicles)): ?>
                <div class="form-group">
                    <label class="form-label">Replace Vehicle for JOB #<?php echo $jobRow['jobID']; ?> <span class="label-note">(optional — keep current if blank)</span></label>
                    <select class="form-select" name="linkedNewVehicle">
                        <option value="">-- Keep Current Vehicle (<?php echo sanitize($jobRow['vehiclePlate']); ?>) --</option>
                        <?php foreach ($suspendAvailVehicles as $veh): ?>
                            <option value="<?php echo $veh['vehicleID']; ?>"><?php echo sanitize($veh['plateNum'] . ' — ' . $veh['model'] . ' (' . $veh['type'] . ', ' . number_format($veh['capacityWeight'], 0) . ' kg)'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="linkedNewVehicle" value="0">
                <?php endif; ?>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalSuspendCollector')">Cancel</button>
                    <button type="submit" class="btn-danger c-btn-small">Confirm Suspension</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Suspend Provider -->
    <?php if ($reqRow && !$providerSuspended): ?>
    <div class="modal-overlay" id="modalSuspendProvider">
        <div class="modal modal-wide">
            <div class="modal-title">Suspend Provider — <?php echo $providerInfo ? sanitize($providerInfo['fullname']) : ''; ?></div>
            <div class="suspend-notice">
                ⚠ This action will suspend the provider. All their active requests and jobs will be cancelled. This cannot be undone.
            </div>
            <ul class="suspend-warning-list">
                <li>The current linked request (REQ #<?php echo $reqRow['requestID']; ?>) and its job will be <strong>cancelled</strong>.</li>
                <li>All other <strong>Pending</strong> requests from this provider will be <strong>rejected</strong>.</li>
                <li>All other <strong>Approved / Scheduled</strong> requests will be <strong>cancelled</strong>, along with their active jobs.</li>
            </ul>
            <form method="POST">
                <input type="hidden" name="action"            value="suspend_provider">
                <input type="hidden" name="targetProviderID"  value="<?php echo $reqRow['providerID']; ?>">
                <input type="hidden" name="linkedRequestID"   value="<?php echo $reqRow['requestID']; ?>">
                <input type="hidden" name="linkedJobIDForReq" value="<?php echo $jobRow ? $jobRow['jobID'] : 0; ?>">
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalSuspendProvider')">Cancel</button>
                    <button type="submit" class="btn-danger c-btn-small">Confirm Suspension</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // $canAct && $jobRow ?>

    <?php if ($canAct && !$jobRow && $reqRow): ?>

    <!-- Cancel Request modal for request-only issues (no job) -->
    <div class="modal-overlay" id="modalCancelRequest">
        <div class="modal">
            <div class="modal-title">Cancel Request #<?php echo $reqRow['requestID']; ?></div>
            <p class="modal-desc">This will reject the collection request. This action cannot be undone.</p>
            <form method="POST">
                <input type="hidden" name="action"    value="cancel_request">
                <input type="hidden" name="jobID"     value="0">
                <input type="hidden" name="requestID" value="<?php echo $reqRow['requestID']; ?>">
                <div class="form-group">
                    <label class="form-label">Reason for Cancellation <span class="required-star">*</span></label>
                    <textarea class="form-textarea" name="reason" placeholder="Describe why this request is being cancelled..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalCancelRequest')">Back</button>
                    <button type="submit" class="btn-danger c-btn-small">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reschedule Request (request-only) -->
    <div class="modal-overlay" id="modalRescheduleRequest">
        <div class="modal modal-wide">
            <button class="modal-close-btn" onclick="closeModal('modalRescheduleRequest')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <div class="modal-title">Reschedule Request</div>

            <?php if ($pendingJobForReq): ?>
            <div class="reassign-warning-notice">
                ⚠ A pending job (JOB #<?php echo $pendingJobForReq['jobID']; ?>) is linked to this request.
                It will be <strong>cancelled</strong> and a new job created with the details below.
            </div>
            <?php endif; ?>

            <p class="modal-desc">
                Current preferred datetime: <strong><?php echo fmtDateTime($reqRow['preferredDateTime']); ?></strong>
            </p>

            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $pendingJobForReq ? 'reschedule_request_with_job' : 'reschedule_request'; ?>">
                <input type="hidden" name="requestID" value="<?php echo $reqRow['requestID']; ?>">
                <?php if ($pendingJobForReq): ?>
                <input type="hidden" name="jobID" value="<?php echo $pendingJobForReq['jobID']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">New Preferred Date &amp; Time <span class="required-star">*</span></label>
                    <input type="datetime-local" class="form-input" name="new_datetime" required
                        min="<?php echo date('Y-m-d\TH:i'); ?>"
                        value="<?php echo date('Y-m-d\TH:i', strtotime($reqRow['preferredDateTime'])); ?>">
                </div>

                <?php if ($pendingJobForReq): ?>
                <div class="form-group">
                    <label class="form-label">New Scheduled Date <span class="required-star">*</span></label>
                    <input type="date" class="form-input" name="new_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">New Start Time <span class="required-star">*</span></label>
                    <input type="time" class="form-input" name="new_time" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estimated End Time</label>
                    <input type="time" class="form-input" name="new_end_time">
                </div>
                <div class="form-group">
                    <label class="form-label">Collector <span class="required-star">*</span></label>
                    <select class="form-select" name="collectorID" required>
                        <option value="<?php echo $pendingJobForReq['collectorID']; ?>" selected>
                            <?php echo sanitize($pendingJobForReq['collectorName']); ?> (current)
                        </option>
                        <?php foreach ($reqOnlyAvailCollectors as $col): ?>
                        <option value="<?php echo $col['collectorID']; ?>">
                            <?php echo sanitize($col['fullname']); ?> — <?php echo sanitize($col['phone']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vehicle <span class="required-star">*</span></label>
                    <select class="form-select" name="vehicleID" required>
                        <option value="<?php echo $pendingJobForReq['vehicleID']; ?>" selected>
                            <?php echo sanitize($pendingJobForReq['vehiclePlate'] . ' — ' . $pendingJobForReq['vehicleModel'] . ' (' . $pendingJobForReq['vehicleType'] . ')'); ?> (current)
                        </option>
                        <?php foreach ($reqOnlyAvailVehicles as $veh): ?>
                        <option value="<?php echo $veh['vehicleID']; ?>">
                            <?php echo sanitize($veh['plateNum'] . ' — ' . $veh['model'] . ' (' . $veh['type'] . ', ' . number_format($veh['capacityWeight'], 0) . ' kg)'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalRescheduleRequest')">Cancel</button>
                    <button type="submit" class="c-btn-primary c-btn-small">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Suspend Provider modal for request-only issues (no job) -->
    <?php if ($reqRow && !$providerSuspended): ?>
    <div class="modal-overlay" id="modalSuspendProvider">
        <div class="modal modal-wide">
            <div class="modal-title">Suspend Provider — <?php echo $providerInfo ? sanitize($providerInfo['fullname']) : ''; ?></div>
            <div class="suspend-notice">
                ⚠ This action will suspend the provider. All their active requests and jobs will be cancelled. This cannot be undone.
            </div>
            <ul class="suspend-warning-list">
                <li>The current linked request (REQ #<?php echo $reqRow['requestID']; ?>) will be <strong>rejected</strong>.</li>
                <li>All other <strong>Pending</strong> requests from this provider will be <strong>rejected</strong>.</li>
                <li>All other <strong>Approved / Scheduled</strong> requests will be <strong>cancelled</strong>, along with their active jobs.</li>
            </ul>
            <form method="POST">
                <input type="hidden" name="action"            value="suspend_provider">
                <input type="hidden" name="targetProviderID"  value="<?php echo $reqRow['providerID']; ?>">
                <input type="hidden" name="linkedRequestID"   value="<?php echo $reqRow['requestID']; ?>">
                <input type="hidden" name="linkedJobIDForReq" value="0">
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalSuspendProvider')">Cancel</button>
                    <button type="submit" class="btn-danger c-btn-small">Confirm Suspension</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; // $canAct && !$jobRow && $reqRow ?>

    <!-- Mark as Resolved (always rendered when canAct, no job dependency) -->
    <?php if ($canAct): ?>
    <div class="modal-overlay" id="modalMarkResolved">
        <div class="modal">
            <div class="modal-title">Mark Issue as Resolved</div>
            <p class="modal-desc">
                Please provide resolution notes before marking this issue as resolved.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="resolve">
                <div class="form-group">
                    <label class="form-label">Resolution Notes <span class="required-star">*</span></label>
                    <textarea class="form-textarea" name="resolve_notes" placeholder="Describe how this issue was resolved..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn c-btn-small" onclick="closeModal('modalMarkResolved')">Cancel</button>
                    <button type="submit" class="btn-success c-btn-small">Mark Resolved</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        function openModal(id) { 
            document.getElementById(id).classList.add("active"); 
        }

        function closeModal(id) { 
            document.getElementById(id).classList.remove("active"); 
        }

        // For dropoff reassignment — show confirmation prompt if re-reassigning
        function openModalReassignDropoff(isReAssign) {
            if (isReAssign) {
                if (!confirm('A previous reassignment already exists. Confirming will cancel that job and create a new one. Continue?')) return;
            }
            openModal('modalReassignDropoff');
        }

        const successMsg = <?php echo json_encode($successMsg); ?>;
        const errorMsg = <?php echo json_encode($errorMsg); ?>;

        // Show toast for PHP session flash messages already rendered in banner,
        // but also show as toast for quicker feedback

        function showToast(msg, type) {
            var t = document.getElementById("toast");
            t.textContent = msg;
            t.className = 'toast ' + (type || '');
            t.classList.add("show");
            setTimeout(function() { t.classList.remove("show"); }, 3500);
            
        }

        if (successMsg) showToast(successMsg, 'success');
        if (errorMsg) showToast(errorMsg,   'error');

    </script>
</body>
</html>