<script>
    (function () {
        var STORAGE_KEY = 'organett-theme';
        var stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'light' || stored === 'dark') {
            document.documentElement.setAttribute('data-theme', stored);
        }
        window.toggleTheme = function () {
            var root = document.documentElement;
            var current = root.getAttribute('data-theme')
                || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
            var next = current === 'light' ? 'dark' : 'light';
            localStorage.setItem(STORAGE_KEY, next);
            root.setAttribute('data-theme', next);
            // Chart.js bakes colors in at draw time — reload so dashboard charts re-init correctly.
            if (document.getElementById('yieldChart') || document.getElementById('gradeChart')) {
                window.location.reload();
            }
        };
    })();
</script>
