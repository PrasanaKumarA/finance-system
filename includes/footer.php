</div><!-- /.main-content -->

<script>
    // Theme Toggle Logic
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