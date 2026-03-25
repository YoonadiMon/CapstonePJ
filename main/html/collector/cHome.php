<?php
    include("../../php/dbConn.php");
    if(!isset($_SESSION)) {
        session_start();
    }
    include("../../php/sessionCheck.php");

    // Check if user is provide; only providers can access this page
    if ($_SESSION['userType'] !== 'collector') {
        header("Location: ../../index.html");
        exit();
    }

    // get active user info of curent session
    $_SESSION['collector_id'] = $_SESSION['userID'];
    $collector_id = $_SESSION['collector_id'];
    $collector_name = $_SESSION['fullname'];
    $collector_email = $_SESSION['email'];
    $collector_phone = $_SESSION['phone'];
    $createdAt = $_SESSION['createdAt'];
    $lastlogin = $_SESSION['lastLogin'];

    $initials = '';
    function getInitials($name) {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($words[0], 0, 2));
    }
    $initials = getInitials($collector_name);

    $sql = "SELECT * FROM tblusers INNER JOIN tblcollector ON tblusers.userID = tblcollector.collectorID WHERE tblusers.userID = '$collector_id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $collector_license = $row['licenseNum'];
        $collector_status = $row['status'];
    } else {
        $collector_license = 'N/A';
        $collector_status = 'N/A';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Collector Home</title>
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

        /* WELCOME BANNER */
        .welcome-banner {
            background: linear-gradient(135deg, var(--DarkerBlue) 0%, var(--DarkBlue) 55%, var(--MainBlue) 100%);
            border-radius: 14px;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(74,127,193,.2);
            overflow: hidden;
            position: relative;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            right: -40px; top: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            pointer-events: none;
        }

        .welcome-text h2 { font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 4px; }

        .welcome-text p  { font-size: 0.82rem; color: rgba(255,255,255,.78); }
        
        .welcome-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            border: 2px solid rgba(255,255,255,.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; font-weight: 700; color: white;
            background: linear-gradient(135deg, hsl(130,60%,22%), hsl(130,50%,40%));
            flex-shrink: 0; z-index: 1;
        }

        /* STATS ROW */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        .stat-card {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
            box-shadow: 0 2px 10px var(--shadow-color);
            display: flex; align-items: center; gap: 14px;
        }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .stat-icon.blue   { background: hsla(225,94%,67%,.15); color: var(--MainBlue); }
        .stat-icon.green  { background: hsla(145,50%,45%,.15); color: hsl(145,50%,35%); }
        .stat-icon.orange { background: hsla(30,90%,55%,.15);  color: hsl(30,75%,42%); }
        .stat-icon.purple { background: hsla(260,52%,55%,.15); color: hsl(260,52%,42%); }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--text-color); line-height: 1; }
        .stat-label { font-size: 0.72rem; color: var(--Gray); margin-top: 3px; }

        /* SECTION HEADING */
        .section-heading {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 0.8rem;
        }
        .section-heading h3 {
            font-size: 0.95rem; font-weight: 700; color: var(--text-color);
            display: flex; align-items: center; gap: 7px;
        }
        .section-heading h3 svg { color: var(--MainBlue); flex-shrink: 0; }
        .section-heading a {
            font-size: 0.72rem; font-weight: 600; text-decoration: none;
            color: var(--MainBlue);
            background: hsla(225,94%,67%,.1);
            border: 1.5px solid hsla(225,94%,67%,.25);
            border-radius: 20px; padding: 3px 11px;
            display: inline-flex; align-items: center; gap: 4px;
            transition: background 0.18s, border-color 0.18s, color 0.18s;
            white-space: nowrap;
        }
        .section-heading a:hover {
            background: hsla(225,94%,67%,.18);
            border-color: hsla(225,94%,67%,.5);
            color: var(--DarkerMainBlue);
        }

        /* TWO-COL */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        /* CARD */
        .c-card {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px var(--shadow-color);
        }
        .c-card-body { padding: 1rem 1.25rem; }

        /* PROFILE SUMMARY */
        .profile-top {
            display: flex; align-items: center; gap: 14px;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .profile-top-details > * {
            margin: 2px 0;
        }
        .profile-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            background: linear-gradient(135deg, hsl(130,60%,18%), hsl(130,50%,38%));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; font-weight: 700; color: white; flex-shrink: 0;
        }
        .profile-name  { font-size: 1rem; font-weight: 700; color: var(--text-color); }
        .profile-email { font-size: 0.75rem; color: var(--Gray); margin-top: 2px; }
        .profile-phone { font-size: 0.75rem; color: var(--Gray); }
        .role-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: hsla(130,50%,92%,1); color: #2e7d32;
            border: 1.5px solid hsl(130,40%,70%);
            border-radius: 20px; padding: 2px 9px;
            font-size: 0.68rem; font-weight: 700; margin-top: 4px;
        }
        .dark-mode .role-pill {
            background: hsla(130,50%,15%,.6); color: hsl(130,60%,70%);
            border-color: hsl(130,40%,35%);
        }
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; }
        .profile-grid-item {
            padding: 0.85rem 1.25rem;
            border-right: 1px solid var(--LowMainBlue);
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .profile-grid-item:nth-child(2n) { border-right: none; }
        .profile-grid-item:nth-last-child(-n+2) { border-bottom: none; }
        .grid-val { font-size: 1rem; font-weight: 700; color: var(--text-color); }
        .grid-lbl { font-size: 0.7rem; color: var(--Gray); margin-top: 2px; }

        /* ACTIVITY */
        .activity-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 9px 0; border-bottom: 1px solid var(--LowMainBlue);
        }
        .activity-item:first-child { padding-top: 0; }
        .activity-item:last-child  { border-bottom: none; padding-bottom: 0; }
        .activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
        .activity-desc { font-size: 0.82rem; color: var(--text-color); line-height: 1.45; }
        .activity-time { font-size: 0.7rem; color: var(--Gray); margin-top: 2px; }

        /* USER LIST */
        .user-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .user-item:first-child { padding-top: 0; }
        .user-av {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-weight: 700; color: white; flex-shrink: 0;
        }
        .user-name { font-size: 0.88rem; font-weight: 600; color: var(--text-color); }
        .user-addr { font-size: 0.72rem; color: var(--Gray); margin-top: 1px; }
        .user-info { flex: 1; min-width: 0; overflow: hidden; }

        /* STATUS PILLS */
        .pill {
            font-size: 0.68rem; font-weight: 700;
            padding: 3px 9px; border-radius: 20px;
            white-space: nowrap; flex-shrink: 0;
        }
        .pill-collected { background: hsla(145,50%,45%,.15); color: hsl(145,50%,32%); }
        .pill-pending   { background: hsla(40,90%,55%,.15);  color: hsl(40,75%,38%); }
        .pill-scheduled { background: hsla(225,94%,67%,.15); color: var(--DarkerMainBlue); }

        /* ITEMS TABLE */
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th {
            font-size: 0.68rem; font-weight: 700; color: var(--Gray);
            text-transform: uppercase; letter-spacing: .04em;
            padding: 0 0 8px; text-align: left;
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .items-table td {
            font-size: 0.82rem; color: var(--text-color);
            padding: 9px 0; border-bottom: 1px solid var(--LowMainBlue);
            vertical-align: middle;
        }
        .items-table tr:last-child td { border-bottom: none; }
        .item-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
        .td-muted { color: var(--Gray) !important; }

        /* EXPAND / COLLAPSE */
        .extra-users-wrap {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
        }
        .extra-users-wrap.open { max-height: 500px; }
        .extra-users-wrap .user-item:last-child { border-bottom: none; padding-bottom: 0; }

        .item-extra { display: none; }
        .item-extra.open { display: table-row; }

        @media (max-width: 768px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
            .two-col   { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .welcome-banner { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <header>
        <section class="c-logo-section">
            <a href="../../html/collector/cHome.php" class="c-logo-link">
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
                    <a href="../../html/collector/cHome.php">Home</a>
                    <a href="../../html/collector/cMyJobs.html">My Jobs</a>
                    <a href="../../html/collector/cInProgress.html">Ongoing Jobs</a>
                    <a href="../../html/collector/cCompletedJobs.html">History</a>
                    <a href="../../html/common/About.html">About</a>
                </div>
            </div>
        </nav>

        <nav class="c-navbar-desktop">
            <a href="../../html/collector/cHome.php">Home</a>
            <a href="../../html/collector/cMyJobs.html">My Jobs</a>
            <a href="../../html/collector/cInProgress.html">Ongoing Jobs</a>
            <a href="../../html/collector/cCompletedJobs.html">History</a>
            <a href="../../html/common/About.html">About</a>
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

        <!-- WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2 id="welcomeHeading">Welcome back 👋</h2>
                <p id="welcomeDate"></p>
            </div>
            <div class="welcome-avatar" id="welcomeAvatar"></div>
        </div>

        <!-- OVERVIEW STATS -->
        <div>
            <div class="section-heading">
                <h3>
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Overview
                </h3>
            </div>
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div><div class="stat-value">24</div><div class="stat-label">Total Jobs</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div><div class="stat-value">18</div><div class="stat-label">Completed</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div><div class="stat-value">4</div><div class="stat-label">Ongoing</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div><div class="stat-value">2</div><div class="stat-label">Pending</div></div>
                </div>
            </div>
        </div>

        <!-- PROFILE + ACTIVITY -->
        <div class="two-col">

            <!-- MY PROFILE -->
            <div>
                <div class="section-heading">
                    <h3>
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        My Profile
                    </h3>
                    <a href="../../html/common/Profile.html?user=col1" id="editProfileLink">
                        <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Profile
                    </a>
                </div>
                <div class="c-card">
                    <div class="profile-top">
                        <div class="profile-avatar" id="profileAvatar">RK</div>
                        <div class="profile-top-details">
                            <div class="profile-name" id="profileName"><?php echo $collector_name; ?></div>
                            <div class="role-pill">
                                <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                Collector
                            </div>
                            <div class="profile-email" id="profileEmail"><?php echo $collector_email; ?></div>
                            <div class="profile-phone" id="profilePhone"><?php echo $collector_phone; ?></div>
                        </div>
                    </div>
                    <div class="profile-grid">
                        <div class="profile-grid-item">
                            <div class="grid-val" id="profileStatus"><?php echo $collector_status; ?></div>
                            <div class="grid-lbl">License Status</div>
                        </div>
                        <div class="profile-grid-item">
                            <div class="grid-val" id="profileLicense"><?php echo $collector_license; ?></div>
                            <div class="grid-lbl">License No.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT ACTIVITY -->
            <div>
                <div class="section-heading">
                    <h3>
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Recent Activity
                    </h3>
                </div>
                <div class="c-card">
                    <div class="c-card-body">
                        <div class="activity-item">
                            <div class="activity-dot" style="background:hsl(145,50%,42%)"></div>
                            <div>
                                <div class="activity-desc">Collected 3 items from <strong>Ahmad Zaki</strong></div>
                                <div class="activity-time">Today, 10:32 AM</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:var(--MainBlue)"></div>
                            <div>
                                <div class="activity-desc">Job <strong>#JOB-2041</strong> marked as completed</div>
                                <div class="activity-time">Today, 9:15 AM</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:hsl(40,80%,48%)"></div>
                            <div>
                                <div class="activity-desc">Arrived at <strong>Siti Nora</strong>'s location — pickup pending</div>
                                <div class="activity-time">Today, 8:50 AM</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:hsl(145,50%,42%)"></div>
                            <div>
                                <div class="activity-desc">Collected 2 items from <strong>Lim Wei Jie</strong></div>
                                <div class="activity-time">Yesterday, 3:40 PM</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-dot" style="background:var(--MainBlue)"></div>
                            <div>
                                <div class="activity-desc">New job <strong>#JOB-2045</strong> assigned to you</div>
                                <div class="activity-time">Yesterday, 1:00 PM</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- USERS + ITEMS -->
        <div class="two-col">

            <!-- USERS ASSIGNED -->
            <div>
                <div class="section-heading">
                    <h3>
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                        Users Assigned
                    </h3>
                    <a href="#" id="usersToggleBtn" onclick="toggleUsers(event)">
                        <svg id="usersToggleIcon" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        <span id="usersToggleText">View All</span>
                    </a>
                </div>
                <div class="c-card">
                    <div class="c-card-body">
                        <div class="user-item">
                            <div class="user-av" style="background:linear-gradient(135deg,hsl(225,70%,35%),hsl(225,80%,55%))">AZ</div>
                            <div class="user-info"><div class="user-name">Ahmad Zaki</div><div class="user-addr">12 Jalan Mawar, PJ</div></div>
                            <span class="pill pill-collected">Collected</span>
                        </div>
                        <div class="user-item">
                            <div class="user-av" style="background:linear-gradient(135deg,hsl(260,60%,30%),hsl(260,55%,52%))">SN</div>
                            <div class="user-info"><div class="user-name">Siti Nora</div><div class="user-addr">5 Lorong Dahlia, PJ</div></div>
                            <span class="pill pill-pending">Pending</span>
                        </div>
                        <div class="user-item" id="usersLastVisible">
                            <div class="user-av" style="background:linear-gradient(135deg,hsl(185,55%,28%),hsl(185,50%,48%))">LW</div>
                            <div class="user-info"><div class="user-name">Lim Wei Jie</div><div class="user-addr">88 Jalan Kenanga, PJ</div></div>
                            <span class="pill pill-collected">Collected</span>
                        </div>
                        <div class="extra-users-wrap" id="extraUsersWrap">
                            <div class="user-item">
                                <div class="user-av" style="background:linear-gradient(135deg,hsl(15,65%,30%),hsl(15,60%,50%))">MR</div>
                                <div class="user-info"><div class="user-name">Muthu Raj</div><div class="user-addr">3 Jalan Teratai, PJ</div></div>
                                <span class="pill pill-scheduled">Scheduled</span>
                            </div>
                            <div class="user-item">
                                <div class="user-av" style="background:linear-gradient(135deg,hsl(340,60%,30%),hsl(340,55%,52%))">NA</div>
                                <div class="user-info"><div class="user-name">Nurul Ain</div><div class="user-addr">27 Jalan Anggerik, PJ</div></div>
                                <span class="pill pill-scheduled">Scheduled</span>
                            </div>
                            <div class="user-item">
                                <div class="user-av" style="background:linear-gradient(135deg,hsl(50,65%,30%),hsl(50,60%,50%))">KH</div>
                                <div class="user-info"><div class="user-name">Kavitha Harish</div><div class="user-addr">9 Jalan Cempaka, PJ</div></div>
                                <span class="pill pill-pending">Pending</span>
                            </div>
                            <div class="user-item">
                                <div class="user-av" style="background:linear-gradient(135deg,hsl(200,65%,30%),hsl(200,60%,50%))">RY</div>
                                <div class="user-info"><div class="user-name">Raj Yusof</div><div class="user-addr">14 Lorong Bayu, PJ</div></div>
                                <span class="pill pill-collected">Collected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ITEMS COLLECTED -->
            <div>
                <div class="section-heading">
                    <h3>
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                        Items Collected
                    </h3>
                    <a href="#" id="itemsToggleBtn" onclick="toggleItems(event)">
                        <svg id="itemsToggleIcon" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                        <span id="itemsToggleText">View All</span>
                    </a>
                </div>
                <div class="c-card">
                    <div class="c-card-body">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>From</th>
                                    <th>Qty</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="item-dot" style="background:var(--MainBlue)"></span>Laptop</td>
                                    <td class="td-muted">Ahmad Zaki</td><td>2</td>
                                    <td><span class="pill pill-collected">Done</span></td>
                                </tr>
                                <tr>
                                    <td><span class="item-dot" style="background:hsl(145,50%,42%)"></span>Phone</td>
                                    <td class="td-muted">Ahmad Zaki</td><td>1</td>
                                    <td><span class="pill pill-collected">Done</span></td>
                                </tr>
                                <tr>
                                    <td><span class="item-dot" style="background:hsl(40,80%,48%)"></span>TV</td>
                                    <td class="td-muted">Siti Nora</td><td>1</td>
                                    <td><span class="pill pill-pending">Pending</span></td>
                                </tr>
                                <tr>
                                    <td><span class="item-dot" style="background:hsl(260,52%,52%)"></span>Printer</td>
                                    <td class="td-muted">Lim Wei Jie</td><td>1</td>
                                    <td><span class="pill pill-collected">Done</span></td>
                                </tr>
                                <tr class="item-extra">
                                    <td><span class="item-dot" style="background:hsl(185,55%,40%)"></span>Monitor</td>
                                    <td class="td-muted">Lim Wei Jie</td><td>2</td>
                                    <td><span class="pill pill-collected">Done</span></td>
                                </tr>
                                <tr class="item-extra">
                                    <td><span class="item-dot" style="background:hsl(15,60%,48%)"></span>Keyboard</td>
                                    <td class="td-muted">Muthu Raj</td><td>3</td>
                                    <td><span class="pill pill-scheduled">Scheduled</span></td>
                                </tr>
                                <tr class="item-extra">
                                    <td><span class="item-dot" style="background:hsl(50,70%,45%)"></span>Tablet</td>
                                    <td class="td-muted">Nurul Ain</td><td>1</td>
                                    <td><span class="pill pill-pending">Pending</span></td>
                                </tr>
                                <tr class="item-extra">
                                    <td><span class="item-dot" style="background:hsl(200,60%,45%)"></span>Router</td>
                                    <td class="td-muted">Raj Yusof</td><td>2</td>
                                    <td><span class="pill pill-collected">Done</span></td>
                                </tr>
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
            <a href="../../html/collector/cHome.php">
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
                <a href="../../html/common/Profile.html?user=col1" id="editProfileFooterLink">Edit Profile</a><br>
                <a href="../../html/common/Setting.html">Setting</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        const CURRENT_USER_ID = 'col1';

        const USERS_PREVIEW = {
            col1: {
                name: '<?php echo $collector_name; ?>', initials: '<?php echo $initials; ?>',
                email: '<?php echo $collector_email; ?>', phone: '<?php echo $collector_phone; ?>',
                status: '<?php echo $collector_status; ?>', license: '<?php echo $collector_license; ?>'
            }
        };

        (function initHome() {
            const user = USERS_PREVIEW[CURRENT_USER_ID];
            if (!user) return;

            const firstName = user.name.split(' ')[0];
            document.getElementById('welcomeHeading').textContent = `Welcome back, ${firstName} 👋`;
            document.getElementById('welcomeAvatar').textContent  = user.initials;

            const today = new Date();
            const dateStr = today.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
            document.getElementById('welcomeDate').textContent = `Here's your overview for today — ${dateStr}`;

            document.getElementById('profileAvatar').textContent  = user.initials;
            document.getElementById('profileName').textContent    = user.name;
            document.getElementById('profileEmail').textContent   = user.email;
            document.getElementById('profilePhone').textContent   = user.phone;
            document.getElementById('profileStatus').textContent = user.status.toUpperCase();
            document.getElementById('profileLicense').textContent = user.license;
        })();

        // TOGGLE: USERS ASSIGNED
        let usersExpanded = false;
        function toggleUsers(e) {
            e.preventDefault();
            usersExpanded = !usersExpanded;
            const wrap = document.getElementById('extraUsersWrap');
            const icon = document.getElementById('usersToggleIcon');
            const text = document.getElementById('usersToggleText');
            wrap.classList.toggle('open', usersExpanded);
            icon.style.cssText = `transform:rotate(${usersExpanded?180:0}deg);transition:transform .25s ease`;
            text.textContent = usersExpanded ? 'Show Less' : 'View All';
        }

        // TOGGLE: ITEMS COLLECTED
        let itemsExpanded = false;
        function toggleItems(e) {
            e.preventDefault();
            itemsExpanded = !itemsExpanded;
            document.querySelectorAll('.item-extra').forEach(row =>
                row.classList.toggle('open', itemsExpanded));
            const icon = document.getElementById('itemsToggleIcon');
            const text = document.getElementById('itemsToggleText');
            icon.style.cssText = `transform:rotate(${itemsExpanded?180:0}deg);transition:transform .25s ease`;
            text.textContent = itemsExpanded ? 'Show Less' : 'View All';
        }
    </script>
</body>
</html>