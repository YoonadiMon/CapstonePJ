<?php
session_start();
include("../../php/dbConn.php");

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../../index.html");
    exit();
}

// Get the actual logged-in user ID from session
$userID = $_SESSION['userID'];
$userType = $_SESSION['userType'];

// Fetch current user data
$userQuery = "SELECT userID, username, fullname, email, phone, userType FROM tblusers WHERE userID = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userID);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) {
    // User not found in database, logout
    session_destroy();
    header("Location: ../../index.html");
    exit();
}

$userData = $userResult->fetch_assoc();

// Fetch additional role-specific data
$roleData = [];
if ($userData['userType'] == 'provider') {
    $providerQuery = "SELECT address, state, postcode, point, suspended FROM tblprovider WHERE providerID = ?";
    $stmt = $conn->prepare($providerQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $providerResult = $stmt->get_result();
    if ($providerResult->num_rows > 0) {
        $roleData = $providerResult->fetch_assoc();
    }
} elseif ($userData['userType'] == 'collector') {
    $collectorQuery = "SELECT licenseNum, status FROM tblcollector WHERE collectorID = ?";
    $stmt = $conn->prepare($collectorQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $collectorResult = $stmt->get_result();
    if ($collectorResult->num_rows > 0) {
        $roleData = $collectorResult->fetch_assoc();
    }
} elseif ($userData['userType'] == 'admin') {
    // Admin might have additional data if needed
    $adminQuery = "SELECT * FROM tbladmin WHERE adminID = ?";
    $stmt = $conn->prepare($adminQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $adminResult = $stmt->get_result();
    if ($adminResult->num_rows > 0) {
        $roleData = $adminResult->fetch_assoc();
    }
}

// Handle profile update
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Check if email already exists for another user
    $emailCheckQuery = "SELECT userID FROM tblusers WHERE email = ? AND userID != ?";
    $emailCheckStmt = $conn->prepare($emailCheckQuery);
    $emailCheckStmt->bind_param("si", $email, $userID);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();
    
    if ($emailCheckResult->num_rows > 0) {
        $errors[] = "Email already in use by another account";
    }
    $emailCheckStmt->close();
    
    // Handle role-specific updates
    if ($userData['userType'] == 'provider') {
        $address = trim($_POST['address']);
        $state = trim($_POST['state']);
        $postcode = trim($_POST['postcode']);
        
        if (empty($address)) $errors[] = "Address is required";
        if (empty($state)) $errors[] = "State is required";
        if (empty($postcode)) $errors[] = "Postcode is required";
    }
    
    if (empty($errors)) {
        // Update main user table
        $updateUserQuery = "UPDATE tblusers SET fullname = ?, email = ?, phone = ? WHERE userID = ?";
        $updateStmt = $conn->prepare($updateUserQuery);
        $updateStmt->bind_param("sssi", $fullname, $email, $phone, $userID);
        
        if ($updateStmt->execute()) {
            // Update role-specific tables
            $roleUpdateSuccess = true;
            
            if ($userData['userType'] == 'provider') {
                $address = trim($_POST['address']);
                $state = trim($_POST['state']);
                $postcode = trim($_POST['postcode']);
                
                $updateProviderQuery = "UPDATE tblprovider SET address = ?, state = ?, postcode = ? WHERE providerID = ?";
                $updateProviderStmt = $conn->prepare($updateProviderQuery);
                $updateProviderStmt->bind_param("sssi", $address, $state, $postcode, $userID);
                $roleUpdateSuccess = $updateProviderStmt->execute();
                $updateProviderStmt->close();
            }
            
            if ($roleUpdateSuccess) {
                $message = "Profile updated successfully!";
                $messageType = "success";
                
                // Refresh user data
                $userData['fullname'] = $fullname;
                $userData['email'] = $email;
                $userData['phone'] = $phone;
                
                if ($userData['userType'] == 'provider') {
                    $roleData['address'] = $address;
                    $roleData['state'] = $state;
                    $roleData['postcode'] = $postcode;
                }
            } else {
                $message = "Error updating role-specific data. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Error updating profile. Please try again.";
            $messageType = "error";
        }
        $updateStmt->close();
    } else {
        $message = implode(", ", $errors);
        $messageType = "error";
    }
}

$stmt->close();
$conn->close();

// Determine home URL based on user type
$homeUrl = "";
switch($userType) {
    case 'admin':
        $homeUrl = "../../html/admin/aHome.php";
        break;
    case 'collector':
        $homeUrl = "../../html/collector/cHome.php";
        break;
    case 'provider':
        $homeUrl = "../../html/provider/pHome.php";
        break;
    default:
        $homeUrl = "../../index.html";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        /* Profile Page Styles */
        .profile-page-wrapper {
            max-width: 820px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 1.25rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--Gray);
            text-decoration: none;
            margin-bottom: 0.75rem;
            padding: 5px 10px 5px 8px;
            border-radius: 8px;
            border: 1.5px solid var(--LowMainBlue);
            background: var(--bg-color);
            transition: color 0.15s, background 0.15s, border-color 0.15s;
        }
        .back-btn:hover {
            color: var(--MainBlue);
            background: var(--LightBlue);
            border-color: var(--MainBlue);
        }
        .dark-mode .back-btn:hover {
            background: var(--DarkerBlue);
            color: var(--LightBlue);
        }
        .back-btn svg { flex-shrink: 0; }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: var(--text-color);
        }
        .page-header p {
            font-size: 0.85rem;
            color: var(--Gray);
        }

        /* Profile Card */
        .profile-card {
            border: 1px solid var(--LowMainBlue);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px var(--shadow-color);
            background: var(--bg-color);
        }

        .profile-banner {
            height: 84px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }
        .dark-mode .profile-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
        }

        .profile-top {
            padding: 0 1.5rem 1.25rem;
            display: flex;
            align-items: flex-end;
        }

        .avatar-wrap {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            margin-top: -34px;
        }

        .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
            background: #93c5fd;
        }
        .dark-mode .avatar {
            background: #1e3a8a;
        }

        .avatar-name-block { padding-bottom: 4px; }
        .full-name {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 2px;
            color: var(--text-color);
        }
        .user-email {
            font-size: 0.78rem;
            color: var(--Gray);
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            margin-top: 6px;
            border: 1.5px solid;
        }
        .role-admin { 
            background: hsla(237,52%,93%,1); 
            color: hsl(233, 85%, 63%); 
            border-color: hsl(237,40%,75%); 
        }
        .role-collector { 
            background: hsla(130,50%,92%,1); 
            color: #2e7d32;          
            border-color: hsl(130,40%,70%); 
        }
        .role-provider { 
            background: hsla(260,50%,93%,1); 
            color: #3d75e4;          
            border-color: hsl(260,40%,75%); 
        }
        .dark-mode .role-admin { 
            background: hsla(237,52%,20%,0.6); 
            color: hsl(237,80%,80%); 
            border-color: hsl(237,40%,45%); 
        }
        .dark-mode .role-collector { 
            background: hsla(130,50%,15%,0.6); 
            color: hsl(130,60%,70%); 
            border-color: hsl(130,40%,35%); 
        }
        .dark-mode .role-provider { 
            background: hsla(260,50%,20%,0.6); 
            color: hsl(260,80%,80%); 
            border-color: hsl(260,40%,45%); 
        }

        .locked-note {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.68rem;
            color: var(--Gray);
            margin-top: 5px;
            opacity: 0.75;
        }

        /* Divider */
        .card-divider {
            border: none;
            border-top: 1px solid var(--LowMainBlue);
            margin: 0;
        }

        /* Form Sections */
        .form-section { padding: 1.2rem 1.5rem; }
        .form-section + .form-section { border-top: 1px solid var(--LowMainBlue); }

        .section-title {
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--MainBlue);
        }
        .dark-mode .section-title {
            color: var(--LightBlue);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 12px;
        }
        .form-row:last-child { margin-bottom: 0; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group.full { grid-column: 1 / -1; }

        .form-group label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--Gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"] {
            padding: 0.5rem 0.7rem;
            border: 1.5px solid var(--LowMainBlue);
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .form-group input:focus {
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 3px var(--LowMainBlue);
        }
        .form-group input[readonly] {
            background: var(--sec-bg-color);
            color: var(--Gray);
            cursor: not-allowed;
            border-color: transparent;
        }

        /* Form Footer */
        .form-footer {
            padding: 0.9rem 1.5rem;
            border-top: 1px solid var(--LowMainBlue);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            background: var(--sec-bg-color);
        }

        .btn-cancel {
            background: var(--DarkerMainBlue);
            color: white;
            border: none;
            border-radius: 24px;
            padding: 0.4rem 1.1rem;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-cancel:hover { background: var(--MainBlue); }

        .btn-save {
            background: var(--MainBlue);
            color: white;
            border: none;
            border-radius: 24px;
            padding: 0.4rem 1.1rem;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-save:hover { background: var(--DarkerMainBlue); }

        /* Toast */
        .c-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }
        
        .c-toast.success {
            background: #10b981;
            color: white;
        }
        
        .c-toast.error {
            background: #ef4444;
            color: white;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .profile-page-wrapper { padding: 0 1rem; }
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <!-- Logo + Name & Navbar -->
    <header>
        <section class="c-logo-section">
            <a href="<?php echo $homeUrl; ?>" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <!-- Mobile Nav -->
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
                    <?php if ($userType == 'provider'): ?>
                        <a href="../../html/provider/pHome.php">Home</a>
                        <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                        <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                        <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                        <a href="../../html/common/About.html">About</a>
                    <?php elseif ($userType == 'collector'): ?>
                        <a href="../../html/collector/cHome.php">Home</a>
                        <a href="../../html/collector/cMyJobs.php">My Jobs</a>
                        <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a>
                        <a href="../../html/collector/cCompletedJobs.php">History</a>
                        <a href="../../html/common/About.html">About</a>
                    <?php elseif ($userType == 'admin'): ?>
                        <a href="../../html/admin/aHome.php">Home</a>
                        <a href="../../html/admin/aRequests.php">Requests</a>
                        <a href="../../html/admin/aJobs.php">Jobs</a>
                        <a href="../../html/admin/aIssue.php">Issue</a>
                        <a href="../../html/admin/aOperations.php">Operations</a>
                        <a href="../../html/admin/aReport.php">Report</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Desktop Nav -->
        <nav class="c-navbar-desktop">
            <?php if ($userType == 'provider'): ?>
                <a href="../../html/provider/pHome.php">Home</a>
                <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                <a href="../../html/common/About.html">About</a>
            <?php elseif ($userType == 'collector'): ?>
                <a href="../../html/collector/cHome.php">Home</a>
                <a href="../../html/collector/cMyJobs.php">My Jobs</a>
                <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a>
                <a href="../../html/collector/cCompletedJobs.php">History</a>
                <a href="../../html/common/About.html">About</a>
            <?php elseif ($userType == 'admin'): ?>
                <a href="../../html/admin/aHome.php">Home</a>
                <a href="../../html/admin/aRequests.php">Requests</a>
                <a href="../../html/admin/aJobs.php">Jobs</a>
                <a href="../../html/admin/aIssue.php">Issue</a>
                <a href="../../html/admin/aOperations.php">Operations</a>
                <a href="../../html/admin/aReport.php">Report</a>
            <?php endif; ?>
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
        <div class="profile-page-wrapper">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <a href="Setting.php" class="back-btn">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 5l-7 7 7 7"/>
                    </svg>
                    Back to Settings
                </a>
                <h1>Edit Profile</h1>
                <p>Update your profile information</p>
            </div>

            <div class="profile-card">
                <div class="profile-banner"></div>

                <div class="profile-top">
                    <div class="avatar-wrap">
                        <div class="avatar" id="avatarDisplay">
                            <span id="avatarInitials"></span>
                        </div>
                        <div class="avatar-name-block">
                            <div class="full-name"><?php echo htmlspecialchars($userData['fullname']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($userData['email']); ?></div>
                            <div id="roleBadge" class="role-badge role-<?php echo $userType; ?>">
                                <?php 
                                $roleIcon = '';
                                $roleLabel = '';
                                switch($userType) {
                                    case 'admin':
                                        $roleIcon = '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
                                        $roleLabel = 'Admin';
                                        break;
                                    case 'collector':
                                        $roleIcon = '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
                                        $roleLabel = 'Collector';
                                        break;
                                    case 'provider':
                                        $roleIcon = '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
                                        $roleLabel = 'Provider';
                                        break;
                                }
                                echo $roleIcon . ' ' . $roleLabel;
                                ?>
                            </div>
                            <div class="locked-note">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                Role is assigned by the system and cannot be changed
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="card-divider">
                
                <form method="POST" action="" id="profileForm">
                    <div class="form-section">
                        <div class="section-title">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Personal Information
                        </div>
                        <div class="form-row">
                            <div class="form-group full">
                                <label>Full Name</label>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($userData['fullname']); ?>" required />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required />
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($userData['phone']); ?>" required />
                            </div>
                        </div>
                    </div>

                    <?php if ($userType == 'provider'): ?>
                    <div class="form-section">
                        <div class="section-title">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Address Information
                        </div>
                        <div class="form-row">
                            <div class="form-group full">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($roleData['address'] ?? ''); ?>" required />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" value="<?php echo htmlspecialchars($roleData['state'] ?? ''); ?>" required />
                            </div>
                            <div class="form-group">
                                <label>Postcode</label>
                                <input type="text" name="postcode" value="<?php echo htmlspecialchars($roleData['postcode'] ?? ''); ?>" required />
                            </div>
                        </div>
                        <?php if (isset($roleData['point'])): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Recycling Points</label>
                                <input type="text" value="<?php echo htmlspecialchars($roleData['point']); ?>" readonly />
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($userType == 'collector'): ?>
                    <div class="form-section">
                        <div class="section-title">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                            Collector Details
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>License Number</label>
                                <input type="text" value="<?php echo htmlspecialchars($roleData['licenseNum'] ?? ''); ?>" readonly />
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" value="<?php echo htmlspecialchars($roleData['status'] ?? ''); ?>" readonly />
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-footer">
                        <button type="button" class="btn-cancel" onclick="window.location.href='Setting.php'">Cancel</button>
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <hr>

    <!-- Footer -->
    <footer>
        <section class="c-footer-info-section">
            <a href="<?php echo $homeUrl; ?>">
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
            <?php if ($userType == 'provider'): ?>
                <div><b>Quick Links</b><br>
                    <a href="../../html/provider/pHome.php">Home</a><br>
                    <a href="../../html/common/About.html">About</a>
                </div>
                <div><b>Account</b><br>
                    <a href="Profile.php">Edit Profile</a><br>
                    <a href="Setting.php">Settings</a>
                </div>
            <?php elseif ($userType == 'collector'): ?>
                <div><b>My Jobs</b><br>
                    <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
                    <a href="../../html/collector/cInProgress.php">In Progress</a><br>
                    <a href="../../html/collector/cCompletedJobs.php">Completed Jobs</a>
                </div>
                <div><b>Proxy</b><br>
                    <a href="../../html/common/About.html">About</a><br>
                    <a href="Profile.php">Edit Profile</a><br>
                    <a href="Setting.php">Setting</a>
                </div>
            <?php elseif ($userType == 'admin'): ?>
                <div><b>Management</b><br>
                    <a href="../../html/admin/aRequests.php">Collection Requests</a><br>
                    <a href="../../html/admin/aJobs.php">Collection Jobs</a>
                </div>
                <div><b>Proxy</b><br>
                    <a href="Profile.php">Edit Profile</a><br>
                    <a href="Setting.php">Setting</a>
                </div>
            <?php endif; ?>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        // Display toast message if there's a PHP message
        <?php if ($message): ?>
        showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
        <?php endif; ?>
        
        // Set avatar initials
        const fullname = "<?php echo htmlspecialchars($userData['fullname']); ?>";
        const nameParts = fullname.split(' ');
        let initials = '';
        if (nameParts.length >= 2) {
            initials = (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase();
        } else if (nameParts.length === 1) {
            initials = nameParts[0][0].toUpperCase();
        }
        document.getElementById('avatarInitials').textContent = initials;
        
        // Toast function
        function showToast(message, type = 'success') {
            const existingToast = document.querySelector('.c-toast');
            if (existingToast) existingToast.remove();
            
            const t = document.createElement('div');
            t.className = `c-toast ${type}`;
            const icon = type === 'success' ? '✓' : '✕';
            t.innerHTML = `<span style="font-weight:700">${icon}</span> ${message}`;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }
    </script>
</body>
</html>