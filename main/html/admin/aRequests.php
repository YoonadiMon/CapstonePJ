<?php
session_start();
include("../../php/dbConn.php");

// // check if user is logged in
// include("../../php/sessionCheck.php");  

function sanitize($val) {
    return htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
}

$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg   = $_SESSION['errorMsg'] ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestID = (int)($_POST['requestID'] ?? 0);

    if ($requestID <= 0) {
        $_SESSION['errorMsg'] = 'Invalid request ID.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'approve') {
        $stmt = $conn->prepare("
            UPDATE tblcollection_request
            SET status = 'Approved', rejectionReason = NULL
            WHERE requestID = ? AND status = 'Pending'
        ");
        $stmt->bind_param("i", $requestID);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['successMsg'] = 'Request approved successfully.';
        } else {
            $_SESSION['errorMsg'] = 'Failed to approve request or request is no longer pending.';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');

        if ($reason === '') {
            $_SESSION['errorMsg'] = 'Rejection reason is required.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE tblcollection_request
            SET status = 'Rejected', rejectionReason = ?
            WHERE requestID = ? AND status = 'Pending'
        ");
        $stmt->bind_param("si", $reason, $requestID);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['successMsg'] = 'Request rejected successfully.';
        } else {
            $_SESSION['errorMsg'] = 'Failed to reject request or request is no longer pending.';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load Pending Requests
$sql = "
    SELECT 
        cr.requestID,
        cr.status,
        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,
        cr.preferredDateTime,
        cr.createdAt,
        u.fullname,
        u.phone,
        GROUP_CONCAT(DISTINCT it.name ORDER BY it.name SEPARATOR ' · ') AS itemNames,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                it.name,
                CASE 
                    WHEN i.brand IS NOT NULL AND i.brand != '' THEN CONCAT(' (', i.brand, ')')
                    ELSE ''
                END
            )
            ORDER BY it.name SEPARATOR ' · '
        ) AS itemDisplay,
        GROUP_CONCAT(
            DISTINCT TRIM(
                CONCAT(
                    COALESCE(i.brand, ''),
                    CASE 
                        WHEN i.model IS NOT NULL AND i.model != '' AND i.brand IS NOT NULL AND i.brand != ''
                            THEN CONCAT(' ', i.model)
                        WHEN i.model IS NOT NULL AND i.model != ''
                            THEN i.model
                        ELSE ''
                    END
                )
            )
            ORDER BY i.itemID SEPARATOR ' · '
        ) AS brandModel,
        GROUP_CONCAT(DISTINCT i.description ORDER BY i.itemID SEPARATOR ' | ') AS descriptions,
        SUM(i.weight) AS totalWeight
    FROM tblcollection_request cr
    INNER JOIN tblprovider p ON cr.providerID = p.providerID
    INNER JOIN tblusers u ON p.providerID = u.userID
    LEFT JOIN tblitem i ON cr.requestID = i.requestID
    LEFT JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID
    WHERE cr.status = 'Pending'
    GROUP BY 
        cr.requestID,
        cr.status,
        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,
        cr.preferredDateTime,
        cr.createdAt,
        u.fullname,
        u.phone
    ORDER BY cr.preferredDateTime DESC
";

$result = $conn->query($sql);

$requests = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requestID = (int)$row['requestID'];

        $displayId = 'REQ' . str_pad($requestID, 3, '0', STR_PAD_LEFT);
        $preferredDate = !empty($row['preferredDateTime'])
            ? date('d M Y', strtotime($row['preferredDateTime']))
            : '—';
        $sentDate = !empty($row['createdAt'])
            ? date('d M Y, H:i', strtotime($row['createdAt']))
            : '—';
        $weight = number_format((float)($row['totalWeight'] ?? 0), 2);

        $title = $row['itemNames'] ?: 'Collection Request';
        $titleParts = explode(' · ', $title);
        $title = implode(' & ', array_slice($titleParts, 0, 2));

        $description = $row['descriptions']
            ? str_replace(' | ', '. ', $row['descriptions'])
            : 'No description provided.';
        $brandModel = trim($row['brandModel'] ?? '') !== ''
            ? $row['brandModel']
            : 'Not specified';
        $items = $row['itemDisplay'] ?: 'No items listed';

        $addressParts = array_filter([
            $row['pickupAddress'] ?? '',
            $row['pickupState'] ?? '',
            $row['pickupPostcode'] ?? ''
        ], fn($part) => trim((string)$part) !== '');
        $address = !empty($addressParts) ? implode(', ', $addressParts) : 'No address provided';

        $requests[] = [
            'dbID' => $requestID,
            'id' => $displayId,
            'title' => $title,
            'status' => strtolower($row['status']),
            'user' => $row['fullname'] ?: 'Unknown Provider',
            'date' => $preferredDate,
            'weight' => $weight,
            'statusText' => strtolower($row['status']),
            'description' => $description,
            'brand' => $brandModel,
            'address' => $address,
            'items' => $items,
            'contact' => $row['phone'] ?: 'No phone number',
            'sentDate' => $sentDate
        ];
    }
}

$requestsJson = json_encode(
    $requests,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/aRequests.css">

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
            <div class="req-header">
                <h2>Requests</h2>
            </div>

            <div id="requestListView" class="view-list">
                <div class="filter-bar">
                    <div class="filter-group">
                        <div class="sort-dropdown">
                            <button class="sort-dropdown-btn" id="sortDropdownBtn">
                                <i class="fas fa-sort-amount-down"></i> Sort by: Date <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="sort-dropdown-content" id="sortDropdownContent">
                                <a href="#" data-sort="date-desc"><i class="fas fa-calendar"></i> Newest first</a>
                                <a href="#" data-sort="date-asc"><i class="fas fa-calendar"></i> Oldest first</a>
                                <a href="#" data-sort="weight-desc"><i class="fas fa-weight-hanging"></i> Weight (high to low)</a>
                                <a href="#" data-sort="weight-asc"><i class="fas fa-weight-hanging"></i> Weight (low to high)</a>
                            </div>
                        </div>

                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search pending requests..." id="searchInput">
                        </div>
                    </div>
                </div>

                <div class="request-count-container">
                    <span class="request-count" id="requestCount">0 pending requests</span>
                </div>

                <div id="requestsContainer"></div>
            </div>

            <div id="requestDetailView" class="view-detail hidden">
                <div class="detail-view">
                    <button class="back-link" id="backToListBtn">
                        <i class="fas fa-arrow-left"></i> Back to Pending Requests
                    </button>

                    <div class="detail-header">
                        <div class="detail-badge">
                            <h2 id="detailReqId">—</h2>
                            <span class="big-status pending" id="detailStatus">pending</span>
                        </div>
                        <div class="provider-mini" id="detailProvider">
                            <i class="fas fa-store"></i> —
                        </div>
                    </div>

                    <div class="grid-2col">
                        <div class="info-card">
                            <div class="info-row">
                                <div class="info-label">items</div>
                                <div class="info-value" id="detailItems">—</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">description / condition</div>
                                <div class="info-value" id="detailDescription">—</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">brand / model</div>
                                <div class="info-value" id="detailBrand">—</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">weight</div>
                                <div class="info-value" id="detailWeight">—</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">pickup address</div>
                                <div class="info-value" id="detailAddress">—</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">date</div>
                                <div class="info-value" id="detailDate">—</div>
                            </div>
                        </div>

                        <div class="timeline-card">
                            <h4><i class="fas fa-history"></i> Timeline</h4>
                            <div class="timeline-step">
                                <div class="timeline-icon"><i class="fas fa-paper-plane"></i></div>
                                <div class="timeline-content">
                                    <p>Request sent</p>
                                    <small id="detailSentDate">—</small>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="timeline-icon"><i class="fas fa-spinner"></i></div>
                                <div class="timeline-content">
                                    <p>Pending approval</p>
                                    <small>Awaiting admin review</small>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>

                    <div class="action-btns">
                        <button class="btn btn-primary" id="approveBtn">
                            <i class="fas fa-check"></i> Approve Request
                        </button>
                        <button class="btn btn-outline" id="rejectBtn">
                            <i class="fas fa-times"></i> Reject Request
                        </button>
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

    <div class="toast" id="toast"></div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        window.requestsData = <?php echo $requestsJson ?: '[]'; ?>;
        window.successMsg = <?php echo json_encode($successMsg); ?>;
        window.errorMsg = <?php echo json_encode($errorMsg); ?>;
    </script>
    <script src="../../javascript/aRequests.js?v=2"></script>
</body>
</html>