<header class="bg-white dark:bg-dark-card border-b border-card-border dark:border-dark-border h-[72px] md:h-[88px] flex items-center justify-between md:justify-end px-6 md:px-8 z-10 flex-shrink-0 transition-colors">
    <div class="md:hidden flex items-center gap-2">
        <h1 class="text-2xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
    </div>
    
    <div class="flex items-center gap-4">
        <!-- Theme Toggle -->
        <button id="theme-toggle" class="p-2.5 rounded-xl bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-all">
            <i data-lucide="moon" class="w-5 h-5 dark:hidden"></i>
            <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
        </button>

        <a href="../logout.php" class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-all">
            <i data-lucide="power" class="w-5 h-5"></i>
            <span class="hidden md:inline">Logout</span>
        </a>
    </div>
</header>

<script>
    document.getElementById('theme-toggle').addEventListener('click', () => {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('admin_theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('admin_theme', 'dark');
        }
        // Refresh icons if necessary
        if (window.lucide) lucide.createIcons();
    });
</script>
