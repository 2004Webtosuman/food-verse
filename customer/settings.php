<?php
// customer/settings.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Fetch current settings
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$theme = $user['theme'] ?? 'system';
$notifs = json_decode($user['notification_settings'] ?? '{}', true);
$privacy = json_decode($user['privacy_settings'] ?? '{}', true);
$access = json_decode($user['accessibility_settings'] ?? '{}', true);
$is_2fa = $user['is_2fa_enabled'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Personalized Settings - FoodVerse</title>
    <style>
        .toggle-checkbox:checked {
            right: 0;
            border-color: var(--primary-color);
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body class="bg-app-bg min-h-screen pb-32 font-outfit">

    <!-- Header -->
    <header class="bg-white p-6 sticky top-0 z-40 border-b border-card-border shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button onclick="history.back()" class="p-2 border border-card-border rounded-full hover:bg-gray-50 transition-all text-gray-700">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </button>
            <h1 class="text-xl font-bold italic tracking-tighter text-gray-900">SETTINGS HUB</h1>
        </div>
    </header>

    <div class="p-6 max-w-lg mx-auto space-y-6">
        
        <!-- 1. APPEARANCE SETTINGS -->
        <section class="bg-white p-6 rounded-3xl border border-card-border shadow-sm">
            <div class="flex items-center gap-4 mb-6 pb-4 border-b border-card-border">
                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-primary border border-card-border"><i data-lucide="moon" class="w-5 h-5"></i></div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Appearance Mode</h3>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <button onclick="updateTheme('light')" class="<?php echo $theme === 'light' ? 'border-primary ring-2 ring-primary/20' : 'border-card-border'; ?> flex flex-col items-center justify-center p-3 border-2 rounded-2xl transition-all bg-gray-50">
                    <i data-lucide="sun" class="w-5 h-5 mb-1 text-yellow-500"></i><span class="text-[10px] font-bold text-gray-800 uppercase tracking-widest">Light</span>
                </button>
                <button onclick="updateTheme('dark')" class="<?php echo $theme === 'dark' ? 'border-primary ring-2 ring-primary/20 bg-gray-900 border-gray-700' : 'border-card-border bg-gray-800 border-gray-700'; ?> flex flex-col items-center justify-center p-3 border-2 rounded-2xl transition-all">
                    <i data-lucide="moon" class="w-5 h-5 mb-1 text-indigo-400"></i><span class="text-[10px] font-bold text-white uppercase tracking-widest">Dark</span>
                </button>
                <button onclick="updateTheme('system')" class="<?php echo $theme === 'system' ? 'border-primary ring-2 ring-primary/20' : 'border-card-border'; ?> flex flex-col items-center justify-center p-3 border-2 rounded-2xl transition-all bg-gray-100">
                    <i data-lucide="monitor" class="w-5 h-5 mb-1 text-gray-500"></i><span class="text-[10px] font-bold text-gray-800 uppercase tracking-widest">System</span>
                </button>
            </div>
        </section>

        <!-- 2. SECURITY & 2FA SETTINGS -->
        <section class="bg-white p-6 rounded-3xl border border-card-border shadow-sm">
            <div class="flex items-center gap-4 mb-6 pb-4 border-b border-card-border">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-500 border border-red-100"><i data-lucide="shield" class="w-5 h-5"></i></div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Security</h3>
                </div>
            </div>
            
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h4 class="font-bold text-gray-800 text-sm">Two-Factor Auth (2FA)</h4>
                    <p class="text-[10px] text-gray-400 mt-0.5">OTP verification on login</p>
                </div>
                <div class="relative inline-block w-12 align-middle select-none">
                    <input type="checkbox" id="toggle-2fa" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300 transition-all z-10" <?php echo $is_2fa ? 'checked' : ''; ?> onchange="toggle2FA(this)"/>
                    <label for="toggle-2fa" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-colors"></label>
                </div>
            </div>

            <div class="bg-section-bg rounded-2xl p-4 border border-card-border">
                <h4 class="font-bold text-gray-800 text-xs mb-3 uppercase tracking-widest">Update Password</h4>
                <div class="space-y-3">
                    <input type="password" id="cp_current" placeholder="Current Password" class="w-full px-4 py-3 bg-white border border-card-border rounded-xl text-sm focus:border-primary outline-none">
                    <input type="password" id="cp_new" placeholder="New Password" class="w-full px-4 py-3 bg-white border border-card-border rounded-xl text-sm focus:border-primary outline-none">
                    <button onclick="updatePassword()" id="pwdBtn" class="w-full px-4 py-3 bg-gray-900 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-95">Update Security Key</button>
                </div>
            </div>
        </section>

    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-24 left-1/2 -translate-x-1/2 translate-y-20 opacity-0 transition-all duration-300 z-50 bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-2xl font-medium text-sm flex items-center gap-3 border border-gray-700 whitespace-nowrap">
        <div id="toastIconContainer">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-400"></i>
        </div>
        <span id="toastMsg">Saved</span>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();

        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
            
            const iconContainer = document.getElementById('toastIconContainer');
            iconContainer.innerHTML = `<i data-lucide="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5 ${isError ? 'text-red-400' : 'text-green-400'}"></i>`;
            lucide.createIcons();
            
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }

        async function updateTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme === 'dark' ? 'dark' : (theme === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : 'light'));
            
            const btns = document.querySelectorAll('section:first-of-type button');
            btns.forEach(b => {
                b.classList.remove('border-primary', 'ring-2', 'ring-primary/20');
                if(!b.classList.contains('bg-gray-800')) b.classList.add('border-card-border');
            });
            event.currentTarget.classList.remove('border-card-border');
            event.currentTarget.classList.add('border-primary', 'ring-2', 'ring-primary/20');

            await saveSettings({theme});
        }

        async function toggle2FA(checkbox) {
            try {
                const res = await fetch('../api/toggle_2fa.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ is_2fa_enabled: checkbox.checked })
                });
                const data = await res.json();
                showToast(data.message, !data.success);
                if (!data.success) checkbox.checked = !checkbox.checked;
            } catch (e) {
                checkbox.checked = !checkbox.checked;
                showToast("Connection failed.", true);
            }
        }

        async function updatePassword() {
            const cp = document.getElementById('cp_current');
            const np = document.getElementById('cp_new');
            const btn = document.getElementById('pwdBtn');

            if(!cp.value || !np.value) return showToast("Enter passwords", true);
            if(np.value.length < 6) return showToast("New password too short", true);

            btn.disabled = true;
            btn.textContent = "Updating...";

            try {
                const res = await fetch('../api/update_settings.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ security: { current_password: cp.value, new_password: np.value } })
                });
                const data = await res.json();
                showToast(data.message, !data.success);
                if(data.success) { cp.value = ''; np.value = ''; }
            } catch(e) {
                showToast("Network error", true);
            }
            btn.disabled = false;
            btn.textContent = "Update Security Key";
        }

        async function saveSettings(payload) {
            try {
                const res = await fetch('../api/update_settings.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.success) showToast(data.message);
                else showToast(data.message, true);
            } catch(e) {
                showToast("Network error", true);
            }
        }
    </script>
</body>
</html>
