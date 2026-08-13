<style>
    .app-main > :first-child {
        border-bottom-left-radius: 1rem;
        border-bottom-right-radius: 1rem;
    }

    .app-main > :last-child {
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
    }
</style>
<script>
    (function () {
        try {
            if (localStorage.getItem('casini.sidebarOpen') === 'false') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>
