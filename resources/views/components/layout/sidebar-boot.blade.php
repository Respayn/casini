<script>
    (function () {
        try {
            if (localStorage.getItem('casini.sidebarOpen') === 'false') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>
