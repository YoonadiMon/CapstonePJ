<?php
session_start();
include("../../php/dbConn.php");
 
// ── TEMP: hardcoded session for testing (remove once login is done) ──
$_SESSION['userID']   = 9;
$_SESSION['userType'] = 'collector';
// ────────────────────────────────────────────────────────────────────
 
// Basic auth guard
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'collector') {
    header("Location: ../../html/common/Login.html");
    exit();
}
 
$collectorID = $_SESSION['userID'];
 
// Fetch active jobs for this collector
$sql = "
    SELECT
        j.jobID,
        j.status,
        j.scheduledDate,
        COUNT(i.itemID)             AS itemCount,
        COALESCE(SUM(i.weight), 0)  AS totalWeight
    FROM tbljob j
    LEFT JOIN tblitem i ON i.requestID = j.requestID
    WHERE j.collectorID = ?
      AND j.status IN ('Pending', 'Scheduled', 'Ongoing')
    GROUP BY j.jobID
    ORDER BY j.scheduledDate ASC
";
 
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $collectorID);
$stmt->execute();
$result = $stmt->get_result();
 
$jobs = [];
while ($row = $result->fetch_assoc()) {
    $statusMap = [
        'Pending'   => 'pending',
        'Scheduled' => 'accepted',
        'Ongoing'   => 'ongoing',
    ];
 
    $row['statusClass'] = $statusMap[$row['status']] ?? strtolower($row['status']);
    $jobs[] = $row;
}
 
$stmt->close();
$conn->close();
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assigned Jobs - AfterVolt</title>
 
<link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
<link rel="stylesheet" href="../../style/style.css">
<link rel="stylesheet" href="../../style/cMyjobs.css">
 
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
 
<body>
 
<div id="cover" onclick="hideMenu()"></div>
 
<!-- Header -->
<header>
 
<section class="c-logo-section">
<a href="../../html/collector/cHome.html" class="c-logo-link">
<img src="../../assets/images/logo.png" class="c-logo">
<div class="c-text">AfterVolt</div>
</a>
</section>
 
<nav class="c-navbar-side">
<img src="../../assets/images/icon-menu.svg" onclick="showMenu()" class="c-icon-btn">
 
<div id="sidebarNav" class="c-navbar-side-menu">
 
<img src="../../assets/images/icon-menu-close.svg" onclick="hideMenu()" class="close-btn">
 
<div class="c-navbar-side-items">
 
<section class="c-navbar-side-more">
<button id="themeToggleMobile">
<img src="../../assets/images/light-mode-icon.svg">
</button>
 
<a href="../../html/common/Setting.html">
<img src="../../assets/images/setting-light.svg" id="settingImgM">
</a>
</section>
 
<a href="../../html/collector/cHome.html">Home</a>
<a href="../../html/collector/cMyJobs.html">My Jobs</a>
<a href="../../html/collector/cInProgress.html">Ongoing Jobs</a>
<a href="../../html/collector/cCompletedJobs.html">History</a>
<a href="../../html/common/About.html">About</a>
 
</div>
</div>
</nav>
 
<nav class="c-navbar-desktop">
<a href="../../html/collector/cHome.html">Home</a>
<a href="../../html/collector/cMyJobs.html">My Jobs</a>
<a href="../../html/collector/cInProgress.html">Ongoing Jobs</a>
<a href="../../html/collector/cCompletedJobs.html">History</a>
<a href="../../html/common/About.html">About</a>
</nav>
 
<section class="c-navbar-more">
<button id="themeToggleDesktop">
<img src="../../assets/images/light-mode-icon.svg">
</button>
 
<a href="../../html/common/Setting.html">
<img src="../../assets/images/setting-light.svg" id="settingImg">
</a>
</section>
 
</header>
 
<hr>
 
<!-- Main -->
<main>
 
<div class="jobs-container">
 
<aside class="jobs-sidebar">
 
<button class="jobs-back-btn" onclick="window.history.back()">Back</button>
 
<div class="jobs-search-box">
<input type="text" id="jobSearchInput" placeholder="Search" class="jobs-search-input">
<button class="jobs-search-btn" onclick="searchJobs()">
🔍
</button>
</div>
 
<div class="jobs-filter-section">
 
<div class="jobs-filter-header">
<h3>Status Filter</h3>
<button class="jobs-clear-btn" onclick="clearFilters()">Clear</button>
</div>
 
<div class="jobs-filter-options">
 
<label class="jobs-filter-label">
<input type="checkbox" value="ongoing" onchange="filterJobs()" checked>
<span>Ongoing</span>
</label>
 
<label class="jobs-filter-label">
<input type="checkbox" value="accepted" onchange="filterJobs()">
<span>Accepted</span>
</label>
 
<label class="jobs-filter-label">
<input type="checkbox" value="pending" onchange="filterJobs()">
<span>Pending</span>
</label>
 
</div>
</div>
 
<div class="jobs-calendar-section">
<div class="jobs-calendar-header">
<button onclick="previousMonth()">&lt;</button>
<span id="monthDisplay">April</span>
<button onclick="nextMonth()">&gt;</button>
</div>
 
<div class="jobs-calendar">
<div class="jobs-calendar-weekdays">
<div>Sun</div>
<div>Mon</div>
<div>Tue</div>
<div>Wed</div>
<div>Thu</div>
<div>Fri</div>
<div>Sat</div>
</div>
 
<div class="jobs-calendar-dates" id="calendarDates"></div>
</div>
</div>
 
</aside>
 
<section class="jobs-content">
<div class="jobs-grid" id="jobsGrid"></div>
</section>
 
</div>
</main>
 
<hr>
 
<footer>
 
<section class="c-footer-info-section">
<a href="../../html/collector/cHome.html">
<img src="../../assets/images/logo.png" class="c-logo">
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
 
<!-- JS SCRIPTS -->
 
<script src="../../javascript/mainScript.js"></script>
 
<!-- Inject PHP job data BEFORE cMyJobs.js -->
<script>
const jobsData = <?php
$jsJobs = array_map(function($j) {
return [
'id'     => 'JOB' . str_pad($j['jobID'], 3, '0', STR_PAD_LEFT),
'jobID'  => (int)$j['jobID'],
'status' => $j['statusClass'],
'items'  => (int)$j['itemCount'],
'weight' => round((float)$j['totalWeight'], 2),
'date'   => $j['scheduledDate'],
];
}, $jobs);
 
echo json_encode($jsJobs, JSON_PRETTY_PRINT);
?>;
</script>
 
<script src="../../javascript/cMyJobs.js"></script>
 
</body>
</html>