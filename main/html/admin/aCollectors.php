<?php
session_start();
include("../../php/dbConn.php");
 
// check if user is logged in
include("../../php/sessionCheck.php");

// Check if user is admin
if ($_SESSION['userType'] !== 'admin') {
    header("Location: ../../../index.html");
    exit();
}

// Auto-update: set 'on duty' if collector has an Ongoing job
$conn->query("
    UPDATE tblcollector c
    SET c.status = 'on duty'
    WHERE EXISTS (
        SELECT 1 FROM tbljob j
        WHERE j.collectorID = c.collectorID
          AND j.status = 'Ongoing'
    )
    AND c.status != 'on duty'
");
 
// Auto-update: revert 'on duty' back to 'active' when no more Ongoing jobs
$conn->query("
    UPDATE tblcollector c
    SET c.status = 'active'
    WHERE c.status = 'on duty'
      AND NOT EXISTS (
          SELECT 1 FROM tbljob j
          WHERE j.collectorID = c.collectorID
            AND j.status = 'Ongoing'
      )
");
 
function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
 
$validStatuses = ['active', 'on duty', 'inactive', 'suspended'];
 
$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg   = $_SESSION['errorMsg']   ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$conn) {
        $_SESSION['errorMsg'] = 'Database connection failed.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
 
    $action = $_POST['action'] ?? '';
 
    // add
    if ($action === 'add') {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $license  = trim($_POST['license']  ?? '');
        $status   = trim($_POST['status']   ?? '');
        $password = $_POST['password'] ?? '';
 
        $errors = [];
 
        if ($fullname === '') {
            $errors[] = 'Full name is required.';
        } elseif (strlen($fullname) > 100) {
            $errors[] = 'Full name must be 100 characters or fewer.';
        }
 
        if ($username === '') {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3–50 characters (letters, numbers, underscores).';
        }
 
        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (strlen($email) > 150) {
            $errors[] = 'Email must be 150 characters or fewer.';
        }
 
        if ($phone === '') {
            $errors[] = 'Phone number is required.';
        } elseif (!preg_match('/^0[0-9]{9,10}$/', $phone)) {
            $errors[] = 'Enter a valid Malaysian phone number (e.g. 0123456789).';
        }
 
        if ($license === '') {
            $errors[] = 'IC / License number is required.';
        } elseif (!preg_match('/^\d{6}-\d{2}-\d{4}$/', $license)) {
            $errors[] = 'IC / License must be in the format 121212-12-1234.';
        } else {
            // Validate birthday from first 6 digits (YYMMDD)
            $yy = (int)substr($license, 0, 2);
            $mm = (int)substr($license, 2, 2);
            $dd = (int)substr($license, 4, 2);

            // must be 18+ and not over 65 years old
            $currentYY = (int)date('y');
            $yyyy = ($yy > $currentYY) ? 1900 + $yy : 2000 + $yy;

            if (!checkdate($mm, $dd, $yyyy)) {
                $errors[] = 'IC / License contains an invalid date of birth.';
            } else {
                $dob = new DateTime("$yyyy-$mm-$dd");
                $today = new DateTime();
                $age = $today->diff($dob)->y;
                if ($age < 18) {
                    $errors[] = 'Collector must be at least 18 years old.';
                }
                if ($age > 65) {
                    $errors[] = 'IC / License date of birth is not valid (over 65 years).';
                }
            }
        }

        // Only active/inactive allowed on add; on duty and suspended are not selectable
        if (!in_array($status, ['active', 'inactive'])) {
            $errors[] = 'Please select a valid status.';
        }
 
        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
 
        // Duplicate checks
        if (empty($errors)) {
            $checkUser = $conn->prepare("SELECT userID FROM tblusers WHERE username = ?");
            $checkUser->bind_param('s', $username);
            $checkUser->execute();
            if ($checkUser->get_result()->num_rows > 0) {
                $errors[] = 'A user with this username already exists.';
            }
 
            $checkEmail = $conn->prepare("SELECT userID FROM tblusers WHERE email = ?");
            $checkEmail->bind_param('s', $email);
            $checkEmail->execute();
            if ($checkEmail->get_result()->num_rows > 0) {
                $errors[] = 'A user with this email already exists.';
            }
 
            $checkLicense = $conn->prepare("SELECT collectorID FROM tblcollector WHERE licenseNum = ?");
            $checkLicense->bind_param('s', $license);
            $checkLicense->execute();
            if ($checkLicense->get_result()->num_rows > 0) {
                $errors[] = 'A collector with this IC / License number already exists.';
            }

            $checkPhone = $conn->prepare("SELECT userID FROM tblusers WHERE phone = ?");
            $checkPhone->bind_param('s', $phone);
            $checkPhone->execute();
            if ($checkPhone->get_result()->num_rows > 0) {
                $errors[] = 'A user with this phone number already exists.';
            }
        }
 
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $conn->begin_transaction();
            try {
                // Insert into user table
                $insertUser = $conn->prepare(
                    "INSERT INTO tblusers (username, fullname, email, password, phone, userType)
                     VALUES (?, ?, ?, ?, ?, 'collector')"
                );
                $insertUser->bind_param('sssss', $username, $fullname, $email, $hashedPassword, $phone);
                $insertUser->execute();
                $newUserID = $conn->insert_id;

                // Insert into tblcollector using the same ID (collectorID = userID)
                $insertCollector = $conn->prepare(
                    "INSERT INTO tblcollector (collectorID, licenseNum, status) VALUES (?, ?, ?)"
                );
                $insertCollector->bind_param('iss', $newUserID, $license, $status);
                $insertCollector->execute();

                $conn->commit();
                $_SESSION['successMsg'] = 'Collector added successfully.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['errorMsg'] = implode(' ', $errors);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
 
    // edit
    } elseif ($action === 'edit') {
        $collectorID = (int)($_POST['collectorID'] ?? 0);
        $fullname    = trim($_POST['fullname'] ?? '');
        $username    = trim($_POST['username'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $license     = trim($_POST['license'] ?? '');
        $status      = trim($_POST['status'] ?? '');
        $newPassword = $_POST['password']      ?? '';
 
        $errors = [];
 
        if ($collectorID <= 0) { 
            $errors[] = 'Invalid collector record.'; 
        }
 
        if ($fullname === '') {
            $errors[] = 'Full name is required.';
        } elseif (strlen($fullname) > 100) {
            $errors[] = 'Full name must be 100 characters or fewer.';
        }
 
        if ($username === '') {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3–50 characters (letters, numbers, underscores).';
        }
 
        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (strlen($email) > 150) {
            $errors[] = 'Email must be 150 characters or fewer.';
        }
 
        if ($phone === '') {
            $errors[] = 'Phone number is required.';
        } elseif (!preg_match('/^0[0-9]{9,10}$/', $phone)) {
            $errors[] = 'Enter a valid Malaysian phone number.';
        }
 
        if ($license === '') {
            $errors[] = 'IC / License number is required.';
        } elseif (!preg_match('/^\d{6}-\d{2}-\d{4}$/', $license)) {
            $errors[] = 'IC / License must be in the format 121212-12-1234.';
        }
 
        // Block manually setting 'on duty' via the edit form
        if ($status === 'on duty') {
            $curStmtCheck = $conn->query("SELECT status FROM tblcollector WHERE collectorID=$collectorID");
            $curRowCheck  = $curStmtCheck->fetch_assoc();
            $status = $curRowCheck['status'] ?? 'active';
        }

        if (!in_array($status, $validStatuses)) {
            $errors[] = 'Please select a valid status.';
        }

        if ($newPassword !== '' && strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
 
        // Duplicate checks (excluding current records)
        if (empty($errors)) {
            $checkUser = $conn->prepare("SELECT userID FROM tblusers WHERE username = ? AND userID != ?");
            $checkUser->bind_param('si', $username, $collectorID);
            $checkUser->execute();
            if ($checkUser->get_result()->num_rows > 0) {
                $errors[] = 'Another user with this username already exists.';
            }
 
            $checkEmail = $conn->prepare("SELECT userID FROM tblusers WHERE email = ? AND userID != ?");
            $checkEmail->bind_param('si', $email, $collectorID);
            $checkEmail->execute();
            if ($checkEmail->get_result()->num_rows > 0) {
                $errors[] = 'Another user with this email already exists.';
            }
 
            $checkLicense = $conn->prepare("SELECT collectorID FROM tblcollector WHERE licenseNum = ? AND collectorID != ?");
            $checkLicense->bind_param('si', $license, $collectorID);
            $checkLicense->execute();
            if ($checkLicense->get_result()->num_rows > 0) {
                $errors[] = 'Another collector with this IC / License number already exists.';
            }

            $checkPhone = $conn->prepare("SELECT userID FROM tblusers WHERE phone = ? AND userID != ?");
            $checkPhone->bind_param('si', $phone, $collectorID);
            $checkPhone->execute();
            if ($checkPhone->get_result()->num_rows > 0) {
                $errors[] = 'Another user with this phone number already exists.';
            }
        }

        if (empty($errors)) {
            $curStmt = $conn->prepare("SELECT status FROM tblcollector WHERE collectorID = ?");
            $curStmt->bind_param('i', $collectorID); $curStmt->execute();
            $curRow = $curStmt->get_result()->fetch_assoc();
            $currentStatus = $curRow['status'] ?? '';
 
            // status cannot change at all when On Duty
            if (strtolower($currentStatus) === 'on duty' && strtolower($status) !== 'on duty') {
                $_SESSION['errorMsg'] = 'Cannot change status: collector is currently On Duty.';
                header('Location: ' . $_SERVER['PHP_SELF']); exit;
            }

            // Block manually setting to 'suspended' — only allowed via issue interface
            if (strtolower($status) === 'suspended' && strtolower($currentStatus) !== 'suspended') {
                $_SESSION['errorMsg'] = 'Cannot set Suspended: suspension can only be applied through the Issue interface.';
                header('Location: ' . $_SERVER['PHP_SELF']); exit;
            }
 
            // Block non-On Duty status changes when Ongoing or Scheduled jobs exist
            if ($currentStatus !== $status) {
                $blockStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tbljob WHERE collectorID = ? AND status IN ('Ongoing','Scheduled')");
                $blockStmt->bind_param('i', $collectorID); $blockStmt->execute();
                $blockRow = $blockStmt->get_result()->fetch_assoc();
                if ($blockRow['cnt'] > 0) {
                    $_SESSION['errorMsg'] = 'Cannot change status: this collector has active job(s).';
                    header('Location: ' . $_SERVER['PHP_SELF']); exit;
                }
            }
 
            $conn->begin_transaction();
            try {
                // Update user table
                if ($newPassword !== '') {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateUser = $conn->prepare(
                        "UPDATE tblusers SET username=?, fullname=?, email=?, phone=?, password=? WHERE userID=?"
                    );
                    $updateUser->bind_param('sssssi', $username, $fullname, $email, $phone, $hashedPassword, $collectorID);
                } else {
                    $updateUser = $conn->prepare(
                        "UPDATE tblusers SET username=?, fullname=?, email=?, phone=? WHERE userID=?"
                    );
                    $updateUser->bind_param('ssssi', $username, $fullname, $email, $phone, $collectorID);
                }
                $updateUser->execute();

                // Update tblcollector
                $updateCollector = $conn->prepare(
                    "UPDATE tblcollector SET licenseNum=?, status=? WHERE collectorID=?"
                );
                $updateCollector->bind_param('ssi', $license, $status, $collectorID);
                $updateCollector->execute();

                $conn->commit();
                $_SESSION['successMsg'] = 'Collector updated successfully.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['errorMsg'] = implode(' ', $errors);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
 
    // check status change eligibility (AJAX)
    } elseif ($action === 'check_status_change') {
        header('Content-Type: application/json');
        $collectorID = (int)($_POST['collectorID'] ?? 0);
        $newStatus   = trim($_POST['newStatus'] ?? '');

        if ($collectorID <= 0 || !in_array($newStatus, $validStatuses)) {
            echo json_encode(['result' => 'error', 'message' => 'Invalid request.']);
            exit;
        }

        // Get current status
        $curStmt = $conn->prepare("SELECT status FROM tblcollector WHERE collectorID = ?");
        $curStmt->bind_param('i', $collectorID);
        $curStmt->execute();
        $curRow = $curStmt->get_result()->fetch_assoc();
        if (!$curRow) {
            echo json_encode(['result' => 'error', 'message' => 'Collector not found.']);
            exit;
        }

        // no change
        if ($curRow['status'] === $newStatus) {
            echo json_encode(['result' => 'ok']);
            exit;
        }

        // block change if on duty 
        if (strtolower($curRow['status']) === 'on duty') {
            echo json_encode([
                'result'  => 'blocked',
                'message' => 'Cannot change status: collector is currently On Duty. The status will be updated automatically when the job is completed.'
            ]); exit;
        }

        // block manual 'on duty' — auto-managed only
        if (strtolower($newStatus) === 'on duty') {
            echo json_encode([
                'result'  => 'blocked',
                'message' => '"On Duty" is managed automatically and cannot be set manually.'
            ]); exit;
        }

        // block manual 'suspended' — only via issue interface
        if (strtolower($newStatus) === 'suspended') {
            echo json_encode([
                'result'  => 'blocked',
                'message' => 'Cannot set Suspended: suspension can only be applied through the Issue interface.'
            ]); exit;
        }

        // block if Ongoing or Scheduled jobs exist
        $ongoingStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tbljob WHERE collectorID = ? AND status IN ('Ongoing', 'Scheduled')");
        $ongoingStmt->bind_param('i', $collectorID);
        $ongoingStmt->execute();
        $ongoingRow = $ongoingStmt->get_result()->fetch_assoc();
        if ($ongoingRow['cnt'] > 0) {
            echo json_encode([
                'result'  => 'blocked',
                'message' => 'Cannot change status: this collector has ' . $ongoingRow['cnt'] . ' active job(s) (Ongoing or Scheduled).'
            ]);
            exit;
        }

        // Check for Pending jobs — require reassignment
        $pendingStmt = $conn->prepare("
            SELECT j.jobID, j.scheduledDate, j.scheduledTime, j.status,
                   r.pickupAddress, r.pickupState
            FROM tbljob j
            JOIN tblcollection_request r ON j.requestID = r.requestID
            WHERE j.collectorID = ? AND j.status = 'Pending'
        ");
        $pendingStmt->bind_param('i', $collectorID);
        $pendingStmt->execute();
        $pendingResult = $pendingStmt->get_result();
        $pendingJobs = [];
        while ($row = $pendingResult->fetch_assoc()) {
            $pendingJobs[] = $row;
        }

        if (!empty($pendingJobs)) {
            // Build per-job available collectors:
            // active, not this collector, and NOT already assigned a job
            // within ±1 day of each pending job's scheduledDate
            $availCollectorsByJob = [];
            foreach ($pendingJobs as $job) {
                $date = $conn->real_escape_string($job['scheduledDate']);
                $r2 = $conn->query("
                    SELECT c.collectorID, u.fullname, u.phone
                    FROM tblcollector c
                    JOIN tblusers u ON c.collectorID = u.userID
                    WHERE c.status = 'active'
                      AND c.collectorID != $collectorID
                      AND c.collectorID NOT IN (
                          SELECT collectorID FROM tbljob
                          WHERE status IN ('Pending','Scheduled','Ongoing')
                            AND scheduledDate BETWEEN DATE_SUB('$date', INTERVAL 1 DAY)
                                                  AND DATE_ADD('$date', INTERVAL 1 DAY)
                      )
                    ORDER BY u.fullname
                ");
                $list = [];
                while ($row2 = $r2->fetch_assoc()) $list[] = $row2;
                $availCollectorsByJob[(int)$job['jobID']] = $list;
            }

            echo json_encode([
                'result'               => 'needs_reassignment',
                'pendingJobs'          => $pendingJobs,
                'availCollectorsByJob' => $availCollectorsByJob
            ]);
            exit;
        }

        // No blocking issues
        echo json_encode(['result' => 'ok']);
        exit;

    // reassign jobs then change status
    } elseif ($action === 'reassign_and_change_status') {
        $collectorID    = (int)($_POST['collectorID'] ?? 0);
        $newStatus      = trim($_POST['newStatus'] ?? '');
        $assignments    = $_POST['assignments'] ?? [];

        if ($collectorID <= 0 || !in_array($newStatus, $validStatuses)) {
            $_SESSION['errorMsg'] = 'Invalid request.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Server-side On Duty re-check
        $curStmt = $conn->prepare("SELECT status FROM tblcollector WHERE collectorID = ?");
        $curStmt->bind_param('i', $collectorID); $curStmt->execute();
        $curRow = $curStmt->get_result()->fetch_assoc();
        if ($curRow && strtolower($curRow['status']) === 'on duty') {
            $_SESSION['errorMsg'] = 'Cannot change status: collector is currently On Duty.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        // Block manual 'on duty' server-side
        if (in_array(strtolower($newStatus), ['on duty'])) {
            $_SESSION['errorMsg'] = '"' . $newStatus . '" cannot be set manually here.';
            header('Location: ' . $_SERVER['PHP_SELF']); exit;
        }

        // Re-verify no Ongoing jobs (server-side safety check)
        $ongoingStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tbljob WHERE collectorID = ? AND status IN ('Ongoing', 'Scheduled')");
        $ongoingStmt->bind_param('i', $collectorID);
        $ongoingStmt->execute();
        $ongoingRow = $ongoingStmt->get_result()->fetch_assoc();
        if ($ongoingRow['cnt'] > 0) {
            $_SESSION['errorMsg'] = 'Cannot change status: collector has active job(s) (Ongoing or Scheduled).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Fetch all pending jobs for this collector
        $pendingStmt = $conn->prepare("SELECT jobID, scheduledDate FROM tbljob WHERE collectorID = ? AND status = 'Pending'");
        $pendingStmt->bind_param('i', $collectorID);
        $pendingStmt->execute();
        $pendingResult = $pendingStmt->get_result();
        $pendingJobIDs = [];
        $jobDates      = [];
        while ($row = $pendingResult->fetch_assoc()) {
            $pendingJobIDs[] = (int)$row['jobID'];
            $jobDates[(int)$row['jobID']] = $row['scheduledDate'];
        }

        // Validate that all pending jobs have been assigned a new collector
        // and verify no ±1 day conflict for that collector
        $errors = [];
        foreach ($pendingJobIDs as $jobID) {
            if (empty($assignments[$jobID])) {
                $errors[] = "Job #$jobID has no collector assigned.";
            } else {
                $newCollectorID = (int)$assignments[$jobID];
                // Verify the selected collector is active
                $checkStmt = $conn->prepare("SELECT status FROM tblcollector WHERE collectorID = ?");
                $checkStmt->bind_param('i', $newCollectorID);
                $checkStmt->execute();
                $checkRow = $checkStmt->get_result()->fetch_assoc();
                if (!$checkRow || $checkRow['status'] !== 'active') {
                    $errors[] = "Selected collector for Job #$jobID is not active.";
                } elseif (isset($jobDates[$jobID])) {
                    $date = $conn->real_escape_string($jobDates[$jobID]);
                    $conflict = $conn->query("
                        SELECT 1 FROM tbljob
                        WHERE collectorID = $newCollectorID
                          AND status IN ('Pending','Scheduled','Ongoing')
                          AND scheduledDate BETWEEN DATE_SUB('$date', INTERVAL 1 DAY)
                                                AND DATE_ADD('$date', INTERVAL 1 DAY)
                        LIMIT 1
                    ")->num_rows > 0;
                    if ($conflict) {
                        $errors[] = "Job #$jobID: selected collector already has a job within 1 day of " . date('d/m/Y', strtotime($date)) . ".";
                    }
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['errorMsg'] = implode(' ', $errors);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $conn->begin_transaction();
        try {
            // Reassign each pending job
            $updateJob = $conn->prepare("UPDATE tbljob SET collectorID = ? WHERE jobID = ? AND collectorID = ? AND status = 'Pending'");
            foreach ($pendingJobIDs as $jobID) {
                $newCollectorID = (int)$assignments[$jobID];
                $updateJob->bind_param('iii', $newCollectorID, $jobID, $collectorID);
                $updateJob->execute();
            }

            // Apply the full collector edit (user fields + status)
            $fullname    = trim($_POST['fullname'] ?? '');
            $username    = trim($_POST['username'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $phone       = trim($_POST['phone'] ?? '');
            $license     = trim($_POST['license'] ?? '');
            $newPassword = $_POST['password'] ?? '';

            if ($newPassword !== '') {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateUser = $conn->prepare(
                    "UPDATE tblusers SET username=?, fullname=?, email=?, phone=?, password=? WHERE userID=?"
                );
                $updateUser->bind_param('sssssi', $username, $fullname, $email, $phone, $hashedPassword, $collectorID);
            } else {
                $updateUser = $conn->prepare(
                    "UPDATE tblusers SET username=?, fullname=?, email=?, phone=? WHERE userID=?"
                );
                $updateUser->bind_param('ssssi', $username, $fullname, $email, $phone, $collectorID);
            }
            $updateUser->execute();

            $updateCollector = $conn->prepare("UPDATE tblcollector SET licenseNum=?, status=? WHERE collectorID=?");
            $updateCollector->bind_param('ssi', $license, $newStatus, $collectorID);
            $updateCollector->execute();

            $conn->commit();
            $_SESSION['successMsg'] = 'Jobs reassigned and collector updated successfully.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

    // delete
    } elseif ($action === 'delete') {
        $collectorID = (int)($_POST['collectorID'] ?? 0);
 
        if ($collectorID <= 0) {
            $_SESSION['errorMsg'] = 'Invalid collector record.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
 
        // Block deletion if any job records are linked, regardless of job status
        $jobCheckStmt = $conn->prepare("SELECT COUNT(*) AS count FROM tbljob WHERE collectorID = ?");
        $jobCheckStmt->bind_param('i', $collectorID);
        $jobCheckStmt->execute();
        $jobCheckRow = $jobCheckStmt->get_result()->fetch_assoc();
        if ($jobCheckRow['count'] > 0) {
            $_SESSION['errorMsg'] = 'Cannot delete this collector with ' . $jobCheckRow['count'] . ' job record(s) linked to them.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $conn->begin_transaction();
        try {
            // Delete tblcollector row first (FK constraint), then tblusers
            $deleteCollector = $conn->prepare("DELETE FROM tblcollector WHERE collectorID = ?");
            $deleteCollector->bind_param('i', $collectorID);
            $deleteCollector->execute();

            // Delete tblusers row (collectorID = userID, shared PK)
            $deleteUser = $conn->prepare("DELETE FROM tblusers WHERE userID = ?");
            $deleteUser->bind_param('i', $collectorID);
            $deleteUser->execute();

            if ($deleteUser->affected_rows > 0) {
                $conn->commit();
                $_SESSION['successMsg'] = 'Collector deleted successfully.';
            } else {
                $conn->rollback();
                $_SESSION['errorMsg'] = 'Collector not found or could not be deleted.';
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}
 
// search 
$search = trim($_GET['search'] ?? '');
$searchParam = '%' . $search . '%';
 
$sql = "SELECT c.collectorID, c.licenseNum, c.status,
               u.username, u.fullname, u.email, u.phone,
               DATE_FORMAT(u.createdAt, '%d/%m/%Y') AS createdAt,
               DATE_FORMAT(u.lastLogin,  '%d/%m/%Y') AS lastLogin,
               (SELECT COUNT(*) FROM tbljob j WHERE j.collectorID = c.collectorID) AS jobCount,
               (SELECT COUNT(*) FROM tbljob j WHERE j.collectorID = c.collectorID AND j.status = 'Ongoing')   AS ongoingJobCount,
               (SELECT COUNT(*) FROM tbljob j WHERE j.collectorID = c.collectorID AND j.status = 'Scheduled') AS scheduledJobCount
        FROM tblcollector c
        JOIN tblusers u ON c.collectorID = u.userID
        WHERE u.fullname LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?
        ORDER BY c.collectorID";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $searchParam, $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
$collectors = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['collectorID'];
    $collectors[] = $row;
}
 
$totalCollectors = count($collectors);
$collectorsJson  = json_encode($collectors, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Collectors - AfterVolt</title>
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

        .user-count {
            color: var(--Gray);
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .dark-mode .user-count {
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

        .users-table-container {
            background: var(--bg-color);
            border-radius: 16px;
            box-shadow: 0 4px 12px var(--shadow-color);
            overflow: hidden;
        }

        .dark-mode .users-table-container {
            box-shadow: 0 4px 8px var(--BlueGray);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table thead {
            background: var(--LightBlue);
            color: var(--text-color);
        }

        .dark-mode .users-table thead {
            background: var(--LowMainBlue);
        }

        .users-table th {
            padding: 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .users-table td {
            text-align: center;
            padding: 1.25rem;
            border-bottom: 1px solid var(--BlueGray);
            color: var(--text-color);
        }

        .users-table .left {
            text-align: left;
        }

        .users-table tbody tr {
            transition: all 0.2s ease;
        }

        .users-table tbody tr:hover {
            background: var(--shadow-color);
        }

        .dark-mode .users-table tbody tr:hover {
            background: var(--Gray);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-details h4 {
            margin: 0;
            font-weight: 600;
            color: var(--text-color);
        }

        .user-details p {
            margin: 0.25rem 0 0;
            font-size: 0.85rem;
            color: var(--BlueGray);
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

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            flex: 1;
            padding-right: 2.75rem;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .password-toggle:hover { 
            opacity: 1; 
        }

        .password-toggle img { 
            width: 1.25rem; 
            height: 1.25rem; 
        }

        .password-note {
            font-size: 0.85rem;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            color: var(--Gray);
        }

        .dark-mode .password-note {
            color: var(--BlueGray);
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

        /* Status badge styles */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-active {
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .status-on-duty { 
            background: var(--LowMainBlue); 
            color: var(--DarkerMainBlue); 
        }

        .status-inactive {
            background: var(--Gray); 
            color: var(--White); 
        }

        .status-suspended {
            background: hsl(0,   70%, 90%); 
            color: hsl(0,   70%, 35%); 
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

        .status-hint-note {
            color: var(--BlueGray);
            font-size: 0.78rem;
            margin-top: 0.2rem;
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .users-table {
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

            .users-table-container {
                overflow-x: auto;
            }

            .users-table {
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
                <h1>Manage Collectors</h1>
                <p class="user-count">Total Collectors: <?php echo $totalCollectors; ?></p>
            </div>

            <div class="search-add-section">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input"
                        placeholder="Search by name, username, email, or phone..."
                        value="<?php echo sanitize($search); ?>">
                    <button type="submit" class="search-btn">Search</button>
                    <?php if ($search): ?>
                        <a href="?" class="clear-btn">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
                <button class="add-btn" onclick="openAddModal()">
                    + Add New Collector
                </button>
            </div>

            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="left">Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>IC/License</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($collectors)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <h3>No Collectors Found</h3>
                                        <p><?php echo $search ? 'Try a different search term.' : 'Add a new collector to get started.'; ?></p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($collectors as $i => $c): ?>
                                    <?php
                                        $statusClass = match(strtolower($c['status'])) {
                                            'inactive' => 'status-inactive',
                                            'on duty'    => 'status-on-duty',
                                            'suspended' => 'status-suspended',
                                            default => 'status-active'
                                        };
                                    ?>
                                <tr>
                                    <td>#<?php echo sanitize($c['collectorID']); ?></td>
                                    <td class="left">
                                        <div class="user-info">
                                            <div class="user-details">
                                                <h4><?php echo sanitize($c['fullname']); ?></h4>
                                                <p>@<?php echo sanitize($c['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo sanitize($c['email']); ?></td>
                                    <td><?php echo sanitize($c['phone']); ?></td>
                                    <td><?php echo sanitize($c['licenseNum']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo sanitize($c['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn" onclick="openViewModal(<?php echo $i ?>)" title="View">
                                                <img src="../../assets/images/view-icon-white.svg" alt="View">
                                            </button>
                                            <button class="action-btn" onclick="openEditModal(<?php echo $i ?>)" title="Edit">
                                                <img src="../../assets/images/edit-icon-white.svg" alt="Edit">
                                            </button>
                                            <?php 
                                                $totalJobs = (int)$c['jobCount'];
                                            ?>
                                            <?php if ($totalJobs > 0): ?>
                                                <button class="action-btn delete-btn delete-disabled" disabled
                                                    title="Cannot delete: this collector has <?php echo $totalJobs ?> linked job(s)">
                                                    <img src="../../assets/images/delete-icon-white.svg" alt="Delete">
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn delete-btn" onclick="openDeleteModal(<?php echo $i ?>)" title="Delete">
                                                    <img src="../../assets/images/delete-icon-white.svg" alt="Delete">
                                                </button>
                                            <?php endif; ?>
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
                <a href="../../html/admin/aItemProcessing.htphpml">Item Processing</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/Profile.php">Edit Profile</a><br>
                <a href="../../html/common/Setting.php">Setting</a>
            </div>
        </section>
    </footer>

    <div class="toast" id="toast"></div>

    <!-- View Modal -->
     <div class="modal-overlay" id="viewModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('viewModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Collector Details</h2>
            <div class="view-grid">
                <div class="view-field">
                    <label>Full Name</label>
                    <span id="view-fullname">—</span>
                </div>
                <div class="view-field">
                    <label>Username</label>
                    <span id="view-username">—</span>
                </div>
                <div class="view-field">
                    <label>Email</label>
                    <span id="view-email">—</span>
                </div>
                <div class="view-field">
                    <label>Phone</label>
                    <span id="view-phone">—</span>
                </div>
                <div class="view-field">
                    <label>IC / License No.</label>
                    <span id="view-license">—</span>
                </div>
                <div class="view-field">
                    <label>Status</label>
                    <span id="view-status">—</span>
                </div>
                <div class="view-field">
                    <label>Created At</label>
                    <span id="view-createdAt">—</span>
                </div>
                <div class="view-field">
                    <label>Last Login</label>
                    <span id="view-lastLogin">—</span>
                </div>
            </div>
            <div class="modal-buttons">
                <button class="btn-modal btn-cancel" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('editModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Edit Collector</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit-collectorID" name="collectorID">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="edit-fullname">Full Name</label>
                        <input type="text" id="edit-fullname" name="fullname" required>
                        <span class="field-error" id="err-edit-fullname"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-username">Username</label>
                        <input type="text" id="edit-username" name="username" required>
                        <span class="field-error" id="err-edit-username"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-email">Email</label>
                        <input type="email" id="edit-email" name="email" required>
                        <span class="field-error" id="err-edit-email"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-phone">Phone</label>
                        <input type="text" id="edit-phone" name="phone" required>
                        <span class="field-error" id="err-edit-phone"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-license">IC / License No.</label>
                        <input type="text" id="edit-license" name="license" required>
                        <span class="field-error" id="err-edit-license"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-status">Status</label>
                        <select id="edit-status" name="status">
                            <option value="active">Active</option>
                            <option value="on duty" disabled>On Duty (auto-managed)</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended" disabled>Suspended (via Issue only)</option>
                        </select>
                        <small id="edit-status-hint" class="status-hint-note"></small>
                    </div>
                    <div class="form-field full">
                        <label for="edit-password">New Password <span class="password-note">(leave blank to keep current)</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="edit-password" name="password" placeholder="Min. 8 characters">
                            <button type="button" class="password-toggle" onclick="togglePassword('edit-password', this)" tabindex="-1">
                                <img src="../../assets/images/visibility-off-btn-black.svg" class="light-icon" alt="Toggle">
                                <img src="../../assets/images/visibility-off-btn-white.svg" class="dark-icon" alt="Toggle">
                            </button>
                        </div>
                        <span class="field-error" id="err-edit-password"></span>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Save Changes</button>
                </div>
            </form>
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
            <p>Are you sure you want to delete this collector? This action <strong>cannot be undone</strong>.</p>
            <div class="delete-info-box">
                <strong>Collector: </strong><span class="info-name" id="delete-name">—</span><br>
                <strong>IC / License: </strong><span class="info-name" id="delete-license">—</span>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete-collectorID" name="collectorID">
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-confirm-delete">Yes, Delete</button>
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
                This collector has pending jobs. Please reassign each job to an active collector before proceeding with the status change.
            </p>
            <div id="reassign-jobs-container"></div>
            <div class="modal-buttons" style="margin-top:1.5rem;">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('reassignModal')">Cancel</button>
                <button type="button" class="btn-modal btn-save" onclick="submitReassignment()">Confirm Reassignment & Change Status</button>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <button class="modal-close-btn" onclick="closeModal('addModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Add New Collector</h2>
            <form id="addForm" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="add-fullname">Full Name</label>
                        <input type="text" id="add-fullname" name="fullname" placeholder="e.g. Razwan Hakim" required>
                        <span class="field-error" id="err-add-fullname"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-username">Username</label>
                        <input type="text" id="add-username" name="username" placeholder="e.g. razwan_hakim" required>
                        <span class="field-error" id="err-add-username"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-email">Email</label>
                        <input type="email" id="add-email" name="email" placeholder="e.g. razwan@email.com" required>
                        <span class="field-error" id="err-add-email"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-phone">Phone</label>
                        <input type="text" id="add-phone" name="phone" placeholder="e.g. +60112345678" required>
                        <span class="field-error" id="err-add-phone"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-license">IC / License No.</label>
                        <input type="text" id="add-license" name="license" placeholder="e.g. 001122-12-1234" required>
                        <span class="field-error" id="err-add-license"></span>
                    </div>
                    <div class="form-field">
                        <label for="add-status">Status</label>
                        <select id="add-status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-field full">
                        <label for="add-password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="add-password" name="password" placeholder="Min. 8 characters" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('add-password', this)" tabindex="-1">
                                <img src="../../assets/images/visibility-off-btn-black.svg" class="light-icon" alt="Toggle">
                                <img src="../../assets/images/visibility-off-btn-white.svg" class="dark-icon" alt="Toggle">
                            </button>
                        </div>
                        <span class="field-error" id="err-add-password"></span>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Add Collector</button>
                </div>
            </form>
        </div>
    </div>


    <script src="../../javascript/mainScript.js"></script>
    <script>
        // Data from PHP
        const collectors = <?php echo $collectorsJson; ?>;
        const successMsg = <?php echo json_encode($successMsg); ?>;
        const errorMsg = <?php echo json_encode($errorMsg); ?>;
 
        // Toast
        function showToast(msg, type) {
            const t = document.getElementById('toast');
            t.className = 'toast ' + type;
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }
 
        if (successMsg) showToast(successMsg, 'success');
        if (errorMsg)   showToast(errorMsg,   'error');
 
        // Modal helpers
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('active');
            document.body.classList.add('stopScroll');
            modal.querySelector('.modal').scrollTop = 0;
        }
 
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.classList.remove('stopScroll');
        }
 
        // View
        function openViewModal(index) {
            const c = collectors[index];
            document.getElementById('view-fullname').textContent = c.fullname;
            document.getElementById('view-username').textContent = '@' + c.username;
            document.getElementById('view-email').textContent = c.email;
            document.getElementById('view-phone').textContent = c.phone;
            document.getElementById('view-license').textContent = c.licenseNum;
            document.getElementById('view-status').textContent = c.status;
            document.getElementById('view-createdAt').textContent = c.createdAt  || '—';
            document.getElementById('view-lastLogin').textContent = c.lastLogin  || '—';
            openModal('viewModal');
        }
 
        // Reassignment state
        let pendingReassignData = null;

        // Edit
        function openEditModal(index) {
            const c = collectors[index];
            const isOnDuty    = c.status.toLowerCase() === 'on duty';
            const isSuspended = c.status.toLowerCase() === 'suspended';
            const statusSelect = document.getElementById('edit-status');
            const hintEl       = document.getElementById('edit-status-hint');

            document.getElementById('edit-collectorID').value = c.collectorID;
            document.getElementById('edit-fullname').value = c.fullname;
            document.getElementById('edit-username').value = c.username;
            document.getElementById('edit-email').value = c.email;
            document.getElementById('edit-phone').value = c.phone;
            document.getElementById('edit-license').value = c.licenseNum;

            // Reset all options to base disabled state first
            for (const opt of statusSelect.options) {
                // 'on duty' and 'suspended' are always disabled as selectable targets
                opt.disabled = (opt.value === 'on duty' || opt.value === 'suspended');
                // Reset labels
                if (opt.value === 'inactive')  opt.text = 'Inactive';
                if (opt.value === 'suspended') opt.text = 'Suspended (via Issue only)';
            }

            if (isOnDuty) {
                // Locked: show current value, disable entire select
                statusSelect.value    = 'on duty';
                statusSelect.disabled = true;
                hintEl.textContent    = '"On Duty" is managed automatically and cannot be changed manually.';
            } else if (isSuspended) {
                const suspOpt = statusSelect.querySelector('option[value="suspended"]');
                if (suspOpt) suspOpt.disabled = false;
                statusSelect.value    = 'suspended';
                if (suspOpt) suspOpt.disabled = true;
                statusSelect.disabled = false;
                hintEl.textContent    = 'This collector is Suspended. You may restore them to Active or Inactive.';
            } else {
                statusSelect.value    = c.status;
                statusSelect.disabled = false;
                hintEl.textContent    = '';
            }

            // Also disable Inactive if collector has Ongoing or Scheduled jobs
            const scheduledJobCount = parseInt(c.scheduledJobCount) || 0;
            const isBlocked = parseInt(c.ongoingJobCount) > 0 || scheduledJobCount > 0;
            if (isBlocked && !isOnDuty && !isSuspended) {
                const inactiveOpt = statusSelect.querySelector('option[value="inactive"]');
                if (inactiveOpt) {
                    inactiveOpt.disabled = true;
                    inactiveOpt.text = 'Inactive (unavailable: active job assigned)';
                }
                hintEl.textContent = 'Status cannot be changed to Inactive while active jobs (Ongoing or Scheduled) are assigned.';
            }

            const pwField = document.getElementById('edit-password');
            pwField.value = '';
            pwField.type  = 'password';
            const toggleBtn = pwField.nextElementSibling;
            if (toggleBtn) {
                toggleBtn.querySelectorAll('.light-icon').forEach(img => img.src = '../../assets/images/visibility-off-btn-black.svg');
                toggleBtn.querySelectorAll('.dark-icon').forEach(img  => img.src = '../../assets/images/visibility-off-btn-white.svg');
            }

            clearErrors('edit');
            openModal('editModal');
        }

        // Intercept edit form submit to check status change
        async function handleEditFormSubmit(e) {
            if (!validateForm('edit')) { e.preventDefault(); return; }

            const collectorID = document.getElementById('edit-collectorID').value;
            const statusSelect = document.getElementById('edit-status');
            const newStatus   = document.getElementById('edit-status').value;

            if (statusSelect.disabled) return;

            const current = collectors.find(c => c.collectorID == collectorID);
            if (current && current.status === newStatus) return;

            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'check_status_change');
            formData.append('collectorID', collectorID);
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
                        collectorID,
                        newStatus,
                        editFormData: new FormData(document.getElementById('editForm'))
                    };
                    openReassignModal(data.pendingJobs, data.availCollectorsByJob);
                }
            } catch (err) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        document.getElementById('editForm').addEventListener('submit', handleEditFormSubmit);

        function openReassignModal(pendingJobs, availCollectorsByJob) {
            const container = document.getElementById('reassign-jobs-container');

            let html = `<table class="reassign-table">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Pickup Address</th>
                        <th>Scheduled Date</th>
                        <th>Assign To</th>
                    </tr>
                </thead>
                <tbody>`;

            pendingJobs.forEach(job => {
                const avail = availCollectorsByJob[job.jobID] || [];
                const options = avail.length
                    ? avail.map(c =>
                        `<option value="${c.collectorID}">${sanitizeHTML(c.fullname)} — ${sanitizeHTML(c.phone)}</option>`
                      ).join('')
                    : '<option value="" disabled>No available collectors on this date</option>';

                html += `<tr>
                    <td>#${job.jobID}</td>
                    <td>${sanitizeHTML(job.pickupAddress)}, ${sanitizeHTML(job.pickupState)}</td>
                    <td>${job.scheduledDate}</td>
                    <td>
                        <select id="reassign-job-${job.jobID}" data-jobid="${job.jobID}">
                            <option value="">— Select collector —</option>
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

        function sanitizeHTML(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        async function submitReassignment() {
            if (!pendingReassignData) return;

            // Collect all job→collector assignments
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
                showToast('Please assign a collector to every pending job.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reassign_and_change_status');
            formData.append('collectorID', pendingReassignData.collectorID);
            formData.append('newStatus', pendingReassignData.newStatus);

            // Also append the rest of the edit form fields so the full update goes through
            pendingReassignData.editFormData.forEach((val, key) => {
                if (key !== 'action') formData.append(key, val);
            });

            Object.entries(assignments).forEach(([jobID, collectorID]) => {
                formData.append(`assignments[${jobID}]`, collectorID);
            });

            try {
                const resp = await fetch(window.location.pathname, { method: 'POST', body: formData });
                // Follow the redirect URL returned by the server (carries session flash messages)
                window.location.href = resp.url;
            } catch (err) {
                showToast('An error occurred during reassignment.', 'error');
            }
        }
 
        // Delete
        function openDeleteModal(index) {
            const c = collectors[index];
            document.getElementById('delete-collectorID').value = c.collectorID;
            document.getElementById('delete-name').textContent = c.fullname;
            document.getElementById('delete-license').textContent = c.licenseNum;
            openModal('deleteModal');
        }
 
        // Add
        function openAddModal() {
            document.getElementById('addForm').reset();
            clearErrors('add');
            openModal('addModal');
        }
 
        // Validation helpers
        function setError(fieldId, errorId, message) {
            const field = document.getElementById(fieldId);
            const err = document.getElementById(errorId);
            if (field) field.classList.add('invalid');
            if (err)   { err.textContent = message; err.classList.add('show'); }
        }
 
        function clearErrors(prefix) {
            document.querySelectorAll(`#${prefix}Form .invalid`).forEach(el => el.classList.remove('invalid'));
            document.querySelectorAll(`#${prefix}Form .field-error`).forEach(el => {
                el.textContent = '';
                el.classList.remove('show');
            });
        }

        // Password visibility toggle
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelectorAll('.light-icon').forEach(img => {
                img.src = isHidden
                    ? '../../assets/images/visibility-on-btn-black.svg'
                    : '../../assets/images/visibility-off-btn-black.svg';
            });
            btn.querySelectorAll('.dark-icon').forEach(img => {
                img.src = isHidden
                    ? '../../assets/images/visibility-on-btn-white.svg'
                    : '../../assets/images/visibility-off-btn-white.svg';
            });
        }
 
        function validateForm(prefix) {
            clearErrors(prefix);
            let valid = true;
 
            const fullname = document.getElementById(`${prefix}-fullname`).value.trim();
            const username = document.getElementById(`${prefix}-username`).value.trim();
            const email    = document.getElementById(`${prefix}-email`).value.trim();
            const phone    = document.getElementById(`${prefix}-phone`).value.trim();
            const license  = document.getElementById(`${prefix}-license`).value.trim();
 
            if (!fullname) {
                setError(`${prefix}-fullname`, `err-${prefix}-fullname`, 'Full name is required.');
                valid = false;
            } else if (fullname.length > 100) {
                setError(`${prefix}-fullname`, `err-${prefix}-fullname`, 'Full name must be 100 characters or fewer.');
                valid = false;
            }
 
            if (!username) {
                setError(`${prefix}-username`, `err-${prefix}-username`, 'Username is required.');
                valid = false;
            } else if (!/^[a-zA-Z0-9_]{3,50}$/.test(username)) {
                setError(`${prefix}-username`, `err-${prefix}-username`, 'Username must be 3–50 characters (letters, numbers, underscores).');
                valid = false;
            }
 
            if (!email) {
                setError(`${prefix}-email`, `err-${prefix}-email`, 'Email is required.');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                setError(`${prefix}-email`, `err-${prefix}-email`, 'Enter a valid email address.');
                valid = false;
            }
 
            if (!phone) {
                setError(`${prefix}-phone`, `err-${prefix}-phone`, 'Phone number is required.');
                valid = false;
            } else if (!/^0[0-9]{9,10}$/.test(phone)) {
                setError(`${prefix}-phone`, `err-${prefix}-phone`, 'Enter a valid Malaysian phone number (e.g. 0123456789).');
                valid = false;
            }
 
            if (!license) {
                setError(`${prefix}-license`, `err-${prefix}-license`, 'IC / License number is required.');
                valid = false;
            } else if (!/^\d{6}-\d{2}-\d{4}$/.test(license)) {
                setError(`${prefix}-license`, `err-${prefix}-license`, 'IC / License must be in the format 121212-12-1234.');
                valid = false;
            } else {
                // Validate birthday from first 6 digits (YYMMDD)
                const yy = parseInt(license.substring(0, 2), 10);
                const mm = parseInt(license.substring(2, 4), 10);
                const dd = parseInt(license.substring(4, 6), 10);

                const currentYY = parseInt(new Date().getFullYear().toString().slice(-2), 10);
                const yyyy = yy > currentYY ? 1900 + yy : 2000 + yy;

                const dob = new Date(yyyy, mm - 1, dd);
                const isValidDate = dob.getFullYear() === yyyy &&
                                    dob.getMonth() === mm - 1 &&
                                    dob.getDate() === dd;

                if (!isValidDate) {
                    setError(`${prefix}-license`, `err-${prefix}-license`, 'IC / License contains an invalid date of birth.');
                    valid = false;
                } else {
                    const today = new Date();
                    let age = today.getFullYear() - yyyy;
                    const monthDiff = today.getMonth() - (mm - 1);
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dd)) age--;

                    if (age < 18) {
                        setError(`${prefix}-license`, `err-${prefix}-license`, 'Collector must be at least 18 years old.');
                        valid = false;
                    } else if (age > 65) {
                        setError(`${prefix}-license`, `err-${prefix}-license`, 'IC / License date of birth is not valid (over 65 years).');
                        valid = false;
                    }
                }
            }
 
            if (prefix === 'add') {
                // Password required on add
                const password = document.getElementById('add-password').value;
                if (!password) {
                    setError('add-password', 'err-add-password', 'Password is required.');
                    valid = false;
                } else if (password.length < 8) {
                    setError('add-password', 'err-add-password', 'Password must be at least 8 characters.');
                    valid = false;
                }
            } else if (prefix === 'edit') {
                // only validate if filled in
                const password = document.getElementById('edit-password').value;
                if (password && password.length < 8) {
                    setError('edit-password', 'err-edit-password', 'Password must be at least 8 characters.');
                    valid = false;
                }
            }
            return valid;
        }
 
        // Live clear errors on input
        document.querySelectorAll('.form-field input, .form-field select').forEach(el => {
            el.addEventListener('input', function () {
                this.classList.remove('invalid');
                const errEl = document.getElementById('err-' + this.id);
                if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
            });
            el.addEventListener('change', function () {
                this.dispatchEvent(new Event('input'));
            });
        });
 
        // Form submission
        document.getElementById('addForm').addEventListener('submit', function (e) {
            if (!validateForm('add')) e.preventDefault();
        });
    </script>
</body>
</html>