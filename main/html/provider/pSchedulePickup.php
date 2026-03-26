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

    <title>Schedule Pickup - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/pSchedulePickup.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        /* ═══ pSchedulePickup – styled to match cCompletedJobs aesthetic ═══ */
        
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
        
        /* ── Back button (matching history page) ── */
        .psp-back-row {
            margin-bottom: 0.5rem;
        }
        
        .psp-back-btn {
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
        
        .psp-back-btn:hover {
            background: var(--sec-bg-color);
            border-color: var(--MainBlue);
        }
        
        /* ── Page Header ── */
        .psp-header {
            margin-bottom: 1rem;
        }
        
        .psp-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .psp-header p {
            font-size: 0.9rem;
            color: var(--Gray);
        }
        
        /* ── Two-column layout ── */
        .psp-layout {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        @media (min-width: 992px) {
            .psp-layout {
                flex-direction: row;
            }
            .psp-form-col {
                flex: 2;
            }
            .psp-summary-col {
                flex: 1;
            }
        }
        
        /* ── Form Cards ── */
        .psp-card {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .psp-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--BlueGray);
        }
        
        .psp-card-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .psp-card-header .step-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--MainBlue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* ── Form Fields ── */
        .psp-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        @media (max-width: 640px) {
            .psp-form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .psp-form-group {
            margin-bottom: 1rem;
        }
        
        .psp-form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .psp-form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--Gray);
            margin-bottom: 0.35rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        
        .psp-form-control {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .psp-form-control:focus {
            outline: none;
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 3px rgba(0, 112, 243, 0.1);
        }
        
        .psp-form-control::placeholder {
            color: var(--Gray);
            opacity: 0.6;
        }
        
        select.psp-form-control {
            cursor: pointer;
        }
        
        textarea.psp-form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        /* ── Items Section ── */
        .psp-items-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .psp-add-item-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--MainBlue);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .psp-add-item-btn:hover {
            opacity: 0.9;
        }
        
        .psp-add-item-btn svg {
            width: 16px;
            height: 16px;
        }
        
        /* Item Card */
        .psp-item-card {
            background: var(--sec-bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .psp-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .psp-item-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-color);
        }
        
        .psp-remove-item {
            background: none;
            border: none;
            color: var(--Gray);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .psp-remove-item:hover {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }
        
        .psp-item-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .psp-item-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .psp-item-field {
            flex: 1;
        }
        
        .psp-item-field label {
            display: block;
            font-size: 0.7rem;
            color: var(--Gray);
            margin-bottom: 0.2rem;
        }
        
        .psp-item-field input,
        .psp-item-field select {
            width: 100%;
            padding: 0.4rem 0.5rem;
            border: 1px solid var(--BlueGray);
            border-radius: 6px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 0.8rem;
        }
        
        /* Image Upload */
        .psp-image-upload {
            border: 2px dashed var(--BlueGray);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .psp-image-upload:hover {
            border-color: var(--MainBlue);
            background: var(--sec-bg-color);
        }
        
        .psp-image-upload input {
            display: none;
        }
        
        .psp-image-upload svg {
            width: 24px;
            height: 24px;
            color: var(--Gray);
            margin-bottom: 0.5rem;
        }
        
        .psp-image-upload p {
            font-size: 0.8rem;
            color: var(--Gray);
        }
        
        .psp-image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .psp-image-preview-item {
            position: relative;
            width: 60px;
            height: 60px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid var(--BlueGray);
        }
        
        .psp-image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .psp-image-preview-item button {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(0,0,0,0.5);
            border: none;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        /* ── Summary Card ── */
        .psp-summary-card {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 12px;
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        
        .psp-summary-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--BlueGray);
        }
        
        .psp-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .psp-summary-item span:first-child {
            color: var(--Gray);
        }
        
        .psp-summary-item span:last-child {
            font-weight: 500;
            color: var(--text-color);
        }
        
        .psp-summary-total {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--BlueGray);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .psp-points-estimate {
            background: var(--LightBlue);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .psp-points-estimate .value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--DarkBlue);
            line-height: 1;
        }
        
        .psp-points-estimate .label {
            font-size: 0.8rem;
            color: var(--Gray);
        }
        
        .psp-submit-btn {
            width: 100%;
            background: var(--MainBlue);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 1rem;
        }
        
        .psp-submit-btn:hover {
            opacity: 0.9;
        }
        
        .psp-submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* ── Date/Time Picker ── */
        .psp-datetime-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        /* ── Success Modal ── */
        .psp-modal-overlay {
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
        
        .psp-modal {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        
        .psp-modal-icon {
            width: 64px;
            height: 64px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .psp-modal-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }
        
        .psp-modal h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .psp-modal p {
            color: var(--Gray);
            margin-bottom: 1.5rem;
        }
        
        .psp-modal-btn {
            background: var(--MainBlue);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* ── Helper classes ── */
        .text-danger {
            color: #dc3545;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php
    include("../../php/dbConn.php");
    if(!isset($_SESSION)) {
        session_start();
    }
    include("../../php/sessionCheck.php");

    // Check if user is provider; only providers can access this page
    if ($_SESSION['userType'] !== 'provider') {
        header("Location: ../../../index.html");
        exit();
    }

    // Get active user info of current session
    $_SESSION['provider_id'] = $_SESSION['userID'];
    $provider_id = $_SESSION['provider_id'];
    $provider_name = $_SESSION['fullname'];
    $provider_email = $_SESSION['email'];
    $createdAt = $_SESSION['createdAt'];
    $lastlogin = $_SESSION['lastLogin'];

    // Fetch provider statistics
    $stats = [
        'address' => 'N/A',
        'state' => 'N/A',
        'postcode' => 'N/A',
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
        }
    }

    // Fetch item types for dropdown
    $itemTypes = [];
    $sql = "SELECT itemTypeID, name, recycle_points FROM tblitem_type ORDER BY name";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $itemTypes[] = $row;
        }
    }
    ?>
    
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
                    <a href="../../html/common/About.html">About</a>
                </div>
            </div>
        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <a href="../../html/provider/pHome.php">Home</a>
            <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
            <a href="../../html/provider/pMainPickup.php">My Pickup</a>
            <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
            <a href="../../html/common/About.html">About</a>
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
        <div class="psp-back-row">
            <a href="pHome.php" class="psp-back-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Back to Home
            </a>
        </div>

        <!-- Page Header -->
        <div class="psp-header">
            <h1>Schedule E-Waste Pickup</h1>
            <p>Fill in the details below to request a pickup. Your Green Points balance: <strong><?php echo $stats['point']; ?></strong></p>
        </div>

        <!-- Two-column layout -->
        <div class="psp-layout">
            <!-- LEFT: Form Column -->
            <div class="psp-form-col">
                <!-- Pickup Details Card -->
                <div class="psp-card">
                    <div class="psp-card-header">
                        <span class="step-badge">1</span>
                        <h2>Pickup Location</h2>
                    </div>

                    <div class="psp-form-grid">
                        <div class="psp-form-group full-width">
                            <label class="psp-form-label">Pickup Address</label>
                            <input type="text" class="psp-form-control" id="pickupAddress" placeholder="Street address, building, unit no." value="<?php echo htmlspecialchars($stats['address']); ?>">
                        </div>

                        <div class="psp-form-group">
                            <label class="psp-form-label">State</label>
                            <select class="psp-form-control" id="pickupState">
                                <option value="">Select State</option>
                                <option value="Johor" <?php echo ($stats['state'] == 'Johor') ? 'selected' : ''; ?>>Johor</option>
                                <option value="Kedah" <?php echo ($stats['state'] == 'Kedah') ? 'selected' : ''; ?>>Kedah</option>
                                <option value="Kelantan" <?php echo ($stats['state'] == 'Kelantan') ? 'selected' : ''; ?>>Kelantan</option>
                                <option value="Kuala Lumpur" <?php echo ($stats['state'] == 'Kuala Lumpur') ? 'selected' : ''; ?>>Kuala Lumpur</option>
                                <option value="Labuan" <?php echo ($stats['state'] == 'Labuan') ? 'selected' : ''; ?>>Labuan</option>
                                <option value="Melaka" <?php echo ($stats['state'] == 'Melaka') ? 'selected' : ''; ?>>Melaka</option>
                                <option value="Negeri Sembilan" <?php echo ($stats['state'] == 'Negeri Sembilan') ? 'selected' : ''; ?>>Negeri Sembilan</option>
                                <option value="Pahang" <?php echo ($stats['state'] == 'Pahang') ? 'selected' : ''; ?>>Pahang</option>
                                <option value="Penang" <?php echo ($stats['state'] == 'Penang') ? 'selected' : ''; ?>>Penang</option>
                                <option value="Perak" <?php echo ($stats['state'] == 'Perak') ? 'selected' : ''; ?>>Perak</option>
                                <option value="Perlis" <?php echo ($stats['state'] == 'Perlis') ? 'selected' : ''; ?>>Perlis</option>
                                <option value="Putrajaya" <?php echo ($stats['state'] == 'Putrajaya') ? 'selected' : ''; ?>>Putrajaya</option>
                                <option value="Sabah" <?php echo ($stats['state'] == 'Sabah') ? 'selected' : ''; ?>>Sabah</option>
                                <option value="Sarawak" <?php echo ($stats['state'] == 'Sarawak') ? 'selected' : ''; ?>>Sarawak</option>
                                <option value="Selangor" <?php echo ($stats['state'] == 'Selangor') ? 'selected' : ''; ?>>Selangor</option>
                                <option value="Terengganu" <?php echo ($stats['state'] == 'Terengganu') ? 'selected' : ''; ?>>Terengganu</option>
                            </select>
                        </div>

                        <div class="psp-form-group">
                            <label class="psp-form-label">Postcode</label>
                            <input type="text" class="psp-form-control" id="pickupPostcode" placeholder="e.g., 47500" value="<?php echo htmlspecialchars($stats['postcode']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Schedule Card -->
                <div class="psp-card">
                    <div class="psp-card-header">
                        <span class="step-badge">2</span>
                        <h2>Preferred Schedule</h2>
                    </div>

                    <div class="psp-datetime-grid">
                        <div class="psp-form-group">
                            <label class="psp-form-label">Preferred Date</label>
                            <input type="date" class="psp-form-control" id="preferredDate" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>">
                        </div>

                        <div class="psp-form-group">
                            <label class="psp-form-label">Preferred Time</label>
                            <select class="psp-form-control" id="preferredTime">
                                <option value="09:00:00">09:00 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="14:00:00" selected>02:00 PM</option>
                                <option value="15:00:00">03:00 PM</option>
                                <option value="16:00:00">04:00 PM</option>
                                <option value="17:00:00">05:00 PM</option>
                            </select>
                        </div>
                    </div>

                    <div class="psp-form-group mt-2">
                        <label class="psp-form-label">Special Instructions (Optional)</label>
                        <textarea class="psp-form-control" id="specialInstructions" placeholder="Any special instructions for the collector? e.g., gate code, landmark, etc."></textarea>
                    </div>
                </div>

                <!-- E-Waste Items Card -->
                <div class="psp-card">
                    <div class="psp-card-header">
                        <span class="step-badge">3</span>
                        <h2>E-Waste Items</h2>
                    </div>

                    <div class="psp-items-header">
                        <span class="psp-form-label">Add items you want to recycle</span>
                        <button class="psp-add-item-btn" onclick="addNewItem()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Item
                        </button>
                    </div>

                    <!-- Items Container -->
                    <div id="itemsContainer">
                        <!-- Items will be dynamically added here -->
                    </div>

                    <!-- Image Upload Section -->
                    <div class="psp-form-group mt-3">
                        <label class="psp-form-label">Upload Item Photos (Optional)</label>
                        <div class="psp-image-upload" onclick="document.getElementById('imageInput').click()">
                            <input type="file" id="imageInput" multiple accept="image/*" onchange="handleImageUpload(event)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="9" cy="9" r="2"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <p>Click to upload or drag and drop</p>
                            <p style="font-size: 0.7rem;">PNG, JPG up to 5MB</p>
                        </div>
                        <div id="imagePreview" class="psp-image-preview"></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Summary Column -->
            <div class="psp-summary-col">
                <div class="psp-summary-card">
                    <div class="psp-summary-title">Pickup Summary</div>
                    
                    <div class="psp-summary-item">
                        <span>Items Count</span>
                        <span id="summaryItemsCount">0 items</span>
                    </div>
                    
                    <div class="psp-summary-item">
                        <span>Total Weight</span>
                        <span id="summaryTotalWeight">0 kg</span>
                    </div>
                    
                    <div class="psp-summary-item">
                        <span>Pickup Date</span>
                        <span id="summaryDate">Not set</span>
                    </div>
                    
                    <div class="psp-summary-item">
                        <span>Pickup Time</span>
                        <span id="summaryTime">Not set</span>
                    </div>
                    
                    <div class="psp-points-estimate">
                        <div class="value" id="estimatedPoints">0</div>
                        <div class="label">Estimated Green Points</div>
                    </div>
                    
                    <button class="psp-submit-btn" onclick="submitPickupRequest(<?php echo $provider_id; ?>)" id="submitBtn">
                        Submit Pickup Request
                    </button>
                    
                    <p class="text-danger mt-2" style="font-size: 0.8rem; text-align: center;">
                        * Points are estimated based on item types and weight
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Success Modal -->
    <div class="psp-modal-overlay" id="successModal">
        <div class="psp-modal">
            <div class="psp-modal-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h3>Pickup Request Submitted!</h3>
            <p>Your pickup request has been submitted successfully. Request ID: <strong id="requestIdDisplay">REQ001</strong></p>
            <p style="font-size: 0.9rem;">Our collector will contact you within 24 hours to confirm the schedule.</p>
            <button class="psp-modal-btn" onclick="redirectToHome()">View My Pickups</button>
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
                <a href="../../html/provider/pEwasteGuide.php">E-Waste Guide</a>
            </div>
            <div>
                <b>My Activity</b><br>
                <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a><br>
                <a href="../../html/provider/pMainPickup.php">My Pickup</a>
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
    <script>
        // pSchedulePickup.js
        
        let itemCount = 0;
        let items = [];
        let uploadedImages = [];
        
        // E-waste types from database (populated from PHP)
        const eWasteTypes = <?php echo json_encode($itemTypes); ?>;
        
        // Brand list
        const brands = [
            'Apple',
            'Samsung',
            'Dell',
            'HP',
            'Lenovo',
            'Asus',
            'Acer',
            'Microsoft',
            'Sony',
            'LG',
            'Panasonic',
            'Canon',
            'Epson',
            'Other'
        ];
        
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date for pickup (tomorrow)
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('preferredDate').min = tomorrow.toISOString().split('T')[0];
            
            // Add first item by default
            addNewItem();
        });
        
        function addNewItem() {
            itemCount++;
            const itemId = `item_${itemCount}`;
            
            // Build item type options with recycle points data
            let typeOptions = '<option value="">Select Type</option>';
            eWasteTypes.forEach(type => {
                typeOptions += `<option value="${type.itemTypeID}" data-points="${type.recycle_points}">${type.name}</option>`;
            });
            
            const itemHtml = `
                <div class="psp-item-card" id="${itemId}">
                    <div class="psp-item-header">
                        <span class="psp-item-title">Item #${itemCount}</span>
                        <button class="psp-remove-item" onclick="removeItem('${itemId}')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="psp-item-grid">
                        <div class="psp-item-field">
                            <label>Type</label>
                            <select onchange="updateSummary()">
                                ${typeOptions}
                            </select>
                        </div>
                        <div class="psp-item-field">
                            <label>Brand</label>
                            <select onchange="updateSummary()">
                                <option value="">Select</option>
                                ${brands.map(brand => `<option value="${brand}">${brand}</option>`).join('')}
                            </select>
                        </div>
                        <div class="psp-item-field">
                            <label>Model</label>
                            <input type="text" placeholder="e.g., iPhone 12" onchange="updateSummary()">
                        </div>
                        <div class="psp-item-field">
                            <label>Description</label>
                            <input type="text" placeholder="Brief description" onchange="updateSummary()">
                        </div>
                        <div class="psp-item-field">
                            <label>Weight (kg)</label>
                            <input type="number" step="0.1" min="0" placeholder="0.0" onchange="updateSummary()">
                        </div>
                        <div class="psp-item-field">
                            <label>Length (cm)</label>
                            <input type="number" step="0.1" min="0" placeholder="0.0" onchange="updateSummary()">
                        </div>
                        <div class="psp-item-field">
                            <label>Width (cm)</label>
                            <input type="number" step="0.1" min="0" placeholder="0.0" onchange="updateSummary()">
                        </div>
                        <div class="psp-item-field">
                            <label>Height (cm)</label>
                            <input type="number" step="0.1" min="0" placeholder="0.0" onchange="updateSummary()">
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', itemHtml);
            updateSummary();
        }
        
        function removeItem(itemId) {
            const itemElement = document.getElementById(itemId);
            if (itemElement) {
                itemElement.remove();
                itemCount--;
                
                // Renumber remaining items
                renumberItems();
                updateSummary();
            }
        }
        
        function renumberItems() {
            const itemCards = document.querySelectorAll('.psp-item-card');
            itemCards.forEach((card, index) => {
                const title = card.querySelector('.psp-item-title');
                if (title) {
                    title.textContent = `Item #${index + 1}`;
                }
            });
        }
        
        function handleImageUpload(event) {
            const files = event.target.files;
            const preview = document.getElementById('imagePreview');
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    continue;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageId = 'img_' + Date.now() + '_' + i;
                    uploadedImages.push({
                        id: imageId,
                        data: e.target.result,
                        file: file
                    });
                    
                    const imageHtml = `
                        <div class="psp-image-preview-item" id="${imageId}">
                            <img src="${e.target.result}" alt="Preview">
                            <button onclick="removeImage('${imageId}')">✕</button>
                        </div>
                    `;
                    preview.insertAdjacentHTML('beforeend', imageHtml);
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeImage(imageId) {
            const imageElement = document.getElementById(imageId);
            if (imageElement) {
                imageElement.remove();
                uploadedImages = uploadedImages.filter(img => img.id !== imageId);
            }
        }
        
        function updateSummary() {
            const itemCards = document.querySelectorAll('.psp-item-card');
            let totalItems = 0;
            let totalWeight = 0;
            let estimatedPoints = 0;
            
            itemCards.forEach((card, index) => {
                const typeSelect = card.querySelector('select:first-child');
                const weightInput = card.querySelector('input[type="number"]:first-child');
                
                if (typeSelect && weightInput) {
                    const selectedOption = typeSelect.options[typeSelect.selectedIndex];
                    const typePoints = selectedOption ? parseInt(selectedOption.dataset.points || 0) : 0;
                    const weight = parseFloat(weightInput.value) || 0;
                    
                    totalWeight += weight;
                    
                    // Calculate points: weight * recycle_points for the item type
                    estimatedPoints += weight * typePoints;
                }
            });
            
            // Update summary
            document.getElementById('summaryItemsCount').textContent = itemCards.length + ' items';
            document.getElementById('summaryTotalWeight').textContent = totalWeight.toFixed(1) + ' kg';
            document.getElementById('estimatedPoints').textContent = Math.round(estimatedPoints);
            
            // Update date and time
            const date = document.getElementById('preferredDate').value;
            const time = document.getElementById('preferredTime').value;
            
            if (date) {
                const formattedDate = new Date(date).toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
                document.getElementById('summaryDate').textContent = formattedDate;
            }
            
            if (time) {
                document.getElementById('summaryTime').textContent = time.substring(0, 5);
            }
        }
        
        function submitPickupRequest(providerId) {
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Disable submit button
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            // Prepare data according to database schema
            const requestData = {
                providerID: providerId,
                pickupAddress: document.getElementById('pickupAddress').value,
                pickupState: document.getElementById('pickupState').value,
                pickupPostcode: document.getElementById('pickupPostcode').value,
                preferredDateTime: document.getElementById('preferredDate').value + ' ' + document.getElementById('preferredTime').value,
                status: 'Pending',
                specialInstructions: document.getElementById('specialInstructions').value,
                items: collectItemData()
            };
            
            // Send data to server
            fetch('../../php/submitPickupRequest.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('requestIdDisplay').textContent = data.requestID;
                    document.getElementById('successModal').style.display = 'flex';
                } else {
                    alert('Error submitting request: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Pickup Request';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the request');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Pickup Request';
            });
        }
        
        function collectItemData() {
            const items = [];
            const itemCards = document.querySelectorAll('.psp-item-card');
            
            itemCards.forEach(card => {
                const inputs = card.querySelectorAll('select, input');
                
                items.push({
                    itemTypeID: inputs[0]?.value || null,
                    brand: inputs[1]?.value || '',
                    model: inputs[2]?.value || '',
                    description: inputs[3]?.value || '',
                    weight: parseFloat(inputs[4]?.value) || 0,
                    length: parseFloat(inputs[5]?.value) || 0,
                    width: parseFloat(inputs[6]?.value) || 0,
                    height: parseFloat(inputs[7]?.value) || 0,
                    status: 'Pending'
                });
            });
            
            return items;
        }
        
        function validateForm() {
            // Validate address
            if (!document.getElementById('pickupAddress').value) {
                alert('Please enter pickup address');
                return false;
            }
            
            if (!document.getElementById('pickupState').value) {
                alert('Please select state');
                return false;
            }
            
            if (!document.getElementById('pickupPostcode').value) {
                alert('Please enter postcode');
                return false;
            }
            
            // Validate date
            if (!document.getElementById('preferredDate').value) {
                alert('Please select preferred date');
                return false;
            }
            
            // Validate items
            const itemCards = document.querySelectorAll('.psp-item-card');
            if (itemCards.length === 0) {
                alert('Please add at least one item');
                return false;
            }
            
            let hasValidItem = false;
            itemCards.forEach(card => {
                const inputs = card.querySelectorAll('select, input');
                if (inputs[0]?.value && inputs[4]?.value > 0) {
                    hasValidItem = true;
                }
            });
            
            if (!hasValidItem) {
                alert('Please fill in at least one item with type and weight');
                return false;
            }
            
            return true;
        }
        
        function redirectToHome() {
            window.location.href = 'pMainPickup.php';
        }
        
        // Listen for form changes to update summary
        document.getElementById('preferredDate').addEventListener('change', updateSummary);
        document.getElementById('preferredTime').addEventListener('change', updateSummary);
    </script>
</body>
</html>