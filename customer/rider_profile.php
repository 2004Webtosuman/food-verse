<?php
// customer/rider_profile.php — View rider profile & submit rating
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$rider_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $_SESSION['user_id'];

// Fetch rider info
$stmt = $pdo->prepare("SELECT id, full_name, email, phone, bio, vehicle_type, profile_pic, created_at FROM users WHERE id = ? AND role = 'delivery'");
$stmt->execute([$rider_id]);
$rider = $stmt->fetch();

if (!$rider) {
    $_SESSION['flash_message'] = "Rider not found.";
    redirect('orders.php');
}

// Get average rating and count
$ratingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings FROM rider_ratings WHERE rider_id = ?");
$ratingStmt->execute([$rider_id]);
$ratingData = $ratingStmt->fetch();
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalRatings = $ratingData['total_ratings'] ?? 0;

// Total deliveries
$delivStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE delivery_user_id = ? AND status = 'delivered'");
$delivStmt->execute([$rider_id]);
$totalDeliveries = $delivStmt->fetch()['count'];

// Check if user already rated this order
$alreadyRated = false;
if ($order_id) {
    $checkStmt = $pdo->prepare("SELECT id FROM rider_ratings WHERE order_id = ? AND user_id = ?");
    $checkStmt->execute([$order_id, $user_id]);
    $alreadyRated = (bool)$checkStmt->fetch();
}

// Handle rating submission
$ratingMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $rating = (int)$_POST['rating'];
    $review = sanitize($_POST['review'] ?? '');
    $rateOrderId = (int)$_POST['order_id'];

    if ($rating >= 1 && $rating <= 5 && $rateOrderId > 0) {
        try {
            $ins = $pdo->prepare("INSERT INTO rider_ratings (rider_id, order_id, user_id, rating, review) VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$rider_id, $rateOrderId, $user_id, $rating, $review]);
            $ratingMsg = 'success';
            $alreadyRated = true;
            // Refresh rating data
            $ratingStmt->execute([$rider_id]);
            $ratingData = $ratingStmt->fetch();
            $avgRating = round($ratingData['avg_rating'] ?? 0, 1);
            $totalRatings = $ratingData['total_ratings'] ?? 0;
        } catch (PDOException $e) {
            $ratingMsg = 'error';
        }
    }
}

// Recent reviews
$reviewsStmt = $pdo->prepare("SELECT rr.rating, rr.review, rr.created_at, u.full_name 
                              FROM rider_ratings rr 
                              JOIN users u ON rr.user_id = u.id 
                              WHERE rr.rider_id = ? 
                              ORDER BY rr.created_at DESC LIMIT 10");
$reviewsStmt->execute([$rider_id]);
$reviews = $reviewsStmt->fetchAll();

$memberSince = date('M Y', strtotime($rider['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title><?php echo htmlspecialchars($rider['full_name']); ?> - Rider Profile</title>
    <style>
        .star-btn { cursor: pointer; transition: all 0.15s; }
        .star-btn:hover, .star-btn.active { color: #facc15; transform: scale(1.2); }
    </style>
</head>
<body class="min-h-screen pb-32">

    <!-- Header -->
    <header class="p-6 bg-white flex items-center gap-4 sticky top-0 z-40 shadow-sm border-b border-card-border">
        <button onclick="history.back()" class="p-2 bg-gray-50 rounded-full hover:bg-gray-100 transition-all border border-card-border">
            <i data-lucide="chevron-left" class="w-6 h-6 text-gray-700"></i>
        </button>
        <h1 class="text-xl font-bold italic tracking-tighter text-gray-900">RIDER PROFILE</h1>
    </header>

    <div class="p-6 space-y-6">
        <!-- Profile Card -->
        <div class="bg-white p-8 rounded-3xl border border-card-border text-center shadow-sm">
            <div class="w-24 h-24 mx-auto rounded-3xl bg-section-bg border-2 border-primary/20 overflow-hidden mb-4 shadow-md">
                <?php 
                    $riderImg = (!empty($rider['profile_pic']) && file_exists('../' . $rider['profile_pic']))
                        ? '../' . $rider['profile_pic']
                        : 'https://ui-avatars.com/api/?name=' . urlencode($rider['full_name']) . '&background=FF6B35&color=fff&size=200&font-size=0.4&bold=true';
                ?>
                <img src="<?php echo $riderImg; ?>" alt="<?php echo htmlspecialchars($rider['full_name']); ?>" class="w-full h-full object-cover">
            </div>
            <h2 class="text-2xl font-black text-gray-900"><?php echo htmlspecialchars($rider['full_name']); ?></h2>
            <p class="text-primary text-xs font-bold uppercase tracking-widest mt-1">Delivery Rider</p>

            <!-- Rating Display -->
            <div class="flex items-center justify-center gap-2 mt-4">
                <div class="flex gap-0.5">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i data-lucide="star" class="w-5 h-5 <?php echo $i <= round($avgRating) ? 'text-yellow-500 fill-yellow-500' : 'text-gray-300'; ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="text-yellow-500 font-black text-lg"><?php echo $avgRating ?: '—'; ?></span>
                <span class="text-gray-400 text-xs">(<?php echo $totalRatings; ?> ratings)</span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white p-4 rounded-2xl text-center border border-card-border shadow-sm">
                <p class="text-2xl font-black text-primary"><?php echo $totalDeliveries; ?></p>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-1">Deliveries</p>
            </div>
            <div class="bg-white p-4 rounded-2xl text-center border border-card-border shadow-sm">
                <p class="text-2xl font-black text-primary"><?php echo $avgRating ?: '—'; ?></p>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-1">Rating</p>
            </div>
            <div class="bg-white p-4 rounded-2xl text-center border border-card-border shadow-sm">
                <p class="text-2xl font-black text-primary"><?php echo htmlspecialchars($rider['vehicle_type'] ?? 'Bike'); ?></p>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-1">Vehicle</p>
            </div>
        </div>

        <!-- Details -->
        <div class="bg-white p-6 rounded-3xl border border-card-border space-y-4 shadow-sm">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Rider Details</h3>
            <div class="flex items-center gap-4">
                <i data-lucide="user" class="w-5 h-5 text-primary"></i>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Name</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($rider['full_name']); ?></p>
                </div>
            </div>
            <?php if ($rider['email']): ?>
            <div class="flex items-center gap-4">
                <i data-lucide="mail" class="w-5 h-5 text-secondary"></i>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Email</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($rider['email']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($rider['phone']): ?>
            <div class="flex items-center gap-4">
                <i data-lucide="phone" class="w-5 h-5 text-accent"></i>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Phone</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($rider['phone']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            <div class="flex items-center gap-4">
                <i data-lucide="calendar" class="w-5 h-5 text-secondary"></i>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Member Since</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo $memberSince; ?></p>
                </div>
            </div>
            <?php if ($rider['bio']): ?>
            <div class="flex items-start gap-4">
                <i data-lucide="message-square" class="w-5 h-5 text-primary mt-0.5"></i>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">About</p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($rider['bio']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Rating Form (only if order_id provided and not yet rated) -->
        <?php if ($order_id && !$alreadyRated): ?>
        <div class="bg-white p-6 rounded-3xl border border-primary/20 shadow-sm">
            <h3 class="text-sm font-bold mb-4 flex items-center gap-2 text-gray-900">
                <i data-lucide="star" class="w-5 h-5 text-yellow-500"></i>
                Rate This Delivery
            </h3>

            <?php if ($ratingMsg === 'success'): ?>
                <div class="p-4 bg-green-50 border border-green-200 rounded-2xl text-green-600 text-sm font-bold mb-4">
                    ✅ Thank you for your rating!
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                
                <!-- Star Rating -->
                <div class="flex gap-2 mb-4 justify-center" id="starContainer">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" onclick="setRating(<?php echo $i; ?>)" class="star-btn p-1">
                            <i data-lucide="star" class="w-8 h-8 text-gray-300" id="star<?php echo $i; ?>"></i>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="0">

                <textarea name="review" placeholder="Write a review (optional)..." rows="3"
                    class="w-full p-4 bg-section-bg border border-card-border focus:border-primary/30 rounded-2xl focus:outline-none text-sm text-gray-800 placeholder:text-gray-400 mb-4 resize-none"></textarea>

                <button type="submit" name="submit_rating" class="w-full py-4 bg-primary text-white font-bold rounded-2xl hover:bg-primary-hover hover:shadow-lg transition-all active:scale-95 uppercase tracking-wider text-sm">
                    Submit Rating
                </button>
            </form>
        </div>
        <?php elseif ($order_id && $alreadyRated): ?>
        <div class="bg-green-50 p-4 rounded-2xl border border-green-200 text-center">
            <p class="text-green-600 text-sm font-bold flex items-center justify-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                You've already rated this delivery
            </p>
        </div>
        <?php endif; ?>

        <!-- Recent Reviews -->
        <?php if (!empty($reviews)): ?>
        <div>
            <h3 class="text-sm font-bold mb-4 text-gray-400 uppercase tracking-widest">Recent Reviews</h3>
            <div class="space-y-3">
                <?php foreach ($reviews as $rev): ?>
                    <div class="bg-white p-4 rounded-2xl border border-card-border shadow-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-800"><?php echo htmlspecialchars($rev['full_name']); ?></span>
                            <div class="flex gap-0.5">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i data-lucide="star" class="w-3 h-3 <?php echo $i <= $rev['rating'] ? 'text-yellow-500 fill-yellow-500' : 'text-gray-200'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if ($rev['review']): ?>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($rev['review']); ?></p>
                        <?php endif; ?>
                        <p class="text-[9px] text-gray-400 mt-2"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();

        function setRating(value) {
            document.getElementById('ratingInput').value = value;
            for (let i = 1; i <= 5; i++) {
                const star = document.getElementById('star' + i);
                if (i <= value) {
                    star.classList.remove('text-gray-300');
                    star.classList.add('text-yellow-500', 'fill-yellow-500');
                    star.parentElement.classList.add('active');
                } else {
                    star.classList.add('text-gray-300');
                    star.classList.remove('text-yellow-500', 'fill-yellow-500');
                    star.parentElement.classList.remove('active');
                }
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
