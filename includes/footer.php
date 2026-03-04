</div><!-- /.main-content -->

<script>
    // Theme Toggle
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
            setTheme(getTheme() === 'dark' ? 'light' : 'dark');
        });
    })();

    // Mobile Sidebar Toggle
    (function () {
        const btn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (!btn || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        btn.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        if (overlay) overlay.addEventListener('click', closeSidebar);
    })();
</script>

</body>

</html>