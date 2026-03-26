<?php
    include("../../php/dbConn.php");
    if(!isset($_SESSION)) {
        session_start();
    }
    include("../../php/sessionCheck.php");

    // Check if user is provide; only providers can access this page
    if ($_SESSION['userType'] !== 'provider') {
        header("Location: ../../../index.html");
        exit();
    }

    // get active user info of curent session
    $_SESSION['provider_id'] = $_SESSION['userID'];
    $provider_id = $_SESSION['provider_id'];
    $provider_name = $_SESSION['fullname'];
    $provider_email = $_SESSION['email'];
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
    $initials = getInitials($provider_name);

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
    $allItemStats = [];
    $approvedItemStats = [];
    $pendingItemStats = [];

    $sql = "SELECT * FROM tblprovider p JOIN tblcollection_request cr ON p.providerID = cr.providerID
                                        JOIN tblitem i ON cr.requestID = i.requestID
                                        JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID 
                                        WHERE p.providerID = '$provider_id'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // echo "<script>console.log('DB Success - Item stats row: " . json_encode($row) . "');</script>";
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
                    'itemStatus' => $row['status'] ?? 'N/A',
                    'itemName' => $row['name'] ?? 'N/A',
                    'itemRecyclePoints' => $row['recycle_points'] ?? 0
                ];
            }
        } else {
            error_log("DB Notice - No records found for provider ID: $provider_id");
        }
    } else {
        error_log("DB Error - Query failed: " . mysqli_error($conn));
    }

    $sql = "SELECT * FROM tblprovider p JOIN tblcollection_request cr ON p.providerID = cr.providerID
                                        JOIN tblitem i ON cr.requestID = i.requestID
                                        JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID 
                                        WHERE p.providerID = '$provider_id' and cr.status = 'Approved'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {

                $approvedItemStats[] = [
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
                    'itemStatus' => $row['status'] ?? 'N/A',
                    'itemName' => $row['name'] ?? 'N/A',
                    'itemRecyclePoints' => $row['recycle_points'] ?? 0
                ];
            }
        } else {
            error_log("DB Notice - No records found for provider ID: $provider_id");
        }
    } else {
        error_log("DB Error - Query failed: " . mysqli_error($conn));
    }

    $sql = "SELECT * FROM tblprovider p JOIN tblcollection_request cr ON p.providerID = cr.providerID
                                        JOIN tblitem i ON cr.requestID = i.requestID
                                        JOIN tblitem_type it ON i.itemTypeID = it.itemTypeID 
                                        WHERE p.providerID = '$provider_id' and cr.status = 'Pending'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {

                $pendingItemStats[] = [
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
                    'itemStatus' => $row['status'] ?? 'N/A',
                    'itemName' => $row['name'] ?? 'N/A',
                    'itemRecyclePoints' => $row['recycle_points'] ?? 0
                ];
            }
        } else {
            error_log("DB Notice - No records found for provider ID: $provider_id");
        }
    } else {
        error_log("DB Error - Query failed: " . mysqli_error($conn));
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Provider Home - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">
    <!-- <link rel="stylesheet" href="../../style/pHome.css"> -->

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
        
        .ph-back-row {
            margin-bottom: 0.5rem;
        }
        
        .ph-back-btn {
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
        }
        
        .ph-back-btn:hover {
            background: var(--sec-bg-color);
            border-color: var(--MainBlue);
        }
        
        .ph-stats-bar {
            display: flex;
            flex-wrap: wrap;
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .ph-stats-card {
            flex: 1;
            min-width: 120px;
            padding: 0.5rem 1rem;
            text-align: center;
        }
        
        .ph-stats-label {
            font-size: 0.75rem;
            color: var(--Gray);
            letter-spacing: 0.03em;
            margin-bottom: 0.25rem;
        }
        
        .ph-stats-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--DarkBlue);
            line-height: 1.2;
        }
        
        .dark-mode .ph-stats-value {
            color: var(--LightBlue);
        }
        
        .ph-stats-divider {
            width: 1px;
            background: var(--BlueGray);
            margin: 0.5rem 0;
        }
        
        @media (max-width: 640px) {
            .ph-stats-bar {
                flex-direction: column;
                gap: 0.75rem;
            }
            .ph-stats-divider {
                width: 100%;
                height: 1px;
                margin: 0.25rem 0;
            }
        }
        
        /* ── Greeting section ── */
        .ph-greeting {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .ph-greeting h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-color);
            line-height: 1.2;
        }
        
        .ph-greeting h1 span {
            color: var(--MainBlue);
        }
        
        .ph-greeting p {
            font-size: 0.85rem;
            color: var(--Gray);
            margin-top: 4px;
        }
        
        .ph-submit-btn {
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
        
        .ph-submit-btn svg {
            width: 18px;
            height: 18px;
        }
        
        .ph-submit-btn:hover {
            opacity: 0.9;
        }
        
        /* ── Two-column layout (like history page) ── */
        .ph-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        @media (min-width: 992px) {
            .ph-layout {
                flex-direction: row;
            }
            .ph-profile-col {
                flex: 0.05 0 330px;
            }
            .ph-content-col {
                flex: 1;
            }
        }
        
        /* ── Profile card (left column) ── */
        .ph-profile-col {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .ph-avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 1.25rem;
        }
        
        .ph-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--LightBlue);
            border: 4px solid var(--MainBlue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--DarkBlue);
            margin-bottom: 1rem;
        }
        
        .ph-profile-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .ph-profile-id {
            font-size: 0.8rem;
            color: var(--Gray);
            background-color: var(--sec-bg-color);
            border-radius: 999px;
            padding: 0.25rem 1rem;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .ph-profile-divider {
            width: 100%;
            height: 1px;
            background-color: var(--BlueGray);
            margin: 1rem 0;
        }
        
        .ph-profile-meta {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .ph-meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            padding: 0.25rem 0;
        }
        
        .ph-meta-row span:first-child {
            color: var(--Gray);
        }
        
        .ph-meta-row span:last-child {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .ph-edit-link {
            display: inline-block;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: var(--MainBlue);
            text-decoration: none;
            font-weight: 500;
        }
        
        .ph-edit-link:hover {
            text-decoration: underline;
        }
        
        /* ── Content column ── */
        .ph-content-col {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        /* ── Points card ── */
        .ph-points-card {
            background: linear-gradient(135deg, var(--DarkBlue) 0%, #1a3a5a 100%);
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        
        .ph-points-left h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--LightBlue);
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .ph-points-left p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .ph-tier {
            background: rgba(255,255,255,0.15);
            border-radius: 999px;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            font-size: 1rem;
            backdrop-filter: blur(4px);
        }
        
        .ph-next {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-top: 0.5rem;
            text-align: right;
        }
        
        .ph-progress-wrap {
            margin-top: 1rem;
            min-width: 200px;
        }
        
        .ph-progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }
        
        .ph-progress-bar {
            height: 6px;
            background: rgba(255,255,255,0.15);
            border-radius: 999px;
            overflow: hidden;
        }
        
        .ph-progress-fill {
            height: 100%;
            width: 68%;
            background: var(--MainBlue);
            border-radius: 999px;
        }
        
        /* ── Mini stat cards ── */
        .ph-mini-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }
        
        .ph-stat-card {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 10px;
            padding: 1.25rem;
            box-shadow: 0 2px 4px var(--shadow-color);
        }
        
        .ph-stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            color: var(--MainBlue);
        }
        
        .ph-stat-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--DarkBlue);
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .dark-mode .ph-stat-card h3 {
            color: var(--LightBlue);
        }
        
        .ph-stat-card p {
            font-size: 0.75rem;
            color: var(--Gray);
        }
        
        .ph-stat-card small {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--Gray);
        }
        
        .ph-history-section {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .ph-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--BlueGray);
            background: var(--sec-bg-color);
        }
        
        .ph-section-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .ph-view-all {
            font-size: 0.85rem;
            color: var(--MainBlue);
            text-decoration: none;
            font-weight: 500;
        }
        
        .ph-view-all:hover {
            text-decoration: underline;
        }
        
        .ph-table-wrap {
            overflow-x: auto;
        }
        
        .ph-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .ph-table thead th {
            text-align: left;
            padding: 1rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--Gray);
            background: var(--bg-color);
            border-bottom: 1px solid var(--BlueGray);
        }
        
        .ph-table tbody tr {
            border-bottom: 1px solid var(--BlueGray);
            transition: background 0.15s;
        }
        
        .ph-table tbody tr:hover {
            background: var(--sec-bg-color);
        }
        
        .ph-table tbody td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            color: var(--text-color);
        }
        
        .ph-device-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .ph-device-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--LightBlue);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .ph-device-icon svg {
            width: 20px;
            height: 20px;
        }
        
        .ph-device-name {
            font-weight: 500;
        }
        
        .ph-device-qty {
            font-size: 0.75rem;
            color: var(--Gray);
        }
        
        .ph-date {
            color: var(--Gray);
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        .ph-weight {
            font-weight: 500;
            white-space: nowrap;
        }
        
        .ph-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        
        .ph-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        
        .ph-badge.collected {
            background: #e3f7e8;
            color: #0b5e2e;
        }
        
        .dark-mode .ph-badge.collected {
            background: #1a3a2a;
            color: #8fdfb2;
        }
        
        .ph-badge.pending {
            background: #fff3d6;
            color: #8a6300;
        }
        
        .dark-mode .ph-badge.pending {
            background: #3a2e1a;
            color: #ffd966;
        }
        
        .ph-badge.processing {
            background: #e0f0ff;
            color: #0057a3;
        }
        
        .dark-mode .ph-badge.processing {
            background: #1a2a3a;
            color: #99c9ff;
        }
        
        .ph-badge.cancelled {
            background: #ffe5e5;
            color: #a10000;
        }
        
        .dark-mode .ph-badge.cancelled {
            background: #3a1a1a;
            color: #ff9999;
        }
        
        .ph-pts {
            font-weight: 600;
            color: var(--DarkBlue);
            white-space: nowrap;
        }
        
        .dark-mode .ph-pts {
            color: var(--LightBlue);
        }
        
        .ph-pts.zero {
            color: var(--Gray);
            font-weight: 400;
        }
        
        /* Awareness banner */
        .ph-awareness {
            background: linear-gradient(135deg, var(--MainBlue) 0%, #1a4a7a 100%);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .ph-awareness h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .ph-awareness p {
            font-size: 0.9rem;
            opacity: 0.95;
            max-width: 600px;
            line-height: 1.6;
        }
        
        .ph-awareness-btn {
            background: white;
            color: var(--DarkBlue);
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            flex-shrink: 0;
        }
        
        .ph-awareness-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Loading state */
        .ph-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: var(--Gray);
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
                        <a href="../../html/common/Setting.php">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>
                    <a href="../../html/provider/pHome.php">Home</a>
                    <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                    <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                    <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                    <a href="../../html/common/About.php">About</a>
                </div>
            </div>
        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <a href="../../html/provider/pHome.php">Home</a>
            <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
            <a href="../../html/provider/pMainPickup.php">My Pickup</a>
            <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
            <a href="../../html/common/About.php">About</a>
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
        <!-- <div class="ph-back-row">
            <button class="ph-back-btn" onclick="window.location.href='../../html/common/Dashboard.html'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Dashboard
            </button>
        </div> -->

        <section class="ph-stats-bar">
            <div class="ph-stats-card">
                <div class="ph-stats-label">Total Submissions</div>
                <div class="ph-stats-value" id="statSubmissions"><?php echo count($allItemsStats)?></div>
            </div>
            <div class="ph-stats-divider"></div>
            <div class="ph-stats-card">
                <div class="ph-stats-label">Total Weight</div>
                <div class="ph-stats-value" id="statWeight">
                    <?php
                $totalWeight = 0;

                foreach ($approvedItemStats as $item) {
                    $totalWeight += (float) $item['weight'];
                }

                echo $totalWeight;
                ?> kg</div>
            </div>
            <div class="ph-stats-divider"></div>
            <div class="ph-stats-card">
                <div class="ph-stats-label">Green Points</div>
                <div class="ph-stats-value" id="statPoints"><?php echo $stats['point']; ?></div>
            </div>
        </section>

        <!-- Greeting -->
        <div class="ph-greeting">
            <div>
                <h1>Welcome back, <span id="providerName"><?php echo $provider_name; ?></span> 👋</h1>
                <p id="currentDateTime">Thursday, 19 Feb 2026 · Kuala Lumpur, MY</p>
            </div>
            <a href="pSchedulePickup.php" class="ph-submit-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Schedule Pickup
            </a>
        </div>

        <!-- Two-column layout (like history page) -->
        <div class="ph-layout">
            <!-- LEFT: Profile Column -->
            <aside class="ph-profile-col">
                <div class="ph-avatar-section">
                    <div class="ph-avatar" id="avatarInitials"><?php echo $initials; ?></div>
                    <div class="ph-profile-name" id="displayName"><?php echo $provider_name; ?></div>
                    <div class="ph-profile-id" id="providerId">PRV-2024-00182</div>
                </div>

                <div class="ph-profile-divider"></div>

                <div class="ph-profile-meta">
                    <div class="ph-meta-row">
                        <span>Email</span>
                        <span id="profileEmail"><?php echo $provider_email; ?></span>
                    </div>
                    <div class="ph-meta-row">
                        <span>Phone</span>
                        <span id="profilePhone">+60 11-2345 6789</span>
                    </div>
                    <div class="ph-meta-row">
                        <span>Member since</span>
                        <span id="memberSince"><?php echo $createdAt;?></span>
                    </div>
                    <div class="ph-meta-row">
                        <span>Address</span>
                        <span id="profileLocation"><?php echo $stats['address']; ?></span>
                    </div>
                    <div class="ph-meta-row">
                        <span>State</span>
                        <span id="profileLocation"><?php echo $stats['state']; ?></span>
                    </div>
                    <div class="ph-meta-row">
                        <span>Postcode</span>
                        <span id="profileLocation"><?php echo $stats['postcode']; ?></span>
                    </div>
                </div>

                <a href="../common/Profile.php" class="ph-edit-link">✏ Edit Profile</a>
            </aside>

            <!-- RIGHT: Content Column -->
            <div class="ph-content-col">
            <!-- Points Card --> 
            <div class="ph-points-card">
                <div class="ph-points-left">
                    <h2 id="totalPoints"><?php echo number_format($stats['point']); ?></h2>
                    <p>Total Green Points Earned</p>
                    <div class="ph-progress-wrap">
                        <?php
                        $currentPoints = $stats['point'] ?? 0;

                        // Define Tier Boundaries
                        $tiers = [
                            ['name' => 'Bronze',   'min' => 0,    'max' => 100,  'emoji' => '🥉'],
                            ['name' => 'Silver',   'min' => 100,  'max' => 300,  'emoji' => '🥈'],
                            ['name' => 'Gold',     'min' => 300,  'max' => 600,  'emoji' => '🥇'],
                            ['name' => 'Platinum', 'min' => 600,  'max' => 1000, 'emoji' => '💎']
                        ];

                        // Determine Current Tier
                        $userTier = $tiers[0];
                        $nextTier = null;

                        foreach ($tiers as $index => $tier) {
                            if ($currentPoints >= $tier['min']) {
                                $userTier = $tier;
                                $nextTier = $tiers[$index + 1] ?? null; 
                            }
                        }

                        // Calculate Progress Percentage
                        if ($nextTier) {
                            $range = $userTier['max'] - $userTier['min'];
                            $progressInRange = $currentPoints - $userTier['min'];
                            $percent = ($progressInRange / $range) * 100;
                            $percent = max(0, min(100, $percent));
                            $ptsToNext = $userTier['max'] - $currentPoints;
                        } else {
                            // Max tier reached
                            $percent = 100;
                            $ptsToNext = 0;
                        }
                        ?>

                        <div class="ph-progress-label">
                            <span id="currentTier">
                                <?php echo $nextTier ? $userTier['name'] . " → " . $nextTier['name'] : "Max Tier Reached"; ?>
                            </span>
                            <span id="pointsProgress">
                                <?php echo $currentPoints; ?> / <?php echo $userTier['max']; ?>
                            </span>
                        </div>
                        
                        <div class="ph-progress-bar">
                            <div class="ph-progress-fill" id="progressFill" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                </div>
            <div>
                <div class="ph-tier" id="tierBadge">
                    <?php echo $userTier['emoji'] . " " . $userTier['name']; ?>
                </div>
                <div class="ph-next" id="nextTierInfo">
                    <?php if ($nextTier): ?>
                        <?php echo number_format($ptsToNext); ?> pts to <?php echo $nextTier['name']; ?>
                    <?php else: ?>
                        Ultimate Rank Achieved!
                    <?php endif; ?>
                </div>
            </div>
        </div>

                <!-- Mini Stats -->
                <div class="ph-mini-stats">
                    <div class="ph-stat-card">
                        <div class="ph-stat-icon">📦</div>
                        <h3 id="totalSubmissions"><?php echo count($allItemsStats); ?></h3>
                        <p>Total Submissions</p>
                    </div>
                    <div class="ph-stat-card">
                        <div class="ph-stat-icon">⚖️</div>
                        <h3 id="totalWeight"><?php
                        $totalWeight = 0;

                        foreach ($approvedItemStats as $item) {
                            $totalWeight += (float) $item['weight'];
                        }

                        echo $totalWeight;
                        ?> <small>kg</small></h3>
                        <p>Total Weight</p>
                    </div>
                    <div class="ph-stat-card">
                        <div class="ph-stat-icon">⏳</div>
                        <h3 id="pendingPickups"><?php echo count($pendingItemStats); ?></h3>
                        <p>Pending Pickup</p>
                    </div>
                </div>

                <div class="ph-history-section">
                    <div class="ph-section-header">
                        <h2>📋 Recent Submission</h2> <a href="pMainPickup.php" class="ph-view-all">View all →</a>
                    </div>

                    <div class="ph-table-wrap">
                        <table class="ph-table">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Date</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody id="recentSubmissionsTable">
                                <?php if (!empty($allItemsStats)): ?>
                                    <?php 
                                        // We only take the first item from the array
                                        $latest = $allItemsStats[0]; 
                                        echo "<script>console.log('Latest submission data: " . json_encode($latest) . "');</script>";
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($latest['itemName']); ?></strong></td>
                                        <td><?php echo date('d M Y', strtotime($latest['createdAt'])); ?></td>
                                        <td><?php echo htmlspecialchars($latest['weight']); ?> kg</td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($latest['status']); ?>">
                                                <?php echo htmlspecialchars($latest['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($latest['itemRecyclePoints'] ?? '0'); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;">No recent submissions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Awareness Banner -->
                <div class="ph-awareness">
                    <div>
                        <h3>🌍 Did you know?</h3>
                        <p>Only 22% of e-waste is formally recycled globally. By submitting through AfterVolt, you've helped divert <strong id="totalWeightAwareness">47 kg</strong> of hazardous material from landfills — great work!</p>
                    </div>
                    <a href="pEwasteGuide.php" class="ph-awareness-btn">Read E-Waste Guide</a>
                </div>
            </div>
        </div>
    </main>

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
                <a href="../../html/provider/pEwasteGuide.php">E-Waste Guide</a>
            </div>
            <div>
                <b>My Activity</b><br>
                <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a><br>
                <a href="../../html/provider/pMainPickup.php">My Pickup</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/About.php">About</a><br>
                <a href="../../html/common/Profile.php">Edit Profile</a><br>
                <a href="../../html/common/Setting.php">Setting</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        // pHome.js - Matching the aesthetic of cCompletedJobs
        
        document.addEventListener('DOMContentLoaded', function() {
            // Set current date/time
            updateDateTime();
            
            // Load user data
            loadUserProfile();
            
            // Load recent submissions
            loadRecentSubmissions();
            
            // Update stats
            updateStats();
        });
        
        function updateDateTime() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            const dateStr = now.toLocaleDateString('en-US', options);
            document.getElementById('currentDateTime').textContent = `${dateStr} · Kuala Lumpur, MY`;
        }
        
        function loadUserProfile() {
            // Simulate loading user data
            // In production, this would fetch from localStorage or API
            
            const userData = {
                name: <?php echo $provider_name?>,
                initials: <?php echo $initials?>,
                id: 'PRV-2024-00182',
                email: 'farid@email.com',
                phone: '+60 11-2345 6789',
                memberSince: 'Jan 2024',
                location: 'Petaling Jaya, MY'
            };
            
            document.getElementById('providerName').textContent = userData.name;
            document.getElementById('avatarInitials').textContent = userData.initials;
            document.getElementById('displayName').textContent = userData.name;
            document.getElementById('providerId').textContent = userData.id;
            document.getElementById('profileEmail').textContent = userData.email;
            document.getElementById('profilePhone').textContent = userData.phone;
            document.getElementById('memberSince').textContent = userData.memberSince;
            document.getElementById('profileLocation').textContent = userData.location;
        }
        
        function loadRecentSubmissions() {
            // Simulated data - in production, this would come from an API
            const submissions = [
                {
                    icon: '📱',
                    name: 'Smartphones',
                    qty: 3,
                    date: '15 Feb 2026',
                    weight: '0.9 kg',
                    status: 'collected',
                    statusText: 'Collected',
                    points: '+180 pts'
                },
                {
                    icon: '💻',
                    name: 'Laptop',
                    qty: 1,
                    date: '10 Feb 2026',
                    weight: '2.3 kg',
                    status: 'processing',
                    statusText: 'Processing',
                    points: '+460 pts'
                },
                {
                    icon: '🖥️',
                    name: 'Monitor',
                    qty: 2,
                    date: '28 Jan 2026',
                    weight: '8.5 kg',
                    status: 'pending',
                    statusText: 'Pending',
                    points: '—'
                },
                {
                    icon: '⌨️',
                    name: 'Keyboard & Mouse',
                    qty: 4,
                    date: '15 Jan 2026',
                    weight: '1.2 kg',
                    status: 'collected',
                    statusText: 'Collected',
                    points: '+240 pts'
                },
                {
                    icon: '📟',
                    name: 'Old Tablets',
                    qty: 2,
                    date: '02 Jan 2026',
                    weight: '1.8 kg',
                    status: 'cancelled',
                    statusText: 'Cancelled',
                    points: '—'
                }
            ];
            
            const tbody = document.getElementById('recentSubmissionsTable');
            tbody.innerHTML = '';
            
            submissions.forEach(sub => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="ph-device-cell">
                            <div class="ph-device-icon">${sub.icon}</div>
                            <div>
                                <div class="ph-device-name">${sub.name}</div>
                                <div class="ph-device-qty">${sub.qty} units</div>
                            </div>
                        </div>
                    </td>
                    <td class="ph-date">${sub.date}</td>
                    <td class="ph-weight">${sub.weight}</td>
                    <td><span class="ph-badge ${sub.status}"><span class="ph-badge-dot"></span>${sub.statusText}</span></td>
                    <td class="ph-pts ${sub.points === '—' ? 'zero' : ''}">${sub.points}</td>
                `;
                tbody.appendChild(row);
            });
        }
        
        function updateStats() {
            // Update stats values
            document.getElementById('statSubmissions').textContent = '18';
            document.getElementById('statWeight').textContent = '47 kg';
            document.getElementById('statPoints').textContent = '3,420';
            
            document.getElementById('totalSubmissions').textContent = '18';
            document.getElementById('totalWeight').textContent = '47';
            document.getElementById('pendingPickups').textContent = '2';
            document.getElementById('totalWeightAwareness').textContent = '47 kg';
        }
        
        // Dark mode support
        function updateTheme() {
            const isDark = document.body.classList.contains('dark-mode');
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