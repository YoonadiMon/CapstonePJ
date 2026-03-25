<?php
include("dbConn.php");
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    // Store the attempted page URL
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Show notification and redirect
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AfterVolt - Access Denied</title>
        <link rel="icon" type="image/png" href="../../assets/images/Logo.png">
        <link rel="stylesheet" href="../../style/style.css">
        <link rel="stylesheet" href="../../style/notification.css">
    </head>
    <body>
        <div class="notification">
            <div class="notification-card">
                <div class="notification-icon"><img src="../../assets/images/banned-icon-red.svg" alt=""></div>
                <h1 class="notification-title">Authentication Required</h1>
                <p class="notification-message">
                    Redirecting you to the sign in page...
                </p>
                <a href="../../../signIn.php" class="c-btn c-btn-primary">
                    Sign In
                </a>
                <p class="notification-countdown">Redirecting in <span id="countdown">3</span> seconds</p>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>
        </div>
        <script src="../../javascript/mainScript.js"></script>
        <script>
            let timeLeft = 3;
            const countdownEl = document.getElementById("countdown");
            
            const timer = setInterval(() => {
                timeLeft--;
                countdownEl.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    window.location.href = "../../../signIn.php";
                }
            }, 1000);
        </script>
    </body>
    </html>';
    exit();
}

// Get user type from session
$userType = isset($_SESSION['userType']) ? $_SESSION['userType'] : 'provider';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Get other user info from session
$userID = $_SESSION['userID'];
$fullName = $_SESSION['fullname'] ?? '';
$email = $_SESSION['email'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$createdAt = $_SESSION['createdAt'] ?? '';
$lastlogin = $_SESSION['lastLogin'] ?? '';

// Check if provider or collector is suspended
$isSuspended = false;

if ($userType === 'provider') {
    $stmt = $conn->prepare("SELECT suspended FROM tblprovider WHERE providerID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($suspended);
    $stmt->fetch();
    $stmt->close();
    if ($suspended == 1) {
        $isSuspended = true;
    }

} elseif ($userType === 'collector') {
    $stmt = $conn->prepare("SELECT status FROM tblcollector WHERE collectorID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($collectorStatus);
    $stmt->fetch();
    $stmt->close();
    if ($collectorStatus === 'suspended') {
        $isSuspended = true;
    }
}

if ($isSuspended) {
    session_unset();
    session_destroy();

    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AfterVolt - Account Suspended</title>
        <link rel="icon" type="image/png" href="../../assets/images/Logo.png">
        <link rel="stylesheet" href="../../style/style.css">
        <link rel="stylesheet" href="../../style/notification.css">
    </head>
    <body>
        <div class="notification">
            <div class="notification-card">
                <div class="notification-icon"><img src="../../assets/images/banned-icon-red.svg" alt=""></div>
                <h1 class="notification-title">Account Suspended</h1>
                <p class="notification-message">
                    Your account has been suspended and you are currently unable to access the AfterVolt platform.
                    This may be due to a violation of our terms of service or a pending review by our administrators.
                </p>
                <p class="notification-contact">
                    If you believe this is a mistake or would like more information,
                    please contact our support team at <strong>support@aftervolt.com</strong>.
                </p>
                <a href="../../../signIn.php" class="c-btn c-btn-primary">
                    Back to Sign In
                </a>
            </div>
        </div>
        <script src="../../javascript/mainScript.js"></script>
    </body>
    </html>';
    exit();
}

// Set home page based on user type
switch ($userType) {
    case 'admin':
        $homePage = '../../html/admin/aHome.php';
        break;
    case 'collector':
        $homePage = '../../html/collector/cHome.php';
        break;
    case 'provider':
        $homePage = '../../html/provider/pHome.php';
        break;
    default:
        // Invalid user type, redirect to login
        header("Location: ../../../signIn.php");
        exit();
}
?>