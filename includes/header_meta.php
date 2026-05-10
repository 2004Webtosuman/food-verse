<?php
// includes/header_meta.php
$sys_theme = 'system';
if (isset($_SESSION['user_id'])) {
    global $pdo;
    $t_stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
    $t_stmt->execute([$_SESSION['user_id']]);
    $db_theme = $t_stmt->fetchColumn();
    if ($db_theme) $sys_theme = $db_theme;
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
    // Theme Initializer to prevent strict flashes
    (function(){
        let theme = "<?php echo $sys_theme; ?>";
        if(theme === 'system') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        if(theme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
    })();
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/food-verse/assets/css/theme.css">
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#FF6B35',
                    secondary: '#6C5CE7',
                    accent: '#00C2A8',
                    'primary-hover': '#E85A2A',
                    'app-bg': 'var(--bg-color)',
                    'section-bg': 'var(--section-bg)',
                    'card-border': 'var(--card-border)',
                    // Legacy aliases for easier migration
                    neon: '#FF6B35',
                    dark: 'var(--bg-color)',
                },
                fontFamily: {
                    outfit: ['Outfit', 'sans-serif'],
                },
                borderRadius: {
                    '3xl': '1.5rem',
                    '4xl': '2rem',
                }
            }
        }
    }
</script>
<style>
    /* body bg/color fully handled by theme.css via CSS variables */
    .neon-glow {
        box-shadow: 0 4px 20px rgba(255, 107, 53, 0.15);
    }
    [data-theme="dark"] .neon-glow {
        box-shadow: 0 4px 20px rgba(255, 107, 53, 0.3);
    }
    .glass {
        background: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(241, 234, 228, 0.6);
    }
    [data-theme="dark"] .glass {
        background: rgba(26, 31, 46, 0.80);
        border-color: rgba(45, 55, 72, 0.6);
    }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .card-shadow {
        box-shadow: 0 2px 16px rgba(0, 0, 0, 0.04), 0 1px 4px rgba(0, 0, 0, 0.02);
    }
</style>

<!-- Location Selection System -->
<link rel="stylesheet" href="/food-verse/assets/css/location_styles.css">
<script src="/food-verse/config/districts.js"></script>
<script src="/food-verse/assets/js/location_picker.js"></script>
