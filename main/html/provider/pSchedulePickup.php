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
                        <label class="psp-form-label">Pickup Address <span class="required-star">*</span></label>
                        <input type="text" class="psp-form-control" id="pickupAddress" placeholder="Street address, building, unit no." value="<?php echo htmlspecialchars($stats['address']); ?>">
                        <div class="error-message" id="addressError" style="display: none;"></div>
                    </div>

                    <div class="psp-form-group">
                        <label class="psp-form-label">State <span class="required-star">*</span></label>
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
                        <div class="error-message" id="stateError" style="display: none;"></div>
                    </div>

                    <div class="psp-form-group">
                        <label class="psp-form-label">Postcode <span class="required-star">*</span></label>
                        <input type="text" class="psp-form-control" id="pickupPostcode" placeholder="e.g., 47500" value="<?php echo htmlspecialchars($stats['postcode']); ?>" maxlength="5">
                        <div class="error-message" id="postcodeError" style="display: none;"></div>
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
                        <label class="psp-form-label">Preferred Date <span class="required-star">*</span></label>
                        <input type="date" class="psp-form-control" id="preferredDate" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>">
                        <div class="error-message" id="dateError" style="display: none;"></div>
                    </div>

                    <div class="psp-form-group">
                        <label class="psp-form-label">Preferred Time <span class="required-star">*</span></label>
                        <select class="psp-form-control" id="preferredTime">
                            <option value="09:00:00">09:00 AM</option>
                            <option value="10:00:00">10:00 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="14:00:00" selected>02:00 PM</option>
                            <option value="15:00:00">03:00 PM</option>
                            <option value="16:00:00">04:00 PM</option>
                            <option value="17:00:00">05:00 PM</option>
                        </select>
                        <div class="error-message" id="timeError" style="display: none;"></div>
                    </div>
                </div>

                <div class="psp-form-group mt-2">
                    <label class="psp-form-label">Special Instructions (Optional)</label>
                    <textarea class="psp-form-control" id="specialInstructions" placeholder="Any special instructions for the collector? e.g., gate code, landmark, etc." maxlength="500"></textarea>
                    <div class="char-counter" id="charCounter">0/500 characters</div>
                </div>
            </div>

            <!-- E-Waste Items Card -->
            <div class="psp-card">
                <div class="psp-card-header">
                    <span class="step-badge">3</span>
                    <h2>E-Waste Items <span class="required-star">*</span></h2>
                </div>

                <div class="psp-items-header">
                    <span class="psp-form-label">Add items you want to recycle (at least one item required)</span>
                    <button type="button" class="psp-add-item-btn" onclick="addNewItem()">
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
                <div class="error-message" id="itemsError" style="display: none; margin-top: 10px;"></div>

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
                        <p style="font-size: 0.7rem;">PNG, JPG up to 5MB (Max 5 files)</p>
                    </div>
                    <div id="imagePreview" class="psp-image-preview"></div>
                    <div class="error-message" id="imageError" style="display: none;"></div>
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
    // pSchedulePickup.js with Enhanced Validations - COMPLETE VERSION WITH FIXED SUMMARY
    
    let itemCount = 0;
    let items = [];
    let uploadedImages = [];
    const MAX_IMAGES = 5;
    
    // E-waste types from database (populated from PHP)
    const eWasteTypes = <?php echo json_encode($itemTypes); ?>;
    
    // Brand list
    const brands = [
        'Apple', 'Samsung', 'Dell', 'HP', 'Lenovo', 'Asus', 'Acer',
        'Microsoft', 'Sony', 'LG', 'Panasonic', 'Canon', 'Epson', 'Other'
    ];
    
    // Helper function to show field error
    function showFieldError(fieldId, message) {
        const errorDiv = document.getElementById(fieldId + 'Error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            const inputField = document.getElementById(fieldId);
            if (inputField) inputField.classList.add('error');
        }
    }
    
    function hideFieldError(fieldId) {
        const errorDiv = document.getElementById(fieldId + 'Error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
            const inputField = document.getElementById(fieldId);
            if (inputField) inputField.classList.remove('error');
        }
    }
    
    function clearAllErrors() {
        const errorFields = ['address', 'state', 'postcode', 'date', 'time', 'items', 'image'];
        errorFields.forEach(field => hideFieldError(field));
    }
    
    // Validate postcode format (Malaysian 5-digit)
    function isValidPostcode(postcode) {
        return /^\d{5}$/.test(postcode);
    }
    
    // Validate date is not in the past and not more than 30 days ahead
    function isValidPickupDate(dateStr) {
        const selectedDate = new Date(dateStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 30);
        
        if (selectedDate < today) return false;
        if (selectedDate > maxDate) return false;
        return true;
    }
    
    // Main validation function - validates all required fields including item details
    function validateForm() {
        clearAllErrors();
        let isValid = true;
        
        // 1. Validate Address
        const address = document.getElementById('pickupAddress').value.trim();
        if (address === '') {
            showFieldError('address', 'Pickup address is required');
            isValid = false;
        } else if (address.length < 5) {
            showFieldError('address', 'Please enter a complete address (minimum 5 characters)');
            isValid = false;
        }
        
        // 2. Validate State
        const state = document.getElementById('pickupState').value;
        if (state === '') {
            showFieldError('state', 'Please select a state');
            isValid = false;
        }
        
        // 3. Validate Postcode
        const postcode = document.getElementById('pickupPostcode').value.trim();
        if (postcode === '') {
            showFieldError('postcode', 'Postcode is required');
            isValid = false;
        } else if (!isValidPostcode(postcode)) {
            showFieldError('postcode', 'Please enter a valid 5-digit Malaysian postcode');
            isValid = false;
        }
        
        // 4. Validate Date
        const date = document.getElementById('preferredDate').value;
        if (!date) {
            showFieldError('date', 'Please select a preferred pickup date');
            isValid = false;
        } else if (!isValidPickupDate(date)) {
            showFieldError('date', 'Pickup date must be tomorrow or within the next 30 days');
            isValid = false;
        }
        
        // 5. Validate Time
        const time = document.getElementById('preferredTime').value;
        if (!time) {
            showFieldError('time', 'Please select a preferred time');
            isValid = false;
        }
        
        // 6. Validate Items - Now checks all required fields: type, brand, model, description, weight, length, width, height
        const itemCards = document.querySelectorAll('.psp-item-card');
        if (itemCards.length === 0) {
            showFieldError('items', 'Please add at least one e-waste item');
            isValid = false;
        } else {
            let hasValidItem = false;
            let firstErrorMsg = '';
            
            for (let idx = 0; idx < itemCards.length; idx++) {
                const card = itemCards[idx];
                const inputs = card.querySelectorAll('select, input');
                
                // Get all field values
                const typeValue = inputs[0] ? inputs[0].value : '';
                const brandValue = inputs[1] ? inputs[1].value : '';
                const modelValue = inputs[2] ? inputs[2].value.trim() : '';
                const descriptionValue = inputs[3] ? inputs[3].value.trim() : '';
                const weightValue = inputs[4] ? parseFloat(inputs[4].value) : 0;
                const lengthValue = inputs[5] ? parseFloat(inputs[5].value) : 0;
                const widthValue = inputs[6] ? parseFloat(inputs[6].value) : 0;
                const heightValue = inputs[7] ? parseFloat(inputs[7].value) : 0;
                
                // Check if this item has all required fields filled
                let itemComplete = true;
                let itemErrorMsg = '';
                
                if (!typeValue || typeValue === '') {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Please select an item type`;
                } else if (!brandValue || brandValue === '') {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Please select a brand`;
                } else if (!modelValue) {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Please enter the model`;
                } else if (!descriptionValue) {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Please enter a brief description`;
                } else if (!weightValue || weightValue <= 0) {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Weight must be greater than 0 kg`;
                } else if (!lengthValue || lengthValue <= 0) {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Length must be greater than 0 cm`;
                } else if (!widthValue || widthValue <= 0) {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Width must be greater than 0 cm`;
                } else if (!heightValue || heightValue <= 0) {
                    itemComplete = false;
                    itemErrorMsg = `Item #${idx + 1}: Height must be greater than 0 cm`;
                }
                
                if (itemComplete) {
                    hasValidItem = true;
                } else if (!firstErrorMsg) {
                    firstErrorMsg = itemErrorMsg;
                }
            }
            
            if (!hasValidItem && itemCards.length > 0) {
                if (firstErrorMsg) {
                    showFieldError('items', firstErrorMsg);
                } else {
                    showFieldError('items', 'Please fill in all required fields for at least one item (Type, Brand, Model, Description, Weight, Length, Width, Height)');
                }
                isValid = false;
            }
        }
        
        // 7. Validate Images (max 5 files)
        if (uploadedImages.length > MAX_IMAGES) {
            showFieldError('image', `Maximum ${MAX_IMAGES} images allowed`);
            isValid = false;
        }
        
        return isValid;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Set minimum date for pickup (tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dateInput = document.getElementById('preferredDate');
        dateInput.min = tomorrow.toISOString().split('T')[0];
        
        // Add real-time validation listeners
        document.getElementById('pickupAddress').addEventListener('input', () => hideFieldError('address'));
        document.getElementById('pickupState').addEventListener('change', () => hideFieldError('state'));
        document.getElementById('pickupPostcode').addEventListener('input', () => hideFieldError('postcode'));
        document.getElementById('preferredDate').addEventListener('change', () => {
            hideFieldError('date');
            updateSummary();
        });
        document.getElementById('preferredTime').addEventListener('change', () => {
            hideFieldError('time');
            updateSummary();
        });
        
        // Character counter for special instructions
        const textarea = document.getElementById('specialInstructions');
        const charCounter = document.getElementById('charCounter');
        if (textarea && charCounter) {
            textarea.addEventListener('input', function() {
                const length = this.value.length;
                charCounter.textContent = `${length}/500 characters`;
                if (length > 500) {
                    this.value = this.value.substring(0, 500);
                    charCounter.textContent = `500/500 characters`;
                }
            });
        }
        
        // Add first item by default
        addNewItem();
    });
    
    function addNewItem() {
        itemCount++;
        const itemId = `item_${itemCount}`;
        
        // Build item type options with recycle points data
        let typeOptions = '<option value="">Select Type</option>';
        eWasteTypes.forEach(type => {
            typeOptions += `<option value="${type.itemTypeID}" data-points="${type.recycle_points}">${escapeHtml(type.name)}</option>`;
        });
        
        const itemHtml = `
            <div class="psp-item-card" id="${itemId}">
                <div class="psp-item-header">
                    <span class="psp-item-title">Item #${itemCount}</span>
                    <button type="button" class="psp-remove-item" onclick="removeItem('${itemId}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="psp-item-grid">
                    <div class="psp-item-field">
                        <label>Type <span class="required-star">*</span></label>
                        <select onchange="updateSummary(); hideFieldError('items')">
                            ${typeOptions}
                        </select>
                    </div>
                    <div class="psp-item-field">
                        <label>Brand <span class="required-star">*</span></label>
                        <select onchange="updateSummary(); hideFieldError('items')">
                            <option value="">Select Brand</option>
                            ${brands.map(brand => `<option value="${escapeHtml(brand)}">${escapeHtml(brand)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="psp-item-field">
                        <label>Model <span class="required-star">*</span></label>
                        <input type="text" placeholder="e.g., iPhone 12" oninput="updateSummary(); hideFieldError('items')" maxlength="100">
                    </div>
                    <div class="psp-item-field">
                        <label>Description <span class="required-star">*</span></label>
                        <input type="text" placeholder="Brief description (e.g., working condition, color)" oninput="updateSummary(); hideFieldError('items')" maxlength="200">
                    </div>
                    <div class="psp-item-field">
                        <label>Weight (kg) <span class="required-star">*</span></label>
                        <input type="number" step="0.1" min="0.1" placeholder="0.0" oninput="updateSummary(); hideFieldError('items')" onchange="updateSummary(); hideFieldError('items')">
                    </div>
                    <div class="psp-item-field">
                        <label>Length (cm) <span class="required-star">*</span></label>
                        <input type="number" step="0.1" min="0.1" placeholder="0.0" oninput="updateSummary(); hideFieldError('items')" onchange="updateSummary(); hideFieldError('items')">
                    </div>
                    <div class="psp-item-field">
                        <label>Width (cm) <span class="required-star">*</span></label>
                        <input type="number" step="0.1" min="0.1" placeholder="0.0" oninput="updateSummary(); hideFieldError('items')" onchange="updateSummary(); hideFieldError('items')">
                    </div>
                    <div class="psp-item-field">
                        <label>Height (cm) <span class="required-star">*</span></label>
                        <input type="number" step="0.1" min="0.1" placeholder="0.0" oninput="updateSummary(); hideFieldError('items')" onchange="updateSummary(); hideFieldError('items')">
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', itemHtml);
        updateSummary();
        hideFieldError('items');
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    function removeItem(itemId) {
        const itemElement = document.getElementById(itemId);
        if (itemElement) {
            itemElement.remove();
            itemCount--;
            renumberItems();
            updateSummary();
            hideFieldError('items');
            // If no items left, show error
            if (document.querySelectorAll('.psp-item-card').length === 0) {
                showFieldError('items', 'Please add at least one e-waste item');
            }
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
        const errorDiv = document.getElementById('imageError');
        
        if (uploadedImages.length + files.length > MAX_IMAGES) {
            errorDiv.textContent = `Maximum ${MAX_IMAGES} images allowed. You can upload ${MAX_IMAGES - uploadedImages.length} more.`;
            errorDiv.style.display = 'block';
            return;
        }
        
        errorDiv.style.display = 'none';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Validate file type
            if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                errorDiv.textContent = 'Only JPG and PNG images are allowed';
                errorDiv.style.display = 'block';
                continue;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                errorDiv.textContent = 'File size must be less than 5MB';
                errorDiv.style.display = 'block';
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
                        <button type="button" onclick="removeImage('${imageId}')">✕</button>
                    </div>
                `;
                preview.insertAdjacentHTML('beforeend', imageHtml);
            };
            reader.readAsDataURL(file);
        }
        
        // Clear input to allow re-upload of same files if needed
        document.getElementById('imageInput').value = '';
    }
    
    function removeImage(imageId) {
        const imageElement = document.getElementById(imageId);
        if (imageElement) {
            imageElement.remove();
            uploadedImages = uploadedImages.filter(img => img.id !== imageId);
            document.getElementById('imageError').style.display = 'none';
        }
    }
    
    function updateSummary() {
        const itemCards = document.querySelectorAll('.psp-item-card');
        let totalWeight = 0;
        let estimatedPoints = 0;
        let totalVolume = 0;
        let itemDetails = [];
        
        itemCards.forEach((card, index) => {
            const inputs = card.querySelectorAll('select, input');
            const typeSelect = inputs[0];
            const weightInput = inputs[4];
            const lengthInput = inputs[5];
            const widthInput = inputs[6];
            const heightInput = inputs[7];
            const brandInput = inputs[1];
            const modelInput = inputs[2];
            
            if (typeSelect && weightInput) {
                const selectedOption = typeSelect.options[typeSelect.selectedIndex];
                const typeName = selectedOption ? selectedOption.text : 'Unknown';
                const typePoints = selectedOption ? parseInt(selectedOption.dataset.points || 0) : 0;
                const weight = parseFloat(weightInput.value) || 0;
                const length = parseFloat(lengthInput?.value) || 0;
                const width = parseFloat(widthInput?.value) || 0;
                const height = parseFloat(heightInput?.value) || 0;
                const volume = length * width * height;
                
                totalWeight += weight;
                estimatedPoints += weight * typePoints;
                totalVolume += volume;
                
                if (weight > 0) {
                    itemDetails.push({
                        number: index + 1,
                        type: typeName,
                        brand: brandInput?.value || 'Not specified',
                        model: modelInput?.value || 'Not specified',
                        weight: weight,
                        dimensions: length > 0 && width > 0 && height > 0 ? `${length} × ${width} × ${height} cm` : 'Not specified'
                    });
                }
            }
        });
        
        // Update summary statistics
        document.getElementById('summaryItemsCount').textContent = itemCards.length + ' items';
        document.getElementById('summaryTotalWeight').textContent = totalWeight.toFixed(2) + ' kg';
        document.getElementById('estimatedPoints').textContent = Math.round(estimatedPoints);
        
        // Add more detailed summary information
        const summaryCard = document.querySelector('.psp-summary-card');
        
        // Remove existing dynamic summary items if any (keep the static ones)
        const existingDynamicItems = summaryCard.querySelectorAll('.dynamic-summary-item');
        existingDynamicItems.forEach(item => item.remove());
        
        // Add total volume if available
        if (totalVolume > 0) {
            const volumeDiv = document.createElement('div');
            volumeDiv.className = 'psp-summary-item dynamic-summary-item';
            volumeDiv.innerHTML = `
                <span>Total Volume</span>
                <span>${totalVolume.toFixed(2)} cm³</span>
            `;
            const pointsEstimate = summaryCard.querySelector('.psp-points-estimate');
            pointsEstimate.parentNode.insertBefore(volumeDiv, pointsEstimate);
        }
        
        // Add item details section
        if (itemDetails.length > 0) {
            const detailsDiv = document.createElement('div');
            detailsDiv.className = 'psp-item-details-summary dynamic-summary-item';
            detailsDiv.style.marginTop = '15px';
            detailsDiv.style.borderTop = '1px solid #e0e0e0';
            detailsDiv.style.paddingTop = '12px';
            
            let detailsHtml = '<div style="font-weight: 600; margin-bottom: 8px; font-size: 0.9rem;">Items Details:</div>';
            itemDetails.forEach(item => {
                detailsHtml += `
                    <div style="font-size: 0.75rem; margin-bottom: 8px; padding: 6px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Item #${item.number}:</strong> ${item.type}<br>
                        ${item.brand !== 'Not specified' ? `Brand: ${item.brand}<br>` : ''}
                        ${item.model !== 'Not specified' ? `Model: ${item.model}<br>` : ''}
                        Weight: ${item.weight.toFixed(2)} kg<br>
                        ${item.dimensions !== 'Not specified' ? `Dimensions: ${item.dimensions}` : ''}
                    </div>
                `;
            });
            
            detailsDiv.innerHTML = detailsHtml;
            
            const submitBtn = summaryCard.querySelector('.psp-submit-btn');
            submitBtn.parentNode.insertBefore(detailsDiv, submitBtn);
        }
        
        // Update date and time in summary
        const date = document.getElementById('preferredDate').value;
        const time = document.getElementById('preferredTime').value;
        
        if (date) {
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('summaryDate').textContent = formattedDate;
        } else {
            document.getElementById('summaryDate').textContent = 'Not set';
        }
        
        if (time) {
            const timeMap = {
                '09:00:00': '09:00 AM',
                '10:00:00': '10:00 AM',
                '11:00:00': '11:00 AM',
                '14:00:00': '02:00 PM',
                '15:00:00': '03:00 PM',
                '16:00:00': '04:00 PM',
                '17:00:00': '05:00 PM'
            };
            document.getElementById('summaryTime').textContent = timeMap[time] || time.substring(0, 5);
        } else {
            document.getElementById('summaryTime').textContent = 'Not set';
        }
        
        // Update address summary
        const address = document.getElementById('pickupAddress').value.trim();
        const state = document.getElementById('pickupState').value;
        const postcode = document.getElementById('pickupPostcode').value.trim();
        
        const addressSummary = document.querySelector('.psp-summary-item:first-child');
        if (addressSummary && address) {
            const existingAddressDetail = summaryCard.querySelector('.address-detail');
            if (!existingAddressDetail) {
                const addressDiv = document.createElement('div');
                addressDiv.className = 'psp-summary-item address-detail dynamic-summary-item';
                addressDiv.style.marginTop = '8px';
                addressDiv.style.fontSize = '0.85rem';
                addressDiv.style.color = '#666';
                addressSummary.parentNode.insertBefore(addressDiv, addressSummary.nextSibling);
            }
            const addressDetail = summaryCard.querySelector('.address-detail');
            if (addressDetail) {
                addressDetail.innerHTML = `<span>Location</span><span>${address}, ${postcode}, ${state}</span>`;
            }
        }
    }
    
    function submitPickupRequest(providerId) {
        // Run validation first
        if (!validateForm()) {
            // Scroll to first error
            const firstError = document.querySelector('.error-message[style*="display: block"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        const requestData = {
            providerID: providerId,
            pickupAddress: document.getElementById('pickupAddress').value.trim(),
            pickupState: document.getElementById('pickupState').value,
            pickupPostcode: document.getElementById('pickupPostcode').value.trim(),
            preferredDateTime: document.getElementById('preferredDate').value + ' ' + document.getElementById('preferredTime').value,
            status: 'Pending',
            specialInstructions: document.getElementById('specialInstructions').value.trim(),
            items: collectItemData()
        };
        
        fetch('../../php/submitPickupRequest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
            alert('An error occurred while submitting the request. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Pickup Request';
        });
    }
    
    function collectItemData() {
        const itemsList = [];
        const itemCards = document.querySelectorAll('.psp-item-card');
        
        itemCards.forEach(card => {
            const inputs = card.querySelectorAll('select, input');
            
            // Get all field values
            const typeValue = inputs[0] ? inputs[0].value : '';
            const brandValue = inputs[1] ? inputs[1].value : '';
            const modelValue = inputs[2] ? inputs[2].value.trim() : '';
            const descriptionValue = inputs[3] ? inputs[3].value.trim() : '';
            const weightValue = inputs[4] ? parseFloat(inputs[4].value) : 0;
            const lengthValue = inputs[5] ? parseFloat(inputs[5].value) : 0;
            const widthValue = inputs[6] ? parseFloat(inputs[6].value) : 0;
            const heightValue = inputs[7] ? parseFloat(inputs[7].value) : 0;
            
            // Only include items that have ALL required fields filled
            if (typeValue && typeValue !== '' && 
                brandValue && brandValue !== '' && 
                modelValue && 
                descriptionValue && 
                weightValue > 0 && 
                lengthValue > 0 && 
                widthValue > 0 && 
                heightValue > 0) {
                itemsList.push({
                    itemTypeID: typeValue,
                    brand: brandValue,
                    model: modelValue,
                    description: descriptionValue,
                    weight: weightValue,
                    length: lengthValue,
                    width: widthValue,
                    height: heightValue,
                    status: 'Pending'
                });
            }
        });
        
        return itemsList;
    }
    
    function redirectToHome() {
        window.location.href = 'pMainPickup.php';
    }
    
    // Add event listeners for all input fields to update summary in real-time
    document.getElementById('preferredDate').addEventListener('change', updateSummary);
    document.getElementById('preferredTime').addEventListener('change', updateSummary);
    document.getElementById('pickupAddress').addEventListener('input', updateSummary);
    document.getElementById('pickupState').addEventListener('change', updateSummary);
    document.getElementById('pickupPostcode').addEventListener('input', updateSummary);
</script>
</body>
</html>