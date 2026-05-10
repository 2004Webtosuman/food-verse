<?php
// product.php
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($product_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        // Fetch reviews and average rating
        $revStmt = $pdo->prepare("SELECT pr.*, u.full_name, u.profile_pic FROM product_reviews pr JOIN users u ON pr.user_id = u.id WHERE pr.product_id = ? AND pr.status = 'active' ORDER BY pr.created_at DESC");
        $revStmt->execute([$product_id]);
        $reviews = $revStmt->fetchAll();
        
        $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM product_reviews WHERE product_id = ? AND status = 'active'");
        $avgStmt->execute([$product_id]);
        $ratingData = $avgStmt->fetch();
        $avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
        $reviewCount = $ratingData['count'];

    } catch (PDOException $e) {
        // Fallback or error handling
    }
}

// Fallback for demo
if (!$product) {
    if ($product_id == 1) {
        $product = ['id' => 1, 'name' => 'Double Beef burger', 'price' => 1000, 'description' => 'A delicious double beef burger with fresh lettuce, tomatoes, and our signature sauce.', 'image_url' => 'images/burger.png'];
    } else {
        $product = ['id' => 2, 'name' => 'Peperoni pizza', 'price' => 620, 'description' => 'Classic Italian pizza with premium pepperoni and extra cheese.', 'image_url' => 'images/pizza.png'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/header_meta.php'; ?>
    <title><?php echo sanitize($product['name']); ?> - FoodVerse</title>
</head>
<body class="bg-app-bg pb-24 font-outfit">

    <!-- Hero Image Area -->
    <div class="relative w-full h-[400px] overflow-hidden">
        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-app-bg"></div>
        
        <!-- Top Nav Over Image -->
        <div class="absolute top-0 left-0 right-0 p-6 flex justify-between items-center z-50">
            <button onclick="history.back()" class="p-3 bg-white/80 backdrop-blur-sm rounded-full shadow-md hover:bg-white transition-all">
                <i data-lucide="chevron-left" class="w-6 h-6 text-gray-800"></i>
            </button>
            <a href="customer/cart.php" class="relative">
                <button class="bg-white/80 backdrop-blur-sm p-2 rounded-full shadow-md hover:bg-white transition-all">
                    <i data-lucide="shopping-cart" class="w-6 h-6 text-primary"></i>
                    <span class="absolute -top-1 -right-1 bg-primary text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                        <?php echo get_cart_count(); ?>
                    </span>
                </button>
            </a>
        </div>
    </div>

    <!-- Product Info Content -->
    <div class="relative -mt-12 bg-app-bg rounded-t-[40px] px-8 py-10 shadow-[0_-10px_30px_rgba(0,0,0,0.05)] min-h-[500px] border-t border-card-border">
        <!-- Attributes Badge -->
        <div class="flex items-center gap-3 mb-8">
            <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl border border-card-border shadow-sm">
                <i data-lucide="flame" class="w-4 h-4 text-primary"></i>
                <span class="text-xs font-bold text-gray-600"><?php echo sanitize($product['calories'] ?? '850 cal'); ?></span>
            </div>
            <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl border border-card-border shadow-sm">
                <i data-lucide="clock" class="w-4 h-4 text-secondary"></i>
                <span class="text-xs font-bold text-gray-500"><?php echo sanitize($product['prep_time'] ?? '15-16 min'); ?></span>
            </div>
        </div>

        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-3xl font-black text-gray-900 mb-2 leading-tight uppercase italic tracking-tighter"><?php echo sanitize($product['name']); ?></h1>
                <div class="flex items-center gap-1 text-yellow-400">
                    <?php 
                    $fullStars = floor($avgRating);
                    for($i=0; $i<5; $i++) {
                        $fill = ($i < $fullStars) ? 'fill-current' : '';
                        echo "<i data-lucide='star' class='w-4 h-4 $fill'></i>";
                    }
                    ?>
                    <span class="text-gray-400 text-xs ml-2 font-bold">(<?php echo $avgRating ?: 'No ratings'; ?>)</span>
                </div>
            </div>
            <div class="text-3xl font-black text-primary">
                Rs. <?php echo number_format($product['price'], 0); ?>
            </div>
        </div>

        <!-- Description -->
        <div class="mb-10">
            <h2 class="text-lg font-black text-gray-900 mb-4 uppercase tracking-wider">Description</h2>
            <p class="text-gray-500 leading-relaxed text-sm font-medium">
                <?php echo sanitize($product['description']); ?>
            </p>
        </div>

        <!-- Review System -->
        <div class="mb-10 pt-8 border-t border-card-border">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Customer Reviews</h2>
                <span class="bg-primary/10 text-primary text-[10px] font-black px-3 py-1 rounded-full"><?php echo $reviewCount; ?> REVIEWS</span>
            </div>

            <?php if (is_logged_in()): ?>
                <!-- Submission Form (Sexy & Minimal) -->
                <div class="bg-section-bg rounded-3xl p-6 mb-10 border border-primary/5">
                    <h3 class="font-black text-sm text-gray-800 mb-4 tracking-wide uppercase">Rate this product</h3>
                    <form action="actions/submit_review.php" method="POST" class="space-y-4">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <!-- Star Picker -->
                        <div class="flex gap-2 mb-4" id="starPicker">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <label class="cursor-pointer group">
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" required class="hidden peer">
                                    <i data-lucide="star" class="w-8 h-8 text-gray-300 group-hover:text-yellow-400 transition-colors peer-checked:text-yellow-400 peer-checked:fill-current"></i>
                                </label>
                            <?php endfor; ?>
                        </div>

                        <div class="relative">
                            <textarea name="review_text" rows="3" placeholder="Tell us more about your experience..." 
                                class="w-full bg-white border border-card-border rounded-2xl p-4 text-sm focus:outline-none focus:ring-4 focus:ring-primary/10 transition-all font-medium placeholder:text-gray-300"></textarea>
                        </div>
                        
                        <button type="submit" class="w-full py-4 bg-primary text-white font-black rounded-2xl text-xs uppercase tracking-widest shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all">
                            Submit Review
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Review List Container -->
            <div class="space-y-6">
                <?php if ($reviews): ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="bg-white border border-card-border rounded-3xl p-6 flex gap-4 transition-all hover:border-primary/10 hover:shadow-xl hover:shadow-primary/5">
                            <div class="w-12 h-12 rounded-2xl overflow-hidden shadow-sm border border-card-border flex-shrink-0">
                                <img src="<?php echo $rev['profile_pic'] ?: 'images/default-avatar.png'; ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-black text-gray-900 text-sm tracking-tight"><?php echo sanitize($rev['full_name']); ?></h4>
                                        <div class="flex text-yellow-500 gap-0.5 mt-1">
                                            <?php for($i=0; $i<$rev['rating']; $i++) echo '<i data-lucide="star" class="w-3 h-3 fill-current"></i>'; ?>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                </div>
                                <p class="text-gray-500 text-xs leading-relaxed font-medium">
                                    "<?php echo sanitize($rev['review_text']); ?>"
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-10 opacity-30">
                        <i data-lucide="message-square-dashed" class="w-12 h-12 mx-auto mb-4"></i>
                        <p class="text-sm font-bold uppercase tracking-widest">No reviews yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quantity and Action -->
        <div class="flex items-center gap-4 mt-auto pt-8 border-t border-card-border">
            <form action="actions/wishlist_action.php" method="POST">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <button type="submit" class="w-16 h-16 flex items-center justify-center bg-white border border-card-border rounded-2xl hover:text-primary transition-all hover:border-primary/30 active:scale-90 shadow-sm">
                    <i data-lucide="heart" class="w-6 h-6 text-gray-400"></i>
                </button>
            </form>
            <form action="customer/cart.php" method="POST" class="flex-1">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="action" value="add">
                <button type="submit" class="w-full bg-primary text-white h-16 rounded-2xl font-black hover:bg-primary-hover hover:shadow-lg transition-all active:scale-[0.98] text-lg uppercase tracking-widest shadow-md">
                    Add to Cart
                </button>
            </form>
        </div>

        <!-- ═══════════ Similar Products Section ═══════════ -->
        <?php
        require_once 'includes/recommendation_engine.php';
        $recEngine = new RecommendationEngine($pdo);
        $similarProducts = $recEngine->getSimilarProducts($product_id, 6);
        if (!empty($similarProducts)):
        ?>
        <div class="mt-12 pt-8 border-t border-card-border">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center">
                    <i data-lucide="sparkles" class="w-5 h-5 text-primary"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">You Might Also Like</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Similar to this item</p>
                </div>
            </div>
            <div class="flex overflow-x-auto gap-4 pb-4 -mx-2 px-2 scrollbar-hide">
                <?php foreach ($similarProducts as $sim): ?>
                <a href="product.php?id=<?php echo $sim['id']; ?>"
                   class="min-w-[160px] max-w-[160px] bg-white rounded-2xl p-3 shadow-sm border border-card-border flex flex-col group hover:shadow-lg hover:border-primary/30 transition-all duration-300 flex-shrink-0">
                    <div class="relative mb-3 overflow-hidden rounded-xl">
                        <img src="<?php echo $sim['image_url']; ?>" alt="<?php echo sanitize($sim['name']); ?>"
                             class="w-full aspect-square object-cover bg-section-bg group-hover:scale-105 transition-transform duration-500">
                        <?php if ($sim['avg_rating'] > 0): ?>
                        <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded-lg flex items-center gap-1">
                            <i data-lucide="star" class="w-3 h-3 text-yellow-400 fill-current"></i>
                            <span class="text-[10px] font-black text-gray-700"><?php echo number_format($sim['avg_rating'], 1); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-bold text-xs text-gray-800 line-clamp-1 mb-1"><?php echo sanitize($sim['name']); ?></h3>
                    <p class="font-black text-primary text-sm">Rs. <?php echo number_format($sim['price'], 0); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <style>.scrollbar-hide::-webkit-scrollbar{display:none}.scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}</style>
        <?php endif; ?>

    </div>

    <!-- Bottom Nav Fixed -->
    <?php include 'includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();

        // Track product view (fire-and-forget)
        <?php if (is_logged_in()): ?>
        fetch('actions/track_view.php?product_id=<?php echo $product_id; ?>').catch(() => {});
        <?php endif; ?>
    </script>
</body>
</html>
