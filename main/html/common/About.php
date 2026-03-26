<?php
session_start();
include("../../php/dbConn.php");

// Make sure user is logged in
if (!isset($_SESSION['userType'])) {
    header("Location: ../../../signIn.php");
    exit();
}

$userType = $_SESSION['userType'];

// Set homepage based on logged-in user type
switch ($userType) {
    case 'admin':
        $homePage = "../../html/admin/aHome.php";
        break;

    case 'collector':
        $homePage = "../../html/collector/cHome.php";
        break;

    case 'provider':
        $homePage = "../../html/provider/pHome.php";
        break;

    default:
        session_unset();
        session_destroy();
        header("Location: ../../../signIn.php");
        exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        .about-hero {
            text-align: center;
            padding: 3rem 1rem;
            background-color: var(--sec-bg-color);
            border-radius: 16px;
            margin-bottom: 3rem;
        }

        .about-hero h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .about-hero p {
            font-size: 1.2rem;
            color: var(--text-color);
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .story-section {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin-bottom: 4rem;
            align-items: center;
        }

        .story-item {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
            margin-bottom: 2rem;
        }

        .story-text {
            flex: 1;
        }

        .story-text h2 {
            font-size: 2rem;
            font-weight: 600;
            color: var(--MainBlue);
            margin-bottom: 1.5rem;
        }

        .story-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-color);
        }

        .story-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .story-image img {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 8px 20px var(--shadow-color);
        }

        .about-mission-vision {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            margin: 3rem 0;
        }

        .about-card {
            background-color: var(--sec-bg-color);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px var(--shadow-color);
            text-align: center;
        }

        .about-card h3 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--MainBlue);
            margin-bottom: 1rem;
        }

        .about-card p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: var(--text-color);
        }

        .about-tagline {
            text-align: center;
            margin: 3rem 0 1rem;
            padding: 2rem;
            background-color: var(--sec-bg-color);
            border-radius: 16px;
        }

        .about-tagline p {
            font-size: 1.3rem;
            font-style: italic;
            color: var(--MainBlue);
        }

        @media (min-width: 760px) {
            .story-item {
                flex-direction: row;
                align-items: center;
                gap: 3rem;
            }

            .story-item:nth-child(even) {
                flex-direction: row-reverse;
            }

            .about-mission-vision {
                flex-direction: row;
            }

            .about-card {
                flex: 1;
            }

            .about-hero h1 {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <header>
        <section class="c-logo-section">
            <a href="<?php echo $homePage; ?>" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

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

                    <?php if ($userType === 'admin'): ?>
                        <a href="../../html/admin/aHome.php">Home</a>
                        <a href="../../html/admin/aRequests.php">Requests</a>
                        <a href="../../html/admin/aJobs.php">Jobs</a>
                        <a href="../../html/admin/aIssue.php">Issue</a>
                        <a href="../../html/admin/aOperations.php">Operations</a>
                        <a href="../../html/admin/aReport.php">Report</a>

                    <?php elseif ($userType === 'collector'): ?>
                        <a href="../../html/collector/cHome.php">Home</a>
                        <a href="../../html/collector/cMyJobs.php">My Jobs</a>
                        <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a>
                        <a href="../../html/collector/cCompletedJobs.php">History</a>
                        <a href="../../html/common/About.php">About</a>

                    <?php elseif ($userType === 'provider'): ?>
                        <a href="../../html/provider/pHome.php">Home</a>
                        <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                        <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                        <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                        <a href="../../html/common/About.php">About</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <nav class="c-navbar-desktop">
            <?php if ($userType === 'admin'): ?>
                <a href="../../html/admin/aHome.php">Home</a>
                <a href="../../html/admin/aRequests.php">Requests</a>
                <a href="../../html/admin/aJobs.php">Jobs</a>
                <a href="../../html/admin/aIssue.php">Issue</a>
                <a href="../../html/admin/aOperations.php">Operations</a>
                <a href="../../html/admin/aReport.php">Report</a>

            <?php elseif ($userType === 'collector'): ?>
                <a href="../../html/collector/cHome.php">Home</a>
                <a href="../../html/collector/cMyJobs.php">My Jobs</a>
                <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a>
                <a href="../../html/collector/cCompletedJobs.php">History</a>
                <a href="../../html/common/About.php">About</a>

            <?php elseif ($userType === 'provider'): ?>
                <a href="../../html/provider/pHome.php">Home</a>
                <a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a>
                <a href="../../html/provider/pMainPickup.php">My Pickup</a>
                <a href="../../html/provider/pEwasteGuide.php">E-waste Guide</a>
                <a href="../../html/common/About.php">About</a>
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

    <main>
        <section class="about-hero">
            <h1>About Us</h1>
            <p>Discover how we're transforming electronic waste recycling for a sustainable future.</p>
        </section>

        <section class="story-section">
            <div class="story-item">
                <div class="story-text">
                    <h2>Discover Our Story</h2>
                    <p>
                        At AfterVolt, we are dedicated to transforming how electronic waste is managed in our communities.
                        Built by a team of ICT students from Asia Pacific University, our platform represents a fresh approach
                        to e-waste recycling—one that prioritizes accessibility, transparency, and user engagement.
                    </p>
                </div>
                <div class="story-image">
                    <img src="../../assets/images/about-story-1.jpg" alt="Team working on e-waste solution">
                </div>
            </div>

            <div class="story-item">
                <div class="story-text">
                    <p>
                        Our commitment goes beyond just building a system. We believe that technology can bridge the gap
                        between people who want to recycle responsibly and the infrastructure that makes it possible.
                    </p>
                </div>
                <div class="story-image">
                    <img src="../../assets/images/about-story-2.jpg" alt="AfterVolt platform interface">
                </div>
            </div>

            <div class="story-item">
                <div class="story-text">
                    <p>
                        We're not just developers; we're advocates for environmental awareness. Join us in this journey
                        toward a more sustainable future for Malaysia.
                    </p>
                </div>
                <div class="story-image">
                    <img src="../../assets/images/about-story-3.jpg" alt="Circular economy concept">
                </div>
            </div>
        </section>

        <section class="about-mission-vision">
            <div class="about-card">
                <h3>Our Vision</h3>
                <p>
                    We strive to create a cleaner and healthier planet by responsibly managing electronic waste.
                </p>
            </div>

            <div class="about-card">
                <h3>Our Mission</h3>
                <p>
                    To be your one-stop solution for electronic waste recycling.
                </p>
            </div>
        </section>

        <div class="about-tagline">
            <p>AfterVolt — <em>Recycle responsibly, track transparently, earn rewards.</em></p>
        </div>
    </main>

    <hr>

    <footer>
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

        <section class="c-footer-links-section">
            <?php if ($userType === 'admin'): ?>
                <div>
                    <b>Management</b><br>
                    <a href="../../html/admin/aRequests.php">Collection Requests</a><br>
                    <a href="../../html/admin/aJobs.php">Collection Jobs</a><br>
                    <a href="../../html/admin/aIssue.php">Issue</a>
                </div>
                <div>
                    <b>System Operation</b><br>
                    <a href="../../html/admin/aProviders.php">Providers</a><br>
                    <a href="../../html/admin/aCollectors.php">Collectors</a><br>
                    <a href="../../html/admin/aVehicles.php">Vehicles</a><br>
                    <a href="../../html/admin/aCentres.php">Collection Centres</a><br>
                    <a href="../../html/admin/aItemProcessing.php">Item Processing</a>
                </div>
                <div>
                    <b>Proxy</b><br>
                    <a href="../../html/common/Profile.php">Edit Profile</a><br>
                    <a href="../../html/common/Setting.php">Setting</a>
                </div>

            <?php elseif ($userType === 'collector'): ?>
                <div>
                    <b>My Jobs</b><br>
                    <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
                    <a href="../../html/collector/cInProgress.php">In Progress</a><br>
                    <a href="../../html/collector/cCompletedJobs.php">Completed Jobs</a>
                </div>
                <div>
                    <b>Proxy</b><br>
                    <a href="../../html/common/About.php">About</a><br>
                    <a href="../../html/common/Profile.php">Edit Profile</a><br>
                    <a href="../../html/common/Setting.php">Setting</a>
                </div>

            <?php elseif ($userType === 'provider'): ?>
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
            <?php endif; ?>
        </section>
    </footer>

    <script src="../../javascript/mainScript.js"></script>
</body>
</html>