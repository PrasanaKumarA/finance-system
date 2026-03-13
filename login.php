<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/config.php";

$error = "";
$success = "";
$mode = isset($_GET['mode']) && $_GET['mode'] === 'signup' ? 'signup' : 'login';

// --- Handle Google Login Response ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['credential'])) {
    $jwt = $_POST['credential'];
    
    // Very basic decode without full validation for quick integration
    $parts = explode('.', $jwt);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        
        if ($payload && isset($payload['email'])) {
            $email = $payload['email'];
            $name = $payload['name'] ?? 'Google User';
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // User exists, log them in
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
                header("Location: index.php");
                exit;
            } else {
                // User doesn't exist, create them
                $random_password = bin2hex(random_bytes(10));
                $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
                $role = 'user';
                
                $insert_stmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                
                if ($insert_stmt->execute()) {
                    $new_user_id = $insert_stmt->insert_id;
                    
                    // Seed categories
                    require_once __DIR__ . "/categories/init_defaults.php";
                    seed_default_categories($conn, $new_user_id);
                    
                    // Log them in
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['role'] = $role;
                    $_SESSION['name'] = $name;
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Failed to create account with Google.";
                }
                $insert_stmt->close();
            }
            $stmt->close();
        } else {
            $error = "Invalid Google login payload.";
        }
    } else {
        $error = "Invalid Google Credential format.";
    }
}
// --- Handle Standard Login / Sign Up ---
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "All fields are required!";
    } else {
        if ($_POST['action'] === 'login') {
            /* Secure Prepared Statement */
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];

                    // Seed default categories for existing users who have none
                    require_once __DIR__ . "/categories/init_defaults.php";
                    seed_default_categories($conn, $user['id']);

                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid password!";
                }
            } else {
                $error = "User not found!";
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'signup') {
            $name = trim($_POST['name'] ?? '');
            
            if (empty($name)) {
                $error = "Name is required for sign up!";
            } else {
                // Check if username already exists
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $check_stmt->bind_param("s", $username);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error = "Username already exists. Please choose another.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'user';
                    
                    $insert_stmt = $conn->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
                    $insert_stmt->bind_param("ssss", $name, $username, $hashed_password, $role);
                    
                    if ($insert_stmt->execute()) {
                        $success = "Account created successfully! You can now sign in.";
                        $mode = 'login'; // Switch back to login view
                    } else {
                        $error = "Error creating account. Please try again.";
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mode === 'signup' ? 'Sign Up' : 'Login' ?> — FinanceHub</title>
    <link rel="icon" type="image/jpeg" href="<?php echo BASE_PATH; ?>/assets/images/favi.JPG">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>/assets/images/favi.JPG">
    <?php
    $manifest_path = __DIR__ . '/manifest.json';
    if (file_exists($manifest_path)) {
        $manifest_content = file_get_contents($manifest_path);
        
        // Parse the manifest to make relative URLs absolute for the data URI
        $manifest_data = json_decode($manifest_content, true);
        if ($manifest_data) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $base_url = rtrim($protocol . $_SERVER['HTTP_HOST'] . (defined('BASE_PATH') ? BASE_PATH : ''), '/');
            
            if (isset($manifest_data['start_url'])) {
                $manifest_data['start_url'] = $base_url . '/' . ltrim($manifest_data['start_url'], '/');
            }
            if (isset($manifest_data['icons']) && is_array($manifest_data['icons'])) {
                foreach ($manifest_data['icons'] as &$icon) {
                    if (isset($icon['src'])) {
                        $icon['src'] = $base_url . '/' . ltrim($icon['src'], '/');
                    }
                }
            }
            $manifest_content = json_encode($manifest_data);
        }
        
        $manifest_base64 = base64_encode($manifest_content);
        echo '<link rel="manifest" href="data:application/manifest+json;base64,' . $manifest_base64 . '">';
    } else {
        echo '<link rel="manifest" href="' . (defined('BASE_PATH') ? BASE_PATH : '') . '/manifest.json" crossorigin="use-credentials">';
    }
    ?>
    <meta name="theme-color" content="#4F46E5">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="stylesheet"
        href="<?php echo BASE_PATH; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
    
    <!-- Google Identity Services Script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
        
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('<?php echo BASE_PATH; ?>/service-worker.js');
            });
        }
    </script>
    <script>
        (function () {
            const saved = localStorage.getItem('finance-theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    
    <style>
        .login-tabs {
            display: flex;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--border);
        }
        .login-tab {
            flex: 1;
            text-align: center;
            padding: 12px 16px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .login-tab.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            margin-bottom: -2px;
        }
        .google-btn-wrapper {
            margin-top: 24px;
            text-align: center;
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: var(--text-muted);
            font-size: 13px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }
        .divider:not(:empty)::before {
            margin-right: .5em;
        }
        .divider:not(:empty)::after {
            margin-left: .5em;
        }
    </style>
</head>

<body class="login-page">

    <div class="login-wrapper">
        <div class="login-theme-toggle">
            <button class="theme-toggle" id="themeToggle" title="Toggle theme" type="button">
                <span class="icon-sun">☀️</span>
                <span class="icon-moon">🌙</span>
            </button>
        </div>

        <div class="login-box">
            <div class="login-brand">
                <div class="login-brand-icon">💎</div>
                <h2>FinanceHub</h2>
                <p class="login-subtitle"><?= $mode === 'signup' ? 'Create a new account' : 'Sign in to manage your finances' ?></p>
            </div>

            <?php if (!empty($error)) { ?>
                <div class="error" style="color: var(--danger); background: var(--danger-bg); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 14px;"><?= htmlspecialchars($error) ?></div>
            <?php } ?>
            
            <?php if (!empty($success)) { ?>
                <div class="success" style="color: var(--success); background: var(--success-bg); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 14px;"><?= htmlspecialchars($success) ?></div>
            <?php } ?>

            <div class="login-tabs">
                <a href="login.php?mode=login" class="login-tab <?= $mode === 'login' ? 'active' : '' ?>">Sign In</a>
                <a href="login.php?mode=signup" class="login-tab <?= $mode === 'signup' ? 'active' : '' ?>">Sign Up</a>
            </div>

            <form method="POST" action="login.php<?= $mode === 'signup' ? '?mode=signup' : '' ?>">
                <input type="hidden" name="action" value="<?= $mode ?>">
                
                <?php if ($mode === 'signup') { ?>
                    <label>Full Name</label>
                    <input type="text" name="name" placeholder="Enter your full name" required>
                <?php } ?>

                <label><?= $mode === 'signup' ? 'Choose Username / Email' : 'Username' ?></label>
                <input type="text" name="username" placeholder="<?= $mode === 'signup' ? 'e.g. user@example.com' : 'Enter your username' ?>" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="<?= $mode === 'signup' ? 'Create a secure password' : 'Enter your password' ?>" required>

                <button type="submit" style="width: 100%;"><?= $mode === 'signup' ? 'Create Account' : 'Sign In' ?></button>
            </form>
            
            <div class="divider">OR</div>
            
            <!-- Google Login Integration -->
            <div id="g_id_onload"
                 data-client_id="580203875952-bvttrhkcauq4qnmvu415tskrhrv20fjf.apps.googleusercontent.com"
                 data-context="<?= $mode === 'signup' ? 'signup' : 'signin' ?>"
                 data-ux_mode="popup"
                 data-login_uri="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>"
                 data-itp_support="true">
            </div>
            
            <div class="google-btn-wrapper">
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="<?= $mode === 'signup' ? 'signup_with' : 'signin_with' ?>"
                     data-size="large"
                     data-logo_alignment="left">
                </div>
            </div>

        </div>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('themeToggle');
            if (!toggle) return;
            function getTheme() {
                return localStorage.getItem('finance-theme') || 'light';
            }
            function setTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('finance-theme', theme);
            }
            toggle.addEventListener('click', function () {
                const current = getTheme();
                setTheme(current === 'dark' ? 'light' : 'dark');
            });
        })();
    </script>

</body>

</html>