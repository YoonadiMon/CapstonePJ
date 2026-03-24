<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("main/php/dbConn.php");

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
}

$login_error = "";
$register_error = "";
$register_success = "";
$submitted_email = "";
$reg_fullname = "";
$reg_email = "";
$reg_phone = "";
$show_register_tab = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $submitted_email = $email;

    $stmt = $conn->prepare("SELECT * FROM tblusers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['userType'] = $user['userType'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'] ?? '';
            $_SESSION['lastLogin'] = date("Y-m-d H:i:s");

            if ($user['userType'] === 'provider') {
                header("Location: main/html/provider/pHome.php");
            } elseif ($user['userType'] === 'collector') {
                header("Location: main/html/collector/cHome.php");
            } elseif ($user['userType'] === 'admin') {
                header("Location: main/html/admin/aHome.php");
            } else {
                $login_error = "Invalid user role.";
            }
            exit();
        } else {
            $login_error = "Incorrect email or password. Please try again.";
        }
    } else {
        $login_error = "Account '" . htmlspecialchars($email) . "' does not exist.";
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    $reg_fullname = $fullname;
    $reg_email = $email;
    $reg_phone = $phone;
    $show_register_tab = true;

    $username = 'provider_' . time();

    if ($fullname === '' || $email === '' || $password === '') {
        $register_error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $register_error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $register_error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $register_error = "Password must contain at least one number.";
    } else {
        $check_stmt = $conn->prepare("SELECT userID FROM tblusers WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result && $check_result->num_rows > 0) {
            $register_error = "Email address already registered. Please use a different email or sign in.";
        } else {
            $userType = 'provider';
            $createdAt = date("Y-m-d H:i:s");

            $insert_stmt = $conn->prepare("INSERT INTO tblusers (username, fullname, email, password, phone, userType, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssssss", $username, $fullname, $email, $password, $phone, $userType, $createdAt);

            if ($insert_stmt->execute()) {
                $userID = $insert_stmt->insert_id;

                $empty_address = '';
                $empty_state = '';
                $empty_postcode = '';

                $provider_stmt = $conn->prepare("INSERT INTO tblprovider (providerID, address, state, postcode, point) VALUES (?, ?, ?, ?, 0)");
                $provider_stmt->bind_param("isss", $userID, $empty_address, $empty_state, $empty_postcode);
                $provider_stmt->execute();
                $provider_stmt->close();

                $register_success = "Registration successful! You can now sign in.";
                $show_register_tab = false;
                $reg_fullname = "";
                $reg_email = "";
                $reg_phone = "";
            } else {
                $register_error = "Registration failed. Please try again.";
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In / Sign Up - AfterVolt</title>
    <link rel="icon" type="image/png" href="main/assets/images/bolt-lightning-icon.svg">
    <link rel="stylesheet" href="main/style/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            margin: 1rem 2rem;
        }

        .c-logo-section {
            display: flex;
            align-items: center;
        }

        .c-logo-link {
            text-decoration: none;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .c-logo {
            width: 1.5rem;
            height: auto;
        }

        .c-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color);
        }

        .c-navbar-more {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .c-navbar-more button,
        .c-navbar-more-mobile button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .c-navbar-more button img,
        .c-navbar-more-mobile button img {
            width: 1.4rem;
            height: auto;
        }

        .c-navbar-more-mobile {
            display: none;
        }

        @media (max-width: 759px) {
            .c-navbar-more {
                display: none !important;
            }

            .c-navbar-more-mobile {
                display: block;
            }
        }

        hr {
            height: 1px;
            width: 90%;
            background-color: var(--Gray);
            margin: 0 auto;
            border: none;
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .auth-box {
            background-color: var(--sec-bg-color);
            padding: 2rem;
            border-radius: 28px;
            box-shadow: 0 12px 28px var(--shadow-color);
            width: 100%;
            position: relative;
            margin-top: 2rem;
            transition: all 0.3s ease;
        }

        .auth-box::before {
            content: "Welcome to AfterVolt!";
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            font-weight: 600;
            color: var(--MainBlue);
            white-space: nowrap;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .auth-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .auth-logo img {
            width: 3rem;
            height: auto;
        }

        .auth-logo span {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .tab-container {
            display: flex;
            margin-bottom: 2rem;
            background-color: var(--bg-color);
            border-radius: 50px;
            padding: 0.25rem;
            box-shadow: inset 0 1px 2px var(--shadow-color);
        }

        .tab-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 0.7rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--Gray);
            transition: all 0.3s ease;
            border-radius: 50px;
        }

        .tab-btn.active {
            background-color: var(--MainBlue);
            color: white;
            box-shadow: 0 2px 8px rgba(100, 108, 255, 0.3);
        }

        .tab-btn:hover:not(.active) {
            color: var(--MainBlue);
            background-color: rgba(100, 108, 255, 0.1);
        }

        .form-container {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .form-container.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.4rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1.5px solid var(--BlueGray);
            border-radius: 12px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--MainBlue);
            box-shadow: 0 0 0 3px rgba(100, 108, 255, 0.2);
        }

        .form-group input::placeholder {
            color: var(--Gray);
            opacity: 0.7;
        }

        .input-error {
            border-color: #ff4444 !important;
            background-color: rgba(255, 68, 68, 0.05);
        }

        .input-error:focus {
            box-shadow: 0 0 0 3px rgba(255, 68, 68, 0.2) !important;
        }

        .password-hint {
            font-size: 0.7rem;
            color: var(--Gray);
            margin-top: 0.4rem;
            padding-left: 0.2rem;
        }

        .error-message {
            color: #ff4444;
            font-size: 0.85rem;
            margin: 0.8rem 0 1rem;
            text-align: center;
            padding: 0.6rem;
            background-color: rgba(255, 68, 68, 0.1);
            border-radius: 10px;
        }

        .success-message {
            color: #4CAF50;
            font-size: 0.85rem;
            margin: 0.8rem 0 1rem;
            text-align: center;
            padding: 0.6rem;
            background-color: rgba(76, 175, 80, 0.1);
            border-radius: 10px;
        }

        .submit-btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, var(--MainBlue), var(--DarkerMainBlue));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(100, 108, 255, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(100, 108, 255, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        footer {
            background-color: var(--footer-color);
            padding: 2rem;
            text-align: center;
            margin-top: 1rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-info p {
            color: white;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .footer-copyright {
            color: white;
            font-size: 0.8rem;
            margin-top: 1rem;
            opacity: 0.7;
        }

        @media (max-width: 600px) {
            .auth-box::before {
                font-size: 1.3rem;
                white-space: normal;
                text-align: center;
                width: 100%;
                top: -45px;
            }

            .auth-box {
                margin-top: 3rem;
                padding: 1.5rem;
            }

            .tab-btn {
                font-size: 0.9rem;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div id="cover" class="" onclick="hideMenu()"></div>

    <header>
        <section class="c-logo-section">
            <a href="index.html" class="c-logo-link">
                <img src="main/assets/images/logo.png" alt="Logo" class="c-logo">
                <div class="c-text">AfterVolt</div>
            </a>
        </section>

        <section class="c-navbar-more">
            <button id="themeToggleDesktop" type="button">
                <img src="main/assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
        </section>

        <section class="c-navbar-more-mobile">
            <button id="themeToggleMobile" type="button">
                <img src="main/assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
        </section>
    </header>
    <hr>

    <main>
        <div class="auth-container">
            <div class="auth-box">
                <div class="auth-logo">
                    <img src="main/assets/images/logo.png" alt="AfterVolt Logo">
                    <span>AfterVolt</span>
                </div>

                <div class="tab-container">
                    <button class="tab-btn <?php echo $show_register_tab ? '' : 'active'; ?>" id="loginTab" type="button">Sign In</button>
                    <button class="tab-btn <?php echo $show_register_tab ? 'active' : ''; ?>" id="registerTab" type="button">Sign Up</button>
                </div>

                <div class="form-container <?php echo $show_register_tab ? '' : 'active'; ?>" id="loginForm">
                    <form method="POST" action="" id="loginFormSubmit">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" id="login_email" placeholder="Enter your email" value="<?php echo htmlspecialchars($submitted_email); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" id="login_password" placeholder="Enter your password" required>
                        </div>

                        <?php if ($login_error): ?>
                            <div class="error-message"><?php echo $login_error; ?></div>
                        <?php endif; ?>

                        <?php if ($register_success): ?>
                            <div class="success-message"><?php echo $register_success; ?></div>
                        <?php endif; ?>

                        <button type="submit" name="login" class="submit-btn">SIGN IN</button>
                    </form>
                </div>

                <div class="form-container <?php echo $show_register_tab ? 'active' : ''; ?>" id="registerFormContainer">
                    <form method="POST" action="" id="registerFormSubmit">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="fullname" id="reg_fullname" placeholder="Enter your full name" value="<?php echo htmlspecialchars($reg_fullname); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="reg_email" id="reg_email" placeholder="Enter your email" value="<?php echo htmlspecialchars($reg_email); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="reg_phone" placeholder="Optional" value="<?php echo htmlspecialchars($reg_phone); ?>">
                        </div>

                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="reg_password" id="reg_password" placeholder="Create a password" required>
                            <div class="password-hint" id="passwordHint"></div>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <input type="password" name="confirm_password" id="reg_confirm_password" placeholder="Confirm your password" required>
                        </div>

                        <?php if ($register_error): ?>
                            <div class="error-message"><?php echo $register_error; ?></div>
                        <?php endif; ?>

                        <button type="submit" name="register" class="submit-btn">SIGN UP</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <hr>

    <footer>
        <div class="footer-content">
            <div class="footer-info">
                <p>Promoting responsible e-waste collection and sustainable recycling practices in partnership with APU.</p>
                <p>+60 12 345 6789 | abc@gmail.com</p>
            </div>
            <div class="footer-copyright">
                <p>© 2026 AfterVolt. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleDesktop = document.getElementById('themeToggleDesktop');
            const themeToggleMobile = document.getElementById('themeToggleMobile');
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerFormContainer');
            const loginEmail = document.getElementById('login_email');
            const loginPassword = document.getElementById('login_password');
            const regPassword = document.getElementById('reg_password');
            const passwordHint = document.getElementById('passwordHint');
            const registerFormSubmit = document.getElementById('registerFormSubmit');

            function toggleTheme() {
                document.body.classList.toggle('dark-mode');
                const isDarkMode = document.body.classList.contains('dark-mode');
                const iconSrc = isDarkMode ? 'main/assets/images/dark-mode-icon.svg' : 'main/assets/images/light-mode-icon.svg';

                if (themeToggleDesktop) {
                    const img = themeToggleDesktop.querySelector('img');
                    if (img) img.src = iconSrc;
                }

                if (themeToggleMobile) {
                    const img = themeToggleMobile.querySelector('img');
                    if (img) img.src = iconSrc;
                }
            }

            function switchToLogin() {
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
            }

            function switchToRegister() {
                registerTab.classList.add('active');
                loginTab.classList.remove('active');
                registerForm.classList.add('active');
                loginForm.classList.remove('active');
            }

            function updatePasswordHint() {
                if (!regPassword || !passwordHint) return;

                const password = regPassword.value;
                let validCount = 0;
                let hints = [];

                if (password.length >= 8) validCount++;
                else hints.push('8+ characters');

                if (/[a-z]/.test(password)) validCount++;
                else hints.push('lowercase');

                if (/[A-Z]/.test(password)) validCount++;
                else hints.push('uppercase');

                if (/[0-9]/.test(password)) validCount++;
                else hints.push('number');

                if (password.length === 0) {
                    passwordHint.innerHTML = '';
                } else if (validCount === 4) {
                    passwordHint.innerHTML = '✓ Strong password';
                    passwordHint.style.color = '#4CAF50';
                } else {
                    passwordHint.innerHTML = 'Requires: ' + hints.join(', ');
                    passwordHint.style.color = 'var(--Gray)';
                }
            }

            if (themeToggleDesktop) themeToggleDesktop.addEventListener('click', toggleTheme);
            if (themeToggleMobile) themeToggleMobile.addEventListener('click', toggleTheme);

            loginTab.addEventListener('click', switchToLogin);
            registerTab.addEventListener('click', switchToRegister);

            if (loginEmail) {
                loginEmail.addEventListener('input', function() {
                    this.classList.remove('input-error');
                });
            }

            if (loginPassword) {
                loginPassword.addEventListener('input', function() {
                    this.classList.remove('input-error');
                });
            }

            if (regPassword) {
                regPassword.addEventListener('input', updatePasswordHint);
            }

            if (registerFormSubmit) {
                registerFormSubmit.addEventListener('submit', function(e) {
                    const password = document.getElementById('reg_password').value;
                    const confirm = document.getElementById('reg_confirm_password').value;

                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Passwords do not match.');
                        document.getElementById('reg_confirm_password').classList.add('input-error');
                        return;
                    }

                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long.');
                        document.getElementById('reg_password').classList.add('input-error');
                        return;
                    }

                    if (!/[a-z]/.test(password)) {
                        e.preventDefault();
                        alert('Password must contain at least one lowercase letter.');
                        document.getElementById('reg_password').classList.add('input-error');
                        return;
                    }

                    if (!/[A-Z]/.test(password)) {
                        e.preventDefault();
                        alert('Password must contain at least one uppercase letter.');
                        document.getElementById('reg_password').classList.add('input-error');
                        return;
                    }

                    if (!/[0-9]/.test(password)) {
                        e.preventDefault();
                        alert('Password must contain at least one number.');
                        document.getElementById('reg_password').classList.add('input-error');
                        return;
                    }
                });
            }

            const regInputs = document.querySelectorAll('#reg_fullname, #reg_email, #reg_phone, #reg_password, #reg_confirm_password');
            regInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('input-error');
                });
            });

            updatePasswordHint();
        });
    </script>
</body>
</html>