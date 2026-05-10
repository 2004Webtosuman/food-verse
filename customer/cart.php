<?php
// cart.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Redirect riders to their dashboard
if (is_delivery()) {
    redirect('../delivery/dashboard.php');
}

// Initialize session cart if not exists (for guests or as a temporary cache)
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add/Remove/Update actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($action === 'add' && $product_id > 0) {
        // Fetch stock from DB
        $prod_stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
        $prod_stmt->execute([$product_id]);
        $product = $prod_stmt->fetch();

        if (!$product) {
            $_SESSION['flash_message'] = "Product not found.";
            $_SESSION['flash_type'] = "error";
        } else {
            $current_in_cart = $_SESSION['cart'][$product_id] ?? 0;
            $new_qty = $current_in_cart + $quantity;

            if ($product['stock_quantity'] <= 0) {
                $_SESSION['flash_message'] = "⚠️ \"{$product['name']}\" is out of stock!";
                $_SESSION['flash_type'] = "error";
            } elseif ($new_qty > $product['stock_quantity']) {
                $_SESSION['flash_message'] = "⚠️ Only {$product['stock_quantity']} of \"{$product['name']}\" available. You already have {$current_in_cart} in cart.";
                $_SESSION['flash_type'] = "error";
            } else {
                $_SESSION['cart'][$product_id] = $new_qty;
                $_SESSION['flash_message'] = "Item added to cart!";
                $_SESSION['flash_type'] = "success";
            }
        }
        $redirect_to = $_SERVER['HTTP_REFERER'] ?? '../index.php';
        redirect($redirect_to);
    }

    if ($action === 'remove' && $product_id > 0) {
        unset($_SESSION['cart'][$product_id]);
        redirect('cart.php');
    }

    if ($action === 'update' && $product_id > 0) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            // Validate against stock before updating
            $prod_stmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
            $prod_stmt->execute([$product_id]);
            $product = $prod_stmt->fetch();

            if ($product && $quantity > $product['stock_quantity']) {
                $_SESSION['flash_message'] = "⚠️ Only {$product['stock_quantity']} of \"{$product['name']}\" in stock!";
                $_SESSION['flash_type'] = "error";
                // Cap it at max available
                $_SESSION['cart'][$product_id] = $product['stock_quantity'];
            } else {
                $_SESSION['cart'][$product_id] = $quantity;
            }
        }
        redirect('cart.php');
    }
}

// Fetch items for display
$cart_items = [];
$total_price = 0;
$has_stock_issue = false;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    try {
        $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
        while ($row = $stmt->fetch()) {
            $row['quantity'] = $_SESSION['cart'][$row['id']];
            $row['subtotal'] = $row['price'] * $row['quantity'];
            // Auto-cap if stock decreased since item was added
            if ($row['quantity'] > $row['stock_quantity'] && $row['stock_quantity'] > 0) {
                $_SESSION['cart'][$row['id']] = $row['stock_quantity'];
                $row['quantity'] = $row['stock_quantity'];
                $row['subtotal'] = $row['price'] * $row['quantity'];
                $has_stock_issue = true;
            }
            if ($row['stock_quantity'] <= 0) {
                $has_stock_issue = true;
            }
            $cart_items[] = $row;
            $total_price += $row['subtotal'];
        }
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Shopping Cart - FoodVerse</title>
</head>
<body class="bg-app-bg pb-32 font-outfit">

    <!-- Header -->
    <header class="p-6 flex items-center justify-between bg-white/80 sticky top-0 z-40 backdrop-blur-md border-b border-card-border">
        <button onclick="history.back()" class="p-2 bg-white rounded-full hover:bg-gray-50 transition-all border border-card-border shadow-sm">
            <i data-lucide="chevron-left" class="w-6 h-6 text-gray-700"></i>
        </button>
        <h1 class="text-xl font-bold tracking-tight text-gray-900">My Cart</h1>
        <div class="w-10"></div>
    </header>

    <!-- Flash Message -->
    <?php
    $flash = $_SESSION['flash_message'] ?? '';
    $flash_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    if ($flash):
        $is_error = $flash_type === 'error';
    ?>
    <div class="mx-6 mt-4 px-4 py-3 rounded-2xl text-sm font-semibold flex items-center gap-2
        <?php echo $is_error ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-green-50 text-green-600 border border-green-200'; ?>">
        <i data-lucide="<?php echo $is_error ? 'alert-triangle' : 'check-circle'; ?>" class="w-4 h-4 shrink-0"></i>
        <?php echo htmlspecialchars($flash); ?>
    </div>
    <?php endif; ?>

    <div class="p-6 space-y-4">
        <?php if (empty($cart_items)): ?>
            <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                <div class="w-20 h-20 bg-section-bg rounded-full flex items-center justify-center mb-6">
                    <i data-lucide="shopping-cart" class="w-10 h-10 text-gray-300"></i>
                </div>
                <p class="text-lg font-medium text-gray-500">Your cart is empty</p>
                <a href="../index.php" class="mt-6 text-primary font-bold border-b-2 border-primary/30 hover:border-primary transition-all">Continue Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($cart_items as $item):
                $at_limit = isset($item['stock_quantity']) && $item['quantity'] >= $item['stock_quantity'];
                $out_of_stock = isset($item['stock_quantity']) && $item['stock_quantity'] <= 0;
            ?>
                <div class="bg-white p-4 rounded-3xl border <?php echo $out_of_stock ? 'border-red-200' : 'border-card-border'; ?> flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="relative">
                        <img src="../ <?php echo !empty($item['image_url']) ? $item['image_url'] : 'images/placeholder.png'; ?>" alt="<?php echo $item['name']; ?>" class="w-20 h-20 object-cover rounded-2xl bg-section-bg <?php echo $out_of_stock ? 'opacity-50' : ''; ?>">
                        <?php if ($out_of_stock): ?>
                            <span class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-2xl">
                                <span class="text-white text-[9px] font-black uppercase tracking-widest">Out of Stock</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 group-hover:text-primary transition-colors mb-1"><?php echo sanitize($item['name']); ?></h3>
                        <p class="text-primary font-black mb-1">Rs. <?php echo number_format($item['price'], 0); ?></p>
                        <?php if (isset($item['stock_quantity'])): ?>
                            <p class="text-[10px] font-bold mb-2 <?php echo $item['stock_quantity'] <= 5 ? 'text-red-400' : 'text-gray-400'; ?>">
                                <?php echo $out_of_stock ? '❌ Out of stock' : ($at_limit ? "⚠️ Max {$item['stock_quantity']} available" : "{$item['stock_quantity']} in stock"); ?>
                            </p>
                        <?php endif; ?>

                        <div class="flex items-center justify-between">
                            <form action="cart.php" method="POST" class="flex items-center bg-section-bg rounded-xl p-1 border border-card-border">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="quantity" value="<?php echo $item['quantity'] - 1; ?>" class="w-8 h-8 flex items-center justify-center hover:text-primary transition-all bg-white rounded-lg shadow-sm">
                                    <i data-lucide="minus" class="w-3 h-3"></i>
                                </button>
                                <span class="px-4 text-sm font-bold text-gray-800"><?php echo $item['quantity']; ?></span>
                                <button type="submit" name="quantity" value="<?php echo $item['quantity'] + 1; ?>"
                                    <?php echo $at_limit ? 'disabled title="Max stock reached"' : ''; ?>
                                    class="w-8 h-8 flex items-center justify-center transition-all bg-white rounded-lg shadow-sm <?php echo $at_limit ? 'opacity-30 cursor-not-allowed' : 'hover:text-primary'; ?>">
                                    <i data-lucide="plus" class="w-3 h-3"></i>
                                </button>
                            </form>

                            <form action="cart.php" method="POST">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="p-2 text-gray-400 hover:text-red-500 transition-colors active:scale-95">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Order Summary -->
            <div class="mt-8 bg-white p-6 rounded-[2.5rem] border border-card-border space-y-4 shadow-sm">
                <div class="flex justify-between text-gray-500 font-medium">
                    <span>Subtotal</span>
                    <span>Rs. <?php echo number_format($total_price, 0); ?></span>
                </div>
                <div class="flex justify-between text-gray-500 font-medium">
                    <span>Delivery Fee</span>
                    <span>Rs. 50</span>
                </div>
                <div class="flex justify-between text-2xl font-black text-gray-900 border-t border-card-border pt-4">
                    <span>Total</span>
                    <span class="text-primary">Rs. <?php echo number_format($total_price + 50, 0); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Checkout Bar -->
    <?php if (!empty($cart_items)): ?>
        <div class="fixed bottom-24 left-0 right-0 px-6 z-50">
            <a href="checkout.php" class="w-full bg-primary text-white flex justify-between items-center px-8 py-5 rounded-[24px] font-black shadow-lg hover:bg-primary-hover hover:shadow-xl transition-all active:scale-[0.98] uppercase tracking-wider">
                <span>Go to Checkout</span>
                <span class="bg-white/20 px-4 py-1 rounded-full text-sm">Rs. <?php echo number_format($total_price + 50, 0); ?></span>
            </a>
        </div>
    <?php endif; ?>

    <?php include '../includes/bottom_nav.php'; ?>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
