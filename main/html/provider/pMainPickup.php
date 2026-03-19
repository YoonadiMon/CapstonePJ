<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Pickups - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/pMainPickup.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        /* ═══ pMainPickup – styled to match cCompletedJobs aesthetic ═══ */
        
        main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.5rem 1rem;
        }
        
        @media (min-width: 768px) {
            main {
                padding: 2rem;
            }
        }
        
        /* ── Back button ── */
        .pmp-back-row {
            margin-bottom: 0.5rem;
        }
        
        .pmp-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .pmp-back-btn:hover {
            background: var(--sec-bg-color);
            border-color: var(--MainBlue);
        }
        
        /* ── Page Header ── */
        .pmp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .pmp-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .pmp-header p {
            font-size: 0.9rem;
            color: var(--Gray);
            margin-top: 0.25rem;
        }
        
        .pmp-schedule-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--MainBlue);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        
        .pmp-schedule-btn:hover {
            opacity: 0.9;
        }
        
        .pmp-schedule-btn svg {
            width: 18px;
            height: 18px;
        }
        
        /* ── Stats Bar (matching history page) ── */
        .pmp-stats-bar {
            display: flex;
            flex-wrap: wrap;
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .pmp-stats-card {
            flex: 1;
            min-width: 120px;
            padding: 0.5rem 1rem;
            text-align: center;
        }
        
        .pmp-stats-label {
            font-size: 0.75rem;
            color: var(--Gray);
            letter-spacing: 0.03em;
            margin-bottom: 0.25rem;
        }
        
        .pmp-stats-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--DarkBlue);
            line-height: 1.2;
        }
        
        .dark-mode .pmp-stats-value {
            color: var(--LightBlue);
        }
        
        .pmp-stats-divider {
            width: 1px;
            background: var(--BlueGray);
            margin: 0.5rem 0;
        }
        
        @media (max-width: 640px) {
            .pmp-stats-bar {
                flex-direction: column;
                gap: 0.75rem;
            }
            .pmp-stats-divider {
                width: 100%;
                height: 1px;
                margin: 0.25rem 0;
            }
        }
        
        /* ── Filter Tabs ── */
        .pmp-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .pmp-tab {
            padding: 0.5rem 1.25rem;
            border: 1px solid var(--BlueGray);
            border-radius: 999px;
            background: var(--bg-color);
            color: var(--Gray);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pmp-tab:hover {
            border-color: var(--MainBlue);
            color: var(--MainBlue);
        }
        
        .pmp-tab.active {
            background: var(--MainBlue);
            border-color: var(--MainBlue);
            color: white;
        }
        
        /* ── Search and Filter Bar ── */
        .pmp-search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .pmp-search-box {
            flex: 1;
            min-width: 250px;
            display: flex;
            align-items: center;
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .pmp-search-input {
            flex: 1;
            padding: 0.65rem 1rem;
            border: none;
            background: transparent;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        
        .pmp-search-input:focus {
            outline: none;
        }
        
        .pmp-search-btn {
            padding: 0.65rem 1rem;
            background: transparent;
            border: none;
            border-left: 1px solid var(--BlueGray);
            color: var(--Gray);
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .pmp-search-btn:hover {
            color: var(--MainBlue);
        }
        
        .pmp-filter-select {
            padding: 0.65rem 2rem 0.65rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 0.9rem;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
        }
        
        /* ── Two-column layout (like history page) ── */
        .pmp-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        @media (min-width: 992px) {
            .pmp-layout {
                flex-direction: row;
            }
            .pmp-list-col {
                flex: 1;
                max-width: 400px;
            }
            .pmp-detail-col {
                flex: 2;
            }
        }
        
        /* ── Pickup List (left column) ── */
        .pmp-list-col {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            overflow: hidden;
            height: fit-content;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .pmp-list-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--BlueGray);
            background: var(--sec-bg-color);
        }
        
        .pmp-list-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .pmp-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .pmp-list-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--BlueGray);
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }
        
        .pmp-list-item:hover {
            background: var(--sec-bg-color);
        }
        
        .pmp-list-item.selected {
            background: var(--LightBlue);
            border-left: 4px solid var(--MainBlue);
        }
        
        .dark-mode .pmp-list-item.selected {
            background: var(--DarkBlue);
        }
        
        .pmp-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .pmp-item-id {
            font-weight: 700;
            color: var(--DarkBlue);
            font-size: 0.9rem;
        }
        
        .pmp-item-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .pmp-item-status.pending {
            background: #fff3d6;
            color: #8a6300;
        }
        
        .pmp-item-status.scheduled {
            background: #e0f0ff;
            color: #0057a3;
        }
        
        .pmp-item-status.completed {
            background: #e3f7e8;
            color: #0b5e2e;
        }
        
        .pmp-item-status.cancelled {
            background: #ffe5e5;
            color: #a10000;
        }
        
        .dark-mode .pmp-item-status.pending {
            background: #3a2e1a;
            color: #ffd966;
        }
        
        .dark-mode .pmp-item-status.scheduled {
            background: #1a2a3a;
            color: #99c9ff;
        }
        
        .dark-mode .pmp-item-status.completed {
            background: #1a3a2a;
            color: #8fdfb2;
        }
        
        .dark-mode .pmp-item-status.cancelled {
            background: #3a1a1a;
            color: #ff9999;
        }
        
        .pmp-item-details {
            font-size: 0.8rem;
            color: var(--Gray);
        }
        
        .pmp-item-details div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }
        
        .pmp-item-details svg {
            width: 14px;
            height: 14px;
        }
        
        /* ── Detail Panel (right column) ── */
        .pmp-detail-col {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        /* Empty state */
        .pmp-empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--Gray);
        }
        
        .pmp-empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .pmp-empty-state h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        /* Detail content */
        .pmp-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--BlueGray);
        }
        
        .pmp-detail-title h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .pmp-detail-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: var(--Gray);
            flex-wrap: wrap;
        }
        
        .pmp-detail-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .pmp-action-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 6px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        
        .pmp-action-btn:hover {
            border-color: var(--MainBlue);
            color: var(--MainBlue);
        }
        
        .pmp-action-btn.danger:hover {
            border-color: #dc3545;
            color: #dc3545;
        }
        
        .pmp-action-btn.primary {
            background: var(--MainBlue);
            border-color: var(--MainBlue);
            color: white;
        }
        
        .pmp-action-btn.primary:hover {
            opacity: 0.9;
        }
        
        /* Info cards */
        .pmp-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 640px) {
            .pmp-info-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .pmp-info-card {
            background: var(--sec-bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 1rem;
        }
        
        .pmp-info-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--Gray);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.75rem;
        }
        
        .pmp-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .pmp-info-row span:first-child {
            color: var(--Gray);
        }
        
        .pmp-info-row span:last-child {
            font-weight: 500;
            color: var(--text-color);
        }
        
        /* Items table */
        .pmp-items-section {
            margin-top: 1.5rem;
        }
        
        .pmp-items-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .pmp-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        
        .pmp-items-table th {
            text-align: left;
            padding: 0.75rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--Gray);
            text-transform: uppercase;
            border-bottom: 1px solid var(--BlueGray);
        }
        
        .pmp-items-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--BlueGray);
            color: var(--text-color);
        }
        
        .pmp-items-table tr:last-child td {
            border-bottom: none;
        }
        
        .pmp-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .pmp-badge.pending {
            background: #fff3d6;
            color: #8a6300;
        }
        
        .pmp-badge.collected {
            background: #e3f7e8;
            color: #0b5e2e;
        }
        
        /* Timeline */
        .pmp-timeline {
            margin-top: 1.5rem;
        }
        
        .pmp-timeline-item {
            display: flex;
            gap: 1rem;
            padding: 0.75rem 0;
            border-left: 2px solid var(--BlueGray);
            padding-left: 1rem;
            position: relative;
        }
        
        .pmp-timeline-item::before {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--MainBlue);
            position: absolute;
            left: -6px;
            top: 1rem;
        }
        
        .pmp-timeline-time {
            min-width: 80px;
            font-size: 0.75rem;
            color: var(--Gray);
        }
        
        .pmp-timeline-content {
            flex: 1;
        }
        
        .pmp-timeline-content strong {
            display: block;
            font-size: 0.9rem;
            color: var(--text-color);
        }
        
        .pmp-timeline-content p {
            font-size: 0.8rem;
            color: var(--Gray);
            margin-top: 0.25rem;
        }
        
        /* Confirmation Modal */
        .pmp-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .pmp-modal-content {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
        }
        
        .pmp-modal-content h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .pmp-modal-content p {
            color: var(--Gray);
            margin-bottom: 1.5rem;
        }
        
        .pmp-modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .pmp-modal-btn {
            padding: 0.5rem 1.5rem;
            border: 1px solid var(--BlueGray);
            border-radius: 6px;
            background: var(--bg-color);
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pmp-modal-btn.primary {
            background: var(--MainBlue);
            border-color: var(--MainBlue);
            color: white;
        }
        
        .pmp-modal-btn.danger {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <!-- Logo + Name & Navbar -->
    <header>
        <!-- Logo + Name -->
        <section class="c-logo-section">
            <a href="../../html/provider/pHome.php" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

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
                    <a href="../../html/provider/pHome.php">Home</a>
                    <a href="../../html/provider/pSchedulePickup.html">Schedule Pickup</a>
                    <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                    <a href="../../html/provider/pEwasteGuide.html">E-waste Guide</a>
                    <a href="../../html/common/About.html">About</a>
                </div>
            </div>
        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <a href="../../html/provider/pHome.php">Home</a>
            <a href="../../html/provider/pSchedulePickup.html">Schedule Pickup</a>
            <a href="../../html/provider/pMainPickup.php">My Pickup</a>
            <a href="../../html/provider/pEwasteGuide.html">E-waste Guide</a>
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

    <!-- Main Content -->
    <main>
        <!-- Back button -->
        <div class="pmp-back-row">
            <a href="pHome.php" class="pmp-back-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Back to Home
            </a>
        </div>

        <!-- Page Header -->
        <div class="pmp-header">
            <div>
                <h1>My Pickups</h1>
                <p>Track and manage your e-waste collection requests</p>
            </div>
            <a href="pSchedulePickup.html" class="pmp-schedule-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Schedule New Pickup
            </a>
        </div>

        <!-- Stats Bar -->
        <section class="pmp-stats-bar">
            <div class="pmp-stats-card">
                <div class="pmp-stats-label">Total Pickups</div>
                <div class="pmp-stats-value" id="statTotal">0</div>
            </div>
            <div class="pmp-stats-divider"></div>
            <div class="pmp-stats-card">
                <div class="pmp-stats-label">Pending</div>
                <div class="pmp-stats-value" id="statPending">0</div>
            </div>
            <div class="pmp-stats-divider"></div>
            <div class="pmp-stats-card">
                <div class="pmp-stats-label">Scheduled</div>
                <div class="pmp-stats-value" id="statScheduled">0</div>
            </div>
            <div class="pmp-stats-divider"></div>
            <div class="pmp-stats-card">
                <div class="pmp-stats-label">Completed</div>
                <div class="pmp-stats-value" id="statCompleted">0</div>
            </div>
        </section>

        <!-- Filter Tabs -->
        <div class="pmp-tabs">
            <button class="pmp-tab active" onclick="filterPickups('all')">All Pickups</button>
            <button class="pmp-tab" onclick="filterPickups('pending')">Pending</button>
            <button class="pmp-tab" onclick="filterPickups('scheduled')">Scheduled</button>
            <button class="pmp-tab" onclick="filterPickups('completed')">Completed</button>
            <button class="pmp-tab" onclick="filterPickups('cancelled')">Cancelled</button>
        </div>

        <!-- Search and Filter -->
        <div class="pmp-search-bar">
            <div class="pmp-search-box">
                <input type="text" class="pmp-search-input" id="searchInput" placeholder="Search by ID or address..." onkeyup="searchPickups()">
                <button class="pmp-search-btn" onclick="searchPickups()">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
                        <path d="M9 17C13.4183 17 17 13.4183 17 9C17 4.58172 13.4183 1 9 1C4.58172 1 1 4.58172 1 9C1 13.4183 4.58172 17 9 17Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M19 19L14.65 14.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <select class="pmp-filter-select" id="sortFilter" onchange="sortPickups()">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="date-asc">Pickup Date (Earliest)</option>
                <option value="date-desc">Pickup Date (Latest)</option>
            </select>
        </div>

        <!-- Two-column layout -->
        <div class="pmp-layout">
            <!-- LEFT: Pickup List -->
            <aside class="pmp-list-col">
                <div class="pmp-list-header">
                    <h3>Pickup Requests</h3>
                </div>
                <div class="pmp-list" id="pickupList">
                    <!-- Populated by JavaScript -->
                    <div class="pmp-empty-state">
                        <p>Loading pickups...</p>
                    </div>
                </div>
            </aside>

            <!-- RIGHT: Detail Panel -->
            <section class="pmp-detail-col" id="detailPanel">
                <!-- Empty state -->
                <div class="pmp-empty-state" id="emptyState">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                        <path d="M8 10h8M8 14h5"/>
                    </svg>
                    <h3>No Pickup Selected</h3>
                    <p>Select a pickup from the list to view details</p>
                </div>

                <!-- Detail content (hidden by default) -->
                <div id="detailContent" style="display: none;">
                    <!-- Header -->
                    <div class="pmp-detail-header">
                        <div class="pmp-detail-title">
                            <h2 id="detailRequestId">REQ001</h2>
                            <div class="pmp-detail-meta">
                                <span id="detailCreatedAt">Created on 24 Feb 2026</span>
                                <span>·</span>
                                <span id="detailItemsCount">3 items</span>
                            </div>
                        </div>
                        <div class="pmp-detail-actions" id="detailActions">
                            <!-- Actions will be dynamically populated based on status -->
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div class="pmp-info-grid">
                        <!-- Pickup Details -->
                        <div class="pmp-info-card">
                            <div class="pmp-info-title">Pickup Details</div>
                            <div class="pmp-info-row">
                                <span>Address:</span>
                                <span id="detailAddress">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>State:</span>
                                <span id="detailState">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>Postcode:</span>
                                <span id="detailPostcode">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>Preferred Date:</span>
                                <span id="detailPreferredDate">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>Preferred Time:</span>
                                <span id="detailPreferredTime">—</span>
                            </div>
                        </div>

                        <!-- Job Details -->
                        <div class="pmp-info-card">
                            <div class="pmp-info-title">Collection Job</div>
                            <div class="pmp-info-row">
                                <span>Job ID:</span>
                                <span id="detailJobId">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>Collector:</span>
                                <span id="detailCollector">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>Vehicle:</span>
                                <span id="detailVehicle">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>Scheduled:</span>
                                <span id="detailScheduled">—</span>
                            </div>
                            <div class="pmp-info-row">
                                <span>Status:</span>
                                <span id="detailJobStatus">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="pmp-items-section">
                        <h4 class="pmp-items-title">E-Waste Items</h4>
                        <table class="pmp-items-table">
                            <thead>
                                <tr>
                                    <th>Item Type</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Qty</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="detailItemsTable">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Timeline -->
                    <div class="pmp-timeline" id="timelineSection">
                        <h4 class="pmp-items-title">Activity Timeline</h4>
                        <div id="timelineContent">
                            <!-- Populated by JS -->
                        </div>
                    </div>

                    <!-- Special Instructions -->
                    <div class="pmp-info-card" style="margin-top: 1rem;" id="instructionsSection">
                        <div class="pmp-info-title">Special Instructions</div>
                        <p id="detailInstructions" style="font-size: 0.9rem; color: var(--text-color);">—</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Cancel Confirmation Modal -->
    <div class="pmp-modal" id="cancelModal">
        <div class="pmp-modal-content">
            <h3>Cancel Pickup Request</h3>
            <p>Are you sure you want to cancel this pickup request? This action cannot be undone.</p>
            <div class="pmp-modal-actions">
                <button class="pmp-modal-btn" onclick="closeCancelModal()">No, Keep It</button>
                <button class="pmp-modal-btn danger" onclick="confirmCancel()">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div class="pmp-modal" id="rescheduleModal">
        <div class="pmp-modal-content">
            <h3>Reschedule Pickup</h3>
            <p>Select new date and time:</p>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--Gray); margin-bottom: 0.25rem;">New Date</label>
                <input type="date" id="rescheduleDate" class="pmp-form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--BlueGray); border-radius: 6px; background: var(--bg-color); color: var(--text-color);">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.8rem; color: var(--Gray); margin-bottom: 0.25rem;">New Time</label>
                <select id="rescheduleTime" class="pmp-form-control" style="width: 100%; padding: 0.5rem; border: 1px solid var(--BlueGray); border-radius: 6px; background: var(--bg-color); color: var(--text-color);">
                    <option value="09:00">09:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:00">11:00 AM</option>
                    <option value="14:00">02:00 PM</option>
                    <option value="15:00">03:00 PM</option>
                    <option value="16:00">04:00 PM</option>
                </select>
            </div>
            <div class="pmp-modal-actions">
                <button class="pmp-modal-btn" onclick="closeRescheduleModal()">Cancel</button>
                <button class="pmp-modal-btn primary" onclick="confirmReschedule()">Confirm</button>
            </div>
        </div>
    </div>

    <hr>
    
    <!-- Footer -->
    <footer>
        <!-- Column 1 -->
        <section class="c-footer-info-section">
            <a href="../../html/provider/pHome.php">
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
                <b>Recycling</b><br>
                <a href="../../html/provider/pEwasteGuide.html">E-Waste Guide</a><br>
                <a href="../../html/provider/pWasteType.html">E-Waste Types</a>
            </div>
            <div>
                <b>My Activity</b><br>
                <a href="../../html/provider/pSchedulePickup.html">Schedule Pickup</a><br>
                <a href="../../html/provider/pMainPickup.php">My Pickup</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/About.html">About</a><br>
                <a href="../../html/common/Profile.html">Edit Profile</a><br>
                <a href="../../html/common/Setting.html">Setting</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        // pMainPickup.js
        
        // Sample data based on database schema
        let pickups = [];
        let filteredPickups = [];
        let selectedPickupId = null;
        let currentFilter = 'all';
        let currentSort = 'newest';
        
        document.addEventListener('DOMContentLoaded', function() {
            loadPickupData();
        });
        
        function loadPickupData() {
            // Simulate loading data from database
            // In production, this would fetch from:
            // - Collection Request table
            // - Job table
            // - E-waste Item table
            // - Provider table
            // - Collector table
            // - Vehicle table
            
            pickups = [
                {
                    requestID: 'REQ001',
                    providerID: 'PRV-2024-00182',
                    pickupAddress: 'No. 15, Jalan SS15/4',
                    pickupState: 'Selangor',
                    pickupPostcode: '47500',
                    preferredDateTime: '2026-02-25 14:00',
                    status: 'Pending',
                    createdAt: '2026-02-24T10:30:00',
                    rejectionReason: null,
                    items: [
                        { type: 'Laptop', brand: 'Dell', model: 'XPS 13', quantity: 1, weight: 1.8, status: 'Pending' },
                        { type: 'Smartphone', brand: 'Samsung', model: 'Galaxy S21', quantity: 2, weight: 0.4, status: 'Pending' }
                    ],
                    job: null,
                    timeline: [
                        { time: '2026-02-24T10:30:00', action: 'Request Created', description: 'Pickup request submitted' }
                    ]
                },
                {
                    requestID: 'REQ002',
                    providerID: 'PRV-2024-00182',
                    pickupAddress: 'Block B-3-5, Apartment Mutiara',
                    pickupState: 'Kuala Lumpur',
                    pickupPostcode: '50400',
                    preferredDateTime: '2026-02-26 10:00',
                    status: 'Scheduled',
                    createdAt: '2026-02-23T15:45:00',
                    rejectionReason: null,
                    items: [
                        { type: 'Monitor', brand: 'LG', model: '27UL850', quantity: 1, weight: 5.2, status: 'Pending' },
                        { type: 'Keyboard', brand: 'Logitech', model: 'MX Keys', quantity: 1, weight: 0.8, status: 'Pending' },
                        { type: 'Mouse', brand: 'Logitech', model: 'MX Master 3', quantity: 1, weight: 0.2, status: 'Pending' }
                    ],
                    job: {
                        jobID: 'JOB001',
                        collectorID: 'COL001',
                        collectorName: 'Ahmad bin Collection',
                        vehicleID: 'VH001',
                        vehiclePlate: 'ABC 1234',
                        scheduledDate: '2026-02-26',
                        scheduledTime: '10:00',
                        estimatedEndTime: '11:00',
                        status: 'Scheduled'
                    },
                    timeline: [
                        { time: '2026-02-23T15:45:00', action: 'Request Created', description: 'Pickup request submitted' },
                        { time: '2026-02-24T09:15:00', action: 'Job Assigned', description: 'Collector assigned to your pickup' }
                    ]
                },
                {
                    requestID: 'REQ003',
                    providerID: 'PRV-2024-00182',
                    pickupAddress: 'No. 8, Jalan Teknologi 3/5',
                    pickupState: 'Selangor',
                    pickupPostcode: '47810',
                    preferredDateTime: '2026-02-20 14:00',
                    status: 'Completed',
                    createdAt: '2026-02-18T11:20:00',
                    rejectionReason: null,
                    items: [
                        { type: 'Desktop Computer', brand: 'Custom', model: 'Gaming PC', quantity: 1, weight: 12.5, status: 'Collected' },
                        { type: 'Printer', brand: 'HP', model: 'LaserJet Pro', quantity: 1, weight: 8.3, status: 'Collected' }
                    ],
                    job: {
                        jobID: 'JOB002',
                        collectorID: 'COL002',
                        collectorName: 'Siti binti Collection',
                        vehicleID: 'VH002',
                        vehiclePlate: 'XYZ 5678',
                        scheduledDate: '2026-02-20',
                        scheduledTime: '14:00',
                        estimatedEndTime: '15:30',
                        startedAt: '2026-02-20T14:05:00',
                        completedAt: '2026-02-20T15:15:00',
                        status: 'Completed'
                    },
                    timeline: [
                        { time: '2026-02-18T11:20:00', action: 'Request Created', description: 'Pickup request submitted' },
                        { time: '2026-02-19T10:30:00', action: 'Job Assigned', description: 'Collector assigned to your pickup' },
                        { time: '2026-02-20T14:05:00', action: 'Pickup Started', description: 'Collector arrived at location' },
                        { time: '2026-02-20T15:15:00', action: 'Pickup Completed', description: 'Items collected successfully' }
                    ]
                },
                {
                    requestID: 'REQ004',
                    providerID: 'PRV-2024-00182',
                    pickupAddress: 'Unit G-12, Digital Hub',
                    pickupState: 'Selangor',
                    pickupPostcode: '63000',
                    preferredDateTime: '2026-02-22 09:00',
                    status: 'Cancelled',
                    createdAt: '2026-02-21T08:15:00',
                    rejectionReason: 'Provider requested cancellation',
                    items: [
                        { type: 'Tablet', brand: 'Apple', model: 'iPad Pro', quantity: 2, weight: 1.4, status: 'Cancelled' }
                    ],
                    job: null,
                    timeline: [
                        { time: '2026-02-21T08:15:00', action: 'Request Created', description: 'Pickup request submitted' },
                        { time: '2026-02-21T16:30:00', action: 'Cancelled', description: 'Request cancelled by provider' }
                    ]
                },
                {
                    requestID: 'REQ005',
                    providerID: 'PRV-2024-00182',
                    pickupAddress: 'No. 3, Jalan SS15/8',
                    pickupState: 'Selangor',
                    pickupPostcode: '47500',
                    preferredDateTime: '2026-02-28 15:00',
                    status: 'Pending',
                    createdAt: '2026-02-25T09:30:00',
                    rejectionReason: null,
                    items: [
                        { type: 'Television', brand: 'Sony', model: 'Bravia 55"', quantity: 1, weight: 15.0, status: 'Pending' },
                        { type: 'Speaker', brand: 'JBL', model: 'Charge 5', quantity: 2, weight: 1.2, status: 'Pending' },
                        { type: 'Cables', brand: 'Various', model: 'Assorted', quantity: 10, weight: 0.8, status: 'Pending' }
                    ],
                    job: null,
                    timeline: [
                        { time: '2026-02-25T09:30:00', action: 'Request Created', description: 'Pickup request submitted' }
                    ]
                }
            ];
            
            filteredPickups = [...pickups];
            renderPickupList();
            updateStats();
        }
        
        function renderPickupList() {
            const listContainer = document.getElementById('pickupList');
            
            if (filteredPickups.length === 0) {
                listContainer.innerHTML = `
                    <div class="pmp-empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <h3>No Pickups Found</h3>
                        <p>No pickups match your current filter</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            filteredPickups.forEach(pickup => {
                const date = new Date(pickup.preferredDateTime);
                const formattedDate = date.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
                const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                const itemCount = pickup.items.reduce((sum, item) => sum + item.quantity, 0);
                
                html += `
                    <div class="pmp-list-item ${selectedPickupId === pickup.requestID ? 'selected' : ''}" onclick="selectPickup('${pickup.requestID}')">
                        <div class="pmp-item-header">
                            <span class="pmp-item-id">${pickup.requestID}</span>
                            <span class="pmp-item-status ${pickup.status.toLowerCase()}">${pickup.status}</span>
                        </div>
                        <div class="pmp-item-details">
                            <div>${pickup.pickupAddress}</div>
                            <div>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                ${formattedDate} · ${formattedTime}
                            </div>
                            <div>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                </svg>
                                ${itemCount} items
                            </div>
                        </div>
                    </div>
                `;
            });
            
            listContainer.innerHTML = html;
        }
        
        function selectPickup(requestId) {
            selectedPickupId = requestId;
            const pickup = pickups.find(p => p.requestID === requestId);
            
            if (pickup) {
                showPickupDetails(pickup);
            }
            
            // Update selected state in list
            renderPickupList();
        }
        
        function showPickupDetails(pickup) {
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('detailContent').style.display = 'block';
            
            // Basic info
            document.getElementById('detailRequestId').textContent = pickup.requestID;
            
            const createdDate = new Date(pickup.createdAt);
            document.getElementById('detailCreatedAt').textContent = `Created on ${createdDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })}`;
            
            // Pickup details
            document.getElementById('detailAddress').textContent = pickup.pickupAddress;
            document.getElementById('detailState').textContent = pickup.pickupState;
            document.getElementById('detailPostcode').textContent = pickup.pickupPostcode;
            
            const prefDate = new Date(pickup.preferredDateTime);
            document.getElementById('detailPreferredDate').textContent = prefDate.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });
            document.getElementById('detailPreferredTime').textContent = prefDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            
            // Job details
            if (pickup.job) {
                document.getElementById('detailJobId').textContent = pickup.job.jobID;
                document.getElementById('detailCollector').textContent = pickup.job.collectorName || 'Not assigned';
                document.getElementById('detailVehicle').textContent = pickup.job.vehiclePlate || 'Not assigned';
                
                const scheduledDate = pickup.job.scheduledDate ? new Date(pickup.job.scheduledDate + ' ' + pickup.job.scheduledTime) : null;
                document.getElementById('detailScheduled').textContent = scheduledDate ? scheduledDate.toLocaleString() : 'Not scheduled';
                
                const statusSpan = document.createElement('span');
                statusSpan.className = `pmp-item-status ${pickup.job.status.toLowerCase()}`;
                statusSpan.textContent = pickup.job.status;
                document.getElementById('detailJobStatus').innerHTML = statusSpan.outerHTML;
            } else {
                document.getElementById('detailJobId').textContent = '—';
                document.getElementById('detailCollector').textContent = '—';
                document.getElementById('detailVehicle').textContent = '—';
                document.getElementById('detailScheduled').textContent = '—';
                document.getElementById('detailJobStatus').textContent = '—';
            }
            
            // Items table
            const itemsTable = document.getElementById('detailItemsTable');
            itemsTable.innerHTML = '';
            
            pickup.items.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.type}</td>
                    <td>${item.brand}</td>
                    <td>${item.model}</td>
                    <td>${item.quantity}</td>
                    <td>${item.weight} kg</td>
                    <td><span class="pmp-badge ${item.status.toLowerCase()}">${item.status}</span></td>
                `;
                itemsTable.appendChild(row);
            });
            
            document.getElementById('detailItemsCount').textContent = pickup.items.length + ' items';
            
            // Timeline
            const timeline = document.getElementById('timelineContent');
            timeline.innerHTML = '';
            
            pickup.timeline.sort((a, b) => new Date(b.time) - new Date(a.time)).forEach(event => {
                const eventDate = new Date(event.time);
                const timeStr = eventDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                const dateStr = eventDate.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                
                const item = document.createElement('div');
                item.className = 'pmp-timeline-item';
                item.innerHTML = `
                    <div class="pmp-timeline-time">${dateStr}<br>${timeStr}</div>
                    <div class="pmp-timeline-content">
                        <strong>${event.action}</strong>
                        <p>${event.description}</p>
                    </div>
                `;
                timeline.appendChild(item);
            });
            
            // Special instructions
            document.getElementById('detailInstructions').textContent = 'No special instructions provided';
            
            // Action buttons based on status
            const actionsContainer = document.getElementById('detailActions');
            actionsContainer.innerHTML = '';
            
            if (pickup.status === 'Pending') {
                actionsContainer.innerHTML = `
                    <button class="pmp-action-btn" onclick="openRescheduleModal('${pickup.requestID}')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Reschedule
                    </button>
                    <button class="pmp-action-btn danger" onclick="openCancelModal('${pickup.requestID}')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        Cancel Request
                    </button>
                `;
            } else if (pickup.status === 'Scheduled') {
                actionsContainer.innerHTML = `
                    <button class="pmp-action-btn" onclick="openRescheduleModal('${pickup.requestID}')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Reschedule
                    </button>
                    <button class="pmp-action-btn danger" onclick="openCancelModal('${pickup.requestID}')">
                        Cancel Request
                    </button>
                `;
            } else if (pickup.status === 'Completed') {
                actionsContainer.innerHTML = `
                    <button class="pmp-action-btn" onclick="downloadReceipt('${pickup.requestID}')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Download Receipt
                    </button>
                `;
            }
        }
        
        function filterPickups(status) {
            currentFilter = status;
            
            // Update active tab
            document.querySelectorAll('.pmp-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            if (status === 'all') {
                filteredPickups = [...pickups];
            } else {
                filteredPickups = pickups.filter(p => p.status.toLowerCase() === status.toLowerCase());
            }
            
            applySearchAndSort();
        }
        
        function searchPickups() {
            applySearchAndSort();
        }
        
        function sortPickups() {
            currentSort = document.getElementById('sortFilter').value;
            applySearchAndSort();
        }
        
        function applySearchAndSort() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            // Apply search
            let results = filteredPickups;
            if (searchTerm) {
                results = results.filter(p => 
                    p.requestID.toLowerCase().includes(searchTerm) ||
                    p.pickupAddress.toLowerCase().includes(searchTerm)
                );
            }
            
            // Apply sort
            results.sort((a, b) => {
                switch(currentSort) {
                    case 'newest':
                        return new Date(b.createdAt) - new Date(a.createdAt);
                    case 'oldest':
                        return new Date(a.createdAt) - new Date(b.createdAt);
                    case 'date-asc':
                        return new Date(a.preferredDateTime) - new Date(b.preferredDateTime);
                    case 'date-desc':
                        return new Date(b.preferredDateTime) - new Date(a.preferredDateTime);
                    default:
                        return 0;
                }
            });
            
            filteredPickups = results;
            renderPickupList();
        }
        
        function updateStats() {
            document.getElementById('statTotal').textContent = pickups.length;
            document.getElementById('statPending').textContent = pickups.filter(p => p.status === 'Pending').length;
            document.getElementById('statScheduled').textContent = pickups.filter(p => p.status === 'Scheduled').length;
            document.getElementById('statCompleted').textContent = pickups.filter(p => p.status === 'Completed').length;
        }
        
        // Modal functions
        let currentRequestId = null;
        
        function openCancelModal(requestId) {
            currentRequestId = requestId;
            document.getElementById('cancelModal').style.display = 'flex';
        }
        
        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }
        
        function confirmCancel() {
            if (currentRequestId) {
                const pickup = pickups.find(p => p.requestID === currentRequestId);
                if (pickup) {
                    pickup.status = 'Cancelled';
                    pickup.rejectionReason = 'Cancelled by provider';
                    pickup.timeline.push({
                        time: new Date().toISOString(),
                        action: 'Cancelled',
                        description: 'Request cancelled by provider'
                    });
                    
                    // Update job if exists
                    if (pickup.job) {
                        pickup.job.status = 'Cancelled';
                    }
                    
                    updateStats();
                    applySearchAndSort();
                    
                    if (selectedPickupId === currentRequestId) {
                        showPickupDetails(pickup);
                    }
                }
            }
            closeCancelModal();
        }
        
        function openRescheduleModal(requestId) {
            currentRequestId = requestId;
            
            // Set min date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('rescheduleDate').min = tomorrow.toISOString().split('T')[0];
            
            document.getElementById('rescheduleModal').style.display = 'flex';
        }
        
        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
        }
        
        function confirmReschedule() {
            const newDate = document.getElementById('rescheduleDate').value;
            const newTime = document.getElementById('rescheduleTime').value;
            
            if (!newDate || !newTime) {
                alert('Please select both date and time');
                return;
            }
            
            if (currentRequestId) {
                const pickup = pickups.find(p => p.requestID === currentRequestId);
                if (pickup) {
                    pickup.preferredDateTime = `${newDate} ${newTime}`;
                    pickup.timeline.push({
                        time: new Date().toISOString(),
                        action: 'Rescheduled',
                        description: `Pickup rescheduled to ${newDate} at ${newTime}`
                    });
                    
                    applySearchAndSort();
                    
                    if (selectedPickupId === currentRequestId) {
                        showPickupDetails(pickup);
                    }
                }
            }
            closeRescheduleModal();
        }
        
        function downloadReceipt(requestId) {
            alert('Receipt download functionality will be implemented here');
            // In production, this would generate a PDF receipt
        }
        
        // Dark mode support
        function updateTheme() {
            // Update any theme-specific elements if needed
        }
        
        // Listen for theme changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    updateTheme();
                }
            });
        });
        
        observer.observe(document.body, { attributes: true });
    </script>
</body>
</html>