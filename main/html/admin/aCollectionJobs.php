<?php
session_start();
include("../../php/dbConn.php");

// // check if user is logged in
include("../../php/sessionCheck.php");

if (!isset($conn)) {
    die("Database connection not found.");
}

date_default_timezone_set('Asia/Kuala_Lumpur');

// ---- update statuses

// Auto-update: set 'on duty' if collector has an Ongoing job
$conn->query("
    UPDATE tblcollector c
    SET c.status = 'on duty'
    WHERE EXISTS (
        SELECT 1 FROM tbljob j
        WHERE j.collectorID = c.collectorID
          AND j.status = 'Ongoing'
    )
    AND c.status != 'on duty'
");

// Auto-update: revert 'on duty' back to 'active' when no more Ongoing jobs
$conn->query("
    UPDATE tblcollector c
    SET c.status = 'active'
    WHERE c.status = 'on duty'
      AND NOT EXISTS (
          SELECT 1 FROM tbljob j
          WHERE j.collectorID = c.collectorID
            AND j.status = 'Ongoing'
      )
");
// ----

function esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function queryAll(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_free_result($result);
    return $rows;
}

function queryOne(mysqli $conn, string $sql): ?array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row ?: null;
}

function formatDelay(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }

    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    if ($mins === 0) {
        return $hours . ' hr';
    }

    return $hours . ' hr ' . $mins . ' min';
}

function buildAddress(...$parts): string
{
    $clean = [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part !== '') {
            $clean[] = $part;
        }
    }
    return implode(', ', $clean);
}

function geocodeAddress($address) {
    static $cache = [];
    
    // If address is empty, immediately return default coordinates
    if (empty($address)) {
        return ['lat' => 3.1390, 'lng' => 101.6869];
    }
    
    $cacheKey = md5($address);
    
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }
    
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AfterVolt/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        // Ensure the response contains the expected data
        if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lng'])) {
            $result = [
                'lat' => (float)$data[0]['lat'],
                'lng' => (float)$data[0]['lng']
            ];
            $cache[$cacheKey] = $result;
            return $result;
        }
    }
    
    return ['lat' => 3.1390, 'lng' => 101.6869];
}

// -------------------------------------------------------------------
// Helper function to get route distance from OSRM (with caching)
// -------------------------------------------------------------------
function getRouteDistance($startLat, $startLng, $endLat, $endLng) {
    static $distanceCache = [];
    $cacheKey = $startLat . ',' . $startLng . '|' . $endLat . ',' . $endLng;
    if (isset($distanceCache[$cacheKey])) {
        return $distanceCache[$cacheKey];
    }
    
    $url = "https://router.project-osrm.org/route/v1/driving/{$startLng},{$startLat};{$endLng},{$endLat}?overview=false";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AfterVolt/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['routes'][0]['distance'])) {
            $distance = $data['routes'][0]['distance'] / 1000; // km
            $distanceCache[$cacheKey] = $distance;
            return $distance;
        }
    }
    
    // Fallback: haversine approximation
    $R = 6371;
    $dLat = deg2rad($endLat - $startLat);
    $dLng = deg2rad($endLng - $startLng);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($startLat)) * cos(deg2rad($endLat)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $R * $c;
    $distanceCache[$cacheKey] = $distance;
    return $distance;
}

// Delayed Jobs
$delayedSql = "
    SELECT
        j.jobID,
        j.requestID,
        j.status AS jobStatus,
        j.scheduledDate,
        j.scheduledTime,
        TIMESTAMPDIFF(
            MINUTE,
            CONCAT(j.scheduledDate, ' ', j.scheduledTime),
            NOW()
        ) AS delayMinutes,
        u.fullname AS collectorName,
        v.plateNum,
        v.type AS vehicleType,
        r.pickupAddress,
        r.pickupState,
        r.pickupPostcode,
        COALESCE(
            (
                SELECT i.subject
                FROM tblissue i
                WHERE i.jobID = j.jobID
                ORDER BY i.reportedAt DESC
                LIMIT 1
            ),
            CASE
                WHEN j.status = 'Pending' THEN 'Collector has not started yet'
                WHEN j.status = 'Scheduled' THEN 'Scheduled time exceeded'
                WHEN j.status = 'Ongoing' THEN 'Still in progress beyond schedule'
                ELSE 'Delayed'
            END
        ) AS delayReason
    FROM tbljob j
    INNER JOIN tblcollection_request r ON r.requestID = j.requestID
    INNER JOIN tblusers u ON u.userID = j.collectorID
    LEFT JOIN tblvehicle v ON v.vehicleID = j.vehicleID
    WHERE j.status IN ('Pending', 'Scheduled', 'Ongoing')
      AND CONCAT(j.scheduledDate, ' ', j.scheduledTime) < NOW()
      AND NOT EXISTS (
          SELECT 1 FROM tblissue i 
          WHERE i.jobID = j.jobID 
          AND i.status IN ('Open', 'Assigned')
      )
    ORDER BY delayMinutes DESC, j.scheduledDate ASC, j.scheduledTime ASC
";

$delayedRows = queryAll($conn, $delayedSql);

$delayedJobs = [];
foreach ($delayedRows as $row) {
    $jobIdFormatted = 'JOB' . str_pad((string)$row['jobID'], 3, '0', STR_PAD_LEFT);

    $delayedJobs[] = [
        'id' => $jobIdFormatted,
        'jobID' => (int)$row['jobID'],
        'requestID' => (int)$row['requestID'],
        'collector' => $row['collectorName'],
        'location' => buildAddress($row['pickupAddress'], $row['pickupState'], $row['pickupPostcode']),
        'delay' => formatDelay(max(0, (int)$row['delayMinutes'])),
        'reason' => $row['delayReason'],
        'time' => date('h:i A', strtotime($row['scheduledTime'])),
        'status' => 'Delayed',
        'vehicle' => trim(($row['vehicleType'] ?? '') . ' ' . ($row['plateNum'] ?? ''))
    ];
}

// -------------------------------------------------------------------
// Helper function to determine job progress based on activity logs
// -------------------------------------------------------------------
function getJobProgress(mysqli $conn, int $jobID, array $pickupCoords, array $centreCoords, array $baseCoords) {
    $sql = "SELECT action, description, dateTime, type FROM tblactivity_log 
            WHERE jobID = $jobID 
            ORDER BY dateTime ASC";
    $logs = queryAll($conn, $sql);

    $departedFromBase  = false;  // NEW: track if collector has left base
    $pickupDone        = false;
    $allDone           = false;
    $deliveredCentreIDs = [];
    $allCentreIDs      = array_keys($centreCoords);

    foreach ($logs as $log) {
        $desc   = strtolower($log['description'] ?? '');
        $action = strtolower($log['action']      ?? '');
        $type   = strtolower($log['type']        ?? '');

        // Phase 0 → 1: collector departed from base toward pickup
        // Log: type='Job', action='Departed', desc contains 'collector started journey from base'
        if ($type === 'job' && $action === 'departed' 
            && stripos($desc, 'collector started journey from base') !== false) {
            $departedFromBase = true;
        }

        // Phase 1 → 2: pickup completed
        // Log: desc contains 'collected from provider location'
        if (stripos($desc, 'collected from provider location') !== false) {
            $pickupDone       = true;
            $departedFromBase = true; // implicitly departed too
        }

        // Phase 2/3: one centre delivered
        // Log: desc contains 'items delivered to ... (ID: 123)'
        if (stripos($desc, 'items delivered to') !== false) {
            if (preg_match('/\(id:\s*(\d+)\)/i', $log['description'], $m)) {
                $deliveredCentreIDs[] = (int)$m[1];
            }
        }

        // Final phase: all done, returning to base
        // Log: type='Request', action='Status Change', desc contains 'ongoing to collected'
        if ($type === 'request' && $action === 'status change'
            && stripos($desc, 'ongoing to collected') !== false) {
            $allDone = true;
        }
    }

    $undelivered = array_diff($allCentreIDs, $deliveredCentreIDs);

    if ($allDone || (empty($undelivered) && $pickupDone)) {
        // All dropoffs done → return to base
        $lastCentreID = end($allCentreIDs);
        $start        = isset($centreCoords[$lastCentreID]) ? $centreCoords[$lastCentreID] : $pickupCoords;
        $end          = $baseCoords;
        $statusText   = 'Returning to base';

    } elseif ($pickupDone && !empty($undelivered)) {
        // Pickup done → heading to next centre
        $nextCentreID = reset($undelivered);

        if (!empty($deliveredCentreIDs)) {
            $lastDelivered = end($deliveredCentreIDs);
            $start = isset($centreCoords[$lastDelivered]) ? $centreCoords[$lastDelivered] : $pickupCoords;
        } else {
            $start = $pickupCoords;
        }

        $end        = $centreCoords[$nextCentreID];
        $statusText = 'Delivering to centre';

    } elseif ($departedFromBase) {
        // Departed but pickup not done → heading to pickup
        $start      = $baseCoords;
        $end        = $pickupCoords;
        $statusText = 'Heading to pickup';

    } else {
        // Not yet departed → still at base (job scheduled/pending)
        $start      = $baseCoords;
        $end        = $pickupCoords;
        $statusText = 'Not yet departed';
    }

    return [
        'startLat'           => $start['lat'],
        'startLng'           => $start['lng'],
        'endLat'             => $end['lat'],
        'endLng'             => $end['lng'],
        'statusText'         => $statusText,
        'pickupDone'         => $pickupDone,
        'allDone'            => $allDone,
        'deliveredCentres'   => $deliveredCentreIDs,
        'undeliveredCentres' => array_values($undelivered),
    ];
}

// Active Collectors – fetch collectors with any ongoing job
$activeCollectorsSql = "
    SELECT
        c.collectorID,
        u.fullname,
        c.status AS collectorStatus,
        j.jobID,
        j.requestID,
        j.status AS jobStatus,
        j.scheduledDate,
        j.scheduledTime,
        v.plateNum,
        v.type AS vehicleType,
        cr.pickupAddress,
        cr.pickupState,
        cr.pickupPostcode,
        centreData.centreID,
        centreData.centreName,
        centreData.centreAddress,
        centreData.centreState,
        centreData.centrePostcode
    FROM tblcollector c
    INNER JOIN tblusers u ON u.userID = c.collectorID
    INNER JOIN (
        -- Get one ongoing job per collector
        SELECT j1.collectorID, j1.jobID, j1.requestID, j1.status, j1.scheduledDate, j1.scheduledTime, j1.vehicleID
        FROM tbljob j1
        WHERE j1.status = 'Ongoing'
          AND j1.jobID = (
              SELECT MIN(j2.jobID)
              FROM tbljob j2
              WHERE j2.collectorID = j1.collectorID AND j2.status = 'Ongoing'
          )
    ) j ON j.collectorID = c.collectorID
    LEFT JOIN tblvehicle v ON v.vehicleID = j.vehicleID
    LEFT JOIN tblcollection_request cr ON cr.requestID = j.requestID
    LEFT JOIN (
        SELECT 
            i.requestID,
            MIN(c.centreID) as centreID,
            MIN(c.name) as centreName,
            MIN(c.address) as centreAddress,
            MIN(c.state) as centreState,
            MIN(c.postcode) as centrePostcode
        FROM tblitem i
        INNER JOIN tblcentre c ON c.centreID = i.centreID
        GROUP BY i.requestID
    ) centreData ON centreData.requestID = j.requestID
    WHERE c.status IN ('active', 'on duty')
    ORDER BY u.fullname ASC
";

$activeCollectorRows = queryAll($conn, $activeCollectorsSql);

// Base coordinates (APU)
$baseAddress = "APU, Jalan Teknologi 5, 57000 Kuala Lumpur, Federal Territory of Kuala Lumpur, Malaysia";
$baseCoords = ['lat' => 3.1069, 'lng' => 101.6883];

// Geocode a location by postcode
function geocodeByPostcode(string $postcode, string $state = ''): array {
    static $cache = [];

    $postcode = trim($postcode);
    if (empty($postcode) || !preg_match('/^\d{5}$/', $postcode)) {
        return geocodeByState($state); // fallback to state centroid
    }

    $cacheKey = 'pc_' . $postcode;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    // Postcode-only query 
    $url = "https://nominatim.openstreetmap.org/search"
         . "?postalcode=" . urlencode($postcode)
         . "&countrycodes=my"
         . "&format=json&limit=1";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'AfterVolt/1.0 (your@email.com)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    usleep(1100000); // only sleeps when actual HTTP call was made

    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);
        if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
            $result = [
                'lat' => (float)$data[0]['lat'],
                'lng' => (float)$data[0]['lon'],
            ];
            $cache[$cacheKey] = $result;
            return $result;
        }
    }
    // Nominatim failed — fall back to hardcoded state centroids
    return geocodeByState($state);
}

// Hardcoded state centroids as last-resort fallback
function geocodeByState(string $state): array {
    $centroids = [
        'Kuala Lumpur'          => ['lat' => 3.1390,  'lng' => 101.6869],
        'WP Kuala Lumpur'       => ['lat' => 3.1390,  'lng' => 101.6869],
        'Selangor'              => ['lat' => 3.0738,  'lng' => 101.5183],
        'Putrajaya'             => ['lat' => 2.9264,  'lng' => 101.6964],
        'WP Putrajaya'          => ['lat' => 2.9264,  'lng' => 101.6964],
        'Labuan'                => ['lat' => 5.2831,  'lng' => 115.2308],
        'WP Labuan'             => ['lat' => 5.2831,  'lng' => 115.2308],
        'Johor'                 => ['lat' => 1.9344,  'lng' => 103.3587],
        'Kedah'                 => ['lat' => 6.1184,  'lng' => 100.3685],
        'Kelantan'              => ['lat' => 6.1254,  'lng' => 102.2381],
        'Melaka'                => ['lat' => 2.1896,  'lng' => 102.2501],
        'Negeri Sembilan'       => ['lat' => 2.7258,  'lng' => 101.9424],
        'Pahang'                => ['lat' => 3.8126,  'lng' => 103.3256],
        'Perak'                 => ['lat' => 4.5921,  'lng' => 101.0901],
        'Perlis'                => ['lat' => 6.4449,  'lng' => 100.2048],
        'Pulau Pinang'          => ['lat' => 5.4141,  'lng' => 100.3288],
        'Penang'                => ['lat' => 5.4141,  'lng' => 100.3288],
        'Sabah'                 => ['lat' => 5.9788,  'lng' => 116.0753],
        'Sarawak'               => ['lat' => 1.5533,  'lng' => 110.3592],
        'Terengganu'            => ['lat' => 5.3117,  'lng' => 103.1324],
    ];

    $state = trim($state);
    if (isset($centroids[$state])) {
        return $centroids[$state];
    }
    // Try case-insensitive partial match
    foreach ($centroids as $name => $coords) {
        if (stripos($state, $name) !== false || stripos($name, $state) !== false) {
            return $coords;
        }
    }
    // Nothing matched — default to APU
    return $baseCoords;
}

$activeCollectors = [];
$totalDistance = 0; // accumulator for quick stats

foreach ($activeCollectorRows as $row) {
    $hasActiveJob = ((string)$row['jobStatus'] === 'Ongoing');
    
    $pickupFullAddress = buildAddress(
        $row['pickupAddress'] ?? '',
        $row['pickupState'] ?? '',
        $row['pickupPostcode'] ?? '',
        'Malaysia'
    );
    
    $centreFullAddress = buildAddress(
        $row['centreAddress'] ?? '',
        $row['centreState'] ?? '',
        $row['centrePostcode'] ?? '',
        'Malaysia'
    );
    
    $pickupCoords = null;
    $centreCoords = null;
    
    if ($hasActiveJob && !empty($pickupFullAddress)) {

    if (!empty($row['pickupPostcode']) && !empty($row['pickupState'])) {
        $pickupCoords = geocodeByPostcode($row['pickupPostcode'], $row['pickupState']);
    } else {

        $pickupCoords = ['lat' => 3.1390, 'lng' => 101.6869];
    }

    if (!empty($row['centrePostcode']) && !empty($row['centreState'])) {
        $centreCoords = geocodeByPostcode($row['centrePostcode'], $row['centreState']);
    } else {
        // fallback
        $centreCoords = ['lat' => 3.1390, 'lng' => 101.6869];
    }
}
    
    // Fetch all centres for this request (for routing)
    $allCentres = [];
    $centreSql = "SELECT c.centreID, c.name, c.address, c.state, c.postcode 
                FROM tblitem i 
                INNER JOIN tblcentre c ON i.centreID = c.centreID 
                WHERE i.requestID = " . (int)$row['requestID'] . " 
                GROUP BY c.centreID 
                ORDER BY MIN(i.itemID) ASC";
    $centreRows = queryAll($conn, $centreSql);

    // Also get the first centre for display purposes (optional)
    $firstCentre = null;
    foreach ($centreRows as $c) {
        $allCentres[$c['centreID']] = geocodeByPostcode($c['postcode'], $c['state']);
        if ($firstCentre === null) {
            $firstCentre = $c;
        }
    }
    
    $jobProgress = null;
    $routeStartLat = null;
    $routeStartLng = null;
    $routeEndLat = null;
    $routeEndLng = null;
    $progressText = '';
    $legDistance = 0;
    
    if ($hasActiveJob && $pickupCoords && !empty($allCentres)) {
        $jobProgress = getJobProgress($conn, (int)$row['jobID'], $pickupCoords, $allCentres, $baseCoords);
        $routeStartLat = $jobProgress['startLat'];
        $routeStartLng = $jobProgress['startLng'];
        $routeEndLat = $jobProgress['endLat'];
        $routeEndLng = $jobProgress['endLng'];
        $progressText = $jobProgress['statusText'];
        
        // Calculate distance for this leg and add to total
        if ($routeStartLat && $routeStartLng && $routeEndLat && $routeEndLng) {
            $legDistance = getRouteDistance($routeStartLat, $routeStartLng, $routeEndLat, $routeEndLng);
            $totalDistance += $legDistance;
        }
    }
    
    // Determine display location based on progress phase
    $displayLocation = '';
    if ($hasActiveJob) {
        if ($progressText === 'Returning to base') {
            $displayLocation = 'APU, Jalan Teknologi 5, 57000 Kuala Lumpur';
        } elseif ($progressText === 'Delivering to centre') {
            // Show the next centre location (if available)
            $displayLocation = buildAddress($row['centreAddress'] ?? '', $row['centreState'] ?? '', $row['centrePostcode'] ?? '');
            if (empty($displayLocation) && !empty($allCentres)) {
                $firstCentre = reset($allCentres);
                $displayLocation = $row['centreAddress'] ?? 'Collection centre';
            }
        } elseif ($progressText === 'Heading to pickup' || $progressText === 'Not yet departed') {
            $displayLocation = buildAddress($row['pickupAddress'] ?? '', $row['pickupState'] ?? '', $row['pickupPostcode'] ?? '');
        } else {
            $displayLocation = buildAddress($row['pickupAddress'] ?? '', $row['pickupState'] ?? '', $row['pickupPostcode'] ?? '');
        }
    } else {
        $displayLocation = 'Waiting for assignment';
    }

    $activeCollectors[] = [
        'id' => 'C' . str_pad((string)$row['collectorID'], 3, '0', STR_PAD_LEFT),
        'collectorID' => (int)$row['collectorID'],
        'name' => $row['fullname'],
        'vehicle' => !empty($row['plateNum']) ? trim(($row['vehicleType'] ?? '') . ' ' . ($row['plateNum'] ?? '')) : 'No vehicle assigned',
        'status' => $hasActiveJob ? 'busy' : 'online',
        'jobId' => $hasActiveJob ? ('JOB' . str_pad((string)$row['jobID'], 3, '0', STR_PAD_LEFT)) : null,
        'jobID_numeric' => (int)$row['jobID'],
        'requestID' => !empty($row['requestID']) ? (int)$row['requestID'] : null,
        'jobStatus' => $row['jobStatus'] ?? '',
        'scheduledDate' => $row['scheduledDate'] ?? null,
        'scheduledTime' => $row['scheduledTime'] ?? null,
        'pickupAddress' => $pickupFullAddress,
        'pickupLabel' => buildAddress($row['pickupAddress'] ?? '', $row['pickupState'] ?? '', $row['pickupPostcode'] ?? ''),
        'pickupLat' => $pickupCoords ? $pickupCoords['lat'] : null,
        'pickupLng' => $pickupCoords ? $pickupCoords['lng'] : null,
        'centreName' => $row['centreName'] ?? '',
        'centreAddress' => $centreFullAddress,
        'centreLabel' => buildAddress($row['centreAddress'] ?? '', $row['centreState'] ?? '', $row['centrePostcode'] ?? ''),
        'centreLat' => $centreCoords ? $centreCoords['lat'] : null,
        'centreLng' => $centreCoords ? $centreCoords['lng'] : null,
        'currentRoad' => $hasActiveJob ? ('Pickup: ' . buildAddress($row['pickupAddress'] ?? '', $row['pickupState'] ?? '', $row['pickupPostcode'] ?? '')) : 'Waiting for assignment',
        'displayLocation' => $displayLocation,
        'routeStartLat' => $routeStartLat,
        'routeStartLng' => $routeStartLng,
        'routeEndLat' => $routeEndLat,
        'routeEndLng' => $routeEndLng,
        'progressText' => $progressText
    ];
}

// Quick stats
$completedTodayRow = queryOne($conn, "
    SELECT COUNT(*) AS totalCompleted
    FROM tbljob
    WHERE status = 'Completed'
      AND DATE(completedAt) = CURDATE()
");
$completedToday = (int)($completedTodayRow['totalCompleted'] ?? 0);

$avgResponseRow = queryOne($conn, "
    SELECT
        ROUND(AVG(TIMESTAMPDIFF(
            MINUTE,
            r.createdAt,
            CONCAT(j.scheduledDate, ' ', j.scheduledTime)
        ))) AS avgMinutes
    FROM tbljob j
    INNER JOIN tblcollection_request r ON r.requestID = j.requestID
");
$avgResponseMinutes = (int)($avgResponseRow['avgMinutes'] ?? 0);
$avgResponseText = $avgResponseMinutes > 0 ? $avgResponseMinutes . 'min' : '0min';

$totalDistanceRounded = round($totalDistance, 1);

$delayedLookup = [];
foreach ($delayedJobs as $job) {
    $delayedLookup[$job['id']] = $job;
}

$jsData = [
    'delayedJobs' => $delayedJobs,
    'activeCollectors' => $activeCollectors,
    'quickStats' => [
        'completedToday' => $completedToday,
        'avgResponse' => $avgResponseText,
        'totalDistance' => $totalDistanceRounded
    ],
    'delayedLookup' => $delayedLookup
];

if (isset($_GET['fetch_data']) && $_GET['fetch_data'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($jsData);
    exit;
}

// issue submission (unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_issue'])) {
    header('Content-Type: application/json');
    
    // validate input
    $jobId = isset($_POST['jobId']) ? intval($_POST['jobId']) : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $issueType = isset($_POST['issueType']) ? trim($_POST['issueType']) : '';
    $severity = isset($_POST['severity']) ? trim($_POST['severity']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $reportedBy = $_SESSION['userID'];
    
    // Validation
    $errors = [];
    
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($severity)) $errors[] = 'Severity is required';
    if (empty($description)) $errors[] = 'Description is required';
    
    // Validate Job ID and get requestID
    $requestId = 0;
    if ($jobId <= 0) {
        $errors[] = 'Invalid Job ID';
    } else {
        $checkJobSql = "SELECT jobID, requestID, status FROM tbljob WHERE jobID = ?";
        $checkStmt = mysqli_prepare($conn, $checkJobSql);
        mysqli_stmt_bind_param($checkStmt, "i", $jobId);
        mysqli_stmt_execute($checkStmt);
        $jobResult = mysqli_stmt_get_result($checkStmt);
        $jobExists = mysqli_fetch_assoc($jobResult);
        mysqli_stmt_close($checkStmt);
        
        if (!$jobExists) {
            $errors[] = 'Job ID does not exist in the system';
        } else {
            if (in_array($jobExists['status'], ['Completed', 'Cancelled', 'Rejected'])) {
                $errors[] = 'Cannot report issue for ' . strtolower($jobExists['status']) . ' jobs';
            }
            // Get the requestID from the job
            $requestId = $jobExists['requestID'];
        }
    }
    
    // "Other" issue type
    if (empty($issueType)) {
        $errors[] = 'Issue type is required';
    }
    $storedIssueType = $issueType;
    
    if (empty($errors)) {
        // Insert with requestID from the job
        $sql = "INSERT INTO tblissue (jobID, requestID, subject, issueType, severity, description, reportedBy, reportedAt, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Open')";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iissssi", $jobId, $requestId, $subject, $storedIssueType, $severity, $description, $reportedBy);
            
            if (mysqli_stmt_execute($stmt)) {
                $issueId = mysqli_insert_id($conn);
                echo json_encode([
                    'success' => true,
                    'message' => 'Issue reported successfully',
                    'issueId' => $issueId
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_stmt_error($stmt)]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Jobs - AfterVolt</title>

    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="../../style/style.css">
    <link rel="stylesheet" href="../../style/aCollectionJobs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <header>
        <section class="c-logo-section">
            <a href="../../html/admin/aHome.php" class="c-logo-link">
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

                    <a href="../../html/admin/aHome.php">Home</a>
                    <a href="../../html/admin/aRequests.php">Requests</a>
                    <a href="../../html/admin/aJobs.php">Jobs</a>
                    <a href="../../html/admin/aIssue.php">Issue</a>
                    <a href="../../html/admin/aOperations.php">Operations</a>
                    <a href="../../html/admin/aReport.php">Report</a>
                </div>
            </div>
        </nav>

        <nav class="c-navbar-desktop">
            <a href="../../html/admin/aHome.php">Home</a>
            <a href="../../html/admin/aRequests.php">Requests</a>
            <a href="../../html/admin/aJobs.php">Jobs</a>
            <a href="../../html/admin/aIssue.php">Issue</a>
            <a href="../../html/admin/aOperations.php">Operations</a>
            <a href="../../html/admin/aReport.php">Report</a>
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
    <div class="page-container">
        <div class="ops-header">
            <h1>Collection Jobs</h1>
        </div>
        
        <div style="margin-bottom: 0.7rem; margin-top: -1rem;">
            <button onclick="goBackToJobs()" style="background: none; border: none; color: var(--MainBlue); cursor: pointer; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0;">
                <i class="fas fa-arrow-left"></i>
                Back to Jobs
            </button>
        </div>

        <div class="dashboard-grid">
            <div class="jobs-column">
                <div class="delayed-panel" id="delayedPanel">
                    <div class="panel-header warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Delayed Jobs</h3>
                        <span class="panel-badge" id="panelDelayedCount">0</span>
                    </div>
                    <div class="panel-content" id="delayedList"></div>
                </div>

                <div class="active-collectors-box">
                    <div class="box-header">
                        <h3><i class="fas fa-users"></i> Active Collections</h3>
                        <span class="collector-count" id="activeCollectorCount">0</span>
                    </div>
                    <div class="collector-list" id="activeCollectorList"></div>
                </div>
            </div>

            <div class="map-column">
                <div class="map-container" id="mapContainer">
                    <div class="map-placeholder" id="mapPlaceholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>Loading map...</p>
                    </div>

                    <div id="actualMap" style="height: 100%; width: 100%; display: none;"></div>

                    <div class="map-controls">
                        <button class="map-control-btn" onclick="centerMapOnAll()">
                            <i class="fas fa-location-arrow"></i>
                        </button>
                        <button class="map-control-btn" onclick="toggleMapLayers()">
                            <i class="fas fa-layer-group"></i>
                        </button>
                        <button class="map-control-btn" onclick="zoomToFit()">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>

                    <div class="route-info-box" id="routeInfoBox" style="display: none;">
                        <div><strong id="routeCollectorName">Collector</strong></div>
                        <div id="routeCurrentLocation">Current location: -</div>
                        <div id="routeEta">ETA to collection centre: -</div>
                    </div>

                    <div id="etaBubble" class="eta-bubble" style="display: none;">
                        <span class="eta-dot"></span>
                        <span id="etaBubbleText">ETA: -</span>
                    </div>
                </div>

                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value" id="completedToday">0</span>
                            <span class="stat-label">Completed Today</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value" id="avgResponse">0</span>
                            <span class="stat-label">Avg Response</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value" id="totalDistance">0</span>
                            <span class="stat-label">Total KM</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<hr>

<footer>
    <section class="c-footer-info-section">
        <a href="../../html/admin/aHome.php">
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
        <div>
            <b>Management</b><br>
            <a href="../../html/admin/aRequests.php">Collection Requests</a><br>
            <a href="../../html/admin/aJobs.php">Collection Jobs</a><br>
            <a href="../../html/admin/aIssue.php">Issue</a><br>
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
    </section>
</footer>

<!-- View Job Details Modal -->
<div class="modal-overlay" id="jobDetailsModal">
    <div class="modal-box modal-box-lg">
        <div class="modal-header">
            <h3>Job Details</h3>
            <button class="modal-close" onclick="closeJobDetailsModal()">&times;</button>
        </div>

        <div class="job-details-grid">
            <div class="detail-card">
                <h4>Job Information</h4>
                <p><strong>Job ID:</strong> <span id="detailsJobId"></span></p>
                <p><strong>Status:</strong> <span id="detailsStatus"></span></p>
                <p><strong>Scheduled Time:</strong> <span id="detailsTime"></span></p>
                <p><strong>Location:</strong> <span id="detailsLocation"></span></p>
            </div>

            <div class="detail-card">
                <h4>Collector Information</h4>
                <p><strong>Collector:</strong> <span id="detailsCollector"></span></p>
                <p><strong>Vehicle:</strong> <span id="detailsVehicle"></span></p>
                <p><strong>Reason:</strong> <span id="detailsReason"></span></p>
            </div>

            <div class="detail-card detail-card-full">
                <h4>Admin Action Summary</h4>
                <textarea id="detailsAdminNotes" rows="4" placeholder="Add internal admin notes here"></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="closeJobDetailsModal()">Close</button>
        </div>
    </div>
</div>

<!-- Report Issue Modal -->
<div class="report-issue-modal" id="reportIssueModal">
    <div class="report-issue-content">
        <div class="report-issue-header">
            <h3><i class="fas fa-flag"></i> Report Issue</h3>
            <button type="button" class="report-issue-close" id="closeReportIssueModal">&times;</button>
        </div>

        <form id="reportIssueForm">
            <div class="report-issue-body">
                <div class="issue-form-group">
                    <label for="issueJobId">
                        <i class="fas fa-briefcase"></i> Job ID
                    </label>
                    <input type="text" id="issueJobId" name="jobId" readonly>
                </div>

                <div class="issue-form-group">
                    <label for="issueRequestId">
                        <i class="fas fa-file-alt"></i> Request ID
                    </label>
                    <input type="text" id="issueRequestId" name="requestId" readonly>
                </div>

                <div class="issue-form-group">
                    <label for="issueSubject">
                        <i class="fas fa-heading"></i> Subject <span class="required">*</span>
                    </label>
                    <input type="text" id="issueSubject" name="subject" placeholder="Brief summary of the issue" required>
                </div>

                <div class="issue-form-group">
                    <label for="issueType">
                        <i class="fas fa-exclamation-circle"></i> Issue Type <span class="required">*</span>
                    </label>
                    <select id="issueType" name="issueType" required>
                        <option value="">-- Select Issue Type --</option>
                        <option value="Operational">Operational</option>
                        <option value="Vehicle">Vehicle</option>
                        <option value="Safety">Safety</option>
                        <option value="Technical">Technical</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="issue-form-group">
                    <label>
                        <i class="fas fa-signal"></i> Severity <span class="required">*</span>
                    </label>
                    <div class="issue-priority">
                        <label class="priority-option low">
                            <input type="radio" name="severity" value="Low" required>
                            Low
                        </label>
                        <label class="priority-option medium">
                            <input type="radio" name="severity" value="Medium" required>
                            Medium
                        </label>
                        <label class="priority-option high">
                            <input type="radio" name="severity" value="High" required>
                            High
                        </label>
                        <label class="priority-option critical">
                            <input type="radio" name="severity" value="Critical" required>
                            Critical
                        </label>
                    </div>
                </div>

                <div class="issue-form-group">
                    <label for="issueDescription">
                        <i class="fas fa-pen"></i> Description <span class="required">*</span>
                    </label>
                    <textarea id="issueDescription" name="description" placeholder="Describe the issue in detail..." required></textarea>
                </div>
            </div>

            <div class="report-issue-footer">
                <button type="button" class="btn-secondary" id="cancelReportIssueBtn">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Popup Modal -->
<div class="success-popup-modal" id="successPopupModal">
    <div class="success-popup-content">
        <div class="success-popup-header">
            <h3><i class="fas fa-check-circle" style="color: #2ecc71;"></i> Issue Submitted Successfully!</h3>
            <button class="success-popup-close" id="closeSuccessPopup">&times;</button>
        </div>
        <div class="success-popup-body">
        </div>
        <div class="success-popup-footer">
            <button class="btn-primary" id="goToIssuesBtn">
                <i class="fas fa-exclamation-circle"></i> Go to Issues
            </button>
        </div>
    </div>
</div>

<script src="../../javascript/mainScript.js"></script>
<script src="../../javascript/aCollectionJobs.js?v=<?php echo time(); ?>"></script>
<script>
    window.collectionJobsData = <?php echo json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

</body>
</html>