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

function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

// Auto-update vehicle statuses based on jobs and maintenance:
// - If a job is Ongoing → vehicle = In Use
// - If no ongoing jobs AND a maintenance record is Scheduled/In Progress with startDate <= TODAY → vehicle = Maintenance
// - If none of the above → vehicle = Available  (only if currently In Use)

// Auto-update: Mark 'In Use' for vehicles with an Ongoing job
$conn->query("
    UPDATE tblvehicle v
    SET v.status = 'In Use'
    WHERE EXISTS (
        SELECT 1 FROM tbljob j
        WHERE j.vehicleID = v.vehicleID
            AND j.status = 'Ongoing'
    )
    AND v.status != 'In Use'
");
 
// Auto-update: revert back to 'Available' when last Ongoing job is now gone
$conn->query("
    UPDATE tblvehicle v
    SET v.status = 'Available'
    WHERE v.status = 'In Use'
      AND NOT EXISTS (
          SELECT 1 FROM tbljob j
          WHERE j.vehicleID = v.vehicleID 
            AND j.status = 'Ongoing'
      )
");

// Auto update: Maintenance Status -> In Progress: when today reaches the startDate
$conn->query("
    UPDATE tblmaintenance m
    SET m.status = 'In Progress'
    WHERE m.status = 'Scheduled'
      AND m.startDate <= CURDATE()
");

// Auto-update: Mark Maintenance when a scheduled/in progress record's startDate has arrived
// excluding inactive vehicles 
$conn->query("
    UPDATE tblvehicle v
    SET v.status = 'Maintenance'
    WHERE v.status = 'Available'
        AND EXISTS (
            SELECT 1 FROM tblmaintenance m
            WHERE m.vehicleID = v.vehicleID
            AND m.status IN ('Scheduled','In Progress')
            AND m.startDate <= CURDATE()
        )
");

// Auto-update: revert Maintenance to Available when all maintenance records are Completed/Cancelled
$conn->query("
    UPDATE tblvehicle v
    SET v.status = 'Available'
    WHERE v.status = 'Maintenance'
      AND NOT EXISTS (
          SELECT 1 FROM tblmaintenance m
          WHERE m.vehicleID = v.vehicleID
            AND m.status IN ('Scheduled', 'In Progress')
      )
");

// Auto-update: all scheduled/in progress maintenance cancelled once vehicle inactive
$conn->query("
    UPDATE tblmaintenance m
    SET m.status = 'Cancelled'
    WHERE m.status IN ('Scheduled','In Progress')
        AND EXISTS (
            SELECT 1 FROM tblvehicle v
            WHERE v.vehicleID = m.vehicleID
            AND v.status = 'Inactive'
        )
");

$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg   = $_SESSION['errorMsg']   ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$conn) {
        $_SESSION['errorMsg'] = 'Database connection failed.';
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
 
    $action = $_POST['action'] ?? '';
 
    // add
    if ($action === 'add') {
        $plateNum = trim($_POST['plateNum'] ?? '');
        $model    = trim($_POST['model']    ?? '');
        $type     = trim($_POST['type']     ?? '');
        $capacity = floatval($_POST['capacity'] ?? 0);
        $status   = $_POST['status'] ?? 'Available';
 
        $allowed = ['Available','Inactive'];
        if (!in_array($status, $allowed)) $status = 'Available';
 
        $stmt = $conn->prepare("INSERT INTO tblvehicle (plateNum, model, type, capacityWeight, status) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssds', $plateNum, $model, $type, $capacity, $status);
        if ($stmt->execute()) {
            $_SESSION['successMsg'] = 'Vehicle added successfully.';
        } else {
            $_SESSION['errorMsg'] = 'Failed to add vehicle.';
        }
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
 
    // edit
    elseif ($action === 'edit') {
        $id        = intval($_POST['vehicleID'] ?? 0);
        $plateNum  = trim($_POST['plateNum']   ?? '');
        $model     = trim($_POST['model']      ?? '');
        $type      = trim($_POST['type']       ?? '');
        $capacity  = floatval($_POST['capacity'] ?? 0);
        $newStatus = $_POST['status'] ?? 'Available';
 
        // Fetch current status
        $cur = $conn->query("SELECT status FROM tblvehicle WHERE vehicleID=$id")->fetch_assoc();
        $currentStatus = $cur['status'] ?? 'Available';
 
        $statusError = '';
 
        // Block: 'In Use' and 'Maintenance' cannot be set manually
        if ($newStatus === 'In Use') {
            $newStatus   = $currentStatus;
            $statusError = 'Vehicle is still in use in an ongoing job.';
        } elseif ($newStatus === 'Maintenance') {
            $newStatus   = $currentStatus;
            $statusError = 'Maintenance is still in progress.';
        }
        // Block: cannot set Available while vehicle has an Ongoing job
        elseif ($newStatus === 'Available' && $currentStatus === 'In Use') {
            $newStatus   = $currentStatus;
            $statusError = 'Cannot set Available while the vehicle has an Ongoing job.';
        }
        // Block: cannot set Available while active maintenance still exists
        elseif ($newStatus === 'Available' && $currentStatus === 'Maintenance') {
            $hasActiveMaint = $conn->query("
                SELECT 1 FROM tblmaintenance
                WHERE vehicleID = $id
                  AND status IN ('Scheduled','In Progress')
                LIMIT 1
            ")->num_rows > 0;
            if ($hasActiveMaint) {
                $newStatus   = $currentStatus;
                $statusError = 'Cannot set Available while maintenance is still Scheduled or In Progress.';
            }
        }
 
        if ($statusError) {
            $_SESSION['errorMsg'] = $statusError;
        } else {
            $stmt = $conn->prepare("
                UPDATE tblvehicle 
                SET plateNum=?,model=?,type=?,capacityWeight=?,status=? 
                WHERE vehicleID=?");
            $stmt->bind_param('sssdsi', $plateNum, $model, $type, $capacity, $newStatus, $id);
            if ($stmt->execute()) {
                $_SESSION['successMsg'] = 'Vehicle updated successfully.';
            } else {
                $_SESSION['errorMsg'] = 'Failed to update vehicle.';
            }
            $stmt->close();
        }
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
 
    // delete
    elseif ($action === 'delete') {
        $id = intval($_POST['vehicleID'] ?? 0);
        // Block deletion if job record exist
        $hasJobs = $conn->query("SELECT 1 FROM tbljob WHERE vehicleID=$id LIMIT 1")->num_rows > 0;
        $hasMaintenance = $conn->query("SELECT 1 FROM tblmaintenance WHERE vehicleID=$id LIMIT 1")->num_rows > 0;
        if ($hasJobs) {
            $_SESSION['errorMsg'] = 'Cannot delete: vehicle has existing job records.';
        } elseif ($hasMaintenance) {
            $_SESSION['errorMsg'] = 'Cannot delete: vehicle has existing maintenance records.';
        } else {
            $stmt = $conn->prepare("DELETE FROM tblvehicle WHERE vehicleID=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['successMsg'] = 'Vehicle deleted successfully.';
            } else {
                $_SESSION['errorMsg'] = 'Failed to delete vehicle.';
            }
            $stmt->close();
        }
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
 
    // schedule maintenance
    elseif ($action === 'schedule_maintenance') {
        $vehicleID   = intval($_POST['vehicleID'] ?? 0);
        $mtype       = $_POST['maintenanceType'] ?? '';
        $startDate   = $_POST['startDate'] ?? '';
        $endDate     = !empty($_POST['endDate']) ? $_POST['endDate'] : null;
        $description = trim($_POST['description'] ?? '');
 
        $allowedTypes = ['Routine','Repair','Inspection'];
        if (!in_array($mtype, $allowedTypes)) $mtype = 'Routine';

        // Conflict check 1: vehicle already has a job on startDate
        $escapedStart = $conn->real_escape_string($startDate);
        $jobConflict = $conn->query("
            SELECT 1 FROM tbljob
            WHERE vehicleID = $vehicleID
              AND scheduledDate = '$escapedStart'
              AND status IN ('Pending','Scheduled','Ongoing')
            LIMIT 1
        ")->num_rows > 0;

        if ($jobConflict) {
            $_SESSION['errorMsg'] = 'Cannot schedule maintenance: this vehicle already has a job on ' . date('d/m/Y', strtotime($startDate)) . '.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        // Conflict check 2: overlapping active maintenance already exists
        if ($endDate) {
            $escapedEnd = $conn->real_escape_string($endDate);
            $maintConflict = $conn->query("
                SELECT 1 FROM tblmaintenance
                WHERE vehicleID = $vehicleID
                  AND status IN ('Scheduled','In Progress')
                  AND startDate <= '$escapedEnd'
                  AND (endDate IS NULL OR endDate >= '$escapedStart')
                LIMIT 1
            ")->num_rows > 0;
        } else {
            $maintConflict = $conn->query("
                SELECT 1 FROM tblmaintenance
                WHERE vehicleID = $vehicleID
                  AND status IN ('Scheduled','In Progress')
                  AND startDate = '$escapedStart'
                LIMIT 1
            ")->num_rows > 0;
        }

        if ($maintConflict) {
            $_SESSION['errorMsg'] = 'Cannot schedule maintenance: an active maintenance record already exists on overlapping dates.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        // Auto-set status: In Progress if startDate <= today, else Scheduled
        $mstatus = ($startDate <= date('Y-m-d')) ? 'In Progress' : 'Scheduled';
 
        $stmt = $conn->prepare("INSERT INTO tblmaintenance (vehicleID,type,description,startDate,endDate,status) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('isssss', $vehicleID, $mtype, $description, $startDate, $endDate, $mstatus);
        if ($stmt->execute()) {
            // change Available vehicle to Maintenance if record is active and startDate arrived
            if ($startDate <= date('Y-m-d')) {
                $conn->query("UPDATE tblvehicle SET status='Maintenance' WHERE vehicleID=$vehicleID AND status='Available'");
            }
            $_SESSION['successMsg'] = 'Maintenance record saved.';
        } else {
            $_SESSION['errorMsg'] = 'Failed to save maintenance record.';
        }
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
 
    // update maintenance status
    elseif ($action === 'update_maintenance_status') {
        $maintenanceID = intval($_POST['maintenanceID'] ?? 0);
        $newMStatus    = trim($_POST['newMStatus'] ?? '');
        $allowedMStatus = ['In Progress','Completed','Cancelled'];
        if ($maintenanceID <= 0 || !in_array($newMStatus, $allowedMStatus)) {
            $_SESSION['errorMsg'] = 'Invalid maintenance status update.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        // Fetch current maintenance record to validate transition
        $mRow = $conn->query("SELECT status, vehicleID FROM tblmaintenance WHERE maintenanceID=$maintenanceID")->fetch_assoc();
        if (!$mRow) {
            $_SESSION['errorMsg'] = 'Maintenance record not found.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }
        $curMStatus = $mRow['status'];
        $vid        = (int)$mRow['vehicleID'];

        // Validate forward-only transitions
        $validTransitions = [
            'Scheduled'   => ['In Progress', 'Cancelled'],
            'In Progress' => ['Completed',   'Cancelled'],
        ];
        if (!isset($validTransitions[$curMStatus]) || !in_array($newMStatus, $validTransitions[$curMStatus])) {
            $_SESSION['errorMsg'] = 'Invalid status transition from "' . $curMStatus . '" to "' . $newMStatus . '".';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        $stmt = $conn->prepare("UPDATE tblmaintenance SET status=? WHERE maintenanceID=?");
        $stmt->bind_param('si', $newMStatus, $maintenanceID);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // If Completed or Cancelled, check if vehicle can revert to Available
            if (in_array($newMStatus, ['Completed','Cancelled'])) {
                $stillActive = $conn->query("
                    SELECT 1 FROM tblmaintenance
                    WHERE vehicleID = $vid
                      AND status IN ('Scheduled','In Progress')
                    LIMIT 1
                ")->num_rows > 0;
                if (!$stillActive) {
                    $conn->query("
                        UPDATE tblvehicle
                        SET status = 'Available'
                        WHERE vehicleID = $vid
                          AND status = 'Maintenance'
                    ");
                }
            }
            $_SESSION['successMsg'] = 'Maintenance status updated.';
        } else {
            $_SESSION['errorMsg'] = 'Failed to update maintenance status.';
        }
        $stmt->close();
        header('Location: ' . $_SERVER['PHP_SELF']); exit;
    }
 
    // check status change eligibility (AJAX)
    elseif ($action === 'check_status_change') {
        header('Content-Type: application/json');
        $vehicleID = intval($_POST['vehicleID'] ?? 0);
        $newStatus = trim($_POST['newStatus'] ?? '');

        $allowed = ['Available','In Use','Maintenance','Inactive'];
        if ($vehicleID <= 0 || !in_array($newStatus, $allowed)) {
            echo json_encode(['result' => 'error', 'message' => 'Invalid request.']);
            exit;
        }

        // Get current status
        $cur = $conn->query("SELECT status FROM tblvehicle WHERE vehicleID=$vehicleID")->fetch_assoc();
        if (!$cur) {
            echo json_encode(['result' => 'error', 'message' => 'Vehicle not found.']);
            exit;
        }

        // no change
        if ($cur['status'] === $newStatus) {
            echo json_encode(['result' => 'ok']);
            exit;
        }

        // block manual In Use
        if ($newStatus === 'In Use') {
            echo json_encode([
                'result'  => 'blocked',
                'message' => 'Vehicle is still in use in an ongoing job.'
            ]); exit;
        }

        // block manual Maintenance
        if ($newStatus === 'Maintenance') {
            echo json_encode([
                'result'  => 'blocked',
                'message' => 'Maintenance is still in progress'
            ]); exit;
        }

        // block Available while vehicle has an Ongoing job
        if ($newStatus === 'Available' && $cur['status'] === 'In Use') {
            echo json_encode([
                'result'  => 'blocked',
                'message' => 'Cannot set Available while the vehicle has an Ongoing job.'
            ]); exit;
        }

        // block Available while active maintenance still exists
        if ($newStatus === 'Available' && $cur['status'] === 'Maintenance') {
            $hasActiveMaint = $conn->query("
                SELECT 1 FROM tblmaintenance
                WHERE vehicleID = $vehicleID
                  AND status IN ('Scheduled','In Progress')
                LIMIT 1
            ")->num_rows > 0;
            if ($hasActiveMaint) {
                echo json_encode([
                    'result'  => 'blocked',
                    'message' => 'Cannot set Available while maintenance is still Scheduled or In Progress.'
                ]); exit;
            }
        }

        // if setting Inactive, check for pending/scheduled jobs requiring reassignment
        if ($newStatus === 'Inactive') {
            $r = $conn->query("
                SELECT j.jobID, j.scheduledDate, cr.pickupAddress, cr.pickupState
                FROM tbljob j
                JOIN tblcollection_request cr ON j.requestID = cr.requestID
                WHERE j.vehicleID=$vehicleID AND j.status IN ('Pending','Scheduled')
            ");
            $pendingJobs = [];
            while ($row = $r->fetch_assoc()) $pendingJobs[] = $row;

            if (!empty($pendingJobs)) {
                // Build per-job available vehicles: exclude current vehicle and any vehicle
                // already booked (Pending/Scheduled/Ongoing) on the same scheduledDate,
                // or has active maintenance on that date
                $availVehiclesByJob = [];
                foreach ($pendingJobs as $job) {
                    $date  = $conn->real_escape_string($job['scheduledDate']);
                    $r2    = $conn->query("
                        SELECT v.vehicleID, v.plateNum, v.model
                        FROM tblvehicle v
                        WHERE v.status = 'Available'
                        AND v.vehicleID != $vehicleID
                        AND v.vehicleID NOT IN (
                            SELECT vehicleID FROM tbljob
                            WHERE scheduledDate BETWEEN DATE_SUB('$date', INTERVAL 1 DAY)
                                                    AND DATE_ADD('$date', INTERVAL 1 DAY)
                                AND status IN ('Pending','Scheduled','Ongoing')
                        )
                        AND v.vehicleID NOT IN (
                            SELECT vehicleID FROM tblmaintenance
                            WHERE status IN ('Scheduled','In Progress')
                                AND startDate <= DATE_ADD('$date', INTERVAL 1 DAY)
                                AND (endDate IS NULL OR endDate >= DATE_SUB('$date', INTERVAL 1 DAY))
                        )
                        ORDER BY v.plateNum
                    ");
                    $list = [];
                    while ($row = $r2->fetch_assoc()) $list[] = $row;
                    $availVehiclesByJob[(int)$job['jobID']] = $list;
                }

                echo json_encode([
                    'result'             => 'needs_reassignment',
                    'pendingJobs'        => $pendingJobs,
                    'availVehiclesByJob' => $availVehiclesByJob
                ]);
                exit;
            }
        }

        echo json_encode(['result' => 'ok']);
        exit;

    // reassign jobs then apply the full edit
    } elseif ($action === 'reassign_and_change_status') {
        $id          = intval($_POST['vehicleID'] ?? 0);
        $newStatus   = trim($_POST['newStatus']   ?? '');
        $assignments = $_POST['assignments'] ?? [];

        $allowed = ['Available','In Use','Maintenance','Inactive'];
        if ($id <= 0 || !in_array($newStatus, $allowed)) {
            $_SESSION['errorMsg'] = 'Invalid request.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        // Server-side re-check: block manual In Use or Maintenance
        if (in_array($newStatus, ['In Use','Maintenance'])) {
            $_SESSION['errorMsg'] = '"' . $newStatus . '" is managed automatically and cannot be set manually.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        // Fetch all pending/scheduled jobs for this vehicle
        $r = $conn->query("SELECT jobID FROM tbljob WHERE vehicleID=$id AND status IN ('Pending','Scheduled')");
        $pendingJobIDs = [];
        while ($row = $r->fetch_assoc()) $pendingJobIDs[] = (int)$row['jobID'];

        // Fetch scheduledDate per job for conflict checking
        $jobDates = [];
        $r2 = $conn->query("SELECT jobID, scheduledDate FROM tbljob WHERE vehicleID=$id AND status IN ('Pending','Scheduled')");
        while ($row = $r2->fetch_assoc()) $jobDates[(int)$row['jobID']] = $row['scheduledDate'];

        // Validate all jobs have been assigned a new vehicle with no date conflict
        $errors = [];
        foreach ($pendingJobIDs as $jobID) {
            if (empty($assignments[$jobID])) {
                $errors[] = "Job #$jobID has no vehicle assigned.";
            } else {
                $newVehicleID = intval($assignments[$jobID]);
                $check = $conn->query("SELECT status FROM tblvehicle WHERE vehicleID=$newVehicleID")->fetch_assoc();
                if (!$check || $check['status'] !== 'Available') {
                    $errors[] = "Selected vehicle for Job #$jobID is not available.";
                } elseif (isset($jobDates[$jobID])) {
                    $date = $conn->real_escape_string($jobDates[$jobID]);
                    $jobConflict = $conn->query("
                        SELECT 1 FROM tbljob
                        WHERE vehicleID = $newVehicleID
                        AND scheduledDate BETWEEN DATE_SUB('$date', INTERVAL 1 DAY)
                                                AND DATE_ADD('$date', INTERVAL 1 DAY)
                        AND status IN ('Pending','Scheduled','Ongoing')
                        LIMIT 1
                    ")->num_rows > 0;
                    $maintConflict = $conn->query("
                        SELECT 1 FROM tblmaintenance
                        WHERE vehicleID = $newVehicleID
                        AND status IN ('Scheduled','In Progress')
                        AND startDate <= DATE_ADD('$date', INTERVAL 1 DAY)
                        AND (endDate IS NULL OR endDate >= DATE_SUB('$date', INTERVAL 1 DAY))
                        LIMIT 1
                    ")->num_rows > 0;
                    if ($jobConflict) {
                        $errors[] = "Job #$jobID: selected vehicle is already booked on " . date('d/m/Y', strtotime($date)) . ".";
                    } elseif ($maintConflict) {
                        $errors[] = "Job #$jobID: selected vehicle has maintenance scheduled on " . date('d/m/Y', strtotime($date)) . ".";
                    }
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['errorMsg'] = implode(' ', $errors);
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        $plateNum = trim($_POST['plateNum'] ?? '');
        $model    = trim($_POST['model']    ?? '');
        $type     = trim($_POST['type']     ?? '');
        $capacity = floatval($_POST['capacity'] ?? 0);

        $conn->begin_transaction();
        try {
            // Reassign each pending/scheduled job
            $updateJob = $conn->prepare("UPDATE tbljob SET vehicleID=? WHERE jobID=? AND vehicleID=? AND status IN ('Pending','Scheduled')");
            foreach ($pendingJobIDs as $jobID) {
                $newVehicleID = intval($assignments[$jobID]);
                $updateJob->bind_param('iii', $newVehicleID, $jobID, $id);
                $updateJob->execute();
            }

            // Apply the full vehicle edit
            $updateVehicle = $conn->prepare("
                UPDATE tblvehicle 
                SET plateNum=?,model=?,type=?,capacityWeight=?,status=? 
                WHERE vehicleID=?");
            $updateVehicle->bind_param('sssdsi', $plateNum, $model, $type, $capacity, $newStatus, $id);
            $updateVehicle->execute();

            $conn->commit();
            $_SESSION['successMsg'] = 'Jobs reassigned and vehicle updated successfully.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }
    }
}
 
// search
$search = trim($_GET['search'] ?? '');
 
if ($search !== '') {
    $like   = '%' . $conn->real_escape_string($search) . '%';
    $result = $conn->query("SELECT * FROM tblvehicle WHERE plateNum LIKE '$like' OR model LIKE '$like' OR type LIKE '$like' OR status LIKE '$like' ORDER BY createdAt DESC");
} else {
    $result = $conn->query("SELECT * FROM tblvehicle ORDER BY createdAt DESC");
}
 
$vehicles = [];
while ($row = $result->fetch_assoc()) $vehicles[] = $row;
$totalVehicles = count($vehicles);

// Embed maintenance records and format createdAt into each vehicle for JS
foreach ($vehicles as &$v) {
    $v['maintenance'] = getMaintenanceRecords($conn, (int)$v['vehicleID']);
    $v['createdAt']   = date('d/m/Y', strtotime($v['createdAt']));
}
unset($v);

$vehiclesJson = json_encode($vehicles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
 
function getMaintenanceRecords(mysqli $conn, int $vid): array {
    $r    = $conn->query("SELECT * FROM tblmaintenance WHERE vehicleID=$vid ORDER BY startDate DESC");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    return $list;
}
 
function vehicleHasJobs(mysqli $conn, int $vid): bool {
    return $conn->query("SELECT 1 FROM tbljob WHERE vehicleID=$vid LIMIT 1")->num_rows > 0;
}
 
function getPendingJobsForVehicle(mysqli $conn, int $vid): array {
    $r = $conn->query("
        SELECT j.jobID, j.scheduledDate, cr.requestID, cr.pickupAddress, cr.pickupState
        FROM tbljob j
        JOIN tblcollection_request cr ON j.requestID = cr.requestID
        WHERE j.vehicleID=$vid AND j.status IN ('Pending','Scheduled')
    ");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    return $list;
}
 
function getAvailableVehicles(mysqli $conn, int $excludeID): array {
    $r = $conn->query("SELECT vehicleID, plateNum, model FROM tblvehicle WHERE status='Available' AND vehicleID!=$excludeID ORDER BY plateNum");
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    return $list;
}
 
$statusClass = [
    'Available'   => 'status-available',
    'In Use'      => 'status-in-use',
    'Maintenance' => 'status-maintenance',
    'Inactive'    => 'status-inactive',
];
$maintStatusClass = [
    'Scheduled'   => 'maint-scheduled',
    'In Progress' => 'maint-in-progress',
    'Completed'   => 'maint-completed',
    'Cancelled'   => 'maint-cancelled',
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Vehicles - AfterVolt</title>
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

        .vehicle-count {
            color: var(--Gray);
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .dark-mode .vehicle-count {
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

        .vehicles-table-container {
            background: var(--bg-color);
            border-radius: 16px;
            box-shadow: 0 4px 12px var(--shadow-color);
            overflow: hidden;
        }

        .vehicles-table {
            width: 100%;
            border-collapse: collapse;
        }

        .vehicles-table thead {
            background: var(--LightBlue);
            color: var(--text-color);
        }

        .dark-mode .vehicles-table thead {
            background: var(--LowMainBlue);
        }

        .vehicles-table th {
            padding: 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .vehicles-table td {
            text-align: center;
            padding: 1.25rem;
            border-bottom: 1px solid var(--BlueGray);
            color: var(--text-color);
        }

        .vehicles-table .left {
            text-align: left;
        }

        .vehicles-table tbody tr {
            transition: all 0.2s ease;
        }

        .vehicles-table tbody tr:hover {
            background: var(--shadow-color);
        }

        .dark-mode .vehicles-table tbody tr:hover {
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

        .modal-medium { 
            max-width: 720px; 
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

        .maintenance-link {
            color: var(--MainBlue);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            font-family: inherit;
        }

        .maintenance-link:hover {
            text-decoration: underline;
            color: var(--DarkerBlue);
        }

        .no-maintenance {
            color: var(--Gray);
            font-size: 0.85rem;
        }

        .maintenance-section {
            border-top: 1px solid var(--BlueGray);
            padding-top: 1.25rem;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .maintenance-section h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .maintenance-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 10px;
            margin-bottom: 0.5rem;
            gap: 1rem;
        }

        .maintenance-row-info { 
            flex: 1; 
        }

        .maintenance-row-info strong {
            display: block;
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 0.2rem;
        }

        .maintenance-row-info span {
            font-size: 0.8rem;
            color: var(--BlueGray);
        }

        .maintenance-row-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-available {
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .status-inactive {
            background: var(--Gray); 
            color: var(--White); 
        }

        .status-maintenance {
            background: hsl(0,   70%, 90%); 
            color: hsl(0,   70%, 35%); 
        }

        .status-in-use {
            background: var(--LowMainBlue); 
            color: var(--DarkerMainBlue); 
        }

        .maint-scheduled { 
            background: var(--LowMainBlue); 
            color: var(--DarkerMainBlue); 
        }

        .maint-in-progress { 
            background: hsl(0,   70%, 90%); 
            color: hsl(0,   70%, 35%); 
        }
        
        .maint-completed { 
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .maint-cancelled { 
            background: var(--Gray); 
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

        .edit-status-btn {
            background: var(--MainBlue);
            color: var(--White);
        }
        
        .edit-status-btn:hover:not(:disabled) {
            background: var(--DarkerBlue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--BlueGray);
        }

        .dark-mode .edit-status-btn { 
            background: var(--DarkerMainBlue); 
        }

        .edit-status-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
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

        .warning-box {
            background: hsl(0,   70%, 90%); 
            color: hsl(0,   70%, 35%); 
            border: 1px solid red;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            font-size: 0.9rem
        }

        .reassign-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .reassign-table th {
            background: var(--LightBlue);
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dark-mode .reassign-table th {
            background: var(--LowMainBlue);
        }

        .reassign-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--BlueGray);
            color: var(--text-color);
            vertical-align: middle;
        }

        .reassign-table select {
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--BlueGray);
            border-radius: 6px;
            font-size: 0.875rem;
            color: var(--text-color);
            background: var(--bg-color);
            width: 100%;
            font-family: inherit;
        }

        .reassign-table select:focus {
            border-color: var(--MainBlue);
            outline: none;
        }

        .reassign-note {
            margin-bottom: 1.25rem; 
            color: var(--text-color);
        }

        .delete-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none; 
            box-shadow: none;
        }

        .edit-status-note {
            color: var(--BlueGray);
            font-size: 0.78rem;
            margin-top: 0.2rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .vehicles-table {
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

            .vehicles-table-container {
                overflow-x: auto;
            }

            .vehicles-table {
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
                <h1>Manage Vehicles</h1>
                <p class="vehicle-count">Total Vehicles: <?php echo $totalVehicles; ?></p>
            </div>

            <div class="search-add-section">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input"
                        placeholder="Search by plate, model, type, status…"
                        value="<?php echo sanitize($search) ?>">
                    <button type="submit" class="search-btn">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF'] ?>" class="clear-btn">Clear</a>
                    <?php endif; ?>
                </form>
                <button class="add-btn" onclick="openAddModal()">
                    + Add New Vehicle
                </button>
            </div>

            <div class="vehicles-table-container">
                <table class="vehicles-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="left">Plate Number</th>
                            <th>Model</th>
                            <th>Type</th>
                            <th>Capacity Weight (kg)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($vehicles)): ?>
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <h3>No vehicles found</h3>
                                <p><?php echo $search ? 'Try a different search term.' : 'Add a vehicle to get started.' ?></p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($vehicles as $i => $v):
                            $hasJobs      = vehicleHasJobs($conn, $v['vehicleID']);
                            $hasMaintenance = !empty($v['maintenance']);
                            $cantDelete   = $hasJobs || $hasMaintenance;
                            $badgeCss     = $statusClass[$v['status']] ?? 'status-available';
                        ?>
                        <tr>
                            <td><?php echo $v['vehicleID'] ?></td>
                            <td class="left"><strong><?php echo sanitize($v['plateNum']) ?></strong></td>
                            <td><?php echo sanitize($v['model']) ?></td>
                            <td><?php echo sanitize($v['type']) ?></td>
                            <td><?php echo number_format($v['capacityWeight'], 0) ?></td>
                            <td><span class="status-badge <?php echo $badgeCss ?>"><?php echo sanitize($v['status']) ?></span></td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn" title="View"
                                        onclick='openViewModal(<?php echo $i ?>)'>
                                        <img src="../../assets/images/view-icon-white.svg" alt="View">
                                    </button>
                                    <button class="action-btn" title="Schedule Maintenance"
                                        onclick='openMaintenanceSchedModal(<?php echo $i ?>)'>
                                        <img src="../../assets/images/calendar-icon-white.svg" alt="Maintenance">
                                    </button>
                                    <button class="action-btn" title="Edit"
                                        onclick='openEditModal(<?php echo $i ?>)'>
                                        <img src="../../assets/images/edit-icon-white.svg" alt="Edit">
                                    </button>
                                    <button class="action-btn delete-btn <?php echo $cantDelete ? 'delete-disabled' : '' ?>"
                                            title="<?php echo $hasJobs ? 'Cannot delete: vehicle has job records' : ($hasMaintenance ? 'Cannot delete: vehicle has maintenance records' : 'Delete') ?>"
                                            <?php echo $cantDelete ? 'disabled' : '' ?>
                                            onclick='openDeleteModal(<?php echo $i ?>)'>
                                        <img src="../../assets/images/delete-icon-white.svg" alt="Delete">
                                    </button>
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
            <h2>Vehicle Details</h2>
            <div class="view-grid">
                <div class="view-field">
                    <label>Plate Number</label>
                    <span id="view-plateNum">—</span>
                </div>
                <div class="view-field">
                    <label>Model</label>
                    <span id="view-model">—</span>
                </div>
                <div class="view-field">
                    <label>Type</label>
                    <span id="view-type">—</span>
                </div>
                <div class="view-field">
                    <label>Capacity Weight (kg)</label>
                    <span id="view-capacity">—</span>
                </div>
                <div class="view-field">
                    <label>Status</label>
                    <span id="view-status">—</span>
                </div>
                <div class="view-field">
                    <label>Created At</label>
                    <span id="view-createdAt">—</span>
                </div>
            </div>
            <!-- Maintenance Summary -->
            <div class="maintenance-section">
                <h3>Maintenance Records</h3>
                <div id="view-maintenance-list">
                    <p class="no-maintenance">No maintenance records found.</p>
                </div>
            </div>

            <div class="modal-buttons">
                <button class="btn-modal btn-cancel" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View Maintenance Details Modal -->
    <div class="modal-overlay" id="maintenanceViewModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('maintenanceViewModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Maintenance Details</h2>
            <div class="view-grid">
                <div class="view-field full">
                    <label>Vehicle</label>
                    <span id="mview-vehicle">—</span>
                </div>
                <div class="view-field">
                    <label>Type of Maintenance</label>
                    <span id="mview-type">—</span>
                </div>
                <div class="view-field">
                    <label>Status</label>
                    <span id="mview-status">—</span>
                </div>
                <div class="view-field">
                    <label>Start Date</label>
                    <span id="mview-startDate">—</span>
                </div>
                <div class="view-field">
                    <label>End Date</label>
                    <span id="mview-endDate">—</span>
                </div>
                <div class="view-field full">
                    <label>Description</label>
                    <span id="mview-description">—</span>
                </div>
            </div>
            <div class="modal-buttons">
                <button class="btn-modal btn-cancel" onclick="closeModal('maintenanceViewModal')">Close</button>
                <button class="btn-modal edit-status-btn" id="btn-open-edit-mstatus" onclick="openEditMaintenanceStatusModal()">Edit Status</button>
            </div>
        </div>
    </div>

    <!-- Edit Maintenance Status Modal -->
    <div class="modal-overlay" id="editMaintenanceStatusModal">
        <div class="modal modal-small">
            <button class="modal-close-btn" onclick="closeModal('editMaintenanceStatusModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Edit Maintenance Status</h2>
            <form method="POST" id="editMaintenanceStatusForm">
                <input type="hidden" name="action" value="update_maintenance_status">
                <input type="hidden" name="maintenanceID" id="emstatus-maintenanceID">
                <div class="form-grid">
                    <div class="form-field full">
                        <label>Vehicle</label>
                        <input type="text" id="emstatus-vehicle-display" disabled>
                    </div>
                    <div class="form-field full">
                        <label>Maintenance Type</label>
                        <input type="text" id="emstatus-type-display" disabled>
                    </div>
                    <div class="form-field full">
                        <label for="emstatus-status">Status *</label>
                        <!-- Options populated dynamically by JS based on current status -->
                        <select id="emstatus-status" name="newMStatus" required></select>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editMaintenanceStatusModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Save Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule Maintenance Modal -->
    <div class="modal-overlay" id="maintenanceSchedModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('maintenanceSchedModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Schedule Maintenance</h2>
            <form method="POST">
                <input type="hidden" name="action" value="schedule_maintenance">
                <input type="hidden" name="vehicleID" id="msched-vehicleID">
                <div class="form-grid">
                    <div class="form-field full">
                        <label>Vehicle</label>
                        <input type="text" id="msched-vehicle-display" disabled>
                    </div>
                    <div class="form-field">
                        <label for="msched-type">Type of Maintenance *</label>
                        <select id="msched-type" name="maintenanceType" required>
                            <option value="">-- Select Type --</option>
                            <option value="Routine">Routine Service</option>
                            <option value="Repair">Repair</option>
                            <option value="Inspection">Inspection</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="msched-status">Status</label>
                        <select id="msched-status" name="mstatus">
                            <option value="Scheduled">Scheduled</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="msched-startDate">Start Date *</label>
                        <input type="date" id="msched-startDate" name="startDate" required>
                    </div>
                    <div class="form-field">
                        <label for="msched-endDate">End Date</label>
                        <input type="date" id="msched-endDate" name="endDate">
                    </div>
                    <div class="form-field full">
                        <label for="msched-description">Description</label>
                        <textarea id="msched-description" name="description" placeholder="Describe the maintenance work…"></textarea>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('maintenanceSchedModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('editModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Edit Vehicle</h2>
    
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="vehicleID" id="edit-vehicleID">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="edit-plateNum">Plate Number *</label>
                        <input type="text" id="edit-plateNum" name="plateNum" required>
                    </div>
                    <div class="form-field">
                        <label for="edit-model">Model *</label>
                        <input type="text" id="edit-model" name="model" required>
                    </div>
                    <div class="form-field">
                        <label for="edit-type">Type</label>
                        <select id="edit-type" name="type">
                            <option value="Van">Van</option>
                            <option value="Truck">Truck</option>
                            <option value="Car">Car</option>
                            <option value="Lorry">Lorry</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="edit-capacity">Capacity Weight (kg) *</label>
                        <input type="number" id="edit-capacity" name="capacity" min="0" step="0.01" required>
                    </div>
                    <div class="form-field">
                        <label for="edit-status">Status</label>
                        <select id="edit-status" name="status">
                            <option value="Available">Available</option>
                            <option value="In Use" disabled>In Use (auto-managed)</option>
                            <option value="Maintenance" disabled>Maintenance (auto-managed)</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <small id="edit-status-hint" class="edit-status-note"></small>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reassign Jobs Modal -->
    <div class="modal-overlay" id="reassignModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('reassignModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Reassign Pending Jobs</h2>
            <p id="reassign-intro" class="reassign-note">
                This vehicle has pending/scheduled jobs. Please reassign each job to an available vehicle before proceeding with the status change.
            </p>
            <div id="reassign-jobs-container"></div>
            <div class="modal-buttons" style="margin-top:1.5rem;">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('reassignModal')">Cancel</button>
                <button type="button" class="btn-modal btn-save" onclick="submitReassignment()">Confirm Reassignment & Save</button>
            </div>
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
            <p>Are you sure you want to delete this vehicle? This action <strong>cannot be undone</strong>.</p>
            <div class="delete-info-box">
                <strong>Plate No.: </strong><span class="info-name" id="delete-plateNum">—</span><br>
                <strong>Model: </strong><span class="info-name" id="delete-model">—</span>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="vehicleID" id="delete-vehicleID">
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-confirm-delete">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('addModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Add New Vehicle</h2>
            <form method="POST" id="addForm">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="add-plateNum">Plate Number *</label>
                        <input type="text" id="add-plateNum" name="plateNum" placeholder="e.g. VLW 1234" required>
                    </div>
                    <div class="form-field">
                        <label for="add-model">Model *</label>
                        <input type="text" id="add-model" name="model" placeholder="e.g. Toyota Hiace" required>
                    </div>
                    <div class="form-field">
                        <label for="add-type">Type</label>
                        <select id="add-type" name="type">
                            <option value="Van">Van</option>
                            <option value="Truck">Truck</option>
                            <option value="Pickup">Pickup</option>
                            <option value="Lorry">Lorry</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="add-capacity">Capacity Weight (kg) *</label>
                        <input type="number" id="add-capacity" name="capacity" placeholder="e.g. 2000" min="0" step="0.01" required>
                    </div>
                    <div class="form-field">
                        <label for="add-status">Initial Status</label>
                        <select id="add-status" name="status">
                            <option value="Available">Available</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        const maintCss = {
            'Scheduled':   'maint-scheduled',
            'In Progress': 'maint-in-progress',
            'Completed':   'maint-completed',
            'Cancelled':   'maint-cancelled'
        };
        const vehicles    = <?php echo $vehiclesJson; ?>;
        const successMsg = <?php echo json_encode($successMsg); ?>;
        const errorMsg   = <?php echo json_encode($errorMsg); ?>;

        // Valid forward-only maintenance status transitions
        const MAINT_TRANSITIONS = {
            'Scheduled':   ['In Progress', 'Cancelled'],
            'In Progress': ['Completed',   'Cancelled'],
        };

        // Statuses that are finalized — Edit Status button disabled for these
        const FINAL_MAINT_STATUSES = ['Completed', 'Cancelled'];

        // Statuses that cannot be manually selected in the vehicle edit dropdown
        const AUTO_MANAGED_STATUSES = ['In Use', 'Maintenance'];

        function showToast(msg, type) {
            const t = document.getElementById('toast');
            t.className = 'toast ' + type;
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        if (successMsg) showToast(successMsg, 'success');
        if (errorMsg)   showToast(errorMsg,   'error');

        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('active');
            document.body.classList.add('stopScroll');
            modal.querySelector('.modal').scrollTop = 0;
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            if (!document.querySelector('.modal-overlay.active')) {
                document.body.classList.remove('stopScroll');
            }
        }

        let _activeViewIndex = null;

        function openViewModal(index) {
            _activeVehicleIndex = index;
            const v = vehicles[index];

            document.getElementById('view-plateNum').textContent = v.plateNum;
            document.getElementById('view-model').textContent = v.model;
            document.getElementById('view-type').textContent = v.type;
            document.getElementById('view-capacity').textContent = parseFloat(v.capacityWeight).toLocaleString() + ' kg';
            document.getElementById('view-status').textContent = v.status;
            document.getElementById('view-createdAt').textContent = v.createdAt;

            // Build maintenance list
            const list = document.getElementById('view-maintenance-list');
            
            if (!v.maintenance || v.maintenance.length === 0) {
                list.innerHTML = '<p class="no-maintenance">No maintenance records found.</p>';
            } else {
                list.innerHTML = v.maintenance.map((m, mi) => `
                    <div class="maintenance-row">
                        <div class="maintenance-row-info">
                            <strong>${m.type}</strong>
                            <span>${fmt(m.startDate)} → ${m.endDate ? fmt(m.endDate) : 'TBD'}</span>
                        </div>
                        <div class="maintenance-row-actions">
                            <span class="status-badge ${maintCss[m.status] || ''}">${m.status}</span>
                            <button class="maintenance-link" onclick="openMaintenanceViewModal(${index}, ${mi})">View</button>
                        </div>
                    </div>`).join('');
            }
            openModal('viewModal');
        }

        let _currentMaintVehicleIndex = null;
        let _currentMaintIndex        = null;

        function openMaintenanceViewModal(vehicleIndex, maintIndex) {
            _currentMaintVehicleIndex = vehicleIndex;
            _currentMaintIndex        = maintIndex;
            const v = vehicles[vehicleIndex];
            const m = v.maintenance[maintIndex];
            
            document.getElementById('mview-vehicle').textContent = v.plateNum + ' — ' + v.model;
            document.getElementById('mview-type').textContent = m.type;
            document.getElementById('mview-status').textContent = m.status;
            document.getElementById('mview-startDate').textContent = fmt(m.startDate);
            document.getElementById('mview-endDate').textContent = m.endDate ? fmt(m.endDate) : 'Not set';
            document.getElementById('mview-description').textContent = m.description || '—';

            // Disable Edit Status button if maintenance is already finalized
            const editStatusBtn = document.getElementById('btn-open-edit-mstatus');
            const isFinalized = FINAL_MAINT_STATUSES.includes(m.status);
            editStatusBtn.disabled = isFinalized;
            editStatusBtn.title = isFinalized
                ? `Status is already "${m.status}" and cannot be changed`
                : 'Edit the maintenance status';

            openModal('maintenanceViewModal');
        }

        function openEditMaintenanceStatusModal() {
            const v = vehicles[_currentMaintVehicleIndex];
            const m = v.maintenance[_currentMaintIndex];

            // Guard: should not reach here if finalized, but double-check
            if (FINAL_MAINT_STATUSES.includes(m.status)) {
                showToast(`Maintenance is already "${m.status}" and cannot be changed.`, 'error');
                return;
            }

            document.getElementById('emstatus-maintenanceID').value = m.maintenanceID;
            document.getElementById('emstatus-vehicle-display').value = v.plateNum + ' — ' + v.model;
            document.getElementById('emstatus-type-display').value = m.type;

            // Only show valid forward transitions in the dropdown
            const sel = document.getElementById('emstatus-status');
            const allowed = MAINT_TRANSITIONS[m.status] || [];
            sel.innerHTML = allowed.map(s =>
                `<option value="${s}">${s}</option>`
            ).join('');

            closeModal('maintenanceViewModal');
            openModal('editMaintenanceStatusModal');
        }

        function openMaintenanceSchedModal(index) {
            const v = vehicles[index];
            document.getElementById('msched-vehicleID').value = v.vehicleID;
            document.getElementById('msched-vehicle-display').value = v.plateNum + ' — ' + v.model;
            document.getElementById('msched-type').value = '';
            document.getElementById('msched-status').value = 'Scheduled';
            document.getElementById('msched-startDate').value = '';
            document.getElementById('msched-endDate').value = '';
            document.getElementById('msched-description').value = '';
            openModal('maintenanceSchedModal');
        }

        let _editCurrentStatus = '';
        let _editIndex         = null;

        function openEditModal(index) {
            _editIndex = index;
            const v = vehicles[index];
            _editCurrentStatus = v.status;
            const isAutoManaged = AUTO_MANAGED_STATUSES.includes(v.status);
 
            document.getElementById('edit-vehicleID').value = v.vehicleID;
            document.getElementById('edit-plateNum').value = v.plateNum;
            document.getElementById('edit-model').value = v.model;
            document.getElementById('edit-type').value = v.type;
            document.getElementById('edit-capacity').value = parseFloat(v.capacityWeight);
 
            const sel = document.getElementById('edit-status');
            sel.value = v.status;
            sel.disabled = isAutoManaged;

            document.getElementById('edit-status-hint').textContent = isAutoManaged
                ? `"${v.status}" cannot be changed manually.`
                : '';
 
            openModal('editModal');
        }

        // Intercept edit form submit to check status change
        async function handleEditFormSubmit(e) {
            const vehicleID   = document.getElementById('edit-vehicleID').value;
            const statusSelect = document.getElementById('edit-status');
            const newStatus   = statusSelect.value;

            if (statusSelect.disabled) return;

            const current = vehicles.find(v => v.vehicleID == vehicleID);
            if (current && current.status === newStatus) return;

            e.preventDefault();

            // Block In Use / Maintenance client-side before AJAX call
            if (AUTO_MANAGED_STATUSES.includes(newStatus)) {
                showToast(`"${newStatus}" is managed automatically and cannot be set manually.`, 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'check_status_change');
            formData.append('vehicleID', vehicleID);
            formData.append('newStatus', newStatus);

            try {
                const resp = await fetch(window.location.pathname, { method: 'POST', body: formData });
                const data = await resp.json();

                if (data.result === 'ok') {
                    document.getElementById('editForm').removeEventListener('submit', handleEditFormSubmit);
                    document.getElementById('editForm').submit();

                } else if (data.result === 'blocked') {
                    showToast(data.message, 'error');

                } else if (data.result === 'needs_reassignment') {
                    pendingReassignData = {
                        vehicleID,
                        newStatus,
                        editFormData: new FormData(document.getElementById('editForm'))
                    };
                    openReassignModal(data.pendingJobs, data.availVehiclesByJob);
                }
            } catch (err) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        document.getElementById('editForm').addEventListener('submit', handleEditFormSubmit);

        // Reassignment state
        let pendingReassignData = null;

        function sanitizeHTML(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        function openReassignModal(pendingJobs, availVehiclesByJob) {
            const container = document.getElementById('reassign-jobs-container');

            let html = `<table class="reassign-table">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Pickup Address</th>
                        <th>Scheduled Date</th>
                        <th>Reassign to Vehicle</th>
                    </tr>
                </thead>
                <tbody>`;

            pendingJobs.forEach(job => {
                const avail = availVehiclesByJob[job.jobID] || [];
                const options = avail.length
                    ? avail.map(v =>
                        `<option value="${v.vehicleID}">${sanitizeHTML(v.plateNum)} — ${sanitizeHTML(v.model)}</option>`
                      ).join('')
                    : '<option value="" disabled>No available vehicles on this date</option>';

                html += `<tr>
                    <td>#${job.jobID}</td>
                    <td>${sanitizeHTML(job.pickupAddress)}, ${sanitizeHTML(job.pickupState)}</td>
                    <td>${fmt(job.scheduledDate)}</td>
                    <td>
                        <select id="reassign-job-${job.jobID}" data-jobid="${job.jobID}">
                            <option value="">— Select vehicle —</option>
                            ${options}
                        </select>
                    </td>
                </tr>`;
            });

            html += '</tbody></table>';
            container.innerHTML = html;

            closeModal('editModal');
            openModal('reassignModal');
        }

        async function submitReassignment() {
            if (!pendingReassignData) return;

            const selects = document.querySelectorAll('#reassign-jobs-container select[data-jobid]');
            const assignments = {};
            let allAssigned = true;

            selects.forEach(sel => {
                if (!sel.value) {
                    allAssigned = false;
                } else {
                    assignments[sel.dataset.jobid] = sel.value;
                }
            });

            if (!allAssigned) {
                showToast('Please assign a vehicle to every pending job.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reassign_and_change_status');
            formData.append('vehicleID', pendingReassignData.vehicleID);
            formData.append('newStatus', pendingReassignData.newStatus);

            // Also append the rest of the edit form fields
            pendingReassignData.editFormData.forEach((val, key) => {
                if (key !== 'action') formData.append(key, val);
            });

            Object.entries(assignments).forEach(([jobID, vehicleID]) => {
                formData.append(`assignments[${jobID}]`, vehicleID);
            });

            try {
                const resp = await fetch(window.location.pathname, { method: 'POST', body: formData });
                window.location.href = resp.url;
            } catch (err) {
                showToast('An error occurred during reassignment.', 'error');
            }
        }

        function openDeleteModal(index) {
            const v = vehicles[index];
            document.getElementById('delete-vehicleID').value = v.vehicleID;
            document.getElementById('delete-plateNum').textContent = v.plateNum;
            document.getElementById('delete-model').textContent = v.model;
            openModal('deleteModal');
        }

        function openAddModal() {
            document.getElementById('addForm').reset();
            openModal('addModal');
        }

        function fmt(d) {
            if (!d) return '—';
            const p = d.split('-');
            return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : d;
        }
    </script>

</body>
</html>