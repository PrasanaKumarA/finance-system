<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {

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

    } else {
        $error = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — FinanceHub</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <script>
        (function () {
            const saved = localStorage.getItem('finance-theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>

<body class="login-page">

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
            <p class="login-subtitle">Sign in to manage your finances</p>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php } ?>

        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter your username" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>

            <button type="submit">Sign In</button>
        </form>
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