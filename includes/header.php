<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance System</title>
    <link rel="stylesheet" href="/finance-system/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Apply saved theme instantly to prevent flash
        (function () {
            const saved = localStorage.getItem('finance-theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>

<body>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">💎</div>
            <div class="sidebar-brand-text">
                FinanceHub
                <span>Management System</span>
            </div>
        </div>

        <div class="sidebar-nav">
            <div class="sidebar-section-label">Main Menu</div>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') { ?>
                <a href="/finance-system/admin/index.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01z" />
                    </svg>
                    Admin Panel
                </a>
            <?php } ?>

            <a href="/finance-system/index.php"
                class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') === false ? 'active' : ''; ?>">
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

            <a href="/finance-system/accounts/view_accounts.php"
                class="<?php echo strpos($_SERVER['PHP_SELF'], '/accounts/') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2" />
                    <line x1="2" y1="10" x2="22" y2="10" />
                </svg>
                Accounts
            </a>

            <a href="/finance-system/categories/view_categories.php"
                class="<?php echo strpos($_SERVER['PHP_SELF'], '/categories/') !== false ? 'active' : ''; ?>">
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

            <a href="/finance-system/transactions/add_transaction.php"
                class="<?php echo strpos($_SERVER['PHP_SELF'], '/transactions/') !== false ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                    <polyline points="17 6 23 6 23 12" />
                </svg>
                Transactions
            </a>

            <div class="sidebar-section-label">Analytics</div>

            <a href="/finance-system/reports/index.php"
                class="<?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>">
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
            <a href="/finance-system/logout.php">
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

    <!-- TOP HEADER BAR -->
    <header class="header">
        <div class="header-left">
            <h1 class="header-page-title">
                <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
            </h1>
        </div>
        <div class="header-right">
            <button class="theme-toggle" id="themeToggle" title="Toggle theme" type="button">
                <span class="icon-sun">☀️</span>
                <span class="icon-moon">🌙</span>
            </button>
            <?php if (isset($_SESSION['name'])) { ?>
                <div class="header-user">
                    <div class="header-user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <?php echo htmlspecialchars($_SESSION['name']); ?>
                </div>
            <?php } ?>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <div class="main-content">