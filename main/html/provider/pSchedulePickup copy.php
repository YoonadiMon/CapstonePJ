<?php
    include("../../php/dbConn.php");
    if(!isset($_SESSION)) {
        session_start();
    }
    include("../../php/sessionCheck.php");

    // Check if user is provider; only providers can access this page
    if ($_SESSION['userType'] !== 'provider') {
        header("Location: ../../index.html");
        exit();
    }

    // get active user info of current session
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

    // Fetch item types from database
    $itemTypes = [];
    $sql = "SELECT itemTypeID, name, recycle_points FROM tblitem_type ORDER BY recycle_points ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $itemTypes[] = $row;
        }
    }

    // Fetch provided item statistics (for display purposes)
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
            ORDER BY cr.createdAt DESC
            LIMIT 10";
            
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $allItemsStats[] = $row;
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
                <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                <a href="../../html/provider/pEwasteGuide.html">E-waste Guide</a>
                <a href="../../html/common/About.html">About</a>
            </div>
        </div>
    </nav>

    <!-- Menu Links Desktop + Tablet -->
    <nav class="c-navbar-desktop">
        <a href="../../html/provider/pHome.php">Home</a>
        <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
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
        <p>Fill in the details below to request a pickup. Our collector will contact you to confirm the schedule.</p>
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
                        <input type="text" class="psp-form-control" id="pickupAddress" name="pickupAddress" 
                               placeholder="Street address, building, unit no." 
                               value="<?php echo htmlspecialchars($stats['address']); ?>" required>
                    </div>

                    <div class="psp-form-group">
                        <label class="psp-form-label">State</label>
                        <select class="psp-form-control" id="pickupState" name="pickupState" required>
                            <option value="">Select State</option>
                            <?php
                            $states = ['Johor', 'Kedah', 'Kelantan', 'Kuala Lumpur', 'Labuan', 'Melaka', 
                                      'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 
                                      'Putrajaya', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];
                            foreach ($states as $state) {
                                $selected = ($state == $stats['state']) ? 'selected' : '';
                                echo "<option value=\"$state\" $selected>$state</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="psp-form-group">
                        <label class="psp-form-label">Postcode</label>
                        <input type="text" class="psp-form-control" id="pickupPostcode" name="pickupPostcode" 
                               placeholder="e.g., 47500" 
                               value="<?php echo htmlspecialchars($stats['postcode']); ?>" required>
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
                        <input type="date" class="psp-form-control" id="preferredDate" name="preferredDate" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                               value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>

                    <div class="psp-form-group">
                        <label class="psp-form-label">Preferred Time</label>
                        <select class="psp-form-control" id="preferredTime" name="preferredTime" required>
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
                    <textarea class="psp-form-control" id="specialInstructions" name="specialInstructions" 
                              placeholder="Any special instructions for the collector? e.g., gate code, landmark, etc."></textarea>
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

                <!-- Image Upload Section -->
                <div class="psp-form-group mt-3">
                    <label class="psp-form-label">Upload Item Photos (Optional)</label>
                    <div class="psp-image-upload" onclick="document.getElementById('imageInput').click()">
                        <input type="file" id="imageInput" name="item_images[]" multiple accept="image/*" style="display: none;" onchange="handleImageUpload(event)">
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
                
                <div class="psp-points-info">
                    Your Current Points: <strong><?php echo $stats['point']; ?></strong>
                </div>
                
                <button type="button" class="psp-submit-btn" onclick="submitPickupRequest()" id="submitBtn">
                    Submit Pickup Request
                </button>
                
                <p class="text-danger mt-2" style="font-size: 0.8rem; text-align: center;">
                    * Points are calculated based on item type and weight
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
            <a href="../../html/provider/pEwasteGuide.html">E-Waste Guide</a><br>
            <a href="../../html/provider/pWasteType.html">E-Waste Types</a>
        </div>
        <div>
            <b>My Activity</b><br>
            <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a><br>
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
    // Pass PHP data to JavaScript
    const itemTypesFromDB = <?php echo json_encode($itemTypes); ?>;
    const providerCurrentPoints = <?php echo $stats['point']; ?>;
    
    // pSchedulePickup.js
    
    let itemCount = 0;
    let items = [];
    let uploadedImages = [];
    const providerId = '<?php echo $provider_id; ?>';
    
    // Create a map of item types for easy lookup
    const itemTypeMap = {};
    itemTypesFromDB.forEach(item => {
        itemTypeMap[item.name] = {
            id: item.itemTypeID,
            points: item.recycle_points
        };
    });
    
    // Get unique type names for dropdown
    const eWasteTypes = itemTypesFromDB.map(item => item.name);
    
    // Brand list (you might want to move this to database too)
    const brands = [
        'Apple', 'Samsung', 'Dell', 'HP', 'Lenovo', 'Asus', 'Acer', 
        'Microsoft', 'Sony', 'LG', 'Panasonic', 'Canon', 'Epson', 'Other'
    ];
    
    document.addEventListener('DOMContentLoaded', function() {
        // Set minimum date for pickup (tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('preferredDate').min = tomorrow.toISOString().split('T')[0];
        
        // Add first item by default
        addNewItem();
        
        // Initial summary update
        updateSummary();
    });
    
    function addNewItem() {
        itemCount++;
        const itemId = `item_${itemCount}`;
        
        // Generate options HTML from database item types
        const typeOptions = eWasteTypes.map(type => 
            `<option value="${type}">${type}</option>`
        ).join('');
        
        const brandOptions = brands.map(brand => 
            `<option value="${brand}">${brand}</option>`
        ).join('');
        
        const itemHtml = `
            <div class="psp-item-card" id="${itemId}" data-item-index="${itemCount}">
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
                        <label>Type *</label>
                        <select class="item-type" onchange="updateSummary()" required>
                            <option value="">Select Type</option>
                            ${typeOptions}
                        </select>
                    </div>
                    <div class="psp-item-field">
                        <label>Brand</label>
                        <select class="item-brand" onchange="updateSummary()">
                            <option value="">Select Brand</option>
                            ${brandOptions}
                        </select>
                    </div>
                    <div class="psp-item-field">
                        <label>Model</label>
                        <input type="text" class="item-model" placeholder="e.g., iPhone 12" onchange="updateSummary()">
                    </div>
                    <div class="psp-item-field">
                        <label>Quantity *</label>
                        <input type="number" class="item-quantity" min="1" value="1" onchange="updateSummary()" required>
                    </div>
                    <div class="psp-item-field">
                        <label>Weight (kg) *</label>
                        <input type="number" class="item-weight" step="0.1" min="0.1" placeholder="0.0" onchange="updateSummary()" required>
                    </div>
                    <div class="psp-item-field">
                        <label>Dimensions (cm)</label>
                        <input type="text" class="item-dimensions" placeholder="LxWxH" onchange="updateSummary()">
                    </div>
                    <div class="psp-item-field">
                        <label>Condition</label>
                        <select class="item-condition" onchange="updateSummary()">
                            <option value="Working">Working</option>
                            <option value="Not Working">Not Working</option>
                            <option value="For Parts">For Parts</option>
                        </select>
                    </div>
                    <div class="psp-item-field full-width">
                        <label>Description</label>
                        <input type="text" class="item-description" placeholder="Brief description" onchange="updateSummary()">
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', itemHtml);
        
        // Store item reference
        items.push({
            id: itemId,
            index: itemCount
        });
        
        updateSummary();
    }
    
    function removeItem(itemId) {
        if (confirm('Are you sure you want to remove this item?')) {
            const itemElement = document.getElementById(itemId);
            if (itemElement) {
                itemElement.remove();
                itemCount--;
                
                // Remove from items array
                items = items.filter(item => item.id !== itemId);
                
                // Renumber remaining items
                renumberItems();
                updateSummary();
            }
        }
    }
    
    function renumberItems() {
        const itemCards = document.querySelectorAll('.psp-item-card');
        itemCards.forEach((card, index) => {
            const title = card.querySelector('.psp-item-title');
            if (title) {
                title.textContent = `Item #${index + 1}`;
                card.dataset.itemIndex = index + 1;
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
                    data: e.target.result.split(',')[1], // Remove base64 header
                    file: file,
                    name: file.name,
                    type: file.type
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
        
        itemCards.forEach(card => {
            const type = card.querySelector('.item-type')?.value || '';
            const quantity = parseInt(card.querySelector('.item-quantity')?.value) || 1;
            const weight = parseFloat(card.querySelector('.item-weight')?.value) || 0;
            
            totalItems += quantity;
            totalWeight += weight * quantity;
            
            estimatedPoints += itemRecyclePoints;
        });
        
        // Update summary
        document.getElementById('summaryItemsCount').textContent = totalItems + ' item' + (totalItems !== 1 ? 's' : '');
        document.getElementById('summaryTotalWeight').textContent = totalWeight.toFixed(1) + ' kg';
        document.getElementById('estimatedPoints').textContent = estimatedPoints;
        
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
            const timeFormatted = time.substring(0, 5); // Remove seconds
            document.getElementById('summaryTime').textContent = timeFormatted;
        }
    }
    
    function submitPickupRequest() {
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Disable submit button
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        // Collect form data
        const formData = new FormData();
        
        // Add provider info
        formData.append('providerID', providerId);
        formData.append('pickupAddress', document.getElementById('pickupAddress').value);
        formData.append('pickupState', document.getElementById('pickupState').value);
        formData.append('pickupPostcode', document.getElementById('pickupPostcode').value);
        
        // Combine date and time
        const preferredDateTime = document.getElementById('preferredDate').value + ' ' + document.getElementById('preferredTime').value;
        formData.append('preferredDateTime', preferredDateTime);
        
        formData.append('specialInstructions', document.getElementById('specialInstructions').value);
        
        // Add items as JSON with itemTypeID
        const items = collectItemData();
        formData.append('items', JSON.stringify(items));
        
        // Add images
        uploadedImages.forEach((image, index) => {
            // Convert base64 back to blob
            const byteCharacters = atob(image.data);
            const byteNumbers = new Array(byteCharacters.length);
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            const blob = new Blob([byteArray], {type: image.type || 'image/jpeg'});
            formData.append('item_images[]', blob, image.name || `image_${index}.jpg`);
        });
        
        // Send AJAX request
        fetch('process_schedule_pickup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success modal
                document.getElementById('requestIdDisplay').textContent = data.requestID;
                document.getElementById('successModal').style.display = 'flex';
                
                // Clear form or reset as needed
                console.log('Pickup request submitted successfully:', data);
            } else {
                alert('Error: ' + data.message);
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
        const items = [];
        const itemCards = document.querySelectorAll('.psp-item-card');
        
        itemCards.forEach(card => {
            const typeName = card.querySelector('.item-type')?.value || '';
            const itemTypeID = itemTypeMap[typeName]?.id || null;
            
            items.push({
                itemTypeID: itemTypeID,
                typeName: typeName,
                brand: card.querySelector('.item-brand')?.value || '',
                model: card.querySelector('.item-model')?.value || '',
                quantity: parseInt(card.querySelector('.item-quantity')?.value) || 1,
                weight: parseFloat(card.querySelector('.item-weight')?.value) || 0,
                dimensions: card.querySelector('.item-dimensions')?.value || '',
                condition: card.querySelector('.item-condition')?.value || 'Working',
                description: card.querySelector('.item-description')?.value || '',
                status: 'Pending'
            });
        });
        
        return items;
    }
    
    function validateForm() {
        // Validate address
        if (!document.getElementById('pickupAddress').value.trim()) {
            alert('Please enter pickup address');
            return false;
        }
        
        if (!document.getElementById('pickupState').value) {
            alert('Please select state');
            return false;
        }
        
        if (!document.getElementById('pickupPostcode').value.trim()) {
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
        let allItemsValid = true;
        
        itemCards.forEach((card, index) => {
            const type = card.querySelector('.item-type')?.value;
            const quantity = parseInt(card.querySelector('.item-quantity')?.value);
            const weight = parseFloat(card.querySelector('.item-weight')?.value);
            
            if (!type) {
                alert(`Item #${index + 1}: Please select item type`);
                allItemsValid = false;
                return;
            }
            
            if (!quantity || quantity < 1) {
                alert(`Item #${index + 1}: Please enter valid quantity`);
                allItemsValid = false;
                return;
            }
            
            if (!weight || weight <= 0) {
                alert(`Item #${index + 1}: Please enter valid weight`);
                allItemsValid = false;
                return;
            }
            
            if (type && weight > 0) {
                hasValidItem = true;
            }
        });
        
        if (!allItemsValid) {
            return false;
        }
        
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
    
    // Dark mode support
    function updateTheme() {
        // Update any theme-specific elements
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