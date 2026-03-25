<?php
session_start();
include("../../php/dbConn.php");

// ── TEMP: hardcoded session for testing (remove once login is done) ──
$_SESSION['userID']   = 10;
$_SESSION['userType'] = 'collector';

// ── Fetch completed/cancelled jobs for this collector ────────────────
$collectorUserID = (int) $_SESSION['userID'];

/*
 * Pull every job that belongs to this collector and is in a
 * terminal state (Completed or Cancelled).
 * We also join through to the provider's user row for their name,
 * and to the collection request for the pickup address / date.
 */
$sqlJobs = "
    SELECT
        j.jobID,
        j.status          AS jobStatus,
        j.scheduledDate,
        j.completedAt,
        cr.requestID,
        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,
        cr.preferredDateTime,
        u.fullname        AS providerName
    FROM tbljob            j
    JOIN tblcollection_request cr ON cr.requestID = j.requestID
    JOIN tblprovider           p  ON p.providerID  = cr.providerID
    JOIN tblusers              u  ON u.userID       = p.providerID
    WHERE j.collectorID = $collectorUserID
      AND j.status IN ('Completed', 'Cancelled')
    ORDER BY j.scheduledDate DESC
";

$jobsResult = mysqli_query($conn, $sqlJobs);

$historyData = [];

if ($jobsResult && mysqli_num_rows($jobsResult) > 0) {
    while ($job = mysqli_fetch_assoc($jobsResult)) {

        $jobID     = (int) $job['jobID'];
        $requestID = (int) $job['requestID'];

        // ── Format display date ──────────────────────────────────────
        $scheduledTs  = strtotime($job['scheduledDate']);
        $displayDate  = date('j-n-Y', $scheduledTs);          // e.g. 12-3-2026
        $detailDate   = date('d/m/Y', $scheduledTs);          // e.g. 12/03/2026

        // ── Provider address string ──────────────────────────────────
        $providerAddress = trim(
            $job['pickupAddress'] . ', ' .
                $job['pickupPostcode'] . ', ' .
                $job['pickupState'] . ', Malaysia'
        );

        // ── Fetch items for this request ─────────────────────────────
        $sqlItems = "
            SELECT
                i.itemID,
                i.description,
                i.model,
                i.brand,
                i.weight,
                i.image,
                i.status      AS itemStatus,
                it.name       AS itemTypeName,
                c.name        AS centreName
            FROM tblitem      i
            JOIN tblitem_type it ON it.itemTypeID = i.itemTypeID
            LEFT JOIN tblcentre c ON c.centreID   = i.centreID
            WHERE i.requestID = $requestID
            ORDER BY i.itemID ASC
        ";

        $itemsResult = mysqli_query($conn, $sqlItems);
        $items = [];

        if ($itemsResult && mysqli_num_rows($itemsResult) > 0) {
            while ($item = mysqli_fetch_assoc($itemsResult)) {

                // ── Build image path ─────────────────────────────────
                // If a real image filename is stored use the assets path,
                // otherwise fall back to a placeholder so the UI still works.
                if (!empty($item['image'])) {
                    $imgSrc = '../../assets/images/items/' . htmlspecialchars($item['image']);
                } else {
                    $imgSrc = '../../assets/images/placeholder-item.png';
                }

                $items[] = [
                    'id'          => 'ITEM' . str_pad($item['itemID'], 3, '0', STR_PAD_LEFT),
                    'name'        => $item['itemTypeName'],
                    'brand'       => trim(($item['brand'] ?? '') . ' ' . ($item['model'] ?? '')),
                    'weight'      => number_format((float) $item['weight'], 1),
                    'dropoff'     => $item['centreName'] ?? 'Not assigned',
                    'description' => $item['description'],
                    'img'         => $imgSrc,
                ];
            }
        }

        // ── Map DB status to display status ──────────────────────────
        $statusMap = [
            'Completed' => 'completed',
            'Cancelled' => 'cancelled',
        ];
        $displayStatus = $statusMap[$job['jobStatus']] ?? strtolower($job['jobStatus']);

        $historyData[] = [
            'id'       => 'JOB' . str_pad($jobID, 3, '0', STR_PAD_LEFT),
            'status'   => $displayStatus,
            'date'     => $displayDate,
            'provider' => [
                'name'    => $job['providerName'],
                'address' => $providerAddress,
                'date'    => $detailDate,
            ],
            'items' => $items,
        ];
    }
}

// ── Encode to JSON for inline JS ─────────────────────────────────────
$historyJson = json_encode($historyData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Collector History</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/cCompletedJobs.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>

<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <!-- Logo + Name & Navbar -->
    <header>
        <!-- Logo + Name -->
        <section class="c-logo-section">
            <a href="../../html/collector/cHome.php" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <!-- Menu Links -->

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

                    <a href="../../html/collector/cHome.php">Home</a>
                    <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
                    <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a><br>
                    <a href="../../html/collector/cCompletedJobs.php">History</a>
                    <a href="../../html/common/About.html">About</a><br>
                </div>
            </div>

        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <a href="../../html/collector/cHome.php">Home</a>
            <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
            <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a><br>
            <a href="../../html/collector/cCompletedJobs.php">History</a>
            <a href="../../html/common/About.html">About</a><br>
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

    <!-- Main Content -->
    <main>

        <!-- Back button -->
        <div class="history-back-row">
            <button class="history-back-btn" onclick="window.history.back()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
                Back
            </button>
        </div>

        <!-- Stats Overview Bar -->
        <section class="stats-bar">
            <div class="stats-card">
                <div class="stats-label">Total Completed Jobs</div>
                <div class="stats-value" id="statJobs">0</div>
            </div>
            <div class="stats-divider"></div>
            <div class="stats-card">
                <div class="stats-label">Total Items Dropoff</div>
                <div class="stats-value" id="statItems">0</div>
            </div>
            <div class="stats-divider"></div>
            <div class="stats-card">
                <div class="stats-label">Total Items Weight</div>
                <div class="stats-value" id="statWeight">0 kg</div>
            </div>
        </section>

        <!-- Two-column layout: list + detail -->
        <div class="history-layout">

            <!-- LEFT: Search + Job List -->
            <aside class="history-list-col">
                <!-- Search -->
                <div class="history-search-box">
                    <input type="text" id="historySearch" placeholder="Search" class="history-search-input" oninput="filterHistory()">
                    <button class="history-search-btn" onclick="filterHistory()">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                            <path d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M19 19L14.65 14.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <!-- Job list -->
                <div class="history-job-list" id="historyJobList">
                    <!-- Populated by JS -->
                </div>
            </aside>

            <!-- RIGHT: Job Detail Panel -->
            <section class="history-detail-col" id="historyDetailCol">

                <!-- Empty state (shown when nothing selected) -->
                <div class="detail-empty" id="detailEmpty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="3" />
                        <path d="M8 10h8M8 14h5" />
                    </svg>
                    <p>Select a job to view details</p>
                </div>

                <!-- Detail content (shown when job selected) -->
                <div class="detail-content" id="detailContent" style="display:none;">

                    <!-- Detail Header -->
                    <div class="detail-header">
                        <div class="detail-title-row">
                            <h2 id="detailJobId">JOB001</h2>
                            <span class="detail-badge badge-completed" id="detailStatus">Completed</span>
                        </div>
                        <div class="detail-meta-row">
                            <span class="detail-meta-item" id="detailDate">—</span>
                            <span class="detail-meta-sep">·</span>
                            <span class="detail-meta-item" id="detailProvider">—</span>
                        </div>
                    </div>

                    <!-- Overview cards -->
                    <div class="detail-overview-grid">
                        <div class="detail-overview-card">
                            <div class="detail-overview-title">Provider</div>
                            <p><span>Name :</span> <strong id="dProviderName">—</strong></p>
                            <p><span>Address :</span> <strong id="dProviderAddress">—</strong></p>
                            <p><span>Date :</span> <strong id="dProviderDate">—</strong></p>
                        </div>
                        <div class="detail-overview-card">
                            <div class="detail-overview-title">Items :</div>
                            <ul id="dItemList" class="detail-item-list"></ul>
                            <span class="detail-weight-tag" id="dTotalWeight">0 kg total</span>
                        </div>
                        <div class="detail-overview-card">
                            <div class="detail-overview-title">Brand : &amp; Model :</div>
                            <ul id="dBrandList" class="detail-item-list"></ul>
                        </div>
                    </div>

                    <!-- Item dropdowns -->
                    <div class="detail-section-title">Item Details</div>
                    <div id="dItemDropdowns"></div>

                </div>

                <!-- Image modal -->
                <div id="historyImageModal" class="img-modal-overlay" onclick="closeHistoryModal()">
                    <div class="img-modal-box" onclick="event.stopPropagation()">
                        <button class="img-modal-close" onclick="closeHistoryModal()">✕</button>
                        <img id="historyModalImg" src="" alt="" class="img-modal-img" />
                        <p id="historyModalCaption" class="img-modal-caption"></p>
                    </div>
                </div>

            </section>
        </div>
    </main>

    <hr>
    <!-- Footer -->
    <footer>
        <!-- Column 1 -->
        <section class="c-footer-info-section">
            <a href="../../html/collector/cHome.php">
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
                <b>My Jobs</b><br>
                <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
                <a href="../../html/collector/cInProgress.php">In Progess</a><br>
                <a href="../../html/collector/cCompletedJobs.php">Completed Jobs</a>
            </div>
            <div>
                <b>Support</b><br>
                <a href="../../html/collector/cReportIssues.php">Report Issue</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/About.html">About</a><br>
                <a href="../../html/common/Profile.php">Edit Profile</a><br>
                <a href="../../html/common/Setting.php">Setting</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>

    <!-- ── Inject PHP data into JS ── -->
    <script>
        // Real data from database — replaces the hardcoded historyData in cCompletedJobs.js
        const historyData = <?php echo $historyJson; ?>;
    </script>

    <script src="../../javascript/cCompletedJobs.js"></script>
</body>

</html>