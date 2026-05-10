<?php
// delivery/settings.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_delivery()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Rider';

// Fetch current settings
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$theme = $user['theme'] ?? 'system';
$notifs = json_decode($user['notification_settings'] ?? '{}', true);
$privacy = json_decode($user['privacy_settings'] ?? '{}', true);
$rider = json_decode($user['rider_settings'] ?? '{}', true);
$access = json_decode($user['accessibility_settings'] ?? '{}', true);
$is_2fa = $user['is_2fa_enabled'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme === 'dark' ? 'dark' : 'light'; ?>">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Personalized Settings - FoodVerse Rider</title>
    <style>
        /* Custom toggle switch */
        .toggle-checkbox:checked {
            right: 0;
            border-color: var(--primary-color);
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body class="bg-app-bg min-h-screen">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-card-border hidden md:flex flex-col h-full z-10 flex-shrink-0">
            <div class="p-6 border-b border-card-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Rider Hub</span>
            </div>
            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-section-bg hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i><span>Dashboard</span>
                </a>
                <a href="history.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-section-bg hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="package" class="w-5 h-5"></i><span>My Deliveries</span>
                </a>
                <a href="earnings.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-section-bg hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="wallet" class="w-5 h-5"></i><span>Earnings</span>
                </a>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-section-bg hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-medium transition-all w-full">
                    <i data-lucide="shield-check" class="w-5 h-5"></i><span>Verification</span>
                </a>
                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 bg-primary/5 text-primary border-l-4 border-primary rounded-r-2xl font-bold transition-all w-full">
                    <i data-lucide="settings" class="w-5 h-5"></i><span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header class="bg-white border-b border-card-border h-[72px] md:h-[88px] flex items-center justify-between px-6 md:px-8 z-10 flex-shrink-0">
                <h1 class="text-xl md:hidden font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <div class="hidden md:block"></div>
                <div class="flex items-center gap-6">
                    <a href="../logout.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium transition-all">
                        <i data-lucide="power" class="w-5 h-5"></i><span class="hidden md:inline">Logout</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 md:p-8 w-full bg-app-bg pb-28 md:pb-8" id="settings-scroll">
                
                <div class="max-w-4xl mx-auto space-y-8">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight">Configuration Hub</h2>
                        <p class="text-sm text-gray-500 mt-1 font-medium">Personalize your app experience, secure your account, and tune your logistics algorithms.</p>
                    </div>

                    <!-- 1. APPEARANCE SETTINGS -->
                    <section class="bg-white p-6 md:p-8 rounded-[2rem] border border-card-border shadow-sm">
                        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-card-border">
                            <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center text-primary border border-card-border"><i data-lucide="moon" class="w-6 h-6"></i></div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Appearance</h3>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Customize your UI theme</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <button onclick="updateTheme('light')" class="<?php echo $theme === 'light' ? 'border-primary ring-2 ring-primary/20' : 'border-card-border'; ?> relative flex flex-col items-center justify-center p-4 border-2 rounded-2xl hover:border-primary/50 transition-all bg-gray-50">
                                <i data-lucide="sun" class="w-6 h-6 mb-2 text-yellow-500"></i><span class="text-xs font-bold text-gray-800">Light</span>
                            </button>
                            <button onclick="updateTheme('dark')" class="<?php echo $theme === 'dark' ? 'border-primary ring-2 ring-primary/20' : 'border-card-border'; ?> relative flex flex-col items-center justify-center p-4 border-2 rounded-2xl hover:border-primary/50 transition-all bg-gray-900 !border-gray-700">
                                <i data-lucide="moon" class="w-6 h-6 mb-2 text-indigo-400"></i><span class="text-xs font-bold text-white">Dark</span>
                            </button>
                            <button onclick="updateTheme('system')" class="<?php echo $theme === 'system' ? 'border-primary ring-2 ring-primary/20' : 'border-card-border'; ?> relative flex flex-col items-center justify-center p-4 border-2 rounded-2xl hover:border-primary/50 transition-all bg-gray-100">
                                <i data-lucide="monitor" class="w-6 h-6 mb-2 text-gray-500"></i><span class="text-xs font-bold text-gray-800">System</span>
                            </button>
                        </div>
                    </section>

                    <!-- 2. SECURITY & 2FA SETTINGS -->
                    <section class="bg-white p-6 md:p-8 rounded-[2rem] border border-card-border shadow-sm">
                        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-card-border">
                            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-500 border border-red-100"><i data-lucide="shield" class="w-6 h-6"></i></div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Security & Authentication</h3>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Protect your account with Two-Factor Authentication</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm">Two-Factor Authentication (2FA)</h4>
                                <p class="text-xs text-gray-400 mt-1">Require a 6-digit OTP sent to your email during login.</p>
                            </div>
                            <div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
                                <input type="checkbox" id="toggle-2fa" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300 transition-all duration-300 z-10" <?php echo $is_2fa ? 'checked' : ''; ?> onchange="toggle2FA(this)"/>
                                <label for="toggle-2fa" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-colors duration-300"></label>
                            </div>
                        </div>

                        <div class="bg-section-bg rounded-2xl p-5 border border-card-border">
                            <h4 class="font-bold text-gray-800 text-sm mb-4">Change Password</h4>
                            <div class="space-y-4">
                                <input type="password" id="cp_current" placeholder="Current Password" class="w-full px-4 py-3 bg-white border border-card-border rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-sm text-gray-800">
                                <input type="password" id="cp_new" placeholder="New Password" class="w-full px-4 py-3 bg-white border border-card-border rounded-xl focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-sm text-gray-800">
                                <div class="text-right">
                                    <button onclick="updatePassword()" id="pwdBtn" class="px-6 py-2 bg-gray-900 hover:bg-gray-800 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md">Update Password</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 3. RIDER DELIVERY SETTINGS -->
                    <section class="bg-white p-6 md:p-8 rounded-[2rem] border border-card-border shadow-sm">
                        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-card-border">
                            <div class="w-12 h-12 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 border border-orange-100"><i data-lucide="truck" class="w-6 h-6"></i></div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Delivery Logistics</h3>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Control how orders are broadcasted to you</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm">Auto-Accept Priority Mission</h4>
                                    <p class="text-xs text-gray-400 mt-1">Skip the broadcast ping and instantly claim nearby orders.</p>
                                </div>
                                <div class="relative inline-block w-12 mr-2 align-middle select-none">
                                    <input type="checkbox" id="r_auto" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer border-gray-300 transition-all duration-300 z-10" <?php echo !empty($rider['auto_accept']) ? 'checked' : ''; ?> onchange="updatePref()"/>
                                    <label for="r_auto" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-2">
                                    <h4 class="font-bold text-gray-800 text-sm">Preferred Discovery Radius</h4>
                                    <span id="radiusVal" class="text-xs font-bold text-primary"><?php echo $rider['radius'] ?? '5'; ?> km</span>
                                </div>
                                <input type="range" id="r_radius" min="1" max="15" value="<?php echo $rider['radius'] ?? '5'; ?>" oninput="document.getElementById('radiusVal').textContent = this.value + ' km'; updatePref()" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary">
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-5 right-5 transform translate-y-20 opacity-0 transition-all duration-300 z-50 bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-2xl font-medium text-sm flex items-center gap-3 border border-gray-700">
        <div id="toastIconContainer">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-400"></i>
        </div>
        <span id="toastMsg">Saved</span>
    </div>

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
            // Instant Visual Update mapping standard class logic to CSS variables!
            document.documentElement.setAttribute('data-theme', theme === 'dark' ? 'dark' : (theme === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : 'light'));
            
            // Reload UI strictly for updating borders of buttons
            const btns = document.querySelectorAll('section:first-of-type button');
            btns.forEach(b => {
                b.classList.remove('border-primary', 'ring-2', 'ring-primary/20');
                b.classList.add('border-card-border');
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
                // Revert if failed
                if (!data.success) checkbox.checked = !checkbox.checked;
            } catch (e) {
                checkbox.checked = !checkbox.checked;
                showToast("Connection failed.", true);
            }
        }

        async function updatePref() {
            const rider = {
                auto_accept: document.getElementById('r_auto').checked,
                radius: document.getElementById('r_radius').value
            };
            await saveSettings({rider});
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
                if(data.success) {
                    cp.value = ''; np.value = '';
                }
            } catch(e) {
                showToast("Network error", true);
            }
            btn.disabled = false;
            btn.textContent = "Update Password";
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
