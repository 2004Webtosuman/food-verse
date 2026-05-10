<?php
// checkout.php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    $_SESSION['flash_message'] = "Please login to proceed to checkout.";
    redirect('../login.php');
}

// Redirect riders to their dashboard
if (is_delivery()) {
    redirect('../delivery/dashboard.php');
}

if (empty($_SESSION['cart'])) {
    redirect('../index.php');
}

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;

$total_price = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    // In a real app, fetch price from DB
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $price = $stmt->fetchColumn() ?: (($id == 1) ? 1000 : 620);
    $total_price += ($price * $qty);
}
$grand_total = $total_price + 50; // Including delivery

$payment_method = isset($_GET['payment']) ? sanitize($_GET['payment']) : 'card';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Checkout - FoodVerse</title>
    <style>
        .step-active { border-bottom: 3px solid #FF6B35; color: #FF6B35; }
        .step-inactive { color: #9CA3AF; }
    </style>
</head>

<body class="bg-app-bg min-h-screen pb-32">

    <!-- Header -->
    <header class="p-6 flex items-center justify-between bg-white shadow-sm sticky top-0 z-40">
        <button onclick="history.back()" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
            <i data-lucide="chevron-left" class="w-6 h-6 text-gray-800"></i>
        </button>
        <h1 class="text-xl font-bold text-gray-900">Checkout</h1>
        <div class="w-10"></div>
    </header>

    <!-- Progress Steps -->
    <div class="bg-white px-6 py-4 flex justify-around border-b border-card-border mb-6 font-bold">
        <span
            class="<?php echo $step === 1 ? 'step-active' : 'step-inactive'; ?> pb-2 px-2 text-sm transition-all">1.
            Confirm address</span>
        <span
            class="<?php echo $step === 2 ? 'step-active' : 'step-inactive'; ?> pb-2 px-2 text-sm transition-all">2.
            Payment</span>
        <span
            class="<?php echo $step === 3 ? 'step-active' : 'step-inactive'; ?> pb-2 px-2 text-sm transition-all">3.
            info</span>
    </div>

    <div class="px-6">
        <?php if ($step === 1): ?>
            <!-- Step 1: Address Selection -->
            <h2 class="text-xl font-bold text-gray-900 mb-6">Delivery Address</h2>
            <div class="space-y-4">
                <?php $loc = get_user_location(); ?>
                <div class="bg-white p-6 rounded-3xl border-2 border-primary shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                            <i data-lucide="map-pin" class="w-5 h-5 text-primary"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">Current Location</h3>
                            <p class="text-xs text-gray-400">Selected for this delivery</p>
                        </div>
                    </div>
                    <p id="display-location" class="text-sm text-gray-600 ml-14 leading-relaxed font-medium">
                        <?php echo $loc ? $loc['full_address'] : 'No location selected'; ?>
                    </p>
                    <div class="absolute top-4 right-4 text-primary">
                        <i data-lucide="check-circle" class="w-6 h-6"></i>
                    </div>
                </div>

                <button id="btn-open-location"
                    class="w-full py-4 bg-section-bg border-2 border-dashed border-card-border rounded-2xl flex items-center justify-center gap-2 text-gray-500 font-bold hover:border-primary hover:text-primary transition-all">
                    <i data-lucide="edit-3" class="w-5 h-5"></i>
                    Change Delivery Location
                </button>
            </div>

            <div class="fixed bottom-10 left-0 right-0 px-6">
                <a href="checkout.php?step=2"
                    class="w-full bg-primary text-white flex justify-center py-5 rounded-[24px] font-bold shadow-lg hover:bg-primary-hover transition-all">
                    Next to Payment
                </a>
            </div>

        <?php elseif ($step === 2): ?>
            <!-- Step 2: Payment Selection -->
            <h2 class="text-xl font-bold text-gray-900 mb-6">Payment Method</h2>
            <div class="space-y-4">
                <a href="checkout.php?step=2&payment=card"
                    class="bg-white p-6 rounded-3xl border-2 <?php echo $payment_method === 'card' ? 'border-primary' : 'border-card-border'; ?> shadow-sm flex items-center justify-between transition-all">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-8 bg-gray-100 rounded flex items-center justify-center font-bold text-[10px] text-gray-400 italic">
                            VISA</div>
                        <h3 class="font-bold text-gray-900">Credit Card</h3>
                    </div>
                    <?php if ($payment_method === 'card'): ?>
                        <i data-lucide="check-circle" class="w-6 h-6 text-primary"></i>
                    <?php else: ?>
                        <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                    <?php endif; ?>
                </a>
                <a href="checkout.php?step=2&payment=esewa"
                    class="bg-white p-6 rounded-3xl border-2 <?php echo $payment_method === 'esewa' ? 'border-primary' : 'border-card-border'; ?> shadow-sm flex items-center justify-between transition-all">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-8 bg-green-50 rounded flex items-center justify-center font-bold text-[12px] text-green-600">
                            eSewa</div>
                        <h3 class="font-bold text-gray-900">Mobile Wallet</h3>
                    </div>
                    <?php if ($payment_method === 'esewa'): ?>
                        <i data-lucide="check-circle" class="w-6 h-6 text-primary"></i>
                    <?php else: ?>
                        <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                    <?php endif; ?>
                </a>
                <a href="checkout.php?step=2&payment=cod"
                    class="bg-white p-6 rounded-3xl border-2 <?php echo $payment_method === 'cod' ? 'border-primary' : 'border-card-border'; ?> shadow-sm flex items-center justify-between transition-all">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-8 bg-blue-50 rounded flex items-center justify-center font-bold text-[10px] text-blue-600 italic">
                            CASH</div>
                        <h3 class="font-bold text-gray-900">Cash on Delivery</h3>
                    </div>
                    <?php if ($payment_method === 'cod'): ?>
                        <i data-lucide="check-circle" class="w-6 h-6 text-primary"></i>
                    <?php else: ?>
                        <div class="w-6 h-6 rounded-full border-2 border-gray-200"></div>
                    <?php endif; ?>
                </a>
            </div>

            <div class="fixed bottom-10 left-0 right-0 px-6">
                <a href="checkout.php?step=3&payment=<?php echo $payment_method; ?>"
                    class="w-full bg-primary text-white flex justify-center py-5 rounded-[24px] font-bold shadow-lg hover:bg-primary-hover transition-all">
                    Next to Confirm
                </a>
            </div>

        <?php elseif ($step === 3): ?>
            <!-- Step 3: Final Confirmation -->
            <h2 class="text-xl font-bold text-gray-900 mb-6">Order Confirmation</h2>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-card-border mb-6">
                <div class="flex items-center gap-4 mb-6 border-b border-card-border pb-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center">
                        <i data-lucide="map-pin" class="w-6 h-6 text-primary"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-900">Delivery to Home</h4>
                        <p class="text-xs text-gray-400">123 Street, Kathmandu</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between text-gray-500 text-sm">
                        <span>Items Total</span>
                        <span>Rs. <?php echo number_format($total_price, 0); ?></span>
                    </div>
                    <div class="flex justify-between text-gray-500 text-sm">
                        <span>Delivery Charge</span>
                        <span>Rs. 50</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 pt-3 border-t border-card-border">
                        <span>Grand Total</span>
                        <span class="text-primary">Rs. <?php echo number_format($total_price + 50, 0); ?></span>
                    </div>
                </div>
            </div>

            <form
                action="<?php echo $payment_method === 'esewa' ? '../actions/process_esewa.php' : '../actions/place_order.php'; ?>"
                method="POST">
                <input type="hidden" name="total_price" value="<?php echo $grand_total; ?>">
                <input type="hidden" name="payment_method" value="<?php echo $payment_method; ?>">
                <button type="submit"
                    class="w-full bg-primary text-white flex justify-center py-5 rounded-[24px] font-bold shadow-lg hover:bg-primary-hover transition-all active:scale-[0.98]">
                    <?php echo $payment_method === 'esewa' ? 'Pay with eSewa' : 'Place Order Now'; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

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

            <div id="location-picker-container"></div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Location Picker Logic
        const locationModal = document.getElementById('location-modal-overlay');
        const locationContent = document.getElementById('location-modal-content');
        const btnOpenLocation = document.getElementById('btn-open-location');
        const btnCloseLocation = document.getElementById('btn-close-location');
        const displayLocation = document.getElementById('display-location');

        if (btnOpenLocation) {
            const locPicker = new LocationPicker('location-picker-container', {
                onSave: (location) => {
                    if (displayLocation) displayLocation.innerText = location.full_address;
                    closeLocationModal();
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

            locationModal.addEventListener('click', (e) => {
                if (e.target === locationModal) closeLocationModal();
            });
        }
    </script>
</body>

</html>