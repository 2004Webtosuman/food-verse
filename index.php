<?php
// index.php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
// Redirect logged-in riders or admins to their dashboards
if (is_delivery()) {
    redirect('delivery/dashboard.php');
} elseif (is_admin()) {
    redirect('admin/dashboard.php');
}

// Fetch Categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Fetch All Products for Instant Filter
$products = [];
try {
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.is_deal DESC, p.id DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// ═══════════ Recommendation Engine ═══════════
require_once 'includes/recommendation_engine.php';
$recEngine = new RecommendationEngine($pdo);
$currentUserId = $_SESSION['user_id'] ?? 0;

$rec_popular = [];
$rec_recommended = [];
$rec_collaborative = [];

try {
    if ($currentUserId) {
        $rec_popular = $recEngine->getPopularForYou($currentUserId, 8);
        $rec_recommended = $recEngine->getRecommendedForYou($currentUserId, 8);
        $rec_collaborative = $recEngine->getCollaborativeFiltering($currentUserId, 8);
    }
    // Fallback: if personalized results are empty, use global trending
    if (empty($rec_popular)) {
        $rec_popular = $recEngine->getTrendingNow(8);
    }
} catch (Exception $e) {
    // Silent fail — recommendations are non-critical
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'includes/header_meta.php'; ?>
    <title>FoodVerse - Premium Menu</title>
    <link rel="stylesheet" href="assets/css/modern_menu.css">
    <style type="text/tailwindcss">
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        /* Filter Pills */
        .filter-pill {
            @apply flex items-center gap-2 px-4 py-2.5 rounded-2xl border border-card-border bg-white text-gray-600 text-sm font-bold transition-all cursor-pointer hover:border-primary/30;
        }
        .filter-pill.active {
            @apply bg-primary text-white border-primary shadow-lg shadow-primary/20;
        }
        .filter-pill.active .dot {
            @apply bg-white;
        }
        .dot {
            @apply w-3 h-3 rounded-full border border-black/5;
        }

        /* Custom Dropdown Styling */
        .sort-dropdown {
            @apply flex items-center gap-2 px-3 py-2 rounded-xl border border-card-border bg-white text-gray-600 text-[10px] font-black uppercase tracking-widest transition-all cursor-pointer hover:border-primary/30 outline-none appearance-none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            padding-right: 28px;
        }

        /* Category Scroll */
        .category-scroll {
            @apply flex overflow-x-auto gap-6 pb-6 pt-2 scrollbar-hide;
        }
        .category-item {
            @apply flex flex-col items-center gap-3 transition-all cursor-pointer min-w-[70px];
        }
        .category-item .icon-box {
            @apply w-16 h-16 bg-white rounded-full flex items-center justify-center border border-card-border shadow-sm transition-all;
        }
        .category-item.active .icon-box {
            @apply border-primary ring-4 ring-primary/10 scale-110;
        }
        .category-item span {
            @apply text-[10px] font-black uppercase tracking-widest text-gray-400 whitespace-nowrap;
        }
        .category-item.active span {
            @apply text-primary;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-app-bg pb-24 font-outfit">

    <!-- Header -->
    <header class="p-6 pb-2 bg-white/80 sticky top-0 z-50 backdrop-blur-md border-b border-card-border">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-primary">FoodVerse</h1>
                <?php if (is_admin()): ?>
                    <a href="admin/dashboard.php"
                        class="px-4 py-2 bg-primary/10 border border-primary/20 text-primary text-[11px] font-black uppercase tracking-widest rounded-xl hover:bg-primary hover:text-white transition-all shadow-sm flex items-center gap-2">
                        <i data-lucide="layout-dashboard" class="w-3 h-3"></i>
                        Dashboard
                    </a>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <!-- Settings Button -->
                <?php if (is_logged_in()): ?>
                    <a href="customer/settings.php"
                        class="bg-white p-2 md:p-2.5 rounded-xl shadow-md hover:shadow-lg transition-all border border-card-border text-gray-400 hover:text-primary">
                        <i data-lucide="settings" class="w-5 h-5 md:w-6 h-6"></i>
                    </a>
                <?php endif; ?>

                <!-- Notification Button -->
                <?php if (is_logged_in() && ($_SESSION['user_role'] ?? '') === 'user'): ?>
                <a href="customer/notifications.php" class="relative">
                    <button class="bg-white p-2 md:p-2.5 rounded-xl shadow-md hover:shadow-lg transition-all border border-card-border group">
                        <i data-lucide="bell" class="w-5 h-5 md:w-6 h-6 text-gray-700 group-hover:text-primary transition-colors"></i>
                        <span id="notif-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] md:text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold opacity-0 transition-opacity">0</span>
                    </button>
                </a>
                <?php endif; ?>

                <!-- Cart Button -->
                <a href="customer/cart.php" class="relative">
                    <button
                        class="bg-white p-2 md:p-2.5 rounded-xl shadow-md hover:shadow-lg transition-all border border-card-border">
                        <i data-lucide="shopping-cart" class="w-5 h-5 md:w-6 h-6 text-primary"></i>
                        <span
                            class="absolute -top-1 -right-1 bg-primary text-white text-[9px] md:text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                            <?php echo get_cart_count(); ?>
                        </span>
                    </button>
                </a>

                <!-- Auth Button -->
                <?php if (is_logged_in()): ?>
                    <a href="logout.php"
                        class="bg-white p-2 md:p-2.5 rounded-xl text-gray-400 hover:text-red-500 shadow-md transition-all border border-card-border">
                        <i data-lucide="log-out" class="w-5 h-5 md:w-6 h-6"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php"
                        class="bg-primary text-white px-4 md:px-5 py-2 md:py-2.5 rounded-xl text-[10px] md:text-xs font-bold hover:bg-primary-hover transition-all shadow-md">Log
                        In</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Deliver To Selector -->
        <?php $loc = get_user_location(); ?>
        <button id="btn-open-location" class="flex items-center gap-2 text-gray-500 hover:text-gray-800 transition-all">
            <i data-lucide="map-pin" class="w-4 h-4 text-primary"></i>
            <div class="text-left">
                <p class="text-[10px] uppercase font-bold tracking-widest text-primary leading-none">Deliver to</p>
                <p id="display-location" class="text-xs font-bold text-gray-700 truncate max-w-[200px]">
                    <?php echo $loc ? $loc['full_address'] : 'Select your location'; ?>
                </p>
            </div>
            <i data-lucide="chevron-down" class="w-3 h-3 text-gray-400"></i>
        </button>
    </header>

    <!-- Flash Toast -->
    <?php
    $flash = $_SESSION['flash_message'] ?? '';
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    if ($flash):
        $is_error = $flash_type === 'error';
    ?>
    <div id="flashToast" class="fixed top-20 left-1/2 -translate-x-1/2 z-[200] flex items-center gap-2 px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold animate-fade-in
        <?php echo $is_error ? 'bg-red-500 text-white' : 'bg-green-500 text-white'; ?>"
        style="max-width:90vw">
        <i data-lucide="<?php echo $is_error ? 'alert-triangle' : 'check-circle-2'; ?>" class="w-4 h-4 shrink-0"></i>
        <span><?php echo htmlspecialchars($flash); ?></span>
    </div>
    <script>setTimeout(()=>{const t=document.getElementById('flashToast');if(t)t.style.display='none';},4000);</script>
    <?php endif; ?>

    <!-- Modern Search Section -->
    <div class="px-6 my-10">
        <div id="searchBar"
            class="bg-section-bg rounded-[2rem] p-1.5 flex items-center border border-card-border transition-all">
            <div class="flex-1 px-4">
                <input type="text" id="searchInput" placeholder="Search Food"
                    class="w-full bg-transparent border-none focus:outline-none text-gray-700 font-bold placeholder:text-gray-400 py-3.5">
            </div>
            <button onclick="performSearch()"
                class="bg-primary hover:bg-primary-hover text-white p-4 rounded-[1.75rem] shadow-lg shadow-primary/20 transition-all active:scale-95">
                <i data-lucide="search" class="w-6 h-6"></i>
            </button>
        </div>
    </div>

    <!-- Premium Category Bar -->
    <section class="px-6 mb-8">
        <div class="category-container">
            <div class="category-bar no-scrollbar">
                <div class="category-item active" data-category="all">
                    <div class="category-icon-wrapper">
                        <i data-lucide="layout-grid"></i>
                    </div>
                    <span class="category-name">All</span>
                </div>
                <?php
                $emoji_map = [
                    'Beverages' => '🥤',
                    'Fast Food' => '🍔',
                    'Desserts' => '🍰'
                ];
                foreach ($categories as $cat):
                    $emoji = $emoji_map[$cat['name']] ?? '🍲';
                ?>
                <div class="category-item" data-category="<?php echo sanitize($cat['name']); ?>">
                    <div class="category-icon-wrapper">
                        <?php if(!empty($cat['icon']) && file_exists($cat['icon'])): ?>
                            <img src="<?php echo $cat['icon']; ?>" class="w-full h-full object-cover rounded-[18px]">
                        <?php else: ?>
                            <?php echo $emoji; ?>
                        <?php endif; ?>
                    </div>
                    <span class="category-name"><?php echo sanitize($cat['name']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Dynamic Tag Filters -->
    <section class="px-6 mb-8">
        <div class="tag-filters no-scrollbar">
            <div class="tag-pill" data-tag="spicy">
                <span>🌶️ Spicy</span>
            </div>
            <div class="tag-pill" data-tag="premium">
                <span>👑 Premium</span>
            </div>
            <div class="tag-pill" data-tag="combo">
                <span>🍱 Combo</span>
            </div>
            <div class="tag-pill" data-tag="healthy">
                <span>🥗 Healthy</span>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!--                  RECOMMENDATION SECTIONS               -->
    <!-- ═══════════════════════════════════════════════════════ -->

    <?php if (!empty($rec_popular)): ?>
    <!-- 🔥 Popular / Trending Section -->
    <section class="px-6 mb-10">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl flex items-center justify-center shadow-lg shadow-orange-200">
                    <i data-lucide="flame" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-900 tracking-tight">
                        <?php echo $currentUserId ? 'Popular For You' : 'Trending Now'; ?>
                    </h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <?php echo $currentUserId ? 'Based on your taste' : 'What everyone is ordering'; ?>
                    </p>
                </div>
            </div>
            <span class="bg-gradient-to-r from-orange-100 to-red-100 text-orange-600 text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-wider">
                <?php echo count($rec_popular); ?> items
            </span>
        </div>

        <div class="flex overflow-x-auto gap-4 pb-4 -mx-2 px-2 rec-scroll">
            <?php foreach ($rec_popular as $rp): ?>
            <div class="product-card-v2 min-w-[170px] max-w-[170px] bg-white rounded-2xl p-3 shadow-sm border border-card-border flex flex-col group hover:shadow-[0_0_20px_rgba(255,107,53,0.15)] hover:border-primary/40 transition-all duration-300 flex-shrink-0"
                 data-category="<?php echo sanitize($rp['category_name']); ?>"
                 data-spicy="<?php echo $rp['is_spicy'] ?? 0; ?>"
                 data-premium="<?php echo $rp['is_premium'] ?? 0; ?>"
                 data-combo="<?php echo $rp['is_combo'] ?? 0; ?>"
                 data-healthy="<?php echo $rp['is_healthy'] ?? 0; ?>">
                <a href="product.php?id=<?php echo $rp['id']; ?>" class="relative mb-3 overflow-hidden rounded-xl block">
                    <img src="<?php echo $rp['image_url']; ?>" alt="<?php echo sanitize($rp['name']); ?>"
                         class="w-full aspect-square object-cover bg-section-bg group-hover:scale-105 transition-transform duration-500">
                    <?php if (($rp['avg_rating'] ?? 0) > 0): ?>
                    <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded-lg flex items-center gap-1 shadow-sm">
                        <i data-lucide="star" class="w-3 h-3 text-yellow-400 fill-current"></i>
                        <span class="text-[10px] font-black text-gray-700"><?php echo number_format($rp['avg_rating'], 1); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($rp['popularity_score'] ?? 0) > 2): ?>
                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-0.5 rounded-lg text-[9px] font-black uppercase shadow-sm">
                        Hot
                    </div>
                    <?php endif; ?>
                </a>
                <a href="product.php?id=<?php echo $rp['id']; ?>" class="font-bold text-xs text-gray-800 line-clamp-1 mb-1 hover:text-primary transition-colors">
                    <?php echo sanitize($rp['name']); ?>
                </a>
                <div class="flex justify-between items-center mt-auto">
                    <p class="font-black text-primary text-sm">Rs. <?php echo number_format($rp['price'], 0); ?></p>
                    <form action="customer/cart.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $rp['id']; ?>">
                        <button type="submit" class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all active:scale-90">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($rec_recommended)): ?>
    <!-- ⭐ Recommended For You Section -->
    <section class="px-6 mb-10">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-violet-400 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-200">
                    <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-900 tracking-tight">Recommended For You</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Curated picks just for you</p>
                </div>
            </div>
            <span class="bg-gradient-to-r from-violet-100 to-purple-100 text-purple-600 text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-wider">
                AI Picks
            </span>
        </div>

        <div class="flex overflow-x-auto gap-4 pb-4 -mx-2 px-2 rec-scroll">
            <?php foreach ($rec_recommended as $rr): ?>
            <div class="product-card-v2 min-w-[170px] max-w-[170px] bg-white rounded-2xl p-3 shadow-sm border border-card-border flex flex-col group hover:shadow-[0_0_20px_rgba(139,92,246,0.15)] hover:border-purple-300/50 transition-all duration-300 flex-shrink-0"
                 data-category="<?php echo sanitize($rr['category_name']); ?>"
                 data-spicy="<?php echo $rr['is_spicy'] ?? 0; ?>"
                 data-premium="<?php echo $rr['is_premium'] ?? 0; ?>"
                 data-combo="<?php echo $rr['is_combo'] ?? 0; ?>"
                 data-healthy="<?php echo $rr['is_healthy'] ?? 0; ?>">
                <a href="product.php?id=<?php echo $rr['id']; ?>" class="relative mb-3 overflow-hidden rounded-xl block">
                    <img src="<?php echo $rr['image_url']; ?>" alt="<?php echo sanitize($rr['name']); ?>"
                         class="w-full aspect-square object-cover bg-section-bg group-hover:scale-105 transition-transform duration-500">
                    <?php if (($rr['avg_rating'] ?? 0) > 0): ?>
                    <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded-lg flex items-center gap-1 shadow-sm">
                        <i data-lucide="star" class="w-3 h-3 text-yellow-400 fill-current"></i>
                        <span class="text-[10px] font-black text-gray-700"><?php echo number_format($rr['avg_rating'], 1); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($rr['recommendation_score']) && $rr['recommendation_score'] > 0.5): ?>
                    <div class="absolute top-2 right-2 bg-purple-500 text-white px-2 py-0.5 rounded-lg text-[9px] font-black uppercase shadow-sm">
                        Top Pick
                    </div>
                    <?php endif; ?>
                </a>
                <a href="product.php?id=<?php echo $rr['id']; ?>" class="font-bold text-xs text-gray-800 line-clamp-1 mb-1 hover:text-primary transition-colors">
                    <?php echo sanitize($rr['name']); ?>
                </a>
                <div class="flex justify-between items-center mt-auto">
                    <p class="font-black text-primary text-sm">Rs. <?php echo number_format($rr['price'], 0); ?></p>
                    <form action="customer/cart.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $rr['id']; ?>">
                        <button type="submit" class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center text-purple-500 hover:bg-purple-500 hover:text-white transition-all active:scale-90">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($rec_collaborative)): ?>
    <!-- 👥 People Also Ordered Section -->
    <section class="px-6 mb-10">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-200">
                    <i data-lucide="users" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-900 tracking-tight">People Also Ordered</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Based on similar tastes</p>
                </div>
            </div>
            <span class="bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-600 text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-wider">
                Community
            </span>
        </div>

        <div class="flex overflow-x-auto gap-4 pb-4 -mx-2 px-2 rec-scroll">
            <?php foreach ($rec_collaborative as $rc): ?>
            <div class="product-card-v2 min-w-[170px] max-w-[170px] bg-white rounded-2xl p-3 shadow-sm border border-card-border flex flex-col group hover:shadow-[0_0_20px_rgba(16,185,129,0.15)] hover:border-emerald-300/50 transition-all duration-300 flex-shrink-0"
                 data-category="<?php echo sanitize($rc['category_name']); ?>"
                 data-spicy="<?php echo $rc['is_spicy'] ?? 0; ?>"
                 data-premium="<?php echo $rc['is_premium'] ?? 0; ?>"
                 data-combo="<?php echo $rc['is_combo'] ?? 0; ?>"
                 data-healthy="<?php echo $rc['is_healthy'] ?? 0; ?>">
                <a href="product.php?id=<?php echo $rc['id']; ?>" class="relative mb-3 overflow-hidden rounded-xl block">
                    <img src="<?php echo $rc['image_url']; ?>" alt="<?php echo sanitize($rc['name']); ?>"
                         class="w-full aspect-square object-cover bg-section-bg group-hover:scale-105 transition-transform duration-500">
                    <?php if (($rc['avg_rating'] ?? 0) > 0): ?>
                    <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded-lg flex items-center gap-1 shadow-sm">
                        <i data-lucide="star" class="w-3 h-3 text-yellow-400 fill-current"></i>
                        <span class="text-[10px] font-black text-gray-700"><?php echo number_format($rc['avg_rating'], 1); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($rc['similar_user_count']) && $rc['similar_user_count'] > 1): ?>
                    <div class="absolute top-2 right-2 bg-emerald-500 text-white px-2 py-0.5 rounded-lg text-[9px] font-black shadow-sm flex items-center gap-1">
                        <i data-lucide="trending-up" class="w-3 h-3"></i>
                        <?php echo $rc['similar_user_count']; ?>
                    </div>
                    <?php endif; ?>
                </a>
                <a href="product.php?id=<?php echo $rc['id']; ?>" class="font-bold text-xs text-gray-800 line-clamp-1 mb-1 hover:text-primary transition-colors">
                    <?php echo sanitize($rc['name']); ?>
                </a>
                <div class="flex justify-between items-center mt-auto">
                    <p class="font-black text-primary text-sm">Rs. <?php echo number_format($rc['price'], 0); ?></p>
                    <form action="customer/cart.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $rc['id']; ?>">
                        <button type="submit" class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all active:scale-90">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <style>
        .rec-scroll::-webkit-scrollbar { display: none; }
        .rec-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <!-- Main Menu Content -->
    <section class="px-6 mb-24">
        <div class="flex flex-col gap-6 mb-8">
            <div class="flex items-center justify-between">
                <h2 id="gridHeading" class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <span id="headingText">All Products (<?php echo count($products); ?>)</span>
                    <span id="loadingSpinner"
                        class="hidden w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></span>
                </h2>
                
                <div class="relative">
                    <select id="sortSelect" class="sort-dropdown !py-2 !text-[9px]">
                        <option value="best_match">Best Match</option>
                        <option value="price_low">Price ↑</option>
                        <option value="price_high">Price ↓</option>
                        <option value="rating_high">Rating ↑</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="productGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="product-card-v2" 
                     data-category="<?php echo sanitize($product['category_name']); ?>"
                     data-spicy="<?php echo $product['is_spicy']; ?>"
                     data-premium="<?php echo $product['is_premium']; ?>"
                     data-combo="<?php echo $product['is_combo']; ?>"
                     data-healthy="<?php echo $product['is_healthy']; ?>">
                    
                    <div class="product-image-container">
                        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="product-img">
                        
                        <div class="badge-container">
                            <?php if($product['is_spicy']): ?> <span class="tag-badge spicy">Spicy</span> <?php endif; ?>
                            <?php if($product['is_premium']): ?> <span class="tag-badge premium">Premium</span> <?php endif; ?>
                            <?php if($product['is_healthy']): ?> <span class="tag-badge healthy">Healthy</span> <?php endif; ?>
                            <?php if($product['is_combo']): ?> <span class="tag-badge combo">Combo</span> <?php endif; ?>
                        </div>

                        <form action="actions/wishlist_action.php" method="POST">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="wishlist-btn-modern">
                                <i data-lucide="heart" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>

                    <div class="product-info">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="product-name-modern">
                            <?php echo sanitize($product['name']); ?>
                        </a>
                        <p class="product-price-modern">Rs. <?php echo number_format($product['price'], 0); ?></p>
                        
                        <form action="customer/cart.php" method="POST">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="add-btn-modern">Add to cart</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <style>
        .filter-btn.active {
            @apply bg-primary text-white shadow-lg shadow-primary/20;
        }
    </style>

    <?php include 'includes/bottom_nav.php'; ?>
    <script src="assets/js/modern_filter.js"></script>

    <!-- Location Selection Modal -->
    <div id="location-modal-overlay"
        class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:p-4 opacity-0 pointer-events-none transition-all duration-300">
        <div id="location-modal-content"
            class="w-full sm:max-w-md bg-white text-gray-900 rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl border-t sm:border border-card-border transform translate-y-full sm:translate-y-0 sm:scale-95 transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold tracking-tight text-gray-900">Delivery Location</h3>
                <button id="btn-close-location" class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition-all">
                    <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
                </button>
            </div>

            <div id="location-picker-container">
                <!-- LocationPicker component will render here -->
            </div>
        </div>
    </div>

    <script>
        // Use a more reliable way to initialize icons
        function refreshIcons() {
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        document.addEventListener('DOMContentLoaded', refreshIcons);
        window.addEventListener('load', refreshIcons);

        const searchInput = document.getElementById('searchInput');
        const productGrid = document.getElementById('productGrid');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const sortSelect = document.getElementById('sortSelect');

        let currentCategoryName = 'All Products';
        let currentCategoryId = 0;
        let activeVegType = 'all';
        let activeSort = 'best_match';

        function renderProducts(products) {
            const productGrid = document.getElementById('productGrid');
            const headingText = document.getElementById('headingText');
            if (headingText) headingText.textContent = `Search Results (${products.length})`;
            
            if (products.length === 0) {
                productGrid.innerHTML = `
                    <div class="col-span-full flex flex-col items-center justify-center py-20 opacity-60">
                        <i data-lucide="search-x" class="w-16 h-16 mb-4 text-gray-300"></i>
                        <h3 class="text-xl font-bold text-gray-400">No results found</h3>
                        <p class="text-sm text-gray-400 mt-2">Try searching for something else</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            productGrid.innerHTML = products.map(p => `
                <div class="product-card-v2" 
                     data-category="${p.category_name}"
                     data-spicy="${p.is_spicy || 0}"
                     data-premium="${p.is_premium || 0}"
                     data-combo="${p.is_combo || 0}"
                     data-healthy="${p.is_healthy || 0}">
                    
                    <div class="product-image-container">
                        <img src="${p.image_url}" alt="${p.name}" class="product-img">
                        <div class="badge-container">
                            ${p.is_spicy == 1 ? '<span class="tag-badge spicy">Spicy</span>' : ''}
                            ${p.is_premium == 1 ? '<span class="tag-badge premium">Premium</span>' : ''}
                            ${p.is_healthy == 1 ? '<span class="tag-badge healthy">Healthy</span>' : ''}
                            ${p.is_combo == 1 ? '<span class="tag-badge combo">Combo</span>' : ''}
                        </div>
                        <form action="actions/wishlist_action.php" method="POST">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="product_id" value="${p.id}">
                            <button type="submit" class="wishlist-btn-modern">
                                <i data-lucide="heart" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>

                    <div class="product-info">
                        <a href="product.php?id=${p.id}" class="product-name-modern">${p.name}</a>
                        <p class="product-price-modern">Rs. ${parseInt(p.price).toLocaleString()}</p>
                        <form action="customer/cart.php" method="POST">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="${p.id}">
                            <button type="submit" class="add-btn-modern">Add to cart</button>
                        </form>
                    </div>
                </div>
            `).join('');
            
            if (window.lucide) lucide.createIcons();
            // Critical: Apply current filters to newly rendered search results
            if (window.filterProducts) window.filterProducts();
        }

        async function performSearch() {
            const query = searchInput.value;
            const url = `actions/search_products.php?query=${encodeURIComponent(query)}&category_id=${currentCategoryId}&veg_type=${activeVegType}&sort=${activeSort}`;

            const loader = document.getElementById('loadingSpinner');
            const gridHeading = document.getElementById('gridHeading');

            try {
                if (loader) loader.classList.remove('hidden');
                if (productGrid) productGrid.style.opacity = '0.5';

                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    renderProducts(data.products);
                }
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                if (loader) loader.classList.add('hidden');
                if (productGrid) productGrid.style.opacity = '1';
            }
        }

        searchInput.addEventListener('input', debounce(performSearch, 300));

        sortSelect.addEventListener('change', (e) => {
            activeSort = e.target.value;
            performSearch();
        });

        // Initialize with all products (no need for initial performSearch as PHP rendered it,
        // but performSearch ensures any initial query or sort is applied)
        // performSearch(); 

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Location Picker Logic
        const locationModal = document.getElementById('location-modal-overlay');
        const locationContent = document.getElementById('location-modal-content');
        const btnOpenLocation = document.getElementById('btn-open-location');
        const btnCloseLocation = document.getElementById('btn-close-location');
        const displayLocation = document.getElementById('display-location');

        const locPicker = new LocationPicker('location-picker-container', {
            onSave: (location) => {
                displayLocation.innerText = location.full_address;
                closeLocationModal();
                // Refresh categories or search if needed
                performSearch();
            }
        });

        function openLocationModal() {
            locationModal.classList.remove('opacity-0', 'pointer-events-none');
            locationContent.classList.remove('translate-y-full', 'sm:scale-95');
            document.body.style.overflow = 'hidden';
        }

        function closeLocationModal() {
            locationModal.classList.add('opacity-0', 'pointer-events-none');
            locationContent.classList.add('translate-y-full', 'sm:scale-95');
            document.body.style.overflow = '';
        }

        btnOpenLocation.addEventListener('click', openLocationModal);
        btnCloseLocation.addEventListener('click', closeLocationModal);

        // Close on clicking overlay
        locationModal.addEventListener('click', (e) => {
            if (e.target === locationModal) closeLocationModal();
        });

        // Auto-open if no location is set
        <?php if (!$loc): ?>
            setTimeout(openLocationModal, 1000);
        <?php endif; ?>
    </script>
</body>

</html>