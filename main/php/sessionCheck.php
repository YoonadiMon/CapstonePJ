<?php
include("dbConn.php");
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/*
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
        <title>ReLeaf - Access Denied</title>
        <link rel="icon" type="image/png" href="../../assets/images/Logo.png">
        <link rel="stylesheet" href="../../style/style.css">
        <style>
            .notification {
                top: 0;
                left: 0;
                align-items: center;
                justify-content: center;
                position: fixed;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.85);
                display: flex;
                z-index: 9999;
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .notification-card {
                background: var(--bg-color);
                border-radius: 16px;
                padding: 3rem;
                max-width: 500px;
                width: 90%;
                text-align: center;
                box-shadow: 0 8px 32px var(--shadow-color);
                animation: slideUp 0.4s ease;
            }

            .dark-mode .notification-card {
                border: 1px solid var(--White);
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .notification-icon img {
                max-width: 50%;
                width: 80px;
                height: auto;
                object-fit: contain;
            }
            
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-20px); }
            }
            
            .notification-title {
                font-size: 2rem;
                font-weight: 700;
                color: red;
                margin-bottom: 1rem;
            }
            
            .notification-message {
                font-size: 1.1rem;
                color: var(--text-color);
                margin-bottom: 2rem;
                line-height: 1.6;
            }
            
            .notification-countdown {
                font-size: 0.9rem;
                color: var(--text-color);
                margin-top: 1rem;
            }
            
            .progress-bar {
                width: 100%;
                height: 4px;
                background: var(--BlueGray);
                border-radius: 2px;
                overflow: hidden;
                margin-top: 2rem;
            }
            
            .dark-mode .progress-bar {
                background: var(--Gray);
            }   

            .progress-fill {
                height: 100%;
                background: var(--MainBlue);
                width: 100%;
                animation: progressShrink 3s linear;
            }
            
            @keyframes progressShrink {
                from { width: 100%; }
                to { width: 0%; }
            }

            @media (max-width: 480px) {
                .notification-card {
                    padding: 1.5rem;
                }
                .notification-title {
                    font-size: 1.5rem;
                }
                .notification-message {
                    font-size: 0.95rem;
                }
                .notification-icon img {
                    width: 60px;
                }
            }
        </style>
    </head>
    <body>
        <div class="notification">
            <div class="notification-card">
                <div class="notification-icon"><img src="../../assets/images/banned-icon-red.svg" alt=""></div>
                <h1 class="notification-title">Authentication Required</h1>
                <p class="notification-message">
                    Redirecting you to the sign ip page...
                </p>
                <a href="../signIn.php" class="c-btn c-btn-primary">
                    Sign In
                </a>
                <p class="notification-countdown">Redirecting in <span id="countdown">3</span> seconds</p>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>
        </div>
        <script src="../../javascript/mainScript.js"></script>>
        <script>
            let timeLeft = 3;
            const countdownEl = document.getElementById("countdown");
            
            const timer = setInterval(() => {
                timeLeft--;
                countdownEl.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    window.location.href = "../signIn.php";
                }
            }, 1000);
        </script>
    </body>
    </html>';
    exit();
}
*/
// Get user type from session
$userType = isset($_SESSION['userType']) ? $_SESSION['userType'] : 'provider';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

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
        header("Location: ../signIn.php");
        exit();
}
?>