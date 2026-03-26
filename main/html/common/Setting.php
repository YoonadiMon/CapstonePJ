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
$userType = $_SESSION['userType']; // Get user type from session

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

// Handle password update
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Get current password from database
    $passQuery = "SELECT password FROM tblusers WHERE userID = ?";
    $passStmt = $conn->prepare($passQuery);
    $passStmt->bind_param("i", $userID);
    $passStmt->execute();
    $passResult = $passStmt->get_result();
    $passData = $passResult->fetch_assoc();
    
    // Verify current password using password_verify (since registration uses hashed passwords)
    if (password_verify($currentPassword, $passData['password'])) {
        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 8) {
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password
                $updateQuery = "UPDATE tblusers SET password = ? WHERE userID = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("si", $hashedPassword, $userID);
                
                if ($updateStmt->execute()) {
                    $message = "Password updated successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error updating password. Please try again.";
                    $messageType = "error";
                }
                $updateStmt->close();
            } else {
                $message = "Password must be at least 8 characters long!";
                $messageType = "error";
            }
        } else {
            $message = "New passwords do not match!";
            $messageType = "error";
        }
    } else {
        $message = "Current password is incorrect!";
        $messageType = "error";
    }
    $passStmt->close();
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

    <title>Settings - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        /* Keep your existing styles here */
        main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.2rem;
        }
        .page-subtitle {
            font-size: 0.85rem;
            color: var(--Gray);
        }

        /* ── SETTINGS LAYOUT ── */
        .settings-layout {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        /* ── SIDEBAR ── */
        .settings-sidebar {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 10px var(--shadow-color);
            padding: 6px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            font-size: 0.84rem;
            font-weight: 500;
            color: var(--Gray);
            cursor: pointer;
            border-radius: 9px;
            border-left: none;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }
        .sidebar-item:hover {
            background: var(--sec-bg-color);
            color: var(--text-color);
        }
        .sidebar-item.active {
            background: var(--LightBlue);
            color: var(--DarkerMainBlue);
            font-weight: 600;
        }
        .dark-mode .sidebar-item.active {
            background: var(--DarkerBlue);
            color: var(--LightBlue);
        }
        .sidebar-item svg {
            flex-shrink: 0;
            opacity: 0.6;
        }
        .sidebar-item.active svg {
            opacity: 1;
        }
        .sidebar-item .sidebar-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--MainBlue);
            margin-left: auto;
            opacity: 0;
            transition: opacity 0.15s;
        }
        .sidebar-item.active .sidebar-dot {
            opacity: 1;
        }

        /* ── SIDEBAR DIVIDER ── */
        .sidebar-divider {
            height: 1px;
            background: var(--LowMainBlue);
            margin: 4px 2px;
        }

        /* ── SIDEBAR ACTION BUTTONS ── */
        .sidebar-item.edit-profile {
            color: var(--text-color);
        }
        .sidebar-item.edit-profile:hover {
            background: var(--sec-bg-color);
            color: var(--MainBlue);
        }
        .sidebar-item.logout {
            color: hsl(0, 60%, 52%);
        }
        .sidebar-item.logout:hover {
            background: hsl(0, 80%, 96%);
            color: hsl(0, 65%, 44%);
        }
        .dark-mode .sidebar-item.logout:hover {
            background: hsl(0, 40%, 18%);
            color: hsl(0, 65%, 65%);
        }

        /* ── CARDS ── */
        .tab-panel {
            display: none;
            flex-direction: column;
            gap: 1.2rem;
        }
        .tab-panel.active {
            display: flex;
        }

        .c-card {
            background: var(--bg-color);
            border: 1px solid var(--LowMainBlue);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 10px var(--shadow-color);
        }
        .c-card-header {
            padding: 16px 20px 14px;
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .c-card-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-color);
        }
        .c-card-desc {
            font-size: 0.75rem;
            color: var(--Gray);
            margin-top: 3px;
        }
        .c-card-body {
            padding: 18px 20px;
        }
        .c-card-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--LowMainBlue);
            background: var(--sec-bg-color);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* ── FORM ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }
        .form-row:last-child { margin-bottom: 0; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }

        label {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--Gray);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        
        /* Password input wrapper for eye icon */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-wrapper input {
            flex: 1;
            padding-right: 35px;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            cursor: pointer;
            user-select: none;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        
        .toggle-password:hover {
            opacity: 1;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            padding: 0.5rem 0.75rem;
            border: 1.5px solid var(--LowMainBlue);
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            background: var(--bg-color);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            width: 100%;
        }
        input:focus, select:focus {
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 3px var(--LowMainBlue);
        }

        /* ── TOGGLE ROWS ── */
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 1px solid var(--LowMainBlue);
        }
        .toggle-row:first-child { padding-top: 0; }
        .toggle-row:last-child  { border-bottom: none; padding-bottom: 0; }
        .toggle-label { font-size: 0.88rem; font-weight: 500; color: var(--text-color); }
        .toggle-sub   { font-size: 0.73rem; color: var(--Gray); margin-top: 2px; }

        .c-toggle { position: relative; width: 38px; height: 22px; flex-shrink: 0; }
        .c-toggle input { opacity: 0; width: 0; height: 0; }
        .c-toggle-track {
            position: absolute; inset: 0;
            background: var(--BlueGray);
            border-radius: 22px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .c-toggle-track::after {
            content: '';
            position: absolute;
            left: 3px; top: 3px;
            width: 16px; height: 16px;
            border-radius: 50%;
            background: white;
            transition: transform 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }
        .c-toggle input:checked + .c-toggle-track { background: var(--MainBlue); }
        .c-toggle input:checked + .c-toggle-track::after { transform: translateX(16px); }

        /* ── SELECT ROWS ── */
        .select-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid var(--LowMainBlue);
            gap: 16px;
        }
        .select-row:first-child { padding-top: 0; }
        .select-row:last-child  { border-bottom: none; padding-bottom: 0; }
        .select-row select { max-width: 160px; }

        /* ── BUTTONS ── */
        .c-btn-primary {
            background: var(--MainBlue);
            color: var(--White);
            border-radius: 24px;
            padding: 0.38em 1.2em;
            font-size: 0.82rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .c-btn-primary:hover { background: var(--DarkerMainBlue); }

        .c-btn-cancel {
            background: none;
            color: var(--Gray);
            border: 1.5px solid var(--LowMainBlue);
            border-radius: 24px;
            padding: 0.38em 1.2em;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .c-btn-cancel:hover { background: var(--sec-bg-color); }

        /* ── PASSWORD STRENGTH ── */
        .strength-bar { display: flex; gap: 4px; margin-top: 7px; }
        .strength-seg {
            height: 4px; flex: 1; border-radius: 4px;
            background: var(--LowMainBlue); transition: background 0.3s;
        }
        .strength-seg.weak   { background: hsl(0,65%,55%); }
        .strength-seg.medium { background: hsl(40,90%,52%); }
        .strength-seg.strong { background: hsl(145,50%,45%); }
        .strength-text { font-size: 0.7rem; color: var(--Gray); margin-top: 4px; }

        /* ── STATIC INFO ── */
        .static-info {
            padding: 0.5rem 0.75rem;
            border: 1.5px solid var(--LowMainBlue);
            border-radius: 8px;
            font-size: 0.88rem;
            color: var(--text-color);
            background: var(--sec-bg-color);
        }
        
        /* Toast message styling */
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

        @media (max-width: 768px) {
            .settings-layout { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .settings-sidebar {
                flex-direction: row;
                padding: 5px;
                gap: 4px;
            }
            .sidebar-item {
                flex: 1;
                justify-content: center;
                padding: 9px 10px;
            }
            .sidebar-item .sidebar-dot { display: none; }
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <!-- Logo + Name & Navbar -->
    <header>
        <!-- Logo + Name -->
        <section class="c-logo-section">
            <a href="<?php echo $homeUrl; ?>" class="c-logo-link">
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
                        <a href="../../html/admin/aIssue.html">Issue</a>
                        <a href="../../html/admin/aOperations.html">Operations</a>
                        <a href="../../html/admin/aReport.php">Report</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Menu Links Desktop + Tablet -->
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
                <a href="../../html/admin/aIssue.html">Issue</a>
                <a href="../../html/admin/aOperations.html">Operations</a>
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

        <!-- PAGE HEADER -->
        <div>
            <div class="page-title">Settings</div>
            <div class="page-subtitle">Manage your account security and preferences</div>
        </div>

        <!-- SETTINGS LAYOUT -->
        <div class="settings-layout">

            <!-- SIDEBAR -->
            <aside class="settings-sidebar">
                <div class="sidebar-item active" data-tab="security">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    Security
                    <span class="sidebar-dot"></span>
                </div>
                <div class="sidebar-item" data-tab="appearance">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="5"/>
                        <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                    </svg>
                    Appearance
                    <span class="sidebar-dot"></span>
                </div>

                <div class="sidebar-divider"></div>

                <a href="Profile.php" class="sidebar-item edit-profile">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    Edit Profile
                </a>

                <div class="sidebar-item logout" onclick="handleLogout()">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Log Out
                </div>

            </aside>

            <!-- PANELS -->
            <div>

                <!-- SECURITY -->
                <div id="tab-security" class="tab-panel active">
                    <div class="c-card">
                        <div class="c-card-header">
                            <div class="c-card-title">Change Password</div>
                            <div class="c-card-desc">Update your password regularly to keep your account secure</div>
                        </div>
                        <form method="POST" action="" id="passwordForm">
                            <div class="c-card-body">
                                <div class="form-row">
                                    <div class="form-group full">
                                        <label>Current Password</label>
                                        <div class="password-wrapper">
                                            <input type="password" id="currentPw" name="current_password" placeholder="Enter current password" required />
                                            <span class="toggle-password" onclick="togglePassword('currentPw')">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <div class="password-wrapper">
                                            <input type="password" id="newPw" name="new_password" placeholder="New password" oninput="checkStrength(this.value)" required />
                                            <span class="toggle-password" onclick="togglePassword('newPw')">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="strength-bar">
                                            <div class="strength-seg" id="s1"></div>
                                            <div class="strength-seg" id="s2"></div>
                                            <div class="strength-seg" id="s3"></div>
                                            <div class="strength-seg" id="s4"></div>
                                        </div>
                                        <div class="strength-text" id="strengthText"></div>
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <div class="password-wrapper">
                                            <input type="password" id="confirmPw" name="confirm_password" placeholder="Confirm new password" required />
                                            <span class="toggle-password" onclick="togglePassword('confirmPw')">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="c-card-footer">
                                <button type="button" class="c-btn-cancel" onclick="clearPasswordFields()">Cancel</button>
                                <button type="submit" name="update_password" class="c-btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- APPEARANCE -->
                <div id="tab-appearance" class="tab-panel">
                    <div class="c-card">
                        <div class="c-card-header">
                            <div class="c-card-title">Appearance</div>
                            <div class="c-card-desc">Customise how AfterVolt looks for you</div>
                        </div>
                        <div class="c-card-body">
                            <div class="toggle-row">
                                <div>
                                    <div class="toggle-label">Dark Mode</div>
                                    <div class="toggle-sub">Switch to a darker colour scheme</div>
                                </div>
                                <label class="c-toggle">
                                    <input type="checkbox" id="toggleDark" onchange="toggleDarkMode(this.checked)">
                                    <span class="c-toggle-track"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <!-- Column 1 -->
        <section class="c-footer-info-section">
            <a href="<?php echo $homeUrl; ?>">
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
                <b>Quick Links</b><br>
                <a href="<?php echo $homeUrl; ?>">Home</a><br>
                <a href="../../html/common/About.html">About</a>
            </div>
            <div>
                <b>Account</b><br>
                <a href="Profile.php">Edit Profile</a><br>
                <a href="Setting.php">Settings</a>
            </div>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        // Display toast message if there's a PHP message
        <?php if ($message): ?>
        showToast('<?php echo addslashes($message); ?>', '<?php echo $messageType; ?>');
        <?php endif; ?>
        
        // ── SIDEBAR TABS ──────────────────────────────────────────────────
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', () => {
                if (item.classList.contains('logout') || item.classList.contains('edit-profile')) return;
                
                document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                item.classList.add('active');
                document.getElementById('tab-' + item.dataset.tab).classList.add('active');
            });
        });

        // ── PERSIST SETTINGS ─────────────────────────────────────────────
        function savePref(key, value) {
            localStorage.setItem('pref_' + key, value);
        }
        
        function loadSettings() {
            const dark = localStorage.getItem('pref_darkMode') === 'true';
            if (dark) {
                document.body.classList.add('dark-mode');
                document.getElementById('toggleDark').checked = true;
            }
        }

        // ── PASSWORD STRENGTH ─────────────────────────────────────────────────────
        function checkStrength(val) {
            const segs = ['s1','s2','s3','s4'].map(id => document.getElementById(id));
            const txt  = document.getElementById('strengthText');
            segs.forEach(s => s.className = 'strength-seg');
            if (!val) { txt.textContent = ''; return; }
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            const cls    = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
            const labels = ['', 'Weak', 'Fair', 'Strong', 'Very Strong'];
            for (let i = 0; i < score; i++) segs[i].classList.add(cls);
            txt.textContent = labels[score] || '';
            txt.style.color = cls === 'weak' ? 'hsl(0,65%,52%)' : cls === 'medium' ? 'hsl(40,80%,45%)' : 'hsl(145,50%,40%)';
        }

        // ── TOGGLE PASSWORD VISIBILITY ───────────────────────────────────
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
        }

        // ── CLEAR PASSWORD FIELDS ────────────────────────────────────────
        function clearPasswordFields() {
            document.getElementById('currentPw').value = '';
            document.getElementById('newPw').value = '';
            document.getElementById('confirmPw').value = '';
            checkStrength('');
        }

        // ── DARK MODE ────────────────────────────────────────────────────
        function toggleDarkMode(enabled) {
            document.body.classList.toggle('dark-mode', enabled);
            localStorage.setItem('pref_darkMode', enabled);
        }

        // ── TOAST ────────────────────────────────────────────────────────
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

        // ── FORM VALIDATION ──────────────────────────────────────────────
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPw = document.getElementById('newPw').value;
            const confirmPw = document.getElementById('confirmPw').value;
            
            if (newPw.length < 8) {
                e.preventDefault();
                showToast('Password must be at least 8 characters long!', 'error');
                return false;
            }
            
            if (newPw !== confirmPw) {
                e.preventDefault();
                showToast('New passwords do not match!', 'error');
                return false;
            }
        });

        // ── LOGOUT ───────────────────────────────────────────────────────
        function handleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = '/CapstonePJ/index.html';
            }
        }

        // ── INIT ─────────────────────────────────────────────────────────
        loadSettings();
    </script>
</body>
</html>