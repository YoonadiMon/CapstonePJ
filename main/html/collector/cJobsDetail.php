<?php
session_start();
include("../../php/dbConn.php");

if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'collector') {
    header("Location: /CapstonePJ/signIn.php");
    exit();
}

$collectorID = $_SESSION['userID'];

// ── Get jobID from URL ──
$jobID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$jobID) {
    die("Invalid job ID.");
}

// ── Handle Accept / Reject POST actions (AJAX — returns JSON) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE tbljob SET status = 'Scheduled' WHERE jobID = ? AND collectorID = ?");
        $stmt->bind_param("ii", $jobID, $collectorID);
        $ok = $stmt->execute();
        $stmt->close();

        // Get requestID then update collection request
        $stmt2 = $conn->prepare("SELECT requestID FROM tbljob WHERE jobID = ?");
        $stmt2->bind_param("i", $jobID);
        $stmt2->execute();
        $stmt2->bind_result($requestID);
        $stmt2->fetch();
        $stmt2->close();

        $stmt3 = $conn->prepare("UPDATE tblcollection_request SET status = 'Scheduled' WHERE requestID = ?");
        $stmt3->bind_param("i", $requestID);
        $stmt3->execute();
        $stmt3->close();

        echo json_encode(['success' => $ok, 'action' => 'accept', 'newStatus' => 'Scheduled']);
        exit();
    } elseif ($action === 'reject') {
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

        $stmt = $conn->prepare("UPDATE tbljob SET status = 'Rejected', rejectionReason = ? WHERE jobID = ? AND collectorID = ?");
        $stmt->bind_param("sii", $reason, $jobID, $collectorID);
        $ok = $stmt->execute();
        $stmt->close();

        // Request status stays as 'Approved' — do NOT change

        echo json_encode(['success' => $ok, 'action' => 'reject', 'newStatus' => 'Rejected']);
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit();
}

// ── Fetch Job + Provider info ──
$sql = "
    SELECT
        j.jobID,
        j.status         AS jobStatus,
        j.scheduledDate,
        j.scheduledTime,
        j.rejectionReason,
        r.requestID,
        r.pickupAddress,
        r.pickupState,
        r.pickupPostcode,
        r.preferredDateTime,
        u.fullname       AS providerName
    FROM tbljob j
    JOIN tblcollection_request r ON r.requestID = j.requestID
    JOIN tblusers u               ON u.userID    = r.providerID
    WHERE j.jobID = ?
      AND j.collectorID = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $jobID, $collectorID);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If job not found or doesn't belong to this collector
if (!$job) {
    $job = null;
}

// ── Fetch Items for this job ──
$items = [];
if ($job) {
    $sqlItems = "
        SELECT
            i.itemID,
            i.description,
            i.model,
            i.brand,
            i.weight,
            i.image,
            i.status AS itemStatus,
            it.name AS itemTypeName,
            c.name AS centreName
        FROM tblitem i
        JOIN tblitem_type it ON it.itemTypeID = i.itemTypeID
        LEFT JOIN tblcentre c ON c.centreID   = i.centreID
        WHERE i.requestID = ?
        ORDER BY i.itemID ASC
    ";
    $stmt2 = $conn->prepare($sqlItems);
    $stmt2->bind_param("i", $job['requestID']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt2->close();
}

$conn->close();

// ── Map DB status to CSS class ──
$statusMap = [
    'Pending'   => 'pending',
    'Scheduled' => 'accepted',
    'Ongoing'   => 'ongoing',
    'Completed' => 'completed',
    'Rejected'  => 'rejected',
    'Cancelled' => 'cancelled',
];
$jobStatusClass = $job ? ($statusMap[$job['jobStatus']] ?? strtolower($job['jobStatus'])) : '';

// ── Format display date ──
$displayDate = '';
if ($job) {
    $dt = new DateTime($job['scheduledDate'] . ' ' . $job['scheduledTime']);
    $displayDate = $dt->format('d/m/Y');
}

// ── Build formatted job ID label ──
$jobLabel = $job ? ('JOB' . str_pad($job['jobID'], 3, '0', STR_PAD_LEFT)) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/cJobsDetail.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>

<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <!-- Header -->
    <header>
        <section class="c-logo-section">
            <a href="../../html/collector/cHome.php" class="c-logo-link">
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
                    <a href="../../html/collector/cHome.php">Home</a>
                    <a href="../../html/collector/cMyJobs.php">My Jobs</a>
                    <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a>
                    <a href="../../html/collector/cCompletedJobs.php">History</a>
                    <a href="../../html/common/About.html">About</a>
                </div>
            </div>
        </nav>

        <nav class="c-navbar-desktop">
            <a href="../../html/collector/cHome.php">Home</a>
            <a href="../../html/collector/cMyJobs.php">My Jobs</a>
            <a href="../../html/collector/cInProgress.php">Ongoing Jobs</a>
            <a href="../../html/collector/cCompletedJobs.php">History</a>
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
    <main class="job-details-main">
        <div class="back-wrapper">
            <button class="back-btn" onclick="window.history.back()">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M15 10L5 10M5 10L9 14M5 10L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                Back
            </button>
        </div>

        <?php if (!$job): ?>
            <!-- Job not found -->
            <div style="text-align:center; padding:4rem; color:var(--Gray);">
                <h2>Job not found</h2>
                <p>Please go back and select a valid job.</p>
            </div>

        <?php else: ?>
            <!-- Toast notification -->
            <div id="toastMsg" class="toast-msg" style="display:none;"></div>

            <!-- Accept confirmation modal -->
            <div id="acceptModal" class="img-modal-overlay" onclick="closeAcceptModal()">
                <div class="img-modal-box action-modal-box" onclick="event.stopPropagation()">
                    <button class="img-modal-close" onclick="closeAcceptModal()">&#x2715;</button>
                    <div class="action-modal-icon accept-icon">&#x2713;</div>
                    <h3 class="action-modal-title">Accept Job?</h3>
                    <p class="action-modal-desc">You are about to accept this job. The status will be updated to <strong>Scheduled</strong>.</p>
                    <div class="action-modal-btns">
                        <button class="btn-reject" onclick="closeAcceptModal()">Cancel</button>
                        <button class="btn-accept" onclick="submitAccept()">Yes, Accept</button>
                    </div>
                </div>
            </div>

            <!-- Reject confirmation modal -->
            <div id="rejectModal" class="img-modal-overlay" onclick="closeRejectModal()">
                <div class="img-modal-box action-modal-box" onclick="event.stopPropagation()">
                    <button class="img-modal-close" onclick="closeRejectModal()">&#x2715;</button>
                    <div class="action-modal-icon reject-icon">&#x2715;</div>
                    <h3 class="action-modal-title">Reject Job?</h3>
                    <p class="action-modal-desc">Provide a reason for rejection (optional):</p>
                    <textarea id="rejectReason" class="reject-reason-input" placeholder="e.g. Schedule conflict, outside service area..."></textarea>
                    <div class="action-modal-btns">
                        <button class="btn-reject" onclick="closeRejectModal()">Cancel</button>
                        <button class="btn-reject btn-reject-confirm" onclick="submitReject()">Yes, Reject</button>
                    </div>
                </div>
            </div>

            <div class="job-details-container">

                <div class="job-details-header">
                    <div class="job-title-row">
                        <h1 id="jobTitle"><?= htmlspecialchars($jobLabel) ?></h1>
                        <span id="jobStatus" class="job-status-badge <?= $jobStatusClass ?>">
                            <?= htmlspecialchars($job['jobStatus']) ?>
                        </span>
                    </div>

                    <!-- Show Accept/Reject only if job is still Pending -->
                    <?php if ($job['jobStatus'] === 'Pending'): ?>
                        <div class="job-details-actions">
                            <button class="btn-accept" onclick="acceptJob()">Accept</button>
                            <button class="btn-reject" onclick="rejectJob()">Reject</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Overview Section -->
                <section class="job-overview">
                    <h2>Details Overview</h2>
                    <div class="overview-grid">

                        <div class="overview-card provider-card">
                            <h3>Provider</h3>
                            <div class="provider-info">
                                <p><strong>Name:</strong> <?= htmlspecialchars($job['providerName']) ?></p>
                                <p><strong>Address:</strong>
                                    <?= htmlspecialchars($job['pickupAddress'] . ', ' . $job['pickupPostcode'] . ', ' . $job['pickupState'] . ', Malaysia') ?>
                                </p>
                                <p><strong>Date:</strong> <?= htmlspecialchars($displayDate) ?></p>
                            </div>
                        </div>

                        <div class="overview-card items-card">
                            <h3>Items</h3>
                            <ul id="itemList">
                                <?php foreach ($items as $i => $item): ?>
                                    <li><?= ($i + 1) . '. ' . htmlspecialchars($item['itemTypeName']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="total-weight">
                                <strong>Total Weight:</strong>
                                <?php
                                $totalWeight = array_sum(array_column($items, 'weight'));
                                echo number_format($totalWeight, 2);
                                ?> kg
                            </div>
                        </div>

                        <div class="overview-card brands-card">
                            <h3>Brand &amp; Model</h3>
                            <ul id="brandList">
                                <?php foreach ($items as $i => $item): ?>
                                    <li>
                                        <?= ($i + 1) . '. ' ?>
                                        <?= htmlspecialchars(($item['brand'] ?? 'N/A') . ' ' . ($item['model'] ?? '')) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                    </div>
                </section>

                <!-- Items Dropdown Section -->
                <section class="items-section">
                    <div id="itemsContainer">
                        <?php 
                        // Hardcoded image mapping - all available images in uploads folder
                        $availableImages = [
                            'hdd1.jpg', 'laptop1.jpg', 'monitor1.jpg', 'pc1.jpg', 
                            'photocopier1.jpg', 'printer1.jpg', 'scanner1.jpg', 
                            'tablet_accessory1.jpg', 'tv1.jpg'
                        ];
                        
                        foreach ($items as $item):
                            $itemLabel = 'ITEM' . str_pad($item['itemID'], 3, '0', STR_PAD_LEFT);
                            
                            // Get the image filename from database
                            $dbImage = !empty($item['image']) ? $item['image'] : '';
                            
                            // Check if the database image exists in our uploads folder
                            $imagePath = '../../uploads/' . $dbImage;
                            $imageExists = !empty($dbImage) && file_exists($imagePath);
                            
                            // If image doesn't exist or is empty, try to find a matching image based on item type
                            if (!$imageExists) {
                                // Map item types to possible image names
                                $itemTypeToImage = [
                                    'Laptop' => 'laptop1.jpg',
                                    'PC / CPU' => 'pc1.jpg',
                                    'Monitor' => 'monitor1.jpg',
                                    'Printer' => 'printer1.jpg',
                                    'Scanner' => 'scanner1.jpg',
                                    'Photocopier' => 'photocopier1.jpg',
                                    'External Hard Drive' => 'hdd1.jpg',
                                    'Tablet Accessories' => 'tablet_accessory1.jpg',
                                    'Television' => 'tv1.jpg'
                                ];
                                
                                $itemTypeName = $item['itemTypeName'];
                                if (isset($itemTypeToImage[$itemTypeName])) {
                                    $mappedImage = $itemTypeToImage[$itemTypeName];
                                    $mappedPath = '../../uploads/' . $mappedImage;
                                    if (file_exists($mappedPath)) {
                                        $imagePath = $mappedPath;
                                        $imageExists = true;
                                    }
                                }
                            }
                            
                            // Final image source
                            if ($imageExists) {
                                $imgSrc = $imagePath;
                            } else {
                                // Use placeholder if no image found
                                $imgSrc = 'https://placehold.co/600x400?text=No+Image+Available';
                            }
                            
                            $dropoff = !empty($item['centreName']) ? $item['centreName'] : 'Not assigned yet';
                        ?>
                            <div class="item-dropdown">
                                <div class="item-dropdown-header" onclick="toggleDropdown(this)">
                                    <h3><?= htmlspecialchars($itemLabel) ?> — <?= htmlspecialchars($item['itemTypeName']) ?></h3>
                                    <span class="dropdown-arrow">▼</span>
                                </div>

                                <div class="item-dropdown-content">
                                    <div class="item-grid-with-image">
                                        <div class="item-image-col">
                                            <img src="<?= $imgSrc ?>"
                                                alt="<?= htmlspecialchars($item['itemTypeName']) ?>"
                                                class="item-sample-img"
                                                onerror="this.src='https://placehold.co/600x400?text=No+Image'" />
                                            <a class="view-full-pic-link"
                                                onclick="openImageModal('<?= $imgSrc ?>',
                                           '<?= htmlspecialchars(addslashes($item['itemTypeName'])) ?>')">
                                                View full Pic
                                            </a>
                                        </div>
                                        <div class="item-details-col">
                                            <div class="item-grid">
                                                <div>
                                                    <p><strong>Item:</strong> <?= htmlspecialchars($item['itemTypeName']) ?></p>
                                                    <p><strong>Brand:</strong> <?= htmlspecialchars($item['brand'] ?? 'N/A') ?></p>
                                                    <p><strong>Weight:</strong> <?= htmlspecialchars($item['weight']) ?> kg</p>
                                                </div>
                                                <div>
                                                    <p><strong>Model:</strong> <?= htmlspecialchars($item['model'] ?? 'N/A') ?></p>
                                                    <p><strong>Drop-off:</strong> <?= htmlspecialchars($dropoff) ?></p>
                                                    <p><strong>Description:</strong> <?= htmlspecialchars($item['description']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($items)): ?>
                            <p style="color:var(--Gray); text-align:center; padding:2rem;">No items found for this job.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <hr>

    <!-- Footer -->
    <footer>
        <section class="c-footer-info-section">
            <a href="../../html/collector/cHome.php">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
            </a>
            <div class="c-text">AfterVolt</div>
            <div class="c-text c-text-center">
                Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.
            </div>
            <div class="c-text c-text-label">+60 12 345 6789</div>
            <div class="c-text">abc@gmail.com</div>
        </section>

        <!-- Image Modal (kept in footer area as per your original) -->
        <div id="imageModal" class="img-modal-overlay" onclick="closeImageModal()">
            <div class="img-modal-box" onclick="event.stopPropagation()">
                <button class="img-modal-close" onclick="closeImageModal()">✕</button>
                <img id="modalImg" src="" alt="Full Item Image" class="img-modal-img" />
                <p id="modalCaption" class="img-modal-caption"></p>
            </div>
        </div>

        <section class="c-footer-links-section">
            <div>
                <b>My Jobs</b><br>
                <a href="../../html/collector/cMyJobs.php">My Jobs</a><br>
                <a href="../../html/collector/cInProgress.php">In Progress</a><br>
                <a href="../../html/collector/cCompletedJobs.php">Completed Jobs</a>
            </div>
            <div>
                <b>Support</b><br>
                <a href="../../html/collector/cReportIssues.html">Report Issue</a>
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
        const JOB_ID = <?= (int)$jobID ?>;

        // ── Dropdown toggle ──
        function toggleDropdown(header) {
            const content = header.nextElementSibling;
            const arrow = header.querySelector('.dropdown-arrow');
            content.classList.toggle('active');
            arrow.classList.toggle('rotate');
        }

        // ── Image modal ──
        function openImageModal(src, name) {
            document.getElementById('modalImg').src = src;
            document.getElementById('modalCaption').textContent = name;
            document.getElementById('imageModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // ── Accept modal ──
        function acceptJob() {
            document.getElementById('acceptModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAcceptModal() {
            document.getElementById('acceptModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // ── Reject modal ──
        function rejectJob() {
            document.getElementById('rejectReason').value = '';
            document.getElementById('rejectModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // ── Toast ──
        function showToast(msg, type) {
            const toast = document.getElementById('toastMsg');
            toast.textContent = msg;
            toast.className = 'toast-msg toast-' + type;
            toast.style.display = 'block';
            // Trigger reflow so the animation replays
            void toast.offsetWidth;
            toast.classList.add('toast-show');
            setTimeout(() => {
                toast.classList.remove('toast-show');
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 400);
            }, 3500);
        }

        // ── Hide action buttons + update badge after action ──
        function applyStatusChange(newStatus) {
            // Hide accept/reject buttons
            const actionsEl = document.querySelector('.job-details-actions');
            if (actionsEl) actionsEl.style.display = 'none';

            // Update status badge
            const badge = document.getElementById('jobStatus');
            if (badge) {
                const classMap = {
                    'Scheduled': 'accepted',
                    'Rejected': 'rejected',
                };
                badge.textContent = newStatus;
                badge.className = 'job-status-badge ' + (classMap[newStatus] ?? newStatus.toLowerCase());
            }
        }

        // ── AJAX submit ──
        function postAction(action, reason) {
            const body = new URLSearchParams({
                action
            });
            if (reason !== undefined) body.append('reason', reason);

            return fetch('?id=' + JOB_ID, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            }).then(r => r.json());
        }

        function submitAccept() {
            closeAcceptModal();
            postAction('accept').then(data => {
                if (data.success) {
                    applyStatusChange('Scheduled');
                    showToast('Job accepted! Status updated to Scheduled.', 'success');
                } else {
                    showToast('Something went wrong. Please try again.', 'error');
                }
            }).catch(() => showToast('Network error. Please try again.', 'error'));
        }

        function submitReject() {
            const reason = document.getElementById('rejectReason').value.trim();
            closeRejectModal();
            postAction('reject', reason).then(data => {
                if (data.success) {
                    applyStatusChange('Rejected');
                    showToast('Job has been rejected.', 'error');
                } else {
                    showToast('Something went wrong. Please try again.', 'error');
                }
            }).catch(() => showToast('Network error. Please try again.', 'error'));
        }

        // ── Entrance animation ──
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.job-details-container');
            if (!container) return;
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            setTimeout(() => {
                container.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>