<?php
session_start();
include("../../php/dbConn.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['userID']) || $_SESSION['userType'] != 'admin') {
    header("Location: ../../signin.php");
    exit();
}

// Fetch statistics from database
$total_ewaste = 0;
$total_requests = 0;
$pending_requests = 0;
$total_users = 0;
$total_collectors = 0;
$total_centres = 0;

// Get total e-waste weight from tblitem
$weight_query = "SELECT SUM(weight) as total FROM tblitem WHERE status IN ('Collected', 'Received', 'Processed', 'Recycled')";
$weight_result = $conn->query($weight_query);
if ($weight_result && $row = $weight_result->fetch_assoc()) {
    $total_ewaste = round($row['total'] ?? 0, 2);
}

// Get total and pending requests
$requests_query = "SELECT COUNT(*) as total FROM tblcollection_request";
$requests_result = $conn->query($requests_query);
if ($requests_result && $row = $requests_result->fetch_assoc()) {
    $total_requests = $row['total'];
}

$pending_query = "SELECT COUNT(*) as total FROM tblcollection_request WHERE status = 'Pending'";
$pending_result = $conn->query($pending_query);
if ($pending_result && $row = $pending_result->fetch_assoc()) {
    $pending_requests = $row['total'];
}

// Get total users (providers only for active users)
$users_query = "SELECT COUNT(*) as total FROM tblusers WHERE userType = 'provider'";
$users_result = $conn->query($users_query);
if ($users_result && $row = $users_result->fetch_assoc()) {
    $total_users = $row['total'];
}

// Get total collectors
$collectors_query = "SELECT COUNT(*) as total FROM tblcollector WHERE status = 'active'";
$collectors_result = $conn->query($collectors_query);
if ($collectors_result && $row = $collectors_result->fetch_assoc()) {
    $total_collectors = $row['total'];
}

// Get total centres
$centres_query = "SELECT COUNT(*) as total FROM tblcentre WHERE status = 'Active'";
$centres_result = $conn->query($centres_query);
if ($centres_result && $row = $centres_result->fetch_assoc()) {
    $total_centres = $row['total'];
}

// Fetch weekly trend data
$trend_labels = [];
$trend_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trend_labels[] = date('D', strtotime($date));
    $daily_query = "SELECT SUM(i.weight) as daily_total 
                    FROM tblitem i 
                    JOIN tblcollection_request r ON i.requestID = r.requestID 
                    WHERE DATE(r.createdAt) = '$date' 
                    AND i.status IN ('Collected', 'Received', 'Processed', 'Recycled')";
    $daily_result = $conn->query($daily_query);
    $daily_total = 0;
    if ($daily_result && $row = $daily_result->fetch_assoc()) {
        $daily_total = round($row['daily_total'] ?? 0, 2);
    }
    $trend_data[] = $daily_total;
}

// Fetch waste type distribution
$type_labels = [];
$type_data = [];
$type_query = "SELECT it.name, COUNT(i.itemID) as count 
               FROM tblitem i 
               JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID 
               GROUP BY it.name 
               ORDER BY count DESC LIMIT 5";
$type_result = $conn->query($type_query);
if ($type_result) {
    while ($row = $type_result->fetch_assoc()) {
        $type_labels[] = $row['name'];
        $type_data[] = $row['count'];
    }
}

// Fetch centre summary
$centres_summary = [];
$centres_summary_query = "SELECT c.name, COUNT(r.requestID) as total_requests, 
                         COALESCE(SUM(i.weight), 0) as total_weight,
                         SUM(CASE WHEN r.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                         SUM(CASE WHEN r.status = 'Pending' THEN 1 ELSE 0 END) as pending
                         FROM tblcentre c
                         LEFT JOIN tblitem i ON c.centreID = i.centreID
                         LEFT JOIN tblcollection_request r ON i.requestID = r.requestID
                         GROUP BY c.centreID
                         LIMIT 5";
$centres_result = $conn->query($centres_summary_query);
if ($centres_result) {
    while ($row = $centres_result->fetch_assoc()) {
        $centres_summary[] = $row;
    }
}

// Fetch audit logs
$audit_logs = [];
$audit_query = "SELECT al.*, u.email, u.userType 
                FROM tblactivity_log al 
                JOIN tblusers u ON al.userID = u.userID 
                ORDER BY al.dateTime DESC LIMIT 20";
$audit_result = $conn->query($audit_query);
if ($audit_result) {
    while ($row = $audit_result->fetch_assoc()) {
        $audit_logs[] = $row;
    }
}

// Fetch issue tickets
$issue_tickets = [];
$issue_query = "SELECT i.*, u.email as reported_by_email 
                FROM tblissue i 
                JOIN tblusers u ON i.reportedBy = u.userID 
                ORDER BY i.reportedAt DESC LIMIT 10";
$issue_result = $conn->query($issue_query);
if ($issue_result) {
    while ($row = $issue_result->fetch_assoc()) {
        $issue_tickets[] = $row;
    }
}

// Get ticket counts by status
$open_tickets = 0;
$inprogress_tickets = 0;
$resolved_tickets = 0;
$high_priority = 0;

$ticket_counts_query = "SELECT status, COUNT(*) as count FROM tblissue GROUP BY status";
$ticket_counts_result = $conn->query($ticket_counts_query);
if ($ticket_counts_result) {
    while ($row = $ticket_counts_result->fetch_assoc()) {
        if ($row['status'] == 'Open') $open_tickets = $row['count'];
        if ($row['status'] == 'Assigned') $inprogress_tickets = $row['count'];
        if ($row['status'] == 'Resolved') $resolved_tickets = $row['count'];
    }
}

$high_priority_query = "SELECT COUNT(*) as count FROM tblissue WHERE severity = 'High' AND status != 'Resolved'";
$high_priority_result = $conn->query($high_priority_query);
if ($high_priority_result && $row = $high_priority_result->fetch_assoc()) {
    $high_priority = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | AfterVolt Admin</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        .reports-container { padding: 1rem 0; }
        .reports-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .reports-header h1 { font-size: 2.5rem; font-weight: 700; color: var(--text-color); }
        .date-range { display: flex; gap: 0.5rem; align-items: center; background-color: var(--sec-bg-color); padding: 0.5rem 1rem; border-radius: 8px; }
        .date-range input { padding: 0.5rem; border: 1px solid var(--BlueGray); border-radius: 4px; background-color: var(--bg-color); color: var(--text-color); }
        .export-btn { background-color: var(--MainBlue); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-weight: 500; }
        .export-btn:hover { background-color: var(--DarkerMainBlue); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background-color: var(--sec-bg-color); padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; gap: 1rem; box-shadow: 0 4px 12px var(--shadow-color); }
        .stat-icon { width: 50px; height: 50px; background-color: var(--MainBlue); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; }
        .stat-info h3 { font-size: 0.9rem; color: var(--Gray); margin-bottom: 0.3rem; }
        .stat-info .stat-number { font-size: 1.8rem; font-weight: 700; color: var(--text-color); }
        .stat-info .stat-change { font-size: 0.8rem; color: #4CAF50; margin-top: 0.2rem; }
        .chart-section { display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .chart-card { background-color: var(--sec-bg-color); padding: 1.5rem; border-radius: 16px; box-shadow: 0 4px 12px var(--shadow-color); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        .chart-header h2 { font-size: 1.3rem; font-weight: 600; color: var(--text-color); }
        .chart-container { position: relative; height: 300px; width: 100%; }
        .report-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--BlueGray); padding-bottom: 0.5rem; flex-wrap: wrap; }
        .report-tab { padding: 0.5rem 1.5rem; background-color: var(--sec-bg-color); border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.2s; color: var(--text-color); border: 1px solid transparent; }
        .report-tab:hover { background-color: var(--LowMainBlue); }
        .report-tab.active { background-color: var(--MainBlue); color: white; }
        .report-panel { display: none; }
        .report-panel.active { display: block; }
        .audit-table-container { overflow-x: auto; background-color: var(--sec-bg-color); border-radius: 16px; padding: 1rem; }
        .audit-table { width: 100%; border-collapse: collapse; }
        .audit-table th { text-align: left; padding: 1rem 0.5rem; color: var(--Gray); font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid var(--BlueGray); }
        .audit-table td { padding: 0.8rem 0.5rem; color: var(--text-color); border-bottom: 1px solid var(--BlueGray); }
        .audit-table tr:hover { background-color: var(--LowMainBlue); }
        .badge { padding: 0.3rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500; display: inline-block; }
        .badge-success { background-color: #4CAF50; color: white; }
        .badge-warning { background-color: #FF9800; color: white; }
        .badge-info { background-color: var(--MainBlue); color: white; }
        .badge-secondary { background-color: var(--Gray); color: white; }
        .tickets-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .ticket-category { background-color: var(--sec-bg-color); padding: 1rem; border-radius: 12px; text-align: center; }
        .ticket-category .count { font-size: 2rem; font-weight: 700; color: var(--MainBlue); }
        .ticket-category .label { color: var(--Gray); font-size: 0.9rem; }
        .filter-bar { display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .filter-bar input, .filter-bar select { padding: 0.5rem; border: 1px solid var(--BlueGray); border-radius: 4px; background-color: var(--bg-color); color: var(--text-color); }
        .filter-bar input { flex: 1; min-width: 200px; }
        .priority-high { color: #f44336; font-weight: 500; }
        .priority-medium { color: #FF9800; font-weight: 500; }
        .priority-low { color: #4CAF50; font-weight: 500; }
        @media (min-width: 760px) { .chart-section { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <header>
        <section class="c-logo-section">
            <a href="../../html/admin/aHome.html" class="c-logo-link">
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
                        <a href="../../html/common/Setting.html">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>
                    <a href="../../html/admin/aHome.html">Home</a>
                    <a href="../../html/admin/aRequests.html">Requests</a>
                    <a href="../../html/admin/aJobs.html">Jobs</a>
                    <a href="../../html/admin/aIssue.html">Issue</a>
                    <a href="../../html/admin/aOperations.html">Operations</a>
                    <a href="../../html/admin/aReport.html">Report</a>
                </div>
            </div>
        </nav>

        <nav class="c-navbar-desktop">
            <a href="../../html/admin/aHome.html">Home</a>
            <a href="../../html/admin/aRequests.html">Requests</a>
            <a href="../../html/admin/aJobs.html">Jobs</a>
            <a href="../../html/admin/aIssue.html">Issue</a>
            <a href="../../html/admin/aOperations.html">Operations</a>
            <a href="../../html/admin/aReport.html">Report</a>
        </nav>          
        <section class="c-navbar-more">
            <button id="themeToggleDesktop">
                <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
            <a href="../../html/common/Setting.html">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
    </header>
    <hr>

    <main>
        <div class="reports-container">
            <div class="reports-header">
                <h1>Reports & Analytics</h1>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="date-range">
                        <span>📅</span>
                        <input type="date" id="startDate" value="<?php echo date('Y-m-01'); ?>">
                        <span>to</span>
                        <input type="date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button class="export-btn" onclick="exportData()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-recycle"></i></div>
                    <div class="stat-info">
                        <h3>Total E-Waste</h3>
                        <div class="stat-number"><?php echo number_format($total_ewaste, 2); ?> kg</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                    <div class="stat-info">
                        <h3>Total Requests</h3>
                        <div class="stat-number"><?php echo $total_requests; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3>Active Users</h3>
                        <div class="stat-number"><?php echo $total_users; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Pending Requests</h3>
                        <div class="stat-number"><?php echo $pending_requests; ?></div>
                    </div>
                </div>
            </div>

            <div class="report-tabs">
                <div class="report-tab active" onclick="switchReportTab(event, 'overview')">📊 Overview</div>
                <div class="report-tab" onclick="switchReportTab(event, 'audit')">📋 Audit Trail</div>
                <div class="report-tab" onclick="switchReportTab(event, 'tickets')">🎫 Issue Tickets</div>
            </div>

            <div id="overview" class="report-panel active">
                <div class="chart-section">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h2>Collection Trend (Last 7 Days)</h2>
                            <select id="trendPeriod" onchange="updateTrendChart()">
                                <option value="week">Weekly</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h2>E-Waste by Type</h2>
                        </div>
                        <div class="chart-container">
                            <canvas id="typeChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h2>Collection Summary by Centre</h2>
                    </div>
                    <div class="audit-table-container">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Collection Centre</th>
                                    <th>Total Requests</th>
                                    <th>Total Weight (kg)</th>
                                    <th>Completed</th>
                                    <th>Pending</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($centres_summary)): ?>
                                <tr><td colspan="5" style="text-align: center;">No data available</td></tr>
                                <?php else: ?>
                                <?php foreach ($centres_summary as $centre): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($centre['name']); ?></td>
                                    <td><?php echo $centre['total_requests']; ?></td>
                                    <td><?php echo number_format($centre['total_weight'], 2); ?></td>
                                    <td><?php echo $centre['completed']; ?></td>
                                    <td><?php echo $centre['pending']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="audit" class="report-panel">
                <div class="chart-card">
                    <div class="chart-header">
                        <h2>Compliance & Audit Trail</h2>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" placeholder="Search logs..." id="auditSearch">
                            <select id="auditFilter">
                                <option value="all">All Actions</option>
                                <option value="create">Create</option>
                                <option value="update">Update</option>
                                <option value="assign">Assign</option>
                                <option value="complete">Complete</option>
                            </select>
                        </div>
                    </div>
                    <div class="audit-table-container">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($audit_logs)): ?>
                                <tr><td colspan="5" style="text-align: center;">No audit logs available</td></tr>
                                <?php else: ?>
                                <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['dateTime']); ?></td>
                                    <td><?php echo htmlspecialchars($log['email']); ?></td>
                                    <td><span class="badge <?php echo $log['userType'] == 'admin' ? 'badge-warning' : ($log['userType'] == 'collector' ? 'badge-secondary' : 'badge-info'); ?>"><?php echo ucfirst($log['userType']); ?></span></td>
                                    <td><span class="badge badge-success"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                    <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tickets" class="report-panel">
                <div class="tickets-summary">
                    <div class="ticket-category">
                        <div class="count"><?php echo $open_tickets; ?></div>
                        <div class="label">Open Tickets</div>
                    </div>
                    <div class="ticket-category">
                        <div class="count"><?php echo $inprogress_tickets; ?></div>
                        <div class="label">In Progress</div>
                    </div>
                    <div class="ticket-category">
                        <div class="count"><?php echo $resolved_tickets; ?></div>
                        <div class="label">Resolved</div>
                    </div>
                    <div class="ticket-category">
                        <div class="count"><?php echo $high_priority; ?></div>
                        <div class="label">High Priority</div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h2>Recent Issue Tickets</h2>
                        <div class="filter-bar">
                            <input type="text" placeholder="Search tickets...">
                            <select>
                                <option value="all">All Status</option>
                                <option value="Open">Open</option>
                                <option value="Assigned">In Progress</option>
                                <option value="Resolved">Resolved</option>
                            </select>
                            <select>
                                <option value="all">All Priority</option>
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="audit-table-container">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Reported By</th>
                                    <th>Issue Type</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($issue_tickets)): ?>
                                <tr><td colspan="8" style="text-align: center;">No issue tickets found</td></tr>
                                <?php else: ?>
                                <?php foreach ($issue_tickets as $ticket): ?>
                                <tr>
                                    <td>TCK-<?php echo $ticket['issueID']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['reported_by_email']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['issueType']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td><span class="badge <?php echo $ticket['status'] == 'Resolved' ? 'badge-success' : ($ticket['status'] == 'Assigned' ? 'badge-info' : 'badge-warning'); ?>"><?php echo $ticket['status']; ?></span></td>
                                    <td class="priority-<?php echo strtolower($ticket['severity']); ?>"><?php echo $ticket['severity']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($ticket['reportedAt'])); ?></td>
                                    <td><button class="export-btn" style="padding: 0.2rem 0.5rem;" onclick="viewTicket(<?php echo $ticket['issueID']; ?>)">View</button></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <hr>
    <footer>
        <section class="c-footer-info-section">
            <a href="../../html/admin/aHome.html">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
            </a>
            <div class="c-text">AfterVolt</div>
            <div class="c-text c-text-center">
                Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.
            </div>
            <div class="c-text c-text-label">+60 12 345 6789</div>
            <div class="c-text">abc@gmail.com</div>
        </section>
        
        <section class="c-footer-links-section">
            <div>
                <b>Management</b><br>
                <a href="../../html/admin/aRequests.html">Collection Requests</a><br>
                <a href="../../html/admin/aJobs.html">Collection Jobs</a><br>
                <a href="../../html/admin/aIssue.html">Issue</a><br>
            </div>
            <div>
                <b>System Operation</b><br>
                <a href="../../html/admin/aProviders.html">Providers</a><br>
                <a href="../../html/admin/aCollectors.html">Collectors</a><br>
                <a href="../../html/admin/aVehicles.html">Vehicles</a><br>
                <a href="../../html/admin/aCentres.html">Collection Centres</a><br>
                <a href="../../html/admin/aItemProcessing.html">Item Processing</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/Profile.html">Edit Profile</a><br>
                <a href="../../html/common/Setting.html">Setting</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        let trendChart, typeChart;
        
        const trendLabels = <?php echo json_encode($trend_labels); ?>;
        const trendData = <?php echo json_encode($trend_data); ?>;
        const typeLabels = <?php echo json_encode($type_labels); ?>;
        const typeData = <?php echo json_encode($type_data); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            initTrendChart();
            initTypeChart();
        });

        function switchReportTab(event, tabId) {
            document.querySelectorAll('.report-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.report-panel').forEach(panel => panel.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            setTimeout(() => { if (trendChart) trendChart.resize(); if (typeChart) typeChart.resize(); }, 100);
        }

        function initTrendChart() {
            const ctx = document.getElementById('trendChart').getContext('2d');
            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'E-Waste Collected (kg)',
                        data: trendData,
                        borderColor: 'hsl(225, 94%, 67%)',
                        backgroundColor: 'rgba(98, 133, 244, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
        }

        function initTypeChart() {
            const ctx = document.getElementById('typeChart').getContext('2d');
            typeChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: typeLabels.length ? typeLabels : ['No Data'],
                    datasets: [{
                        data: typeLabels.length ? typeData : [1],
                        backgroundColor: ['hsl(225, 94%, 67%)', 'hsl(237, 52%, 36%)', 'hsl(240, 60%, 20%)', 'hsl(220, 100%, 90%)', 'hsl(226, 28%, 73%)']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }

        function updateTrendChart() { alert('Chart updated'); }
        function exportData() { alert('Exporting data...'); }
        function viewTicket(id) { alert('Viewing ticket: TCK-' + id); }
    </script>
</body>
</html>