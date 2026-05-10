<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script>
    // Theme initialization
    if (localStorage.getItem('admin_theme') === 'dark' || (!('admin_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: '#FF6B35',
                    'primary-hover': '#E85A2A',
                    secondary: '#6C5CE7',
                    accent: '#00C2A8',
                    'app-bg': '#FFF8F2',
                    'section-bg': '#FDF2EC',
                    'card-border': '#F1EAE4',
                    // Dark Mode Palette
                    'dark-bg': '#0F1115',
                    'dark-card': '#16191F',
                    'dark-border': '#1F242C',
                }
            }
        }
    }
</script>
<style>
    body { font-family: 'Outfit', sans-serif; transition: background-color 0.3s, color 0.3s; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.02); }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 107, 53, 0.2); border-radius: 10px; }
    
    .dark .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 107, 53, 0.4); }
</style>
