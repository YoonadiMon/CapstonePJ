<?php
    include("../../php/dbConn.php");
    if(!isset($_SESSION)) {
        session_start();
    }
    include("../../php/sessionCheck.php");

    // Check if user is provide; only providers can access this page
    if ($_SESSION['userType'] !== 'provider') {
        header("Location: ../../index.html");
        exit();
    }

    // get active user info of curent session
    $_SESSION['provider_id'] = $_SESSION['userID'];
    $provider_id = $_SESSION['provider_id'];
    $provider_name = $_SESSION['fullname'];
    $provider_email = $_SESSION['email'];
    $createdAt = $_SESSION['createdAt'];
    $lastlogin = $_SESSION['lastLogin'];

    // Fetch provider statistics
    $stats = [
        'address' => 'N/A',
        'state' => 0,
        'postcode' => 0,
        'point' => 0
    ];

    $sql = "SELECT * FROM tblusers INNER JOIN tblprovider ON tblusers.userID = tblprovider.providerID WHERE tblusers.userID = '$provider_id'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $stats['address'] = $row['address'] ?? 'N/A';
            $stats['state'] = $row['state'] ?? 'N/A';
            $stats['postcode'] = $row['postcode'] ?? 'N/A';
            $stats['point'] = $row['point'] ?? 0;
            echo "<script>console.log('DB Success - Provider stats fetched successfully');</script>";
        } else {
            error_log("DB Error - No provider found with ID: $provider_id");
        }
    } else {
        error_log("DB Error - Active users query: " . mysqli_error($connection));
    }

    // Fetch provided item statistics
    $allItemsStats = [];
    
    $sql = "SELECT 
                cr.requestID,
                cr.pickupAddress,
                cr.pickupState,
                cr.pickupPostcode,
                cr.preferredDateTime,
                cr.status,
                cr.createdAt,
                cr.rejectionReason,
                i.itemID,
                i.centreID,
                i.itemTypeID,
                i.description,
                i.model,
                i.brand,
                i.weight,
                i.length,
                i.width,
                i.height,
                i.image,
                i.status as itemStatus,
                it.name as itemName,
                it.recycle_points as itemRecyclePoints
            FROM tblprovider p 
            JOIN tblcollection_request cr ON p.providerID = cr.providerID
            JOIN tblitem i ON cr.requestID = i.requestID
            JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID 
            WHERE p.providerID = '$provider_id'
            ORDER BY cr.createdAt DESC";
            
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $allItemsStats[] = [
                'requestID' => $row['requestID'] ?? 'N/A',
                'pickupAddress' => $row['pickupAddress'] ?? 'N/A',
                'pickupState' => $row['pickupState'] ?? 'N/A',
                'pickupPostcode' => $row['pickupPostcode'] ?? 'N/A',
                'preferredDateTime' => $row['preferredDateTime'] ?? 'N/A',
                'status' => $row['status'] ?? 'N/A',
                'createdAt' => $row['createdAt'] ?? 'N/A',
                'rejectionReason' => $row['rejectionReason'] ?? 'N/A',
                'itemID' => $row['itemID'] ?? 'N/A',
                'centreID' => $row['centreID'] ?? 'N/A',
                'itemTypeID' => $row['itemTypeID'] ?? 'N/A',
                'description' => $row['description'] ?? 'N/A',
                'model' => $row['model'] ?? 'N/A',
                'brand' => $row['brand'] ?? 'N/A',
                'weight' => $row['weight'] ?? 'N/A',
                'length' => $row['length'] ?? 'N/A',
                'width' => $row['width'] ?? 0,
                'height' => $row['height'] ?? 0,
                'image' => $row['image'] ?? 'N/A',
                'itemStatus' => $row['itemStatus'] ?? 'N/A',
                'itemName' => $row['itemName'] ?? 'N/A',
                'itemRecyclePoints' => $row['itemRecyclePoints'] ?? 0
            ];
        }
    }
?>

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
                <a href="../../html/provider/pMainPickup.html">My Pickup</a>
                <a href="../../html/provider/pEwasteGuide.html">E-waste Guide</a>
                <a href="../../html/common/About.html">About</a>
            </div>
        </div>
    </nav>

    <!-- Menu Links Desktop + Tablet -->
    <nav class="c-navbar-desktop">
        <a href="../../html/provider/pHome.php">Home</a>
        <a href="../../html/provider/pSchedulePickup.html">Schedule Pickup</a>
        <a href="../../html/provider/pMainPickup.html">My Pickup</a>
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
            <div class="pmp-stats-label">Approved</div>
            <div class="pmp-stats-value" id="statScheduled">0</div>
        </div>
        <div class="pmp-stats-divider"></div>
        <div class="pmp-stats-card">
            <div class="pmp-stats-label">Recycle Points</div>
            <div class="pmp-stats-value" id="statCompleted"><?php echo $stats['point']; ?></div>
        </div>
    </section>

    <!-- Filter Tabs -->
    <div class="pmp-tabs">
        <button class="pmp-tab active" onclick="filterPickups('all')">All Pickups</button>
        <button class="pmp-tab" onclick="filterPickups('pending')">Pending</button>
        <button class="pmp-tab" onclick="filterPickups('approved')">Approved</button>
        <button class="pmp-tab" onclick="filterPickups('rejected')">Rejected</button>
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
                <!-- Populated by JavaScript with PHP data -->
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
                            <span id="detailCreatedAt">Created on </span>
                            <span>·</span>
                            <span id="detailItemsCount">0 items</span>
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

                    <!-- Provider Details -->
                    <div class="pmp-info-card">
                        <div class="pmp-info-title">Provider Information</div>
                        <div class="pmp-info-row">
                            <span>Name:</span>
                            <span><?php echo $provider_name; ?></span>
                        </div>
                        <div class="pmp-info-row">
                            <span>Email:</span>
                            <span><?php echo $provider_email; ?></span>
                        </div>
                        <div class="pmp-info-row">
                            <span>Member Since:</span>
                            <span><?php echo date('d M Y', strtotime($createdAt)); ?></span>
                        </div>
                        <div class="pmp-info-row">
                            <span>Last Login:</span>
                            <span><?php echo date('d M Y', strtotime($lastlogin)); ?></span>
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
                                <th>Description</th>
                                <th>Weight</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTable">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Rejection Reason (if any) -->
                <div class="pmp-info-card" style="margin-top: 1rem; display: none;" id="rejectionSection">
                    <div class="pmp-info-title" style="color: #dc3545;">Rejection Reason</div>
                    <p id="detailRejectionReason" style="font-size: 0.9rem; color: var(--text-color);"></p>
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
            <a href="../../html/provider/pMainPickup.html">My Pickup</a>
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
// Pass PHP data to JavaScript
const allItemsStats = <?php echo json_encode($allItemsStats); ?>;
const providerId = "<?php echo $provider_id; ?>";
const providerName = "<?php echo $provider_name; ?>";
const providerEmail = "<?php echo $provider_email; ?>";
const providerPoints = <?php echo $stats['point']; ?>;

let pickups = [];
let filteredPickups = [];
let selectedPickupId = null;
let currentFilter = 'all';
let currentSort = 'newest';

document.addEventListener('DOMContentLoaded', function() {
    transformPickupData();
});

function transformPickupData() {
    // Group items by requestID
    const requestMap = new Map();
    
    if (allItemsStats && allItemsStats.length > 0) {
        allItemsStats.forEach(item => {
            const requestId = item.requestID;
            
            if (!requestMap.has(requestId)) {
                requestMap.set(requestId, {
                    requestID: item.requestID,
                    pickupAddress: item.pickupAddress,
                    pickupState: item.pickupState,
                    pickupPostcode: item.pickupPostcode,
                    preferredDateTime: item.preferredDateTime,
                    status: item.status,
                    createdAt: item.createdAt,
                    rejectionReason: item.rejectionReason,
                    items: []
                });
            }
            
            // Add item to the request
            requestMap.get(requestId).items.push({
                type: item.itemName,
                brand: item.brand,
                model: item.model,
                description: item.description,
                weight: item.weight,
                points: item.itemRecyclePoints,
                status: item.itemStatus
            });
        });
    }
    
    pickups = Array.from(requestMap.values());
    filteredPickups = [...pickups];
    
    // Update stats
    updateStats();
    renderPickupList();
}

function updateStats() {
    const total = pickups.length;
    const pending = pickups.filter(p => p.status.toLowerCase() === 'pending').length;
    const approved = pickups.filter(p => p.status.toLowerCase() === 'approved').length;
    const rejected = pickups.filter(p => p.status.toLowerCase() === 'rejected').length;
    const completed = pickups.filter(p => p.status.toLowerCase() === 'completed').length;
    const cancelled = pickups.filter(p => p.status.toLowerCase() === 'cancelled').length;
    
    document.getElementById('statTotal').textContent = total;
    document.getElementById('statPending').textContent = pending;
    document.getElementById('statScheduled').textContent = approved;
    document.getElementById('statCompleted').textContent = providerPoints;
    
    // You can add more stats display if needed
    console.log(`Stats - Total: ${total}, Pending: ${pending}, Approved: ${approved}, Rejected: ${rejected}, Completed: ${completed}, Cancelled: ${cancelled}`);
}

function filterPickups(status) {
    currentFilter = status;
    
    // Update active tab
    document.querySelectorAll('.pmp-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Filter based on status
    if (status === 'all') {
        filteredPickups = [...pickups];
    } else {
        filteredPickups = pickups.filter(p => p.status.toLowerCase() === status.toLowerCase());
    }
    
    applySearchAndSort();
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
                <p>No ${currentFilter !== 'all' ? currentFilter : ''} pickups match your criteria</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    filteredPickups.forEach(pickup => {
        const date = new Date(pickup.preferredDateTime);
        const formattedDate = date.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
        const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        
        const itemCount = pickup.items.length;
        const statusClass = pickup.status.toLowerCase();
        
        html += `
            <div class="pmp-list-item ${selectedPickupId === pickup.requestID ? 'selected' : ''}" onclick="selectPickup('${pickup.requestID}')">
                <div class="pmp-item-header">
                    <span class="pmp-item-id">${pickup.requestID}</span>
                    <span class="pmp-item-status ${statusClass}">${pickup.status}</span>
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
                        ${itemCount} item${itemCount !== 1 ? 's' : ''}
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
    
    // Items table
    const itemsTable = document.getElementById('detailItemsTable');
    itemsTable.innerHTML = '';
    
    let totalPoints = 0;
    pickup.items.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.type}</td>
            <td>${item.brand || '—'}</td>
            <td>${item.model || '—'}</td>
            <td>${item.description || '—'}</td>
            <td>${item.weight || '—'} kg</td>
            <td>${item.points || 0}</td>
        `;
        itemsTable.appendChild(row);
        totalPoints += parseInt(item.points) || 0;
    });
    
    document.getElementById('detailItemsCount').textContent = pickup.items.length + ' item' + (pickup.items.length !== 1 ? 's' : '');
    
    // Show rejection reason if status is rejected
    if (pickup.status.toLowerCase() === 'rejected' && pickup.rejectionReason) {
        document.getElementById('rejectionSection').style.display = 'block';
        document.getElementById('detailRejectionReason').textContent = pickup.rejectionReason;
    } else {
        document.getElementById('rejectionSection').style.display = 'none';
    }
    
    // Action buttons based on status
    const actionsContainer = document.getElementById('detailActions');
    actionsContainer.innerHTML = '';
    
    // Only show actions for pending pickups
    if (pickup.status.toLowerCase() === 'pending') {
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
    } else if (pickup.status.toLowerCase() === 'completed') {
        // Optionally add download receipt button for completed pickups
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
    let results = [...filteredPickups];
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

// Modal functions (keep your existing modal functions)
let currentRequestId = null;

function openCancelModal(requestId) {
    currentRequestId = requestId;
    document.getElementById('cancelModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

function confirmCancel() {
    if (!currentRequestId) {
        closeCancelModal();
        return;
    }
    
    const confirmBtn = document.querySelector('#cancelModal .danger');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Cancelling...';
    confirmBtn.disabled = true;
    
    fetch('../../php/cancelPickup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'requestId=' + encodeURIComponent(currentRequestId) + '&providerId=' + encodeURIComponent(providerId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update local data
            const pickup = pickups.find(p => p.requestID === currentRequestId);
            if (pickup) {
                pickup.status = 'Cancelled';
            }
            
            // Refresh the lists
            filteredPickups = filteredPickups.map(p => 
                p.requestID === currentRequestId ? {...p, status: 'Cancelled'} : p
            );
            
            updateStats();
            applySearchAndSort();
            
            if (selectedPickupId === currentRequestId) {
                showPickupDetails(pickup);
            }
            
            alert('Pickup request cancelled successfully');
        } else {
            alert('Error cancelling pickup: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the pickup');
    })
    .finally(() => {
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
        closeCancelModal();
    });
}

function openRescheduleModal(requestId) {
    currentRequestId = requestId;
    
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('rescheduleDate').min = tomorrow.toISOString().split('T')[0];
    
    const pickup = pickups.find(p => p.requestID === requestId);
    if (pickup) {
        const currentDate = new Date(pickup.preferredDateTime);
        const dateStr = currentDate.toISOString().split('T')[0];
        document.getElementById('rescheduleDate').value = dateStr;
    }
    
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
    
    if (!currentRequestId) {
        closeRescheduleModal();
        return;
    }
    
    const confirmBtn = document.querySelector('#rescheduleModal .primary');
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Rescheduling...';
    confirmBtn.disabled = true;
    
    const newDateTime = `${newDate} ${newTime}:00`;
    
    fetch('../../php/reschedulePickup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'requestId=' + encodeURIComponent(currentRequestId) + 
              '&newDateTime=' + encodeURIComponent(newDateTime) +
              '&providerId=' + encodeURIComponent(providerId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const pickup = pickups.find(p => p.requestID === currentRequestId);
            if (pickup) {
                pickup.preferredDateTime = newDateTime;
            }
            
            applySearchAndSort();
            
            if (selectedPickupId === currentRequestId) {
                showPickupDetails(pickup);
            }
            
            alert('Pickup rescheduled successfully');
        } else {
            alert('Error rescheduling pickup: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while rescheduling the pickup');
    })
    .finally(() => {
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
        closeRescheduleModal();
    });
}

function downloadReceipt(requestId) {
    window.location.href = '../../php/downloadReceipt.php?requestId=' + requestId;
}
</script>
</body>
</html>