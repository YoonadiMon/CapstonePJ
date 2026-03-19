<?php
session_start();
include("main/php/dbConn.php");

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM tblusers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if ($password == $user['password']) {
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['userType'] = $user['userType'];
            
            // Also set other useful user data
            $_SESSION['fullname'] = $user['fullname'] ?? $user['name'] ?? '';
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'] ?? '';
            $_SESSION['createdAt'] = $user['createdAt'] ?? '';
            $_SESSION['lastLogin'] = date("Y-m-d H:i:s");
            
            if ($user['userType'] == 'provider') {
                header("Location: main/html/provider/pHome.php");
            } else if ($user['userType'] == 'collector') {
                header("Location: main/html/collector/cHome.php");
            } else if ($user['userType'] == 'admin') {
                header("Location: main/html/admin/aHome.php");
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - AfterVolt</title>
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

        .c-navbar-more button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .c-navbar-more button img {
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
            
            .c-navbar-more-mobile button {
                background: none;
                border: none;
                cursor: pointer;
                padding: 0.5rem;
            }
            
            .c-navbar-more-mobile button img {
                width: 1.4rem;
                height: auto;
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

        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }

        .login-box {
            background-color: var(--sec-bg-color);
            padding: 2.5rem 2rem;
            border-radius: 25px;
            box-shadow: 0 8px 20px var(--shadow-color);
            width: 100%;
            position: relative;
            margin-top: 2rem;
        }

        .login-box::before {
            content: "Welcome to AfterVolt Portal!";
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            font-weight: 600;
            color: var(--MainBlue);
            white-space: nowrap;
        }

        .login-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .login-logo img {
            width: 3rem;
            height: auto;
        }

        .login-logo span {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .login-box h2 {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 2rem;
            text-align: center;
        }

        .email-section {
            margin-bottom: 1.2rem;
        }

        .email-input, .password-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--BlueGray);
            border-radius: 8px;
            background-color: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .email-input::placeholder, .password-input::placeholder {
            color: var(--Gray);
        }

        .email-input:focus, .password-input:focus {
            outline: none;
            border-color: var(--MainBlue);
        }

        .input-error {
            border-color: #ff4444 !important;
            background-color: rgba(255, 68, 68, 0.05);
        }

        .password-section {
            margin-bottom: 1.5rem;
        }

        .error-message {
            color: #ff4444;
            font-size: 0.9rem;
            margin: 0.5rem 0 1.5rem;
            text-align: center;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .button-container {
            display: flex;
            gap: 1rem;
            width: 100%;
        }

        .login-btn, .back-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }

        .login-btn {
            background-color: var(--MainBlue);
            color: white;
        }

        .login-btn:hover {
            background-color: var(--DarkerMainBlue);
        }

        .back-btn {
            background-color: transparent;
            color: var(--MainBlue);
            border: 2px solid var(--MainBlue);
        }

        .back-btn:hover {
            background-color: var(--LightBlue);
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
            <button id="themeToggleDesktop">
                <img src="main/assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
        </section>

        <section class="c-navbar-more-mobile">
            <button id="themeToggleMobile">
                <img src="main/assets/images/light-mode-icon.svg" alt="Light Mode Icon">
            </button>
        </section>
    </header>
    <hr>

    <main>
        <div class="login-container">
            <div class="login-box">
                <div class="login-logo">
                    <img src="main/assets/images/logo.png" alt="AfterVolt Logo">
                    <span>AfterVolt</span>
                </div>

                <h2>Provider Sign In</h2>

                <form method="POST" action="" id="loginForm">
                    <div class="email-section">
                        <input type="email" name="email" class="email-input" id="email" placeholder="Email Address" required>
                    </div>

                    <div class="password-section">
                        <input type="password" name="password" class="password-input" id="password" placeholder="Password" required>
                    </div>

                    <?php if ($error): ?>
                        <div class="error-message show"><?php echo $error; ?></div>
                    <?php else: ?>
                        <div class="error-message" id="errorMessage"></div>
                    <?php endif; ?>

                    <div class="button-container">
                        <a href="index.html" class="back-btn">BACK</a>
                        <button type="submit" class="login-btn" id="signInBtn">SIGN IN</button>
                    </div>
                </form>
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
            
            if (themeToggleDesktop) themeToggleDesktop.addEventListener('click', toggleTheme);
            if (themeToggleMobile) themeToggleMobile.addEventListener('click', toggleTheme);

            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const form = document.getElementById('loginForm');
            const errorMessage = document.getElementById('errorMessage');

            emailInput.addEventListener('input', function() {
                this.classList.remove('input-error');
                if (errorMessage) errorMessage.classList.remove('show');
            });
            
            passwordInput.addEventListener('input', function() {
                this.classList.remove('input-error');
                if (errorMessage) errorMessage.classList.remove('show');
            });

            form.addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                const password = passwordInput.value;
                
                emailInput.classList.remove('input-error');
                passwordInput.classList.remove('input-error');
                if (errorMessage) errorMessage.classList.remove('show');
                
                if (!email) {
                    e.preventDefault();
                    showError('Please enter your email address', emailInput);
                    return;
                }
                
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    e.preventDefault();
                    showError('Please enter a valid email address', emailInput);
                    return;
                }
                
                if (!password) {
                    e.preventDefault();
                    showError('Please enter your password', passwordInput);
                    return;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    showError('Password must be at least 8 characters long', passwordInput);
                    return;
                }
                
                if (!/[a-z]/.test(password)) {
                    e.preventDefault();
                    showError('Password must contain at least one lowercase letter', passwordInput);
                    return;
                }
                
                if (!/[A-Z]/.test(password)) {
                    e.preventDefault();
                    showError('Password must contain at least one uppercase letter', passwordInput);
                    return;
                }
                
                if (!/[0-9]/.test(password)) {
                    e.preventDefault();
                    showError('Password must contain at least one number', passwordInput);
                    return;
                }
            });
            
            function showError(message, inputElement) {
                if (errorMessage) {
                    errorMessage.textContent = message;
                    errorMessage.classList.add('show');
                }
                inputElement.classList.add('input-error');
            }
        });
    </script>
</body>
</html>