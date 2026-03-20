<?php
session_start();
include("../../php/dbConn.php");
 
// // check if user is logged in
// include("../../php/sessionCheck.php");
 
function sanitize($val) {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
 
$validStates = ['Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang',
                'Perak','Perlis','Penang','Selangor','Terengganu','Kuala Lumpur','Putrajaya'];
 
$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg   = $_SESSION['errorMsg']   ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$conn) {
        $_SESSION['errorMsg'] = 'Database connection failed.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
 
    $action = $_POST['action'] ?? '';
 
    // edit
    if ($action === 'edit') {
        $providerID = (int)($_POST['providerID'] ?? 0);
        $fullname   = trim($_POST['fullname']  ?? '');
        $username   = trim($_POST['username']  ?? '');
        $email      = trim($_POST['email']     ?? '');
        $phone      = trim($_POST['phone']     ?? '');
        $state      = trim($_POST['state']     ?? '');
        $postcode   = trim($_POST['postcode']  ?? '');
        $address    = trim($_POST['address']   ?? '');
        $suspended  = isset($_POST['suspended']) ? (int)(bool)$_POST['suspended'] : 0;
 
        $errors = [];
 
        if ($providerID <= 0) { 
            $errors[] = 'Invalid provider record.'; 
        }
 
        if ($fullname === '') {
            $errors[] = 'Full name is required.';
        } elseif (strlen($fullname) > 255) {
            $errors[] = 'Full name must be 255 characters or fewer.';
        }
 
        if ($username === '') {
            $errors[] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,100}$/', $username)) {
            $errors[] = 'Username must be 3–100 characters (letters, numbers, underscores).';
        }
 
        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (strlen($email) > 255) {
            $errors[] = 'Email must be 255 characters or fewer.';
        }
 
        if ($phone === '') {
            $errors[] = 'Phone number is required.';
        } elseif (!preg_match('/^0[0-9]{9,10}$/', $phone)) {
            $errors[] = 'Enter a valid Malaysian phone number (e.g. 0123456789).';
        }
 
        if (!in_array($state, $validStates)) {
            $errors[] = 'Please select a valid state.';
        }
 
        if ($postcode === '') {
            $errors[] = 'Postcode is required.';
        } elseif (!preg_match('/^\d{5}$/', $postcode)) {
            $errors[] = 'Postcode must be exactly 5 digits.';
        }
 
        if ($address === '') {
            $errors[] = 'Address is required.';
        } elseif (strlen($address) > 255) {
            $errors[] = 'Address must be 255 characters or fewer.';
        }
 
        // Duplicate checks (excluding current record)
        if (empty($errors)) {
            $checkUser = $conn->prepare("SELECT userID FROM tblusers WHERE username = ? AND userID != ?");
            $checkUser->bind_param('si', $username, $providerID);
            $checkUser->execute();
            if ($checkUser->get_result()->num_rows > 0) {
                $errors[] = 'Another user with this username already exists.';
            }
 
            $checkEmail = $conn->prepare("SELECT userID FROM tblusers WHERE email = ? AND userID != ?");
            $checkEmail->bind_param('si', $email, $providerID);
            $checkEmail->execute();
            if ($checkEmail->get_result()->num_rows > 0) {
                $errors[] = 'Another user with this email already exists.';
            }
 
            $checkPhone = $conn->prepare("SELECT userID FROM tblusers WHERE phone = ? AND userID != ?");
            $checkPhone->bind_param('si', $phone, $providerID);
            $checkPhone->execute();
            if ($checkPhone->get_result()->num_rows > 0) {
                $errors[] = 'Another user with this phone number already exists.';
            }
        }
 
        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                // Update tblusers (providerID = userID)
                $updateUser = $conn->prepare(
                    "UPDATE tblusers SET username=?, fullname=?, email=?, phone=? WHERE userID=?"
                );
                $updateUser->bind_param('ssssi', $username, $fullname, $email, $phone, $providerID);
                $updateUser->execute();
 
                // Update tblprovider
                $updateProvider = $conn->prepare("UPDATE tblprovider SET address=?, state=?, postcode=?, suspended=? WHERE providerID=?");
                $updateProvider->bind_param('sssii', $address, $state, $postcode, $suspended, $providerID);
                $updateProvider->execute();
 
                $conn->commit();
                $_SESSION['successMsg'] = 'Provider updated successfully.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $_SESSION['errorMsg'] = implode(' ', $errors);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
 
    // delete
    } elseif ($action === 'delete') {
        $providerID = (int)($_POST['providerID'] ?? 0);
 
        if ($providerID <= 0) {
            $_SESSION['errorMsg'] = 'Invalid provider record.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Check for any requests
        $checkReqs = $conn->prepare(
            "SELECT COUNT(*) AS total FROM tblcollection_request
             WHERE providerID = ?"
        );
        $checkReqs->bind_param('i', $providerID);
        $checkReqs->execute();
        $reqRow = $checkReqs->get_result()->fetch_assoc();
 
        if ($reqRow['total'] > 0) {
            $_SESSION['errorMsg'] = 'Cannot delete provider with existing collection requests.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
 
        // Safe to delete
        $conn->begin_transaction();
        try {
            $deleteUser = $conn->prepare("DELETE FROM tblusers WHERE userID = ?");
            $deleteUser->bind_param('i', $providerID);
            $deleteUser->execute();
 
            if ($deleteUser->affected_rows > 0) {
                $conn->commit();
                $_SESSION['successMsg'] = 'Provider deleted successfully.';
            } else {
                $conn->rollback();
                $_SESSION['errorMsg'] = 'Provider not found or could not be deleted.';
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['errorMsg'] = 'Database error: ' . sanitize($e->getMessage());
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}
 
// search
$search      = trim($_GET['search'] ?? '');
$searchParam = '%' . $search . '%';
 
$sql = "SELECT p.providerID, p.address, p.state, p.postcode, p.point, p.suspended,
               u.username, u.fullname, u.email, u.phone,
               DATE_FORMAT(u.createdAt, '%d/%m/%Y') AS createdAt,
               DATE_FORMAT(u.lastLogin,  '%d/%m/%Y') AS lastLogin,
               (SELECT COUNT(*) FROM tblcollection_request r WHERE r.providerID = p.providerID) AS reqCount
        FROM tblprovider p
        JOIN tblusers u ON p.providerID = u.userID
        WHERE u.fullname LIKE ?
           OR u.username LIKE ?
           OR u.email    LIKE ?
           OR u.phone    LIKE ?
        ORDER BY p.providerID";
 
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssss', $searchParam, $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
$providers = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['providerID'];
    $row['suspended'] = (int)$row['suspended']; // ensure int 0/1 for JS
    $providers[] = $row;
}
 
$totalProviders = count($providers);
$providersJson  = json_encode($providers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$statesJson     = json_encode($validStates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Providers - AfterVolt</title>
    <link rel="icon" type="image/png" href="../../assets/images/bolt-lightning-icon.svg">

    <link rel="stylesheet" href="../../style/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        .page-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .back-button:hover {
            background: var(--sec-bg-color);
            border-color: var(--MainBlue);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .user-count {
            color: var(--Gray);
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .dark-mode .user-count {
            color: var(--BlueGray);
        }

        .search-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            flex: 1;
        }

        .search-input {
            display: block;
            width: 100%;
            border: 1px solid var(--Gray);
            border-radius: 6px;
            font-size: 1rem;
            color: var(--text-color);
            background-color: var(--bg-color);
            flex: 1;
            padding: 0.875rem 1.25rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border: 1px solid var(--MainBlue);
            box-shadow: 0 0 0 3px var(--LightBlue);
            outline: none;
        }

        .dark-mode .search-input:focus {
            box-shadow: 0 0 0 3px var(--DarkerBlue);
        }

        .search-input::placeholder {
            color: var(--Gray);
        }

        .search-btn {
            padding: 0.5rem 1.5rem;
            background: var(--MainBlue);
            color: var(--White);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .clear-btn {
            padding: 0.5rem 1.5rem;
            background: var(--Gray);
            color: var(--White);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }

        .clear-btn:hover {
            background: var(--BlueGray);
        }

        .users-table-container {
            background: var(--bg-color);
            border-radius: 16px;
            box-shadow: 0 4px 12px var(--shadow-color);
            overflow: hidden;
        }

        .dark-mode .users-table-container {
            box-shadow: 0 4px 8px var(--BlueGray);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table thead {
            background: var(--LightBlue);
            color: var(--text-color);
        }

        .dark-mode .users-table thead {
            background: var(--LowMainBlue);
        }

        .users-table th {
            padding: 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .users-table td {
            text-align: center;
            padding: 1.25rem;
            border-bottom: 1px solid var(--BlueGray);
            color: var(--text-color);
        }

        .users-table .left {
            text-align: left;
        }

        .users-table tbody tr {
            transition: all 0.2s ease;
        }

        .users-table tbody tr:hover {
            background: var(--shadow-color);
        }

        .dark-mode .users-table tbody tr:hover {
            background: var(--Gray);
        }

        .users-table tbody tr.row-suspended { 
            background: rgba(220, 53, 69, 0.3); 
        }
        .users-table tbody tr.row-suspended:hover { 
            background: rgba(220, 53, 69, 0.5); 
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-details h4 {
            margin: 0;
            font-weight: 600;
            color: var(--text-color);
        }

        .user-details p {
            margin: 0.25rem 0 0;
            font-size: 0.85rem;
            color: var(--BlueGray);
        }

        .action-btns {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-btn {
            width: 2.25rem;
            height: 2.25rem;
            background: var(--MainBlue);
            color: var(--White);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
        }

        .action-btn:hover {
            background: var(--DarkBlue);
            transform: translateY(-2px);
        }

        .action-btn img {
            width: 1rem;
            height: 1rem;
        }

        .dark-mode .search-btn,
        .dark-mode .action-btn {
            background-color: var(--DarkerMainBlue);
        }

        .search-btn:hover,
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--BlueGray);
            transition: all 0.3s ease;
        }

        .dark-mode .search-btn:hover,
        .dark-mode .action-btn:hover {
            box-shadow: 0 4px 12px var(--Gray);
        }

        .action-btn.delete-btn {
            background: red;
        }

        .points-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            background: var(--LightBlue);
            color: var(--MainBlue);
        }
 
        .dark-mode .points-badge {
            background: var(--LowMainBlue);
            color: var(--White);
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: var(--DarkBlue);
            color: var(--White);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            z-index: 3000;
            opacity: 0;
            transform: translateY(8px);
            transition: opacity 0.25s, transform 0.25s;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: none;
        }

        .toast.success {
            background: #28a745;
            color: var(--White);
        }

        .toast.error {
            background: #dc3545;
            color: var(--White);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--Gray);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--bg-color);
            border: 1px solid var(--BlueGray);
            border-radius: 20px;
            padding: 2.5rem;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .modal h2 {
            font-size: 1.6rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-right: 2rem;
        }

        .modal h2.danger { 
            color: red; 
        }

        .modal-small { 
            max-width: 480px; 
        }

        .modal-medium { 
            max-width: 720px; 
        }

        .view-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .view-field label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--BlueGray);
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .view-field span {
            font-size: 1rem;
            color: var(--text-color);
            font-weight: 500;
        }

        .view-field.full { 
            grid-column: 1 / -1; 
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-field { 
            display: flex; 
            flex-direction: column; 
            gap: 0.4rem; 
        }

        .form-field.full { 
            grid-column: 1 / -1; 
        }

        .form-field label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--BlueGray);
        }

        .form-field input,
        .form-field select,
        .form-field textarea {
            padding: 0.75rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            font-size: 0.95rem;
            color: var(--text-color);
            background: var(--bg-color);
            transition: border 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 3px var(--LightBlue);
            outline: none;
        }

        .form-field textarea { 
            resize: vertical; 
            min-height: 80px; 
        }

        .form-field select { 
            padding-left: 0.5rem; 
        }

        .field-error { 
            font-size: 0.8rem; 
            color: red; 
            margin-top: 0.2rem; 
            display: none; 
        }

        .field-error.show { 
            display: block; 
        }

        .delete-info-box {
            background: var(--LightBlue);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-top: 1rem;
            margin-bottom: 1.25rem;
        }

        .dark-mode .delete-info-box { 
            background: var(--LowMainBlue); 
        }

        .delete-info-box strong { 
            color: var(--MainBlue); 
        }

        .delete-info-box .info-name { 
            color: var(--text-color); 
            font-weight: 600; 
        }

        .modal-buttons {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 0.5rem;
        }

        .btn-modal {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .btn-cancel {
            background: var(--BlueGray);
            color: var(--text-color);
        }

        .btn-cancel:hover { 
            background: var(--Gray); 
        }

        .btn-confirm-delete {
            background: red;
            color: var(--White);
        }

        .btn-confirm-delete:hover {
            background: red;
            transform: translateY(-2px);
        }

        .btn-save {
            background: var(--MainBlue);
            color: var(--White);
        }

        .btn-save:hover {
            background: var(--DarkerBlue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--BlueGray);
        }

        .dark-mode .btn-save { 
            background: var(--DarkerMainBlue); 
        }

        .modal-close-btn {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            width: 2rem;
            height: 2rem;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .modal-close-btn:hover {
            opacity: 0.5;
        }

        .modal-close-btn img {
            width: 1.25rem;
            height: 1.25rem;
        }

        .dark-icon { 
            display: none; 
        }

        .dark-mode .light-icon { 
            display: none; 
        }

        .dark-mode .dark-icon { 
            display: inline; 
        }

        .delete-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none; 
            box-shadow: none;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-active {
            background: hsl(145, 50%, 88%); 
            color: hsl(145, 60%, 28%); 
        }

        .status-suspended {
            background: hsl(0,   70%, 90%); 
            color: hsl(0,   70%, 35%); 
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .users-table {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }
            
            .search-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                flex-wrap: wrap;
            }

            .search-form .search-input {
                flex: 1 1 100%;
            }

            .search-form .search-btn,
            .search-form .clear-btn {
                flex: 1;
                text-align: center;
            }

            .clear-btn {
                display: block;
            }

            .users-table-container {
                overflow-x: auto;
            }

            .users-table {
                min-width: 800px;
            }

            .view-grid, .form-grid { 
                grid-template-columns: 1fr; 
            }

            .view-field.full, .form-field.full { 
                grid-column: 1; 
            }

            .modal { 
                padding: 1.5rem; 
            }
        }
    </style>

</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>
    
    <!-- Logo + Name & Navbar -->
    <header>
        <!-- Logo + Name -->
        <section class="c-logo-section">
            <a href="../../html/admin/aHome.html" class="c-logo-link">
                <img src="../../assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <!-- Menu Links -->

        <!-- Menu Links Mobile -->
        <nav class="c-navbar-side">
            <img src="../../assets/images/icon-menu.svg" alt="icon-menu" onclick="showMenu()" class="c-icon-btn" id="menuBtn">
            <div id="sidebarNav" class="c-navbar-side-menu">
                
                <img src="../../assets/images/icon-menu-close.svg" alt="icon-menu-close" onclick="hideMenu()" class="close-btn"  id="closeBtn">
                <div class="c-navbar-side-items">
                    <section class="c-navbar-side-more">
                        <button id="themeToggleMobile">
                            <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon" >
                        </button>
                        <a href="../../html/common/Setting.html">
                            <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImgM">
                        </a>
                    </section>

                    <a href="../../html/admin/aHome.html">Home</a>
                    <a href="../../html/admin/aRequests.html">Requests</a><br>
                    <a href="../../html/admin/aJobs.html">Jobs</a><br>
                    <a href="../../html/admin/aIssue.html">Issue</a><br>
                    <a href="../../html/admin/aOperations.html">Operations</a><br>
                    <a href="../../html/admin/aReport.html">Report</a>
                </div>
            </div>

        </nav>

        <!-- Menu Links Desktop + Tablet -->
        <nav class="c-navbar-desktop">
            <a href="../../html/admin/aHome.html">Home</a>
            <a href="../../html/admin/aRequests.html">Requests</a><br>
            <a href="../../html/admin/aJobs.html">Jobs</a><br>
            <a href="../../html/admin/aIssue.html">Issue</a><br>
            <a href="../../html/admin/aOperations.html">Operations</a><br>
            <a href="../../html/admin/aReport.html">Report</a>
        </nav>          
        <section class="c-navbar-more">
            <button id="themeToggleDesktop">
                <img src="../../assets/images/light-mode-icon.svg" alt="Light Mode Icon" >
            </button>
            <a href="../../html/common/Setting.html">
                <img src="../../assets/images/setting-light.svg" alt="Settings" id="settingImg">
            </a>
        </section>
        
    </header>
    <hr>

    <!-- Main Content -->
    <main>
        <div class="page-container">
            <a href="../../html/admin/aHome.html" class="back-button">
                ← Back to Home
            </a>

            <div class="page-header">
                <h1>Manage Providers</h1>
                <p class="user-count">Total Providers: <?php echo $totalProviders; ?></p>
            </div>

            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="search" class="search-input"
                        placeholder="Search by name, username, email, or phone..."
                        value="<?php echo sanitize($search); ?>">
                    <button type="submit" class="search-btn">Search</button>
                    <?php if ($search): ?>
                        <a href="?" class="clear-btn">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="left">Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>State</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($providers)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <h3>No Providers Found</h3>
                                        <p><?php echo $search ? 'Try a different search term.' : 'No providers registered yet.'; ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($providers as $i => $p): ?>
                                <?php $isSuspended = (int)$p['suspended'] === 1;?>
                                <tr class="<?php echo $isSuspended ? 'row-suspended' : ''; ?>">
                                    <td>#<?php echo $p['providerID']; ?></td>
                                    <td class="left">
                                        <div class="user-info">
                                            <div class="user-details">
                                                <h4><?php echo sanitize($p['fullname']); ?></h4>
                                                <p>@<?php echo sanitize($p['username']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo sanitize($p['email']); ?></td>
                                    <td><?php echo sanitize($p['phone']); ?></td>
                                    <td><?php echo sanitize($p['state']); ?></td>
                                    <td><span class="points-badge"><?php echo (int)$p['point']; ?> pts</span></td>
                                    <td> 
                                        <?php if ($isSuspended): ?>
                                            <span class="status-badge status-suspended">Suspended</span>
                                        <?php else: ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="action-btn" onclick="openViewModal(<?php echo $i ?>)" title="View">
                                                <img src="../../assets/images/view-icon-white.svg" alt="View">
                                            </button>
                                            <button class="action-btn" onclick="openEditModal(<?php echo $i ?>)" title="Edit">
                                                <img src="../../assets/images/edit-icon-white.svg" alt="Edit">
                                            </button>
                                            <?php $canDelete = ($p['reqCount'] == 0); ?>
                                            <?php if (!$canDelete): ?>
                                                <button class="action-btn delete-btn delete-disabled" disabled
                                                    title="Cannot delete: Provider has existing request records">
                                                    <img src="../../assets/images/delete-icon-white.svg" alt="Delete">
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn delete-btn" onclick="openDeleteModal(<?php echo $i ?>)" title="Delete">
                                                    <img src="../../assets/images/delete-icon-white.svg" alt="Delete">
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <hr>
    <!-- Footer -->
    <footer>
        <!-- Column 1 -->
        <section class="c-footer-info-section">
            <a href="../../html/admin/aHome.html">
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
                <b>Management</b><br>
                <a href="../../html/admin/aRequests.html">Collection Requests</a><br>
                <a href="../../html/admin/aJobs.html">Collection Jobs</a><br>
                <a href="../../html/admin/aIssue.html">Issue</a><br>
            </div>
            <div>
                <b>System Operation</b><br>
                <a href="../../html/admin/aProviders.php">Providers</a><br>
                <a href="../../html/admin/aCollectors.php">Collectors</a><br>
                <a href="../../html/admin/aVehicles.php">Vehicles</a><br>
                <a href="../../html/admin/aCentres.php">Collection Centres</a><br>
                <a href="../../html/admin/aItemProcessing.html">Item Processing</a>
            </div>
            <div>
                <b>Proxy</b><br>
                <a href="../../html/common/Profile.html">Edit Profile</a><br>
                <a href="../../html/common/Setting.html">Setting</a>
            </div>
        </section>
    </footer>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <!-- View Modal -->
     <div class="modal-overlay" id="viewModal">
        <div class="modal modal-medium">
            <button class="modal-close-btn" onclick="closeModal('viewModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Provider Details</h2>
            <div class="view-grid">
                <div class="view-field">
                    <label>Full Name</label>
                    <span id="view-fullname">—</span>
                </div>
                <div class="view-field">
                    <label>Username</label>
                    <span id="view-username">—</span>
                </div>
                <div class="view-field">
                    <label>Email</label>
                    <span id="view-email">—</span>
                </div>
                <div class="view-field">
                    <label>Phone</label>
                    <span id="view-phone">—</span>
                </div>
                <div class="view-field">
                    <label>State</label>
                    <span id="view-state">—</span>
                </div>
                <div class="view-field">
                    <label>Postcode</label>
                    <span id="view-postcode">—</span>
                </div>
                <div class="view-field full">
                    <label>Address</label>
                    <span id="view-address">—</span>
                </div>
                <div class="view-field full" id="view-points-field">
                    <label>Points</label>
                    <span id="view-points">—</span>
                </div>
                <div class="view-field" id="view-suspended-row" style="display:none">
                    <label>Account Status</label>
                    <span id="view-suspended" class="status-badge status-suspended">Suspended</span>
                </div>
                <div class="view-field">
                    <label>Created At</label>
                    <span id="view-createdAt">—</span>
                </div>
                <div class="view-field">
                    <label>Last Login</label>
                    <span id="view-lastLogin">—</span>
                </div>
            </div>
            <div class="modal-buttons">
                <button class="btn-modal btn-cancel" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

     <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <button class="modal-close-btn" onclick="closeModal('editModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2>Edit Provider</h2>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit-providerID" name="providerID">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="edit-fullname">Full Name</label>
                        <input type="text" id="edit-fullname" name="fullname" required>
                        <span class="field-error" id="err-edit-fullname"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-username">Username</label>
                        <input type="text" id="edit-username" name="username" required>
                        <span class="field-error" id="err-edit-username"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-email">Email</label>
                        <input type="email" id="edit-email" name="email" required>
                        <span class="field-error" id="err-edit-email"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-phone">Phone</label>
                        <input type="text" id="edit-phone" name="phone" required>
                        <span class="field-error" id="err-edit-phone"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-state">State</label>
                        <select id="edit-state" name="state">
                            <option value="">-- Select State --</option>
                            <?php foreach ($validStates as $s): ?>
                                <option value="<?php echo $s ?>"><?php echo $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-error" id="err-edit-state"></span>
                    </div>
                    <div class="form-field">
                        <label for="edit-postcode">Postcode</label>
                        <input type="text" id="edit-postcode" name="postcode">
                        <span class="field-error" id="err-edit-postcode"></span>
                    </div>
                    <div class="form-field full">
                        <label for="edit-address">Address</label>
                        <textarea id="edit-address" name="address"></textarea>
                        <span class="field-error" id="err-edit-address"></span>
                    </div>
                    <div class="form-field" id="edit-suspended-field">
                        <label for="edit-suspended">Account Status</label>
                        <select id="edit-suspended" name="suspended">
                            <option value="0">Active</option>
                            <option value="1">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
 
    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal modal-small">
            <button class="modal-close-btn" onclick="closeModal('deleteModal')">
                <img src="../../assets/images/icon-menu-close.svg" class="light-icon" alt="Close">
                <img src="../../assets/images/icon-menu-close-dark.png" class="dark-icon" alt="Close">
            </button>
            <h2 class="danger">Confirm Deletion</h2>
            <p>Are you sure you want to delete this provider? This action <strong>cannot be undone</strong>.</p>
            <div class="delete-info-box">
                <strong>Provider: </strong><span class="info-name" id="delete-name">—</span><br>
                <strong>Username: </strong><span class="info-name" id="delete-username">—</span>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete-providerID" name="providerID">
                <div class="modal-buttons">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn-modal btn-confirm-delete">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../javascript/mainScript.js"></script>
    <script>
        const providers  = <?php echo $providersJson; ?>;
        const successMsg = <?php echo json_encode($successMsg); ?>;
        const errorMsg   = <?php echo json_encode($errorMsg); ?>;

        function showToast(msg, type) {
            const t = document.getElementById('toast');
            t.className = 'toast ' + type;
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }
 
        if (successMsg) showToast(successMsg, 'success');
        if (errorMsg)   showToast(errorMsg,   'error');

        function openModal(id) {
            const modalElement = document.getElementById(id);
            modalElement.classList.add('active');
            document.body.classList.add('stopScroll');
            const modalContent = modalElement.querySelector('.modal');
            if (modalContent) {
                modalContent.scrollTop = 0;
            }
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.classList.remove('stopScroll');
        }
        
        // View
        function openViewModal(index) {
            const p = providers[index];
            document.getElementById('view-fullname').textContent  = p.fullname;
            document.getElementById('view-username').textContent  = '@' + p.username;
            document.getElementById('view-email').textContent     = p.email;
            document.getElementById('view-phone').textContent     = p.phone;
            document.getElementById('view-state').textContent     = p.state;
            document.getElementById('view-postcode').textContent  = p.postcode;
            document.getElementById('view-address').textContent   = p.address;
            document.getElementById('view-points').textContent    = p.point + ' pts';
            document.getElementById('view-createdAt').textContent = p.createdAt || '—';
            document.getElementById('view-lastLogin').textContent = p.lastLogin  || '—';
            
            const pointsField  = document.getElementById('view-points-field');
            const suspendedRow = document.getElementById('view-suspended-row');
            
            if (p.suspended) {
                pointsField.classList.remove('full');
                suspendedRow.style.display = '';
            } else {
                pointsField.classList.add('full');
                suspendedRow.style.display = 'none';
            }

            openModal('viewModal');
        }

        // edit
        function openEditModal(index) {
            const p = providers[index];
            document.getElementById('edit-providerID').value = p.providerID;
            document.getElementById('edit-fullname').value   = p.fullname;
            document.getElementById('edit-username').value   = p.username;
            document.getElementById('edit-email').value      = p.email;
            document.getElementById('edit-phone').value      = p.phone;
            document.getElementById('edit-state').value      = p.state;
            document.getElementById('edit-postcode').value   = p.postcode;
            document.getElementById('edit-address').value    = p.address;
            document.getElementById('edit-suspended').value   = p.suspended ? '1' : '0';

            document.getElementById('edit-suspended-field').style.display = p.suspended ? '' : 'none';
            
            clearErrors('edit');
            openModal('editModal');
        }

        // delete
        function openDeleteModal(index) {
            const p = providers[index];
            document.getElementById('delete-providerID').value    = p.providerID;
            document.getElementById('delete-name').textContent    = p.fullname;
            document.getElementById('delete-username').textContent = '@' + p.username;
            openModal('deleteModal');
        }

        function setError(fieldId, errorId, message) {
            const field = document.getElementById(fieldId);
            const err   = document.getElementById(errorId);
            if (field) field.classList.add('invalid');
            if (err)   { err.textContent = message; err.classList.add('show'); }
        }
 
        function clearErrors(prefix) {
            document.querySelectorAll(`#${prefix}Form .invalid`).forEach(el => el.classList.remove('invalid'));
            document.querySelectorAll(`#${prefix}Form .field-error`).forEach(el => {
                el.textContent = ''; el.classList.remove('show');
            });
        }

        function validateEditForm() {
            clearErrors('edit');
            let valid = true;
 
            const fullname = document.getElementById('edit-fullname').value.trim();
            const username = document.getElementById('edit-username').value.trim();
            const email    = document.getElementById('edit-email').value.trim();
            const phone    = document.getElementById('edit-phone').value.trim();
            const state    = document.getElementById('edit-state').value;
            const postcode = document.getElementById('edit-postcode').value.trim();
            const address  = document.getElementById('edit-address').value.trim();
 
            if (!fullname) {
                setError('edit-fullname', 'err-edit-fullname', 'Full name is required.');
                valid = false;
            }
 
            if (!username) {
                setError('edit-username', 'err-edit-username', 'Username is required.');
                valid = false;
            } else if (!/^[a-zA-Z0-9_]{3,100}$/.test(username)) {
                setError('edit-username', 'err-edit-username', 'Username must be 3–100 characters (letters, numbers, underscores).');
                valid = false;
            }
 
            if (!email) {
                setError('edit-email', 'err-edit-email', 'Email is required.');
                valid = false;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                setError('edit-email', 'err-edit-email', 'Enter a valid email address.');
                valid = false;
            }
 
            if (!phone) {
                setError('edit-phone', 'err-edit-phone', 'Phone number is required.');
                valid = false;
            } else if (!/^0[0-9]{9,10}$/.test(phone)) {
                setError('edit-phone', 'err-edit-phone', 'Enter a valid Malaysian phone number (e.g. 0123456789).');
                valid = false;
            }
 
            if (!state) {
                setError('edit-state', 'err-edit-state', 'Please select a state.');
                valid = false;
            }
 
            if (!postcode) {
                setError('edit-postcode', 'err-edit-postcode', 'Postcode is required.');
                valid = false;
            } else if (!/^\d{5}$/.test(postcode)) {
                setError('edit-postcode', 'err-edit-postcode', 'Postcode must be exactly 5 digits.');
                valid = false;
            }
 
            if (!address) {
                setError('edit-address', 'err-edit-address', 'Address is required.');
                valid = false;
            }
 
            return valid;
        }
 
        // Live clear errors on input
        document.querySelectorAll('.form-field input, .form-field select, .form-field textarea').forEach(el => {
            el.addEventListener('input', function () {
                this.classList.remove('invalid');
                const errEl = document.getElementById('err-' + this.id);
                if (errEl) { errEl.textContent = ''; errEl.classList.remove('show'); }
            });
            el.addEventListener('change', function () {
                this.dispatchEvent(new Event('input'));
            });
        });
 
        document.getElementById('editForm').addEventListener('submit', function (e) {
            if (!validateEditForm()) e.preventDefault();
        });
    </script>
</body>
</html>