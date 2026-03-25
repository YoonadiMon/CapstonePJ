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

$userID   = $_SESSION['userID'];
$userType = $_SESSION['userType'];

function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

// Fetch all issues with reporter name and assigned admin name
$result = $conn->query("
    SELECT i.issueID, i.requestID, i.jobID,
           i.issueType, i.severity, i.subject, i.description,
           i.status, i.reportedAt, i.assignedAt, i.resolvedAt, i.notes,
           i.assignedAdminID,
           u.fullname AS reportedByName,
           a.fullname AS assignedAdminName
    FROM tblissue i
    JOIN tblusers u ON i.reportedBy = u.userID
    LEFT JOIN tblusers a ON i.assignedAdminID = a.userID
    ORDER BY i.reportedAt DESC
");

$issues = [];
while ($row = $result->fetch_assoc()) {
    $issues[] = $row;
}

$issuesJson = json_encode($issues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Issues - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        .issues-container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .admin-header {
            margin-bottom: 1.75rem;
        }

        .admin-header h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .admin-header p {
            color: var(--Gray);
            font-size: 0.95rem;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 760px) {
            .stats-overview {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .stat-card {
            background-color: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1.25rem 1rem;
            text-align: center;
            box-shadow: 0 2px 8px var(--shadow-color);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            cursor: pointer;
            user-select: none;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px var(--shadow-color);
        }

        .stat-card.active {
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 2px var(--MainBlue), 0 4px 16px var(--shadow-color);
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.4rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--Gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .stat-open     { 
            color: var(--MainBlue); 
        }

        .stat-progress { 
            color: hsl(0, 80%, 55%); 
        }

        .stat-resolved { 
            color: hsl(145, 60%, 40%); 
        }

        .stat-assigned-you {
            color: hsl(270, 60%, 50%);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .section-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge {
            background-color: var(--MainBlue);
            color: var(--White);
            border-radius: 999px;
            padding: 0.15rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .search-bar-wrapper {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .issue-search {
            flex: 1;
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.5rem 0.85rem;
            font-size: 0.875rem;
            cursor: pointer;
            min-width: 0;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .issue-search::placeholder { 
            color: var(--Gray); 
        }

        .issue-search:focus {
            outline: none;
            border-color: var(--MainBlue);
            transform: translateY(-2px);
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .search-btn {
            background-color: var(--MainBlue);
            color: var(--White);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.1rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s, transform 0.1s;
        }

        .search-btn:hover { 
            background-color: var(--DarkerMainBlue); 
        }

        .search-btn:active { 
            transform: scale(0.97); 
        }

        .dark-mode .search-btn {
            background-color: var(--DarkerMainBlue);
        }

        .clear-btn {
            background: var(--Gray);
            color: var(--White);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: none;
        }

        .clear-btn.visible {
            display: block;
        }

        .clear-btn:hover {
            background: var(--BlueGray);
        }

        .clear-btn:active { 
            transform: scale(0.97); 
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .filter-select {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.45rem;
            font-size: 0.875rem;
            cursor: pointer;
            flex: 1;
            min-width: 130px;
            max-width: 400px;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--MainBlue);
            transform: translateY(-2px);
        }

        .issues-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .issue-item {
            background-color: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            cursor: pointer;
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
            position: relative;
        }

        .issue-item:hover {
            box-shadow: 0 4px 16px var(--shadow-color);
            border-color: var(--MainBlue);
            transform: translateY(-1px);
        }

        .issue-content {
            flex: 1;
            min-width: 0;
        }

        .issue-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.3rem;
            flex-wrap: wrap;
        }

        .issue-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 420px;
        }

        .issue-preview {
            font-size: 0.82rem;
            color: var(--Gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.5rem;
        }

        .dark-mode .issue-preview {
            color: var(--BlueGray);
        }

        .issue-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .issue-id {
            font-size: 0.75rem;
            color: var(--Gray);
            font-weight: 600;
        }

        .issue-time {
            font-size: 0.75rem;
            color: var(--BlueGray);
        }

        .dark-mode .issue-time {
            color: var(--Gray);
        }

        .badge-type {
            background-color: var(--LightBlue);
            color: var(--DarkerMainBlue);
            border-radius: 6px;
            padding: 0.15rem 0.5rem;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .badge-severity {
            border-radius: 6px;
            padding: 0.15rem 0.5rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
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

        .badge-status {
            border-radius: 6px;
            padding: 0.15rem 0.5rem;
            font-size: 0.72rem;
            font-weight: 600;
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

        .linked-id {
            font-size: 0.72rem;
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            background: var(--LightBlue);
            color: var(--MainBlue);
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s;
        }

        .linked-id:hover {
            background: var(--LowMainBlue);
        }

        .dark-mode .linked-id {
            background: var(--MainBlue);
            color: var(--DarkBlue);
        }

        .issue-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .action-btn {
            cursor: pointer;
            border: none;
            border-radius: 8px;
            padding: 0.4rem 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            transition: background 0.2s, transform 0.1s;
            background-color: var(--MainBlue);
            color: var(--White);
        }

        .action-btn:active { 
            transform: scale(0.97); 
        }

        .action-btn:hover {
            background-color: var(--DarkerMainBlue);
        }

        .dark-mode .action-btn {
            background-color: var(--DarkerMainBlue);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--Gray);
        }

        .empty-state p {
            font-size: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .issue-actions { 
                display: none; 
            }
            .issue-title { 
                max-width: 200px; 
            }
        }

        @media (max-width: 480px) {
            .search-bar-wrapper,
            .filters {
                flex-direction: column;
            }

            .search-btn,
            .clear-btn,
            .filter-select {
                width: 100%;
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
        <div class="issues-container">

            <!-- Admin Header -->
            <div class="admin-header">
                <h1>Hey Admin!</h1>
                <p>Manage operational issues efficiently</p>
            </div>

            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card" onclick="filterByCard('status', 'Open')">
                    <div class="stat-number stat-open" id="statOpen">0</div>
                    <div class="stat-label">Open Issues</div>
                </div>
                <div class="stat-card" onclick="filterByCard('status', 'Assigned')">
                    <div class="stat-number stat-progress" id="statProgress">0</div>
                    <div class="stat-label">Assigned</div>
                </div>
                <div class="stat-card" onclick="filterByCard('status', 'Resolved')">
                    <div class="stat-number stat-resolved" id="statResolved">0</div>
                    <div class="stat-label">Resolved</div>
                </div>
                <div class="stat-card" onclick="filterByCard('assignedToYou', '')">
                    <div class="stat-number stat-assigned-you" id="statAssignedYou">0</div>
                    <div class="stat-label">Assigned to You</div>
                </div>
            </div>

            <!-- Issues Section -->
            <div class="tickets-section">
                <div class="section-header">
                    <h2>Issues <span class="badge" id="totalBadge">0</span></h2>
                </div>

                <!-- Search -->
                <div class="search-bar-wrapper">
                    <input type="text" class="issue-search" id="issueSearch"
                        placeholder="Search by subject, reporter, or ID…">
                    <button class="search-btn" onclick="applyFilters()">Search</button>
                    <button class="clear-btn" id="clearBtn" onclick="clearAll()">Clear</button>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <select class="filter-select" id="typeFilter">
                        <option value="all">All Types</option>
                        <option value="Operational">Operational</option>
                        <option value="Vehicle">Vehicle</option>
                        <option value="Safety">Safety</option>
                        <option value="Technical">Technical</option>
                        <option value="Other">Other</option>
                    </select>
                    <select class="filter-select" id="severityFilter">
                        <option value="all">All Severities</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="Open">Open</option>
                        <option value="Assigned">Assigned</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                    <select class="filter-select" id="assigneeFilter">
                        <option value="all">All Assignees</option>
                        <option value="me">Assigned to You</option>
                    </select>
                </div>

                <!-- Issue List -->
                <div class="issues-list" id="issuesList"></div>
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

    <script src="../../javascript/mainScript.js"></script>
    <script>
        // Real data from PHP/DB (replaces dummy data)
        const ISSUES = <?php echo $issuesJson; ?>;
        const CURRENT_USER_ID = <?php echo (int)$userID; ?>;

        function formatType(type) {
            const map = { Operational:"Operational", Vehicle:"Vehicle", Safety:"Safety", Technical:"Technical", Other:"Other" };
            return map[type] || type;
        }

        function formatSeverity(sev) {
            return sev.charAt(0).toUpperCase() + sev.slice(1).toLowerCase();
        }

        function formatStatus(status) {
            if (status === "Assigned") return "Assigned";
            return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
        }

        function formatTime(dateStr) {
            if (!dateStr) return "—";
            const d = new Date(dateStr);
            const now = new Date();
            const diffMs = now - d;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHrs  = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            if (diffMins < 60) return diffMins + "m ago";
            if (diffHrs  < 24) return diffHrs  + "h ago";
            if (diffDays < 7)  return diffDays + "d ago";
            return d.toLocaleDateString();
        }

        function truncate(str, len) {
            if (!str) return "";
            return str.length > len ? str.slice(0, len) + "…" : str;
        }

        function renderIssues(issues) {
            const list = document.getElementById("issuesList");

            if (!issues.length) {
                list.innerHTML = `
                    <div class="empty-state">
                        <p>No issues found.</p>
                    </div>`;
                return;
            }

            list.innerHTML = issues.map(issue => {
                const linkedReq = issue.requestID
                    ? `<a class="linked-id" href="../../html/admin/aRequestDetail.php?id=${issue.requestID}" onclick="event.stopPropagation()">REQ #${issue.requestID}</a>`
                    : "";
                const linkedJob = issue.jobID
                    ? `<a class="linked-id" href="../../html/admin/aJobDetail.php?id=${issue.jobID}" onclick="event.stopPropagation()">JOB #${issue.jobID}</a>`
                    : "";

                // CSS class keys match DB ENUM values (capitalised)
                const severityCss = issue.severity ? issue.severity.toLowerCase() : 'low';
                const statusCss   = issue.status   ? issue.status.toLowerCase()   : 'open';

                return `
                <div class="issue-item"
                    data-type="${issue.issueType}"
                    data-severity="${issue.severity}"
                    data-status="${issue.status}"
                    onclick="viewIssue(${issue.issueID})">

                    <div class="issue-content">
                        <div class="issue-header">
                            <div class="issue-title">${truncate(issue.subject, 60)}</div>
                        </div>
                        <div class="issue-preview">${truncate(issue.description, 90)}</div>
                        <div class="issue-meta">
                            <span class="issue-id">#${issue.issueID}</span>
                            <span class="badge-type">${formatType(issue.issueType)}</span>
                            <span class="badge-severity severity-${severityCss}">${formatSeverity(issue.severity)}</span>
                            <span class="badge-status status-${statusCss}">${formatStatus(issue.status)}</span>
                            <span class="issue-time">${formatTime(issue.reportedAt)}</span>
                            ${linkedReq}
                            ${linkedJob}
                        </div>
                    </div>

                    <div class="issue-actions">
                        <button class="action-btn" onclick="event.stopPropagation(); viewIssue(${issue.issueID})">
                            View Details
                        </button>
                    </div>
                </div>`;
            }).join("");
        }

        function updateStats(issues) {
            document.getElementById("statOpen").textContent        = issues.filter(i => i.status === "Open").length;
            document.getElementById("statProgress").textContent    = issues.filter(i => i.status === "Assigned").length;
            document.getElementById("statResolved").textContent    = issues.filter(i => i.status === "Resolved").length;
            document.getElementById("statAssignedYou").textContent = issues.filter(i => parseInt(i.assignedAdminID) === CURRENT_USER_ID).length;
            document.getElementById("totalBadge").textContent      = issues.length;
        }

        function filterByCard(filterType, value) {
            // If this card is already active, clear it (toggle off)
            const cards = document.querySelectorAll('.stat-card');
            const clickedCard = event.currentTarget;
            const wasActive = clickedCard.classList.contains('active');

            // Remove active from all cards
            cards.forEach(c => c.classList.remove('active'));

            if (wasActive) {
                // Toggle off — reset that filter to 'all' and re-apply
                if (filterType === 'status')        document.getElementById('statusFilter').value   = 'all';
                if (filterType === 'severity')      document.getElementById('severityFilter').value = 'all';
                if (filterType === 'assignedToYou') document.getElementById('assigneeFilter').value = 'all';
            } else {
                // Activate this card and set the matching filter
                clickedCard.classList.add('active');
                if (filterType === 'status')        document.getElementById('statusFilter').value   = value;
                if (filterType === 'severity')      document.getElementById('severityFilter').value = value;
                if (filterType === 'assignedToYou') document.getElementById('assigneeFilter').value = 'me';
            }

            applyFilters();
        }

        function applyFilters() {
            var typeVal     = document.getElementById("typeFilter").value;
            var severityVal = document.getElementById("severityFilter").value;
            var statusVal   = document.getElementById("statusFilter").value;
            var assigneeVal = document.getElementById("assigneeFilter").value;
            var searchVal   = document.getElementById("issueSearch").value.trim().toLowerCase();

            var filtered = ISSUES.filter(function(issue) {
                var matchType     = typeVal     === "all" || issue.issueType === typeVal;
                var matchSeverity = severityVal === "all" || issue.severity  === severityVal;
                var matchStatus   = statusVal   === "all" || issue.status    === statusVal;
                var matchAssignee = assigneeVal === "all" || parseInt(issue.assignedAdminID) === CURRENT_USER_ID;
                var matchSearch   = !searchVal  ||
                    (issue.subject         && issue.subject.toLowerCase().indexOf(searchVal) !== -1)         ||
                    (issue.description     && issue.description.toLowerCase().indexOf(searchVal) !== -1)     ||
                    (issue.reportedByName  && issue.reportedByName.toLowerCase().indexOf(searchVal) !== -1)  ||
                    String(issue.issueID).indexOf(searchVal) !== -1;
                return matchType && matchSeverity && matchStatus && matchAssignee && matchSearch;
            });

            renderIssues(filtered);
            document.getElementById("totalBadge").textContent = filtered.length;

            // show Clear button if anything is active
            var hasFilters = searchVal || typeVal !== "all" || severityVal !== "all" || statusVal !== "all" || assigneeVal !== "all";
            document.getElementById("clearBtn").classList.toggle("visible", !!hasFilters);
        }

        function clearAll() {
            document.getElementById("issueSearch").value = "";
            document.getElementById("typeFilter").value = "all";
            document.getElementById("severityFilter").value = "all";
            document.getElementById("statusFilter").value = "all";
            document.getElementById("assigneeFilter").value = "all";
            document.getElementById("clearBtn").classList.remove("visible");
            document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('active'));
            renderIssues(ISSUES);
            document.getElementById("totalBadge").textContent = ISSUES.length;
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateStats(ISSUES);
            renderIssues(ISSUES);

            // Search triggers on button click or Enter key
            document.getElementById("issueSearch").addEventListener("keydown", function(e) {
                if (e.key === "Enter") applyFilters();
            });
            document.getElementById("typeFilter").addEventListener("change", applyFilters);
            document.getElementById("severityFilter").addEventListener("change", applyFilters);
            document.getElementById("statusFilter").addEventListener("change", applyFilters);
            document.getElementById("assigneeFilter").addEventListener("change", applyFilters);
        });

        function viewIssue(id) {
            window.location.href = `../../html/admin/aIssueDetail.php?id=${id}`;
        }
    </script>
</body>
</html>