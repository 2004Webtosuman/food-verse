<?php
// customer/notifications.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Because they are viewing the page, mark all as read automatically
$pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE")->execute([$user_id]);

// Fetch recent notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Notifications - FoodVerse</title>
</head>
<body class="bg-app-bg min-h-screen pb-32">

    <!-- Header -->
    <header class="p-6 flex items-center justify-between bg-white/80 sticky top-0 z-40 backdrop-blur-md border-b border-card-border">
        <button onclick="history.back()" class="p-2 bg-white rounded-full hover:bg-gray-50 transition-all border border-card-border shadow-sm">
            <i data-lucide="chevron-left" class="w-6 h-6 text-gray-700"></i>
        </button>
        <h1 class="text-xl font-bold tracking-tight text-gray-900">Notifications</h1>
        <div class="w-10"></div>
    </header>

    <div class="p-6 max-w-lg mx-auto space-y-4">
        <?php if (empty($notifications)): ?>
            <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                <div class="w-20 h-20 bg-section-bg rounded-full flex items-center justify-center mb-6">
                    <i data-lucide="bell-off" class="w-10 h-10 text-gray-300"></i>
                </div>
                <p class="text-lg font-medium text-gray-500">No notifications yet</p>
                <p class="text-xs text-gray-400 mt-2">When your order status updates, it will appear here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <?php 
                    // Make link relative to customer directory if not already
                    $link = htmlspecialchars($n['link'] ?? '#');
                    if (strpos($link, 'customer/') === 0) {
                        $link = str_replace('customer/', '', $link);
                    }
                ?>
                <a href="<?php echo $link; ?>" class="block bg-white p-5 rounded-[2rem] border border-card-border shadow-sm hover:shadow-md hover:border-primary/30 transition-all relative group">
                    <div class="flex items-start gap-4 z-10 relative">
                        <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center shrink-0 text-primary transition-all">
                            <i data-lucide="bell" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start mb-1 gap-2">
                                <h3 class="font-black text-gray-900 group-hover:text-primary transition-colors text-[13px] tracking-wide uppercase"><?php echo htmlspecialchars($n['title']); ?></h3>
                                <span class="text-[9px] font-black text-gray-400 tracking-widest uppercase mt-1 shrink-0"><?php echo date('M d, g:i A', strtotime($n['created_at'])); ?></span>
                            </div>
                            <p class="text-xs text-gray-500 font-medium leading-tight">
                                <?php echo htmlspecialchars($n['message']); ?>
                            </p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
