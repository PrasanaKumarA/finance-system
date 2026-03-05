<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance System</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            const saved = localStorage.getItem('finance-theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>

<?php
// Determine current page for sidebar active highlighting
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Load user profile data
$profile_picture = null;
$custom_system_name = null;
if (isset($_SESSION['user_id'])) {
    $profile_query = mysqli_query($conn, "SELECT profile_picture, system_name FROM users WHERE id = " . intval($_SESSION['user_id']));
    if ($profile_query && $profile_row = mysqli_fetch_assoc($profile_query)) {
        $profile_picture = $profile_row['profile_picture'];
        $custom_system_name = $profile_row['system_name'];
    }
}
$brand_name = $custom_system_name ?: 'FinanceHub';
$bp = BASE_PATH;
?>

<body>

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">💎</div>
            <div class="sidebar-brand-text">
                <?php echo htmlspecialchars($brand_name); ?>
                <span>Finance Management System</span>
            </div>
        </div>

        <div class="sidebar-nav">
            <div class="sidebar-section-label">Main Menu</div>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') { ?>
                <a href="<?php echo $bp; ?>/admin/index.php" class="<?php echo $current_dir == 'admin' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01z" />
                    </svg>
                    Admin Panel
                </a>
            <?php } ?>

            <a href="<?php echo $bp; ?>/index.php"
                class="<?php echo ($current_file == 'index.php' && ($current_dir == 'finance-system' || $current_dir == 'htdocs')) ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="9" />
                    <rect x="14" y="3" width="7" height="5" />
                    <rect x="14" y="12" width="7" height="9" />
                    <rect x="3" y="16" width="7" height="5" />
                </svg>
                Dashboard
            </a>

            <div class="sidebar-section-label">Finance</div>

            <a href="<?php echo $bp; ?>/accounts/view_accounts.php"
                class="<?php echo $current_dir == 'accounts' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2" />
                    <line x1="2" y1="10" x2="22" y2="10" />
                </svg>
                Accounts
            </a>

            <a href="<?php echo $bp; ?>/categories/view_categories.php"
                class="<?php echo $current_dir == 'categories' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6" />
                    <line x1="8" y1="12" x2="21" y2="12" />
                    <line x1="8" y1="18" x2="21" y2="18" />
                    <line x1="3" y1="6" x2="3.01" y2="6" />
                    <line x1="3" y1="12" x2="3.01" y2="12" />
                    <line x1="3" y1="18" x2="3.01" y2="18" />
                </svg>
                Categories
            </a>

            <a href="<?php echo $bp; ?>/transactions/add_transaction.php"
                class="<?php echo $current_dir == 'transactions' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                    <polyline points="17 6 23 6 23 12" />
                </svg>
                Transactions
            </a>

            <a href="<?php echo $bp; ?>/budgets/set_budget.php"
                class="<?php echo $current_dir == 'budgets' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="1" x2="12" y2="23" />
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
                </svg>
                Budgets
            </a>

            <div class="sidebar-section-label">Analytics</div>

            <a href="<?php echo $bp; ?>/reports/index.php"
                class="<?php echo $current_dir == 'reports' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                    <polyline points="10 9 9 9 8 9" />
                </svg>
                Reports
            </a>
        </div>

        <div class="sidebar-bottom">
            <a href="<?php echo $bp; ?>/logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Logout
            </a>
        </div>
    </nav>

    <!-- SIDEBAR OVERLAY (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- TOP HEADER BAR -->
    <header class="header">
        <div class="header-left">
            <button class="hamburger-btn" id="hamburgerBtn" type="button" title="Toggle menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
            </button>
            <h1 class="header-page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
        </div>
        <div class="header-right">
            <button class="theme-toggle" id="themeToggle" title="Toggle theme" type="button">
                <span class="icon-sun">☀️</span>
                <span class="icon-moon">🌙</span>
            </button>
            <?php if (isset($_SESSION['name'])) { ?>
                <a href="<?php echo $bp; ?>/profile.php" class="header-user" title="Profile settings">
                    <?php if ($profile_picture && file_exists($_SERVER['DOCUMENT_ROOT'] . $bp . '/' . $profile_picture)) { ?>
                        <img class="header-user-avatar-img"
                            src="<?php echo $bp; ?>/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile">
                    <?php } else { ?>
                        <div class="header-user-avatar"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                    <?php } ?>
                    <span class="header-user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </a>
            <?php } ?>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="main-content">