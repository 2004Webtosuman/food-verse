<?php
// wishlist.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

// Redirect riders to their dashboard
if (is_delivery()) {
    redirect('../delivery/dashboard.php');
}

$user_id = $_SESSION['user_id'];
$wishlist_items = [];

try {
    $stmt = $pdo->prepare("SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?");
    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback for demo
    $wishlist_items = [
        ['id' => 1, 'name' => 'Double Beef burger', 'price' => 1000, 'image_url' => 'https://via.placeholder.com/300x200?text=Burger'],
        ['id' => 2, 'name' => 'Peperoni pizza', 'price' => 620, 'image_url' => 'https://via.placeholder.com/300x200?text=Pizza']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>My Wishlist - FoodVerse</title>
</head>
<body class="bg-app-bg pb-24 font-outfit">

    <!-- Header -->
    <header class="p-6 flex items-center justify-between bg-white/80 sticky top-0 z-50 backdrop-blur-md border-b border-card-border">
        <div class="flex items-center gap-4">
            <button onclick="history.back()" class="p-2 bg-white rounded-full hover:bg-gray-50 transition-all border border-card-border shadow-sm">
                <i data-lucide="chevron-left" class="w-6 h-6 text-gray-700"></i>
            </button>
            <h1 class="text-2xl font-bold text-gray-900">Wishlist</h1>
        </div>
        <a href="cart.php" class="relative">
            <button class="bg-white p-2 rounded-full hover:bg-gray-50 transition-all shadow-sm border border-card-border">
                <i data-lucide="shopping-cart" class="w-6 h-6 text-primary"></i>
                <span class="absolute -top-1 -right-1 bg-primary text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                    <?php echo get_cart_count(); ?>
                </span>
            </button>
        </a>
    </header>

    <!-- Wishlist Search -->
    <div class="px-6 my-6">
        <div class="relative group">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
            <input type="text" placeholder="Search in wishlist..." class="w-full pl-12 pr-4 py-4 bg-white border border-card-border focus:border-primary/40 rounded-2xl focus:outline-none transition-all placeholder:text-gray-400 text-gray-800">
        </div>
    </div>

    <!-- Wishlist Content -->
    <div class="px-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php if (empty($wishlist_items)): ?>
            <div class="col-span-full text-center py-20">
                <div class="w-20 h-20 bg-section-bg rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="heart" class="w-10 h-10 text-gray-300"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-400">Your wishlist is empty</h2>
                <a href="../index.php" class="mt-4 inline-block text-primary font-bold border-b-2 border-primary/30">Go explore</a>
            </div>
        <?php else: ?>
            <?php foreach ($wishlist_items as $item): ?>
                <div class="bg-white p-4 rounded-3xl border border-card-border flex flex-col group hover:shadow-md hover:border-primary/20 transition-all">
                    <div class="relative mb-4 overflow-hidden rounded-2xl">
                        <img src="../<?php echo !empty($item['image_url']) ? $item['image_url'] : 'images/placeholder.png'; ?>" alt="<?php echo $item['name']; ?>" class="w-full aspect-square object-cover bg-section-bg group-hover:scale-110 transition-transform duration-500">
                        <form action="../actions/wishlist_action.php" method="POST" class="absolute top-3 right-3">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="p-2 bg-white/80 backdrop-blur-sm rounded-full shadow-md text-red-500 transition-colors">
                                <i data-lucide="heart" class="w-4 h-4 fill-current"></i>
                            </button>
                        </form>
                    </div>
                    <a href="../product.php?id=<?php echo $item['id']; ?>" class="font-bold text-sm text-gray-800 line-clamp-1 mb-1 hover:text-primary transition-colors"><?php echo sanitize($item['name']); ?></a>
                    <p class="font-black text-xl text-primary mb-4">Rs. <?php echo number_format($item['price'], 0); ?></p>
                    <form action="cart.php" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" class="w-full py-3 bg-primary/10 border border-primary/20 text-primary rounded-xl text-xs font-bold hover:bg-primary hover:text-white transition-all active:scale-95">
                            Add to cart
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
