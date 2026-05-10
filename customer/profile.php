<?php
// profile.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

// Redirect riders to their own profile
if (is_delivery()) {
    redirect('../delivery/profile.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'user';

$user = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = ['phone' => '+977 9812345678', 'address' => 'Manamaiju temple, Kathmandu, Nepal'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>My Profile - FoodVerse</title>
</head>
<body class="bg-app-bg pb-24 font-outfit">
    <style>
        @keyframes ai-rotate {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .ai-ring-container {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 150%;
            height: 150%;
            background: conic-gradient(from 0deg, #FF6B35, #6C5CE7, #00C2A8, #FF6B35);
            animation: ai-rotate 4s linear infinite;
        }
    </style>

    <!-- Header / Cover -->
    <div class="h-64 bg-gradient-to-br from-primary to-primary-hover relative">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-app-bg/90"></div>
        <div class="absolute top-8 left-6 z-10">
            <button onclick="history.back()" class="p-3 bg-white/20 backdrop-blur-sm rounded-full text-white hover:bg-white/30 transition-all">
                <i data-lucide="chevron-left" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="absolute -bottom-16 left-1/2 -translate-x-1/2 text-center">
            <div class="relative w-32 h-32 mx-auto">
                <!-- AI Animated Ring -->
                <div class="absolute inset-0 rounded-full overflow-hidden shadow-lg">
                    <div class="ai-ring-container"></div>
                </div>
                <!-- Inner circle for mask effect -->
                <div class="absolute inset-[3px] rounded-full bg-app-bg"></div>
                <!-- Profile Image -->
                <div class="absolute inset-[4px] rounded-full overflow-hidden">
                    <img src="<?php echo !empty($user['profile_pic']) ? '../' . $user['profile_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=FF6B35&color=fff'; ?>" id="preview" alt="Profile" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="mt-20 px-8 text-center mb-12">
        <h1 class="text-3xl font-black italic tracking-tighter text-gray-900 uppercase"><?php echo sanitize($user_name); ?></h1>
        <p class="text-primary text-xs font-bold uppercase tracking-[0.2em] mt-1"><?php echo sanitize($user_role); ?></p>
    </div>

    <!-- Info Cards -->
    <div class="px-6 space-y-4 mb-12">
        <div class="bg-white p-5 rounded-3xl border border-card-border flex items-center justify-between group hover:shadow-md hover:border-primary/20 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm"><?php echo sanitize($user['address'] ?? 'Set your location'); ?></h3>
                    <p class="text-gray-400 text-[10px] uppercase tracking-widest font-black">Location</p>
                </div>
            </div>
            <a href="edit_profile.php" class="px-4 py-2 bg-primary/10 border border-primary/20 text-primary rounded-xl text-xs font-bold hover:bg-primary hover:text-white transition-all">Edit</a>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-card-border flex items-center justify-between group hover:shadow-md hover:border-primary/20 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center text-secondary">
                    <i data-lucide="phone" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800 text-sm"><?php echo sanitize($user['phone'] ?? '+977 1234567890'); ?></h3>
                    <p class="text-gray-400 text-[10px] uppercase tracking-widest font-black">Phone Number</p>
                </div>
            </div>
            <a href="edit_profile.php" class="px-4 py-2 bg-primary/10 border border-primary/20 text-primary rounded-xl text-xs font-bold hover:bg-primary hover:text-white transition-all">Edit</a>
        </div>
    </div>

    <!-- Menu Items -->
    <div class="px-6 space-y-4">
        <?php if ($user_role === 'admin'): ?>
            <a href="../admin/dashboard.php" class="flex items-center justify-between bg-gradient-to-r from-primary/10 to-primary/5 p-6 rounded-3xl border border-primary/20 group hover:shadow-md transition-all">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white shadow-md">
                        <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <span class="font-black text-gray-900 text-lg uppercase tracking-tight">Admin Area</span>
                        <span class="text-primary/70 text-[10px] uppercase tracking-wider font-bold block">Management Console</span>
                    </div>
                </div>
                <i data-lucide="chevron-right" class="w-5 h-5 text-primary group-hover:translate-x-1 transition-transform"></i>
            </a>
        <?php endif; ?>
        
        <a href="settings.php" class="flex items-center justify-between bg-white p-6 rounded-3xl border border-card-border group hover:shadow-md hover:border-gray-300 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-700">
                    <i data-lucide="settings" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="font-black text-gray-900 text-lg uppercase tracking-tight">App Settings</span>
                    <span class="text-gray-500 text-[10px] uppercase tracking-wider font-bold block">Theme, 2FA & Privacy</span>
                </div>
            </div>
            <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300 group-hover:text-gray-700 group-hover:translate-x-1 transition-all"></i>
        </a>
        
        <a href="../feedback.php" class="flex items-center justify-between bg-white p-6 rounded-3xl border border-card-border group hover:shadow-md hover:border-accent/30 transition-all">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-accent/10 rounded-2xl flex items-center justify-center text-accent">
                    <i data-lucide="message-square-plus" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="font-black text-gray-900 text-lg uppercase tracking-tight">Give Feedback</span>
                    <span class="text-accent/70 text-[10px] uppercase tracking-wider font-bold block">Rate your experience</span>
                </div>
            </div>
            <i data-lucide="chevron-right" class="w-5 h-5 text-gray-300 group-hover:text-accent group-hover:translate-x-1 transition-all"></i>
        </a>
        
        <a href="../logout.php" class="w-full flex items-center justify-center bg-white p-5 rounded-3xl text-gray-400 font-bold hover:text-red-500 hover:bg-red-50 hover:border-red-200 border border-card-border transition-all mt-8 uppercase tracking-widest text-sm">
            Logout
        </a>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
