<!-- FOR COMMON PAGES ONLY -->
<?php
include("../../php/dbConn.php");

// // determine user type 
// include("../../php/sessionCheck.php");

$userType = 'provider'; // for testing only, will be replaced by session variable
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Common Page - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    
    <link rel="stylesheet" href="../../style/style.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14../..32,100../..900;1,14../..32,100../..900&display=swap" rel="stylesheet">
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <!-- Logo + Name & Navbar -->
    <header>
        <!-- Logo + Name -->
        <section class="c-logo-section">
            <a href="<?php echo $homePage; ?>" class="c-logo-link">
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

                    <?php if ($userType === 'admin'): ?>
                        <!-- Admin Mobile Menu -->
                        <a href="../../html/admin/aHome.html">Home</a>
                        <a href="../../html/admin/aRequests.html">Requests</a>
                        <a href="../../html/admin/aJobs.html">Jobs</a>
                        <a href="../../html/admin/aIssue.html">Issue</a>
                        <a href="../../html/admin/aOperations.html">Operations</a>
                        <a href="../../html/admin/aReport.html">Report</a>
                    
                    <?php elseif ($userType === 'collector'): ?>
                        <!-- Collector Mobile Menu -->
                        <a href="../../html/collector/cHome.php">Home</a>
                        <a href="../../html/collector/cMyJobs.html">My Jobs</a>
                        <a href="../../html/collector/cInProgress.html">Ongoing Jobs</a>
                        <a href="../../html/collector/cCompletedJobs.html">History</a>
                        <a href="../../html/common/About.html">About</a>
                    
                    <?php elseif ($userType === 'provider'): ?>
                        <!-- Provider Mobile Menu -->
                        <a href="../../html/provider/pHome.php">Home</a>
                        <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                        <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                        <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                        <a href="../../html/common/About.html">About</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <?php if ($userType === 'admin'): ?>
                <!-- Admin Desktop Menu -->
                <a href="../../html/admin/aHome.html">Home</a>
                <a href="../../html/admin/aRequests.html">Requests</a>
                <a href="../../html/admin/aJobs.html">Jobs</a>
                <a href="../../html/admin/aIssue.html">Issue</a>
                <a href="../../html/admin/aOperations.html">Operations</a>
                <a href="../../html/admin/aReport.html">Report</a>
            
            <?php elseif ($userType === 'collector'): ?>
                <!-- Collector Desktop Menu -->
                <a href="../../html/collector/cHome.php">Home</a>
                <a href="../../html/collector/cMyJobs.html">My Jobs</a>
                <a href="../../html/collector/cInProgress.html">Ongoing Jobs</a>
                <a href="../../html/collector/cCompletedJobs.html">History</a>
                <a href="../../html/common/About.html">About</a>
            
            <?php elseif ($userType === 'provider'): ?>
                <!-- Provider Desktop Menu -->
                <a href="../../html/provider/pHome.php">Home</a>
                <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                <a href="../../html/common/About.html">About</a>
            <?php endif; ?>
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
        
    </main>

    <hr>
    
    <!-- Footer -->
    <footer>
        <!-- Column 1 -->
        <section class="c-footer-info-section">
            <a href="<?php echo $homePage; ?>">
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
        
        <!-- Column 2 - Dynamic Footer Links Based on User Type -->
        <section class="c-footer-links-section">
            <?php if ($userType === 'admin'): ?>
                <!-- Admin Footer Links -->
                <div>
                    <b>Management</b><br>
                    <a href="../../html/admin/aRequests.html">Collection Requests</a><br>
                    <a href="../../html/admin/aJobs.html">Collection Jobs</a><br>
                    <a href="../../html/admin/aIssue.html">Issue</a>
                </div>
                <div>
                    <b>System Operation</b><br>
                    <a href="../../html/admin/aProviders.html">Providers</a><br>
                    <a href="../../html/admin/aCollectors.html">Collectors</a><br>
                    <a href="../../html/admin/aVehicles.html">Vehicles</a><br>
                    <a href="../../html/admin/aCentres.html">Collection Centres</a><br>
                    <a href="../../html/admin/aItemProcessing.html">Item Processing</a>
                </div>
                <div>
                    <b>Proxy</b><br>
                    <a href="../../html/common/Profile.html">Edit Profile</a><br>
                    <a href="../../html/common/Setting.html">Setting</a>
                </div>
            
            <?php elseif ($userType === 'collector'): ?>
                <!-- Collector Footer Links -->
                <div>
                    <b>My Jobs</b><br>
                    <a href="../../html/collector/cMyJobs.html">My Jobs</a><br>
                    <a href="../../html/collector/cInProgress.html">In Progress</a><br>
                    <a href="../../html/collector/cCompletedJobs.html">Completed Jobs</a>
                </div>
                <div>
                    <b>Support</b><br>
                    <a href="../../html/collector/cReportIssues.html">Report Issue</a>
                </div>
                <div>
                    <b>Proxy</b><br>
                    <a href="../../html/common/About.html">About</a><br>
                    <a href="../../html/common/Profile.html">Edit Profile</a><br>
                    <a href="../../html/common/Setting.html">Setting</a>
                </div>
            
            <?php elseif ($userType === 'provider'): ?>
                <!-- Provider Footer Links -->
                <div>
                    <b>Recycling</b><br>
                    <a href="../../html/provider/pEwasteGuide.php">E-Waste Guide</a><br>
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
            <?php endif; ?>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
</body>
</html>