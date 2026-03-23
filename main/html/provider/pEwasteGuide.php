<?php
session_start();
include("../../php/dbConn.php");

$centres = [];
$centres_query = "SELECT centreID, name, address, state, postcode, contact, status FROM tblcentre WHERE status = 'Active' ORDER BY name";
$centres_result = $conn->query($centres_query);
if ($centres_result) {
    while ($row = $centres_result->fetch_assoc()) {
        $centres[] = $row;
    }
}

$items_with_points = [];
$items_query = "SELECT name, recycle_points FROM tblitem_type ORDER BY name";
$items_result = $conn->query($items_query);
if ($items_result) {
    while ($row = $items_result->fetch_assoc()) {
        $items_with_points[] = $row;
    }
}

$total_recycled = 0;
$weight_query = "SELECT SUM(weight) as total FROM tblitem WHERE status IN ('Collected', 'Received', 'Processed', 'Recycled')";
$weight_result = $conn->query($weight_query);
if ($weight_result && $row = $weight_result->fetch_assoc()) {
    $total_recycled = round($row['total'] ?? 0, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Waste Guide | AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .guide-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 2rem 0;
            border-bottom: 2px solid var(--BlueGray);
            padding-bottom: 0.5rem;
        }
        .guide-tab {
            padding: 0.8rem 1.5rem;
            background-color: var(--sec-bg-color);
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            color: var(--text-color);
            text-decoration: none;
            border: 1px solid transparent;
        }
        .guide-tab:hover { background-color: var(--LowMainBlue); }
        .guide-tab.active { background-color: var(--MainBlue); color: white; border-bottom: 1px solid var(--MainBlue); }
        .guide-content { display: none; padding: 2rem 0; }
        .guide-content.active { display: block; }
        .find-centre-container { display: flex; flex-direction: column; gap: 2rem; padding: 1rem 0; }
        .search-section { width: 100%; }
        .search-section h2 { font-size: 2rem; font-weight: 600; color: var(--text-color); margin-bottom: 1.5rem; }
        .search-bar { display: flex; width: 100%; max-width: 600px; margin-bottom: 2rem; }
        .search-bar input { flex: 1; padding: 1rem 1.2rem; border: 1px solid var(--BlueGray); border-radius: 50px 0 0 50px; background-color: var(--bg-color); color: var(--text-color); font-size: 1rem; }
        .search-bar button { padding: 1rem 2rem; background-color: var(--MainBlue); color: white; border: none; border-radius: 0 50px 50px 0; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .search-bar button:hover { background-color: var(--DarkerMainBlue); }
        .results-layout { display: flex; flex-direction: column; gap: 2rem; width: 100%; }
        .centre-list { flex: 1; background-color: var(--sec-bg-color); padding: 1.5rem; border-radius: 16px; max-height: 600px; overflow-y: auto; }
        .centre-list h3 { font-size: 1.3rem; font-weight: 600; color: var(--text-color); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--BlueGray); }
        .centre-list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .centre-count { background-color: var(--MainBlue); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; }
        .centre-item { padding: 1rem; border-bottom: 1px solid var(--BlueGray); cursor: pointer; transition: background-color 0.2s; border-radius: 8px; margin-bottom: 0.5rem; }
        .centre-item:hover { background-color: var(--LowMainBlue); }
        .centre-item.active { background-color: var(--MainBlue); color: white; }
        .centre-name { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.3rem; }
        .centre-address { font-size: 0.9rem; margin-bottom: 0.3rem; opacity: 0.8; }
        .centre-distance { font-size: 0.8rem; color: var(--Gray); }
        .centre-details { flex: 1; background-color: var(--sec-bg-color); padding: 1.5rem; border-radius: 16px; }
        .details-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; }
        .details-name { font-size: 1.8rem; font-weight: 600; color: var(--text-color); }
        .details-rating { background-color: var(--MainBlue); color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.9rem; }
        .details-status { color: #4CAF50; font-weight: 500; margin: 0.5rem 0; }
        .details-address { margin: 1rem 0; line-height: 1.6; }
        .details-phone { margin: 1rem 0; font-weight: 500; }
        .details-phone a { color: var(--MainBlue); text-decoration: none; }
        .details-hours { margin: 1.5rem 0; }
        .details-hours h4 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .hours-table { width: 100%; border-collapse: collapse; }
        .hours-table td { padding: 0.3rem 0; }
        .hours-table td:first-child { font-weight: 500; width: 100px; }
        .details-links { margin-top: 1rem; }
        .details-links a { color: var(--MainBlue); text-decoration: none; margin-right: 1rem; }
        @media (min-width: 760px) { .results-layout { flex-direction: row; } .centre-list { max-width: 40%; } .centre-details { max-width: 60%; } }
        .accepted-items-container { padding: 1rem 0; }
        .accepted-items-container h2 { font-size: 2.5rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.5rem; }
        .accepted-items-container h3 { font-size: 1.8rem; font-weight: 600; color: var(--MainBlue); margin: 2rem 0 1rem; }
        .intro-text { font-size: 1.1rem; line-height: 1.6; color: var(--text-color); margin-bottom: 2rem; max-width: 800px; }
        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .item-card { background-color: var(--bg-color); padding: 0.8rem 1rem; border-radius: 12px; display: flex; align-items: center; gap: 0.8rem; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid var(--BlueGray); cursor: pointer; }
        .item-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px var(--shadow-color); }
        .item-card i { font-size: 1.5rem; color: var(--MainBlue); width: 30px; text-align: center; }
        .item-card span { color: var(--text-color); font-size: 0.95rem; flex: 1; }
        .item-points { font-size: 0.8rem; font-weight: 600; color: #4CAF50; background-color: rgba(76, 175, 80, 0.1); padding: 0.2rem 0.5rem; border-radius: 20px; }
        .special-badge { background-color: #ff9800; color: white; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 20px; margin-left: 0.5rem; }
        .item-detail-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .item-detail-content { background-color: var(--sec-bg-color); padding: 2rem; border-radius: 20px; max-width: 400px; width: 90%; position: relative; }
        .close-modal { position: absolute; top: 1rem; right: 1rem; cursor: pointer; font-size: 1.5rem; color: var(--Gray); }
        .category-section { background-color: var(--sec-bg-color); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; }
        .search-items { margin-bottom: 1.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .search-items input { flex: 1; padding: 0.8rem 1rem; border: 1px solid var(--BlueGray); border-radius: 50px; background-color: var(--bg-color); color: var(--text-color); }
        .preparation-container { padding: 1rem 0; }
        .preparation-container h2 { font-size: 2.5rem; font-weight: 700; color: var(--text-color); margin-bottom: 1rem; }
        .preparation-intro { font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem; }
        .steps-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        .step-card { display: flex; flex-direction: column; background-color: var(--sec-bg-color); border-radius: 20px; overflow: hidden; transition: transform 0.2s; }
        .step-card:hover { transform: translateY(-4px); }
        .step-number { width: 40px; height: 40px; background-color: var(--MainBlue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; margin-right: 1rem; }
        .step-header { display: flex; align-items: center; margin-bottom: 0.8rem; }
        .step-title { font-size: 1.2rem; font-weight: 600; color: var(--text-color); }
        .step-content { color: var(--text-color); line-height: 1.5; padding-left: 3rem; }
        .step-icon { margin-right: 0.5rem; color: var(--MainBlue); }
        .checklist-item { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; cursor: pointer; }
        .checklist-item input { width: 18px; height: 18px; cursor: pointer; accent-color: var(--MainBlue); }
        .download-btn { background-color: var(--MainBlue); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; margin-top: 1rem; display: inline-flex; align-items: center; gap: 0.5rem; }
        @media (min-width: 760px) { .steps-grid { grid-template-columns: repeat(2, 1fr); } .step-card { flex-direction: row; padding: 1.5rem; } .step-content { padding-left: 0; } }
        .journey-container { padding: 1rem 0; }
        .journey-container h2 { font-size: 2.5rem; font-weight: 700; color: var(--text-color); margin-bottom: 1rem; }
        .journey-intro { font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem; }
        .journey-stats { display: flex; gap: 1rem; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; }
        .stat-bubble { background: linear-gradient(135deg, var(--MainBlue), var(--DarkerMainBlue)); color: white; padding: 1rem; border-radius: 16px; text-align: center; flex: 1; min-width: 120px; }
        .stat-bubble .number { font-size: 1.8rem; font-weight: 700; }
        .stat-bubble .label { font-size: 0.8rem; opacity: 0.9; }
        .journey-steps { display: flex; flex-direction: column; gap: 2rem; }
        .journey-step { display: flex; flex-direction: column; background-color: var(--sec-bg-color); border-radius: 20px; padding: 1.5rem; transition: transform 0.2s; cursor: pointer; }
        .journey-step:hover { transform: translateX(5px); }
        .journey-step h3 { font-size: 1.3rem; font-weight: 600; color: var(--MainBlue); margin-bottom: 0.8rem; }
        .journey-step p { color: var(--text-color); line-height: 1.6; }
        .journey-list { list-style: none; padding-left: 1rem; }
        .journey-list li { margin: 0.5rem 0; color: var(--text-color); position: relative; padding-left: 1.2rem; }
        .journey-list li:before { content: "•"; color: var(--MainBlue); font-size: 1.2rem; position: absolute; left: 0; }
        .impact-meter { background-color: var(--bg-color); border-radius: 12px; padding: 1rem; margin-top: 1rem; }
        .impact-bar { height: 8px; background-color: var(--BlueGray); border-radius: 4px; overflow: hidden; margin: 0.5rem 0; }
        .impact-fill { width: 0%; height: 100%; background-color: var(--MainBlue); border-radius: 4px; transition: width 1s ease; }
        @media (min-width: 760px) { .journey-step { flex-direction: row; gap: 2rem; align-items: center; } .journey-step-content { flex: 1; } }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <header>
        <section class="c-logo-section">
            <a href="../../html/provider/pHome.php" class="c-logo-link">
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
                        <a href="../../html/common/Setting.html">
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
            <a href="../../html/common/Setting.html">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
    </header>
    <hr>

    <main>
        <div class="guide-tabs">
            <a href="#" class="guide-tab" onclick="switchTab(event, 'find-centre')">📍 Find a Centre</a>
            <a href="#" class="guide-tab active" onclick="switchTab(event, 'accepted-items')">📋 Accepted Items</a>
            <a href="#" class="guide-tab" onclick="switchTab(event, 'preparation')">📝 Preparation Guide</a>
            <a href="#" class="guide-tab" onclick="switchTab(event, 'journey')">🔄 The Journey</a>
        </div>

        <div id="find-centre" class="guide-content">
            <div class="find-centre-container">
                <div class="search-section">
                    <h2>Find a location near you</h2>
                    <div class="search-bar">
                        <input type="text" placeholder="Enter your address or postcode" id="searchInput">
                        <button id="searchBtn">Search</button>
                    </div>
                </div>
                <div class="results-layout">
                    <div class="centre-list">
                        <div class="centre-list-header">
                            <h3>All locations</h3>
                            <span class="centre-count" id="totalCount"><?php echo count($centres); ?></span>
                        </div>
                        <div id="centreList">
                            <?php if (empty($centres)): ?>
                                <div class="centre-item">No centres available</div>
                            <?php else: 
                                foreach ($centres as $index => $centre): ?>
                                <div class="centre-item <?php echo $index === 0 ? 'active' : ''; ?>" data-id="<?php echo $centre['centreID']; ?>" onclick="selectCentre(<?php echo $centre['centreID']; ?>)">
                                    <div class="centre-name"><?php echo htmlspecialchars($centre['name']); ?></div>
                                    <div class="centre-address"><?php echo htmlspecialchars(substr($centre['address'], 0, 50)) . '...'; ?></div>
                                    <div class="centre-distance"><?php echo htmlspecialchars($centre['state']); ?></div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <div class="centre-details" id="centreDetails">
                        <?php if (!empty($centres)): 
                            foreach ($centres as $index => $centre): ?>
                            <div id="detail-<?php echo $centre['centreID']; ?>" class="detail-view" style="<?php echo $index === 0 ? 'display: block;' : 'display: none;'; ?>">
                                <div class="details-header">
                                    <span class="details-name"><?php echo htmlspecialchars($centre['name']); ?></span>
                                    <span class="details-rating">★★★★★</span>
                                </div>
                                <div class="details-status"><?php echo $centre['status'] == 'Active' ? 'Open now' : 'Closed'; ?></div>
                                <div class="details-address">
                                    <?php echo htmlspecialchars($centre['address']); ?><br>
                                    <?php echo htmlspecialchars($centre['postcode'] . ', ' . $centre['state']); ?>
                                </div>
                                <div class="details-phone">
                                    📞 <a href="tel:<?php echo $centre['contact']; ?>"><?php echo $centre['contact']; ?></a>
                                </div>
                                <div class="details-links">
                                    <a href="#">Get directions</a>
                                </div>
                                <div class="details-hours">
                                    <h4>Opening Hours</h4>
                                    <table class="hours-table">
                                        <tr><td>Monday:</td><td>09:00 - 18:00</td></tr>
                                        <tr><td>Tuesday:</td><td>09:00 - 18:00</td></tr>
                                        <tr><td>Wednesday:</td><td>09:00 - 18:00</td></tr>
                                        <tr><td>Thursday:</td><td>09:00 - 18:00</td></tr>
                                        <tr><td>Friday:</td><td>09:00 - 18:00</td></tr>
                                        <tr><td>Saturday:</td><td>10:00 - 16:00</td></tr>
                                        <tr><td>Sunday:</td><td>Closed</td></tr>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="accepted-items" class="guide-content active">
            <div class="accepted-items-container">
                <h2>What Can You Recycle?</h2>
                <p class="intro-text">We accept a wide range of electronic devices. If it runs on electricity or batteries, it can likely be recycled! Click any item to learn more.</p>
                
                <div class="search-items">
                    <input type="text" id="itemSearchInput" placeholder="Search for an item... (e.g., laptop, battery)">
                </div>

                <div class="category-section">
                    <h3>📱 Small Appliances</h3>
                    <div class="items-grid" id="itemsGridSmall">
                        <?php foreach ($items_with_points as $item): 
                            if (in_array($item['name'], ['Smartphone', 'Tablet', 'Laptop', 'Digital camera', 'Projectors', 'MP3 Players', 'DVD Players', 'Power Bank', 'USB Flash Drive', 'External Hard Drive'])): ?>
                        <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                            <i class="fas fa-microchip"></i>
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                            <?php if (in_array($item['name'], ['Power Bank', 'Phone Batteries', 'Laptop Batteries'])): ?>
                                <span class="special-badge">⚠️ Special</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

                <div class="category-section">
                    <h3>💻 Computer Hardware</h3>
                    <div class="items-grid" id="itemsGridComputer">
                        <?php foreach ($items_with_points as $item): 
                            if (in_array($item['name'], ['Keyboards', 'Desktop', 'Computer Mice', 'Monitors', 'Headphones / Earphones', 'Printers', 'Scanner', 'PC / CPU', 'Router', 'Modem', 'Cables'])): ?>
                        <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                            <i class="fas fa-desktop"></i>
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

                <div class="category-section">
                    <h3>📺 Entertainment & Audio</h3>
                    <div class="items-grid" id="itemsGridEntertainment">
                        <?php foreach ($items_with_points as $item): 
                            if (in_array($item['name'], ['Television', 'Speakers', 'Gaming Consoles', 'Camera', 'Projector'])): ?>
                        <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                            <i class="fas fa-tv"></i>
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

                <div class="category-section">
                    <h3>🔋 Batteries & Accessories</h3>
                    <div class="items-grid" id="itemsGridBattery">
                        <?php foreach ($items_with_points as $item): 
                            if (in_array($item['name'], ['AA/AAA Batteries', 'Phone Batteries', 'Laptop Batteries', 'Chargers & Cables', 'Power Banks', 'Adapters', 'Extension Cord'])): ?>
                        <div class="item-card" data-name="<?php echo strtolower($item['name']); ?>" onclick="showItemDetail('<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['recycle_points']; ?>)">
                            <i class="fas fa-battery-full"></i>
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="item-points">+<?php echo $item['recycle_points']; ?> pts</span>
                            <span class="special-badge">⚠️ Special</span>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                    <p style="margin-top: 1rem; color: var(--Gray); font-size: 0.9rem;">
                        <i class="fas fa-exclamation-triangle" style="color: orange;"></i> 
                        Please tape the ends of lithium batteries before disposal.
                    </p>
                </div>
            </div>
        </div>

        <div id="preparation" class="guide-content">
            <div class="preparation-container">
                <h2>How to Prepare Your E-Waste</h2>
                <p class="preparation-intro">Follow these simple steps to ensure your e-waste is ready for collection or drop-off. Check items off as you complete them!</p>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div style="flex:1;">
                            <div class="step-header">
                                <div class="step-number">1</div>
                                <div class="step-title">Backup Your Data</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-database step-icon"></i>
                                Before disposing of any device that stores information, back up your important files and photos.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="1"> <span>I have backed up my data</span>
                            </label>
                        </div>
                    </div>
                    <div class="step-card">
                        <div style="flex:1;">
                            <div class="step-header">
                                <div class="step-number">2</div>
                                <div class="step-title">Wipe Personal Information</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-user-shield step-icon"></i>
                                Perform a factory reset on phones and computers. Remove SIM and memory cards.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="2"> <span>I have wiped my data</span>
                            </label>
                        </div>
                    </div>
                    <div class="step-card">
                        <div style="flex:1;">
                            <div class="step-header">
                                <div class="step-number">3</div>
                                <div class="step-title">Remove Batteries (If Possible)</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-battery-half step-icon"></i>
                                Remove detachable batteries. For built-in batteries, leave them in place.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="3"> <span>Batteries removed if possible</span>
                            </label>
                        </div>
                    </div>
                    <div class="step-card">
                        <div style="flex:1;">
                            <div class="step-header">
                                <div class="step-number">4</div>
                                <div class="step-title">Do Not Dismantle</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-tools step-icon"></i>
                                Leave devices intact. Professional recyclers have the right equipment.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="4"> <span>Device is intact</span>
                            </label>
                        </div>
                    </div>
                    <div class="step-card">
                        <div style="flex:1;">
                            <div class="step-header">
                                <div class="step-number">5</div>
                                <div class="step-title">Pack Securely</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-box step-icon"></i>
                                Place items in a box or bag. Use padding for fragile items.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="5"> <span>Items packed securely</span>
                            </label>
                        </div>
                    </div>
                    <div class="step-card">
                        <div style="flex:1;">
                            <div class="step-header">
                                <div class="step-number">⚠️</div>
                                <div class="step-title">Safety Tip</div>
                            </div>
                            <div class="step-content">
                                <i class="fas fa-exclamation-triangle step-icon" style="color: orange;"></i>
                                Tape lithium battery terminals with clear tape to prevent fire risk.
                            </div>
                            <label class="checklist-item">
                                <input type="checkbox" class="step-checkbox" data-step="safety"> <span>Battery terminals taped</span>
                            </label>
                        </div>
                    </div>
                </div>
                <button class="download-btn" onclick="downloadChecklist()">
                    <i class="fas fa-download"></i> Download Preparation Checklist
                </button>
            </div>
        </div>

        <div id="journey" class="guide-content">
            <div class="journey-container">
                <h2>What Happens to Your E-Waste?</h2>
                <p class="journey-intro">Ever wondered what happens after your e-waste is collected? Here's the journey from your doorstep to material recovery.</p>
                
                <div class="journey-stats">
                    <div class="stat-bubble">
                        <div class="number"><?php echo number_format($total_recycled, 2); ?> kg</div>
                        <div class="label">Total Recycled</div>
                    </div>
                    <div class="stat-bubble">
                        <div class="number" id="co2Saved">0</div>
                        <div class="label">CO₂ Saved (kg)</div>
                    </div>
                    <div class="stat-bubble">
                        <div class="number" id="waterSaved">0</div>
                        <div class="label">Water Saved (L)</div>
                    </div>
                </div>

                <div class="journey-steps">
                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>📦 Step 1: Collection & Sorting</h3>
                            <p>E-waste arrives at the recycling facility and is sorted by type. Items that can be reused are set aside for refurbishment.</p>
                        </div>
                    </div>
                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>🔧 Step 2: Manual Disassembly</h3>
                            <p>Trained workers carefully take apart devices by hand, separating components like circuit boards, plastic casings, glass, metals, and batteries.</p>
                        </div>
                    </div>
                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>⚙️ Step 3: Shredding & Separation</h3>
                            <p>Materials are shredded into small pieces. Magnets and other technologies separate ferrous metals, non-ferrous metals, and plastics.</p>
                        </div>
                    </div>
                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>♻️ Step 4: Material Recovery</h3>
                            <p>Metals are melted down and reused. Plastics are processed into pellets. Precious metals like gold and silver are extracted from circuit boards.</p>
                        </div>
                    </div>
                    <div class="journey-step" onclick="toggleStepDetail(this)">
                        <div class="journey-step-content">
                            <h3>🏭 Step 5: Safe Disposal</h3>
                            <p>Hazardous materials that cannot be recycled are treated and disposed of safely according to environmental regulations.</p>
                        </div>
                    </div>
                </div>

                <div class="impact-meter">
                    <h4>🌍 Your Impact Matters</h4>
                    <p>Recycling 1,000 kg of e-waste saves approximately:</p>
                    <div class="impact-bar"><div class="impact-fill" id="impactFill"></div></div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span>⚡ 1,500 kWh energy</span>
                        <span>💧 150,000 L water</span>
                        <span>🌲 50 trees</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <hr>
    <footer>
        <section class="c-footer-info-section">
            <a href="../../html/provider/pHome.php"><img src="../../assets/images/logo.png" alt="Logo" class="c-logo"></a>
            <div class="c-text">AfterVolt</div>
            <div class="c-text c-text-center">Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.</div>
            <div class="c-text c-text-label">+60 12 345 6789</div>
            <div class="c-text">abc@gmail.com</div>
        </section>
        <section class="c-footer-links-section">
            <div><b>Recycling</b><br><a href="../../html/provider/pEwasteGuide.php">E-Waste Guide</a><br><a href="../../html/provider/pWasteType.html">E-Waste Types</a></div>
            <div><b>My Activity</b><br><a href="../../html/provider/pSchedulePickup.php">Schedule Pickup</a><br><a href="../../html/provider/pMainPickup.php">My Pickup</a></div>
            <div><b>Proxy</b><br><a href="../../html/common/About.html">About</a><br><a href="../../html/common/Profile.html">Edit Profile</a><br><a href="../../html/common/Setting.html">Setting</a></div>
        </section>
    </footer>

    <div id="itemDetailModal" class="item-detail-modal">
        <div class="item-detail-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3 id="modalItemName"></h3>
            <p id="modalItemPoints"></p>
            <p id="modalItemInfo"></p>
        </div>
    </div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        function switchTab(event, tabId) {
            event.preventDefault();
            document.querySelectorAll('.guide-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.guide-content').forEach(content => content.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            if (tabId === 'journey') {
                setTimeout(updateImpactMetrics, 500);
            }
        }

        let centres = <?php echo json_encode($centres); ?>;

        function selectCentre(centreId) {
            document.querySelectorAll('.centre-item').forEach(item => item.classList.remove('active'));
            const selectedItem = document.querySelector(`.centre-item[data-id="${centreId}"]`);
            if (selectedItem) selectedItem.classList.add('active');
            document.querySelectorAll('.detail-view').forEach(detail => detail.style.display = 'none');
            const selectedDetail = document.getElementById(`detail-${centreId}`);
            if (selectedDetail) selectedDetail.style.display = 'block';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchBtn = document.getElementById('searchBtn');
            const searchInput = document.getElementById('searchInput');
            if (searchBtn) {
                searchBtn.addEventListener('click', function() {
                    const searchTerm = searchInput.value.toLowerCase();
                    let found = false;
                    for (let i = 0; i < centres.length; i++) {
                        if (centres[i].name.toLowerCase().includes(searchTerm) || 
                            centres[i].address.toLowerCase().includes(searchTerm) ||
                            (centres[i].postcode && centres[i].postcode.includes(searchTerm))) {
                            selectCentre(centres[i].centreID);
                            found = true;
                            break;
                        }
                    }
                    if (!found && searchTerm) alert('No matching centre found.');
                });
            }
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') searchBtn.click();
                });
            }

            const itemSearch = document.getElementById('itemSearchInput');
            if (itemSearch) {
                itemSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    document.querySelectorAll('.item-card').forEach(card => {
                        const name = card.getAttribute('data-name') || '';
                        if (name.includes(searchTerm) || searchTerm === '') {
                            card.style.display = 'flex';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            const checkboxes = document.querySelectorAll('.step-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const step = this.getAttribute('data-step');
                    if (this.checked) {
                        localStorage.setItem(`prep_step_${step}`, 'true');
                    } else {
                        localStorage.removeItem(`prep_step_${step}`);
                    }
                });
                const saved = localStorage.getItem(`prep_step_${cb.getAttribute('data-step')}`);
                if (saved === 'true') cb.checked = true;
            });
        });

        function showItemDetail(name, points) {
            const modal = document.getElementById('itemDetailModal');
            document.getElementById('modalItemName').textContent = name;
            document.getElementById('modalItemPoints').innerHTML = `Earn <strong>${points} points</strong> for recycling this item!`;
            let info = '';
            if (name.includes('Battery') || name.includes('battery')) {
                info = '⚠️ Special handling required: Please tape battery terminals before disposal.';
            } else if (name.includes('Laptop') || name.includes('Computer')) {
                info = '💡 Tip: Remember to back up and wipe your data before recycling.';
            } else if (name.includes('Phone') || name.includes('Smartphone')) {
                info = '📱 Remove SIM card and memory card before recycling. Factory reset recommended.';
            } else {
                info = '♻️ This item can be recycled at any of our collection centres.';
            }
            document.getElementById('modalItemInfo').innerHTML = info;
            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('itemDetailModal').style.display = 'none';
        }

        function downloadChecklist() {
            let checklist = 'AfterVolt E-Waste Preparation Checklist\n\n';
            const steps = ['Backup Your Data', 'Wipe Personal Information', 'Remove Batteries (If Possible)', 'Do Not Dismantle', 'Pack Securely', 'Tape Battery Terminals'];
            checklist += '☐ ' + steps.join('\n☐ ');
            const blob = new Blob([checklist], { type: 'text/plain' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'ewaste_preparation_checklist.txt';
            link.click();
            URL.revokeObjectURL(link.href);
        }

        function updateImpactMetrics() {
            const totalWeight = <?php echo $total_recycled; ?>;
            const co2Saved = (totalWeight * 0.5).toFixed(1);
            const waterSaved = (totalWeight * 150).toFixed(0);
            document.getElementById('co2Saved').textContent = co2Saved;
            document.getElementById('waterSaved').textContent = waterSaved;
            const impactPercent = Math.min(100, (totalWeight / 1000) * 100);
            document.getElementById('impactFill').style.width = impactPercent + '%';
        }

        function toggleStepDetail(stepElement) {
            const content = stepElement.querySelector('.journey-step-content');
            if (content) {
                const extraInfo = content.querySelector('.extra-detail');
                if (extraInfo) {
                    extraInfo.remove();
                } else {
                    const detail = document.createElement('div');
                    detail.className = 'extra-detail';
                    detail.style.marginTop = '0.5rem';
                    detail.style.padding = '0.5rem';
                    detail.style.backgroundColor = 'var(--bg-color)';
                    detail.style.borderRadius = '8px';
                    detail.style.fontSize = '0.9rem';
                    detail.innerHTML = '💡 Click again to collapse';
                    content.appendChild(detail);
                }
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('itemDetailModal');
            if (event.target === modal) closeModal();
        }
    </script>
</body>
</html>