<?php
include("../../php/dbConn.php");

if (!isset($_SESSION)) {
    session_start();
}

include("../../php/sessionCheck.php");

// for debugging if needed
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Only admins can access this page
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header("Location: ../../../index.html");
    exit();
}

// CURRENT ADMIN FROM SESSION
$_SESSION['admin_id'] = $_SESSION['userID'];
$admin_id = $_SESSION['admin_id'];

// HELPERS
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getInitials($name) {
    $name = trim((string)$name);
    if ($name === '') return 'NA';

    $words = preg_split('/\s+/', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }

    return strtoupper(substr($words[0], 0, 2));
}

function formatRelativeTime($datetime) {
    if (!$datetime) return 'Unknown time';

    $time = strtotime($datetime);
    if (!$time) return $datetime;

    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    if ($diff < 172800) return 'Yesterday, ' . date('g:i A', $time);

    return date('d M Y, g:i A', $time);
}

function activityDotColor($type) {
    $type = strtolower((string)$type);
    switch ($type) {
        case 'request': return 'var(--MainBlue)';
        case 'job': return 'hsl(145,50%,42%)';
        case 'item': return 'hsl(40,80%,48%)';
        case 'issue': return 'hsl(260,52%,52%)';
        default: return 'hsl(0,0%,55%)';
    }
}

function formatCode($prefix, $id) {
    if ($id === null || $id === '') return null;
    return strtoupper($prefix) . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
}

function rolePillClass($userType) {
    $userType = strtolower((string)$userType);
    switch ($userType) {
        case 'admin': return 'pill-scheduled';
        case 'collector': return 'pill-collector';
        case 'provider': return 'pill-provider';
        default: return 'pill-inactive';
    }
}

function roleLabel($userType) {
    return ucfirst((string)$userType);
}

function userStatusLabel($user) {
    if (($user['userType'] ?? '') === 'collector') {
        $status = strtolower((string)($user['collectorStatus'] ?? 'inactive'));

        if ($status === 'active' || $status === 'on duty') return 'Active';
        if ($status === 'suspended') return 'Suspended';
        return 'Inactive';
    }

    if (($user['userType'] ?? '') === 'provider') {
        return !empty($user['providerSuspended']) ? 'Suspended' : 'Active';
    }

    if (($user['userType'] ?? '') === 'admin') {
        return 'Active';
    }

    return 'Unknown';
}

function userStatusClass($user) {
    $label = userStatusLabel($user);
    return $label === 'Active' ? 'pill-active' : 'pill-inactive';
}

function buildActivityHeaderId($activity) {
    $type = strtolower((string)($activity['type'] ?? ''));

    if ($type === 'item') {
        if (!empty($activity['description']) && preg_match('/ID:\s*(\d+)/i', $activity['description'], $match)) {
            return formatCode('ITEM', $match[1]);
        }
        return formatCode('LOG', $activity['logID']);
    }

    if ($type === 'job') {
        if (!empty($activity['jobID'])) {
            return formatCode('JOB', $activity['jobID']);
        }
        return formatCode('LOG', $activity['logID']);
    }

    if ($type === 'request') {
        if (!empty($activity['requestID'])) {
            return formatCode('REQ', $activity['requestID']);
        }
        return formatCode('LOG', $activity['logID']);
    }

    if ($type === 'issue') {
        if (!empty($activity['description']) && preg_match('/Issue\s*\(ID:\s*(\d+)\)/i', $activity['description'], $match)) {
            return formatCode('ISSUE', $match[1]);
        }
        return formatCode('LOG', $activity['logID']);
    }

    if (!empty($activity['jobID'])) return formatCode('JOB', $activity['jobID']);
    if (!empty($activity['requestID'])) return formatCode('REQ', $activity['requestID']);

    return formatCode('LOG', $activity['logID']);
}

function buildActivityMeta($activity) {
    $parts = [];

    if (!empty($activity['fullname'])) {
        $parts[] = $activity['fullname'] . ' (' . ucfirst((string)$activity['userType']) . ')';
    }

    if (!empty($activity['requestID'])) {
        $txt = formatCode('REQ', $activity['requestID']);
        if (!empty($activity['requestStatus'])) {
            $txt .= ' [' . $activity['requestStatus'] . ']';
        }
        $parts[] = $txt;
    }

    if (!empty($activity['jobID'])) {
        $txt = formatCode('JOB', $activity['jobID']);
        if (!empty($activity['jobStatus'])) {
            $txt .= ' [' . $activity['jobStatus'] . ']';
        }
        $parts[] = $txt;
    }

    if (
        !empty($activity['description']) &&
        strtolower((string)($activity['type'] ?? '')) === 'item' &&
        preg_match('/ID:\s*(\d+)/i', $activity['description'], $match)
    ) {
        $parts[] = formatCode('ITEM', $match[1]);
    }

    return implode(' • ', $parts);
}

function cleanActivityDescription($description) {
    if (!$description) return '';

    $text = trim($description);

    $text = preg_replace('/\s*\(ID:\s*\d+\)/i', '', $text);

    $text = preg_replace('/\bvehicle\b/i', 'Vehicle', $text);

    $text = preg_replace('/\bcentre\b/i', '', $text);

    $text = preg_replace('/\s{2,}/', ' ', $text);
    $text = preg_replace('/\s+,/', ',', $text);
    $text = preg_replace('/\s+\./', '.', $text);
    $text = trim($text);

    return $text;
}

// GET CURRENT ADMIN DETAILS

$sqlAdmin = "
    SELECT u.*
    FROM tblusers u
    INNER JOIN tbladmin a ON u.userID = a.adminID
    WHERE u.userID = '$admin_id'
    LIMIT 1
";

$resultAdmin = mysqli_query($conn, $sqlAdmin);

if (!$resultAdmin || mysqli_num_rows($resultAdmin) === 0) {
    die("Access denied. Admin not found.");
}

$admin = mysqli_fetch_assoc($resultAdmin);

// DASHBOARD STATS
$totalUsers = 0;
$totalCollectors = 0;
$totalItems = 0;
$processingItems = 0;
$completedItems = 0;

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblusers");
if ($result) $totalUsers = (int) mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblcollector");
if ($result) $totalCollectors = (int) mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblitem");
if ($result) $totalItems = (int) mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblitem WHERE status = 'Processed'");
if ($result) $processingItems = (int) mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblitem WHERE status = 'Recycled'");
if ($result) $completedItems = (int) mysqli_fetch_assoc($result)['total'];

// RECENT ACTIVITY
$recentActivities = [];

$sqlActivity = "
    SELECT
        al.logID,
        al.requestID,
        al.jobID,
        al.userID,
        al.type,
        al.action,
        al.description,
        al.dateTime,
        u.fullname,
        u.userType,
        r.status AS requestStatus,
        j.status AS jobStatus
    FROM tblactivity_log al
    LEFT JOIN tblusers u ON al.userID = u.userID
    LEFT JOIN tblcollection_request r ON al.requestID = r.requestID
    LEFT JOIN tbljob j ON al.jobID = j.jobID
    ORDER BY al.dateTime DESC, al.logID DESC
    LIMIT 50
";

$result = mysqli_query($conn, $sqlActivity);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recentActivities[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #0d2348 0%, #1a3d72 60%, #2a5298 100%);
            border-radius: 14px;
            padding: 1.6rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 24px rgba(20,50,120,.22);
            position: relative;
            overflow: hidden;
        }
        .welcome-banner::after {
            content: '';
            position: absolute; right: -50px; top: -50px;
            width: 200px; height: 200px; border-radius: 50%;
            background: rgba(255,255,255,.05); pointer-events: none;
        }
        .welcome-text h2 {
            font-size: 1.2rem; font-weight: 700; color: #fff; margin-bottom: 3px;
        }
        .welcome-text p {
            font-size: 0.8rem; color: rgba(255,255,255,.7);
        }
        .welcome-right {
            display: flex; align-items: center; gap: 12px; z-index: 1; flex-shrink: 0;
        }
        .admin-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
            border-radius: 20px; padding: 4px 12px;
            font-size: 0.69rem; font-weight: 700; color: rgba(255,255,255,.88);
        }
        .welcome-avatar {
            width: 50px; height: 50px; border-radius: 50%;
            border: 2px solid rgba(255,255,255,.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; font-weight: 700; color: #fff;
            background: linear-gradient(135deg, hsl(233,65%,32%), hsl(233,75%,52%));
            flex-shrink: 0;
        }

        .section-heading {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 0.75rem;
            gap: 12px;
        }
        .section-heading h3 {
            font-size: 0.88rem; font-weight: 700; color: var(--text-color);
            display: flex; align-items: center; gap: 7px; margin: 0;
        }
        .section-heading h3 svg { color: var(--MainBlue); flex-shrink: 0; }
        .section-heading a {
            font-size: 0.71rem; font-weight: 600; text-decoration: none;
            color: var(--MainBlue);
            background: hsla(225,94%,67%,.09);
            border: 1.5px solid hsla(225,94%,67%,.22);
            border-radius: 20px; padding: 3px 10px;
            display: inline-flex; align-items: center; gap: 4px;
            transition: background 0.15s, border-color 0.15s;
            white-space: nowrap;
            cursor: pointer;
        }
        .section-heading a:hover {
            background: hsla(225,94%,67%,.16);
            border-color: hsla(225,94%,67%,.45);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.85rem;
        }
        .stat-card {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 12px;
            padding: 1rem 1.1rem;
            box-shadow: 0 1px 6px var(--shadow-color);
            display: flex; align-items: center; gap: 12px;
        }
        .stat-icon {
            width: 38px; height: 38px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .stat-icon.blue   { background: hsla(225,94%,67%,.12); color: var(--MainBlue); }
        .stat-icon.green  { background: hsla(145,50%,45%,.12); color: hsl(145,50%,34%); }
        .stat-icon.orange { background: hsla(30,90%,55%,.12);  color: hsl(30,72%,40%); }
        .stat-icon.purple { background: hsla(260,52%,55%,.12); color: hsl(260,52%,40%); }
        .stat-icon.teal   { background: hsla(185,55%,45%,.12); color: hsl(185,52%,33%); }
        .stat-value { font-size: 1.4rem; font-weight: 700; color: var(--text-color); line-height: 1; }
        .stat-label { font-size: 0.69rem; color: var(--Gray); margin-top: 3px; }

        .c-card {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 6px var(--shadow-color);
        }
        .c-card-body { padding: 1rem 1.2rem; }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.1rem;
        }

        .profile-top {
            display: flex; align-items: center; gap: 13px;
            padding: 1rem 1.2rem;
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .profile-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: linear-gradient(135deg, hsl(233,65%,27%), hsl(233,72%,48%));
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .profile-name  { font-size: 0.95rem; font-weight: 700; color: var(--text-color); }
        .profile-sub   { font-size: 0.73rem; color: var(--Gray); margin-top: 2px; }
        .role-pill {
            display: inline-flex; align-items: center; gap: 4px;
            background: hsla(237,52%,93%,1); color: hsl(233,82%,56%);
            border: 1.5px solid hsl(237,40%,78%);
            border-radius: 20px; padding: 2px 8px;
            font-size: 0.66rem; font-weight: 700; margin-top: 4px;
        }

        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; }
        .profile-grid-item {
            padding: 0.8rem 1.2rem;
            border-right: 1px solid var(--LowMainBlue);
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .profile-grid-item:nth-child(2n) { border-right: none; }
        .profile-grid-item:nth-last-child(-n+2) { border-bottom: none; }
        .grid-val { font-size: 0.95rem; font-weight: 700; color: var(--text-color); }
        .grid-lbl { font-size: 0.68rem; color: var(--Gray); margin-top: 2px; }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .activity-item:first-child { padding-top: 0; }
        .activity-item:last-child  { border-bottom: none; padding-bottom: 0; }
        .activity-dot {
            width: 9px; height: 9px; border-radius: 50%;
            flex-shrink: 0; margin-top: 7px;
        }
        .activity-desc { font-size: 0.81rem; color: var(--text-color); line-height: 1.55; }
        .activity-time { font-size: 0.68rem; color: var(--Gray); margin-top: 4px; line-height: 1.5; }

        .activity-filter-btn {
            font-size: 0.71rem;
            font-weight: 600;
            color: var(--MainBlue);
            background: hsla(225,94%,67%,.09);
            border: 1.5px solid hsla(225,94%,67%,.22);
            border-radius: 20px;
            padding: 6px 12px;
            cursor: pointer;
        }

        @media (max-width: 1100px) {
            .stats-row { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .two-col   { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .welcome-banner { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--LowMainBlue);
    overflow: hidden;
    transition: max-height 0.25s ease, opacity 0.25s ease, padding 0.25s ease, margin 0.25s ease;
}

.activity-item.is-hidden {
    max-height: 0;
    opacity: 0;
    padding-top: 0;
    padding-bottom: 0;
    margin: 0;
    border-bottom: none;
    pointer-events: none;
}

.activity-item.is-visible {
    max-height: 220px;
    opacity: 1;
}
    </style>
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
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome back, <?= e($firstName) ?> 👋</h2>
                <p id="welcomeDate"></p>
            </div>
            <div class="welcome-right">
                <div class="admin-badge">
                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Admin
                </div>
                <div class="welcome-avatar"><?= e($adminInitials) ?></div>
            </div>
        </div>

        <div>
            <div class="section-heading">
                <h3>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Overview
                </h3>
            </div>

            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= e($totalUsers) ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= e($totalCollectors) ?></div>
                        <div class="stat-label">Total Collectors</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= e($totalItems) ?></div>
                        <div class="stat-label">Total Items</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= e($processingItems) ?></div>
                        <div class="stat-label">Items in Processing</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon teal">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div>
                        <div class="stat-value"><?= e($completedItems) ?></div>
                        <div class="stat-label">Completed Items</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="two-col">
            <div>
                <div class="section-heading">
                    <h3>
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        My Profile
                    </h3>
                    <a href="../../html/common/Profile.php?userID=<?= urlencode((string)$admin['userID']) ?>">
                        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit Profile
                    </a>
                </div>

                <div class="c-card">
                    <div class="profile-top">
                        <div class="profile-avatar"><?= e($adminInitials) ?></div>
                        <div>
                            <div class="profile-name"><?= e($admin['fullname']) ?></div>
                            <div class="role-pill">
                                <svg width="8" height="8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                                Admin
                            </div>
                            <div class="profile-sub"><?= e($admin['email']) ?></div>
                            <div class="profile-sub"><?= e($admin['phone']) ?></div>
                        </div>
                    </div>

                    <div class="profile-grid">
                        <div class="profile-grid-item">
                            <div class="grid-val">ADM-<?= str_pad((string)$admin['userID'], 3, '0', STR_PAD_LEFT) ?></div>
                            <div class="grid-lbl">Staff ID</div>
                        </div>

                        <div class="profile-grid-item">
                            <div class="grid-val">Administration</div>
                            <div class="grid-lbl">Department</div>
                        </div>

                        <div class="profile-grid-item">
                            <div class="grid-val">Active</div>
                            <div class="grid-lbl">Status</div>
                        </div>

                        <div class="profile-grid-item">
                            <div class="grid-val">
                                <?= !empty($admin['lastLogin']) ? e(date('d M Y, g:i A', strtotime($admin['lastLogin']))) : 'Never' ?>
                            </div>
                            <div class="grid-lbl">Last Login</div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="section-heading">
                <h3>
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Recent Activity
            </h3>

    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <button type="button" id="activityFilterBtn" class="activity-filter-btn" onclick="cycleActivityFilter()">
            Filter: All
        </button>

        <?php if (count($recentActivities) > 5): ?>
            <button type="button" id="activityExpandBtn" class="activity-filter-btn" onclick="toggleActivity()">
                Show More
            </button>
        <?php endif; ?>
    </div>
</div>

                <div class="c-card">
                    <div class="c-card-body">
                        <?php if (empty($recentActivities)): ?>
                            <div class="activity-item">
                                <div class="activity-dot" style="background:hsl(0,0%,55%)"></div>
                                <div>
                                    <div class="activity-desc">No recent activity found.</div>
                                    <div class="activity-time">—</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $index => $activity): ?>
                                <?php $activityType = strtolower((string)($activity['type'] ?? '')); ?>
                                <div
                                class="activity-item is-visible"
                                data-activity-type="<?= e($activityType) ?>"
                                data-activity-index="<?= $index ?>"
                                >
                                    <div class="activity-dot" style="background:<?= e(activityDotColor($activity['type'])) ?>"></div>
                                    <div>
                                       <div class="activity-desc">
                                       <strong><?= e(buildActivityHeaderId($activity)) ?></strong> — <?= e($activity['action']) ?>
                                       <?php if (!empty($activity['description'])): ?>
                                        <br><?= e(cleanActivityDescription($activity['description'])) ?>
                                        <?php endif; ?>
                                    </div>
                                        <div class="activity-time">
                                            <?= e(buildActivityMeta($activity)) ?><br>
                                            <?= e(formatRelativeTime($activity['dateTime'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
            <div class="c-text c-text-label">+60 12 345 6789</div>
            <div class="c-text">abc@gmail.com</div>
        </section>

        <section class="c-footer-links-section">
            <div>
                <b>Management</b><br>
                <a href="../../html/admin/aRequests.php">Requests</a><br>
                <a href="../../html/admin/aJobs.php">Jobs</a><br>
                <a href="../../html/admin/aIssue.php">Issue</a>
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
                <a href="../../html/common/Profile.php?userID=<?= urlencode((string)$admin['userID']) ?>">Edit Profile</a><br>
                <a href="../../html/common/Setting.php">Setting</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>

<script>
    let activityExpanded = false;
    const activityFilters = ['all', 'job', 'request', 'item'];
    let activityFilterIndex = 0;

    function currentActivityFilter() {
        return activityFilters[activityFilterIndex];
    }

    function updateActivityButtons(matchingRowsCount) {
        const filterBtn = document.getElementById('activityFilterBtn');
        const expandBtn = document.getElementById('activityExpandBtn');

        if (filterBtn) {
            const label = currentActivityFilter();
            filterBtn.textContent = 'Filter: ' + label.charAt(0).toUpperCase() + label.slice(1);
        }

        if (expandBtn) {
            if (matchingRowsCount > 5) {
                expandBtn.style.display = 'inline-flex';
                expandBtn.textContent = activityExpanded ? 'Show Less' : 'Show More';
            } else {
                expandBtn.style.display = 'none';
            }
        }
    }

    function filterActivity() {
        const filter = currentActivityFilter();
        const rows = document.querySelectorAll('.activity-item[data-activity-type]');
        let visibleCount = 0;
        let matchingRowsCount = 0;

        rows.forEach(row => {
            const rowType = row.getAttribute('data-activity-type');
            const matchesFilter = (filter === 'all' || rowType === filter);

            if (!matchesFilter) {
                row.classList.remove('is-visible');
                row.classList.add('is-hidden');
                return;
            }

            matchingRowsCount++;

            if (activityExpanded || visibleCount < 5) {
                row.classList.remove('is-hidden');
                row.classList.add('is-visible');
            } else {
                row.classList.remove('is-visible');
                row.classList.add('is-hidden');
            }

            visibleCount++;
        });

        updateActivityButtons(matchingRowsCount);
    }

    function toggleActivity() {
        activityExpanded = !activityExpanded;
        filterActivity();
    }

    function cycleActivityFilter() {
        activityFilterIndex = (activityFilterIndex + 1) % activityFilters.length;
        activityExpanded = false;
        filterActivity();
    }

    (function init() {
        const today = new Date();
        const dateStr = today.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });

        const el = document.getElementById('welcomeDate');
        if (el) {
            el.textContent = `System overview for today — ${dateStr}`;
        }

        filterActivity();
    })();
</script>
</body>
</html>