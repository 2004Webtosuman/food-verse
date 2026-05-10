<?php
// delivery/view_order.php — Unified FoodVerse Rider Order View (Admin Harmony)
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_delivery()) {
    redirect('../login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Fetch order + customer profile pic
$stmt = $pdo->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.address as customer_address, u.profile_pic as customer_pic
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['flash_message'] = "Order not found.";
    redirect('dashboard.php');
}

// Security: Rider can only view their own assigned orders or broadcasted orders
if ($order['delivery_user_id'] !== null && $order['delivery_user_id'] != $user_id) {
    $_SESSION['flash_message'] = "Access denied.";
    redirect('dashboard.php');
}

$items_stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_url 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

// Prefixed Earning (Legit logic from checkout.php: Rs. 50 per delivery)
$rider_earning = 50.00; 

// Coordinates (Nepal Context)
$resLat = (float)($order['restaurant_lat'] ?: 27.7215);
$resLng = (float)($order['restaurant_lng'] ?: 85.3210);
$custLat = (float)($order['delivery_lat'] ?: 27.7172);
$custLng = (float)($order['delivery_lng'] ?: 85.3240);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Mission Detail #<?php echo $order['id']; ?> - FoodVerse</title>
    <!-- Leaflet & OSM -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Routing -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <!-- Rider Tracking System -->
    <script src="../assets/js/rider_tracking.js"></script>
    <style>
        #map { border-radius: 2rem; overflow: hidden; height: 100%; width: 100%; border: 1px solid #F1EAE4; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 107, 53, 0.2); border-radius: 10px; }
    </style>
</head>
<body class="bg-app-bg min-h-screen">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar (Admin Symmetry) -->
        <aside class="w-64 bg-white border-r border-card-border hidden md:flex flex-col h-full z-10 flex-shrink-0">
            <div class="p-6 border-b border-card-border flex items-center h-[88px] gap-2">
                <h1 class="text-xl font-black tracking-tighter uppercase text-primary leading-tight">FOODVERSE</h1>
                <span class="bg-primary/10 text-primary text-[8px] font-bold px-2 py-0.5 rounded-full uppercase ml-1">Mission Control</span>
            </div>

            <nav class="flex-1 py-6 space-y-2 px-4 overflow-y-auto w-full">
                <div class="px-4 mb-6">
                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-3">Satellite Status</p>
                    <div id="gpsStatus" class="px-5 py-3 rounded-2xl border bg-gray-50 border-gray-100 flex items-center gap-3 transition-all duration-500">
                        <div id="gpsDot" class="w-2 h-2 rounded-full bg-gray-400"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">GPS OFF</span>
                    </div>
                </div>

                <div class="px-4 mb-6">
                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-3">Order Status</p>
                    <?php 
                        $status_class = match($order['status']) {
                            'delivered'       => 'bg-green-50 text-green-600 border-green-200',
                            'out_for_delivery'=> 'bg-blue-50 text-blue-500 border-blue-200',
                            default           => 'bg-orange-50 text-orange-500 border-orange-200',
                        };
                    ?>
                    <div class="px-5 py-3 rounded-2xl border <?php echo $status_class; ?> flex items-center gap-3 shadow-sm">
                        <div class="w-2 h-2 rounded-full bg-current animate-pulse"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest"><?php echo str_replace('_', ' ', $order['status']); ?></span>
                    </div>
                </div>



                <div class="px-4 mb-6 pt-6 border-t border-gray-50">
                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-3">Recipient</p>
                    <h4 class="font-black text-gray-900 text-sm uppercase italic"><?php echo htmlspecialchars($order['customer_name']); ?></h4>
                    <p class="text-[11px] text-gray-400 font-medium italic mt-1 line-clamp-2"><?php echo htmlspecialchars($order['customer_address']); ?></p>
                </div>

                <div class="px-4 pt-6 mt-auto">
                    <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent rounded-r-2xl font-bold transition-all w-full leading-none">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        <span>Exit HUD</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-full overflow-hidden relative">
            <header class="bg-white border-b border-card-border h-[88px] flex items-center justify-between px-8 z-10 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-black text-gray-900 tracking-tighter uppercase italic">Mission #AT-<?php echo $order['id']; ?></h2>
                </div>
                <div class="flex items-center gap-6">
                    <div id="locationInfo" class="hidden flex flex-col items-end">
                        <span class="text-[9px] text-gray-400 font-black uppercase tracking-[0.2em]">Real-Time Fix</span>
                        <span id="coordDisplay" class="text-[10px] font-bold text-gray-900 tracking-tight italic">Detecting...</span>
                    </div>
                    <div class="h-10 w-px bg-gray-100"></div>
                    <div class="flex flex-col text-right">
                        <span class="text-[9px] text-gray-400 font-black uppercase tracking-[0.2em]">Expected Earning</span>
                        <span class="text-xl font-black text-primary">Rs. <?php echo number_format($rider_earning, 0); ?></span>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-hidden p-6 gap-6 grid grid-cols-1 lg:grid-cols-12 bg-app-bg pb-28 md:pb-6">
                
                <!-- Left: Map Visualization -->
                <div class="lg:col-span-8 relative group" style="min-height: 320px;">
                    <div id="map" style="height: 100%; min-height: 300px; border-radius: 1.5rem;"></div>
                    <div class="absolute top-6 left-6 z-[1000] flex gap-2">
                        <div class="bg-white/95 backdrop-blur px-5 py-3 rounded-2xl border border-card-border shadow-xl flex items-center gap-3">
                            <i data-lucide="navigation" class="w-4 h-4 text-primary"></i>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-900 italic">Satellite Link Active</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Operations Panel -->
                <div class="lg:col-span-4 flex flex-col gap-6 overflow-y-auto custom-scrollbar h-full pr-2">
                    
                    <!-- Customer Quick Actions -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-card-border shadow-sm">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-[10px] text-gray-400 font-black uppercase tracking-widest">Client Identity</h3>
                            <button id="gpsToggle" onclick="riderGPS.toggle()" class="px-4 py-1.5 bg-gray-50 border border-card-border rounded-full text-[8px] font-black uppercase tracking-widest hover:bg-gray-100 transition-all">Enable GPS</button>
                        </div>
                        <div class="flex items-center gap-5 mb-10">
                            <div class="w-16 h-16 rounded-[1.8rem] bg-gray-50 border border-card-border overflow-hidden flex-shrink-0">
                                <?php
                                    $custPic = $order['customer_pic'] ?? '';
                                    if (!empty($custPic) && file_exists('../' . $custPic)) {
                                        $custImg = '../' . $custPic;
                                    } else {
                                        $custImg = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($order['customer_name']);
                                    }
                                ?>
                                <img src="<?php echo $custImg; ?>" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-gray-900 uppercase italic"><?php echo htmlspecialchars($order['customer_name']); ?></h4>
                                <p class="text-xs text-primary font-bold italic">Secure Protocol Assigned</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="tel:<?php echo $order['customer_phone']; ?>" class="py-4 bg-accent text-white rounded-2xl font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-3 hover:bg-accent/90 transition-all shadow-lg shadow-accent/20 active:scale-95">
                                <i data-lucide="phone" class="w-4 h-4"></i> Establish Link
                            </a>
                            <button onclick="toggleChat()" class="py-4 bg-gray-900 text-white rounded-2xl font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-3 hover:bg-black transition-all active:scale-95 relative">
                                <i data-lucide="message-square" class="w-4 h-4"></i> Data Msg
                                <div id="msgBadge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-primary rounded-full border-2 border-white pulse-primary"></div>
                            </button>
                        </div>
                    </div>

                    <!-- manifest (Order Items) -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-card-border shadow-sm flex-1">
                        <div class="flex items-center justify-between mb-8 border-b border-gray-50 pb-4">
                            <h3 class="text-[10px] text-gray-400 font-black uppercase tracking-widest">Cargo Manifest</h3>
                            <button onclick="startInAppNavigation()" id="btn-start-nav" class="flex items-center gap-2 text-primary font-black uppercase text-[9px] tracking-widest hover:scale-105 transition-transform">
                                <i data-lucide="navigation" class="w-4 h-4"></i> Start Navigation
                            </button>
                        </div>
                        <div class="space-y-6">
                            <?php foreach ($items as $item): ?>
                                <div class="flex items-center gap-4 group">
                                    <div class="w-12 h-12 bg-gray-50 rounded-xl overflow-hidden border border-card-border flex-shrink-0 group-hover:scale-105 transition-transform">
                                        <img src="../<?php echo $item['image_url']; ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h5 class="text-xs font-black text-gray-900 uppercase tracking-tight"><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Qty: <?php echo $item['quantity']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Fulfillment Action (Admin Style) -->
                    <div class="bg-white p-2 rounded-[3.5rem] shadow-2xl border border-card-border">
                        <?php if ($order['status'] == 'confirmed' && $order['delivery_user_id'] === null): ?>
                            <form action="actions/accept_order.php" method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" class="w-full py-6 bg-primary text-white font-black rounded-[3rem] uppercase tracking-[0.2em] text-[11px] hover:bg-primary-hover transition-all flex items-center justify-center gap-3 active:scale-[0.98] shadow-xl shadow-primary/20">
                                    <i data-lucide="crosshair" class="w-5 h-5"></i> Accept Mission
                                </button>
                            </form>
                        <?php elseif ($order['status'] === 'out_for_delivery'): ?>
                            <form method="POST" action="actions/update_mission_status.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="new_status" value="delivered">
                                <button type="submit" name="update_status" class="w-full py-6 bg-accent text-white font-black rounded-[3rem] uppercase tracking-[0.2em] text-[11px] hover:bg-accent/90 transition-all flex items-center justify-center gap-3 active:scale-[0.98] shadow-xl shadow-accent/20">
                                    <i data-lucide="shield-check" class="w-5 h-5"></i> Complete Delivery
                                </button>
                            </form>
                        <?php elseif ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                            <form method="POST" action="actions/update_mission_status.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="new_status" value="out_for_delivery">
                                <button type="submit" name="update_status" class="w-full py-6 bg-primary text-white font-black rounded-[3rem] uppercase tracking-[0.2em] text-[11px] hover:bg-primary-hover transition-all flex items-center justify-center gap-3 active:scale-[0.98] shadow-xl shadow-primary/20">
                                    <i data-lucide="package-check" class="w-5 h-5"></i> Confirm Pickup
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="w-full py-6 bg-gray-50 text-gray-400 font-black rounded-[3rem] uppercase tracking-[0.2em] text-[10px] flex items-center justify-center gap-3 border border-card-border">
                                <i data-lucide="check" class="w-5 h-5"></i> Logistics Finalized
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Chat Modal -->
    <div id="chatModal" class="fixed inset-0 z-[2000] hidden bg-black/60 backdrop-blur-sm p-6 items-center justify-center">
        <div class="bg-white w-full max-w-md h-[600px] rounded-[3rem] shadow-2xl border border-card-border flex flex-col overflow-hidden transition-all scale-95 opacity-0" id="chatContainer">
            <!-- Chat Header -->
            <div class="p-6 border-b border-card-border flex items-center justify-between bg-gray-50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl overflow-hidden border border-card-border">
                        <img src="<?php echo !empty($order['customer_pic']) ? '../' . $order['customer_pic'] : 'https://ui-avatars.com/api/?name=' . urlencode($order['customer_name']) . '&background=FF6B35&color=fff'; ?>" alt="Client" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-gray-900 uppercase italic"><?php echo htmlspecialchars($order['customer_name']); ?></h4>
                        <p class="text-[9px] text-primary font-bold uppercase tracking-widest italic">Client Link Active</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="tel:<?php echo $order['customer_phone']; ?>" class="p-2 hover:bg-gray-100 rounded-full transition-all group">
                        <i data-lucide="phone" class="w-6 h-6 text-primary group-hover:animate-bounce"></i>
                    </a>
                    <button onclick="toggleChat()" class="p-2 hover:bg-gray-100 rounded-full transition-all">
                        <i data-lucide="x" class="w-6 h-6 text-gray-500"></i>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50 scroll-smooth">
                <div class="flex flex-col items-center justify-center h-full text-center space-y-4 opacity-50">
                    <i data-lucide="message-circle" class="w-12 h-12 text-gray-300"></i>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No transmissions yet</p>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="p-4 bg-white border-t border-card-border">
                <form id="chatForm" class="flex gap-2" onsubmit="return sendMessage(event)">
                    <input type="text" id="chatInput" placeholder="Enter transmission..." 
                        class="flex-1 p-4 bg-gray-50 border border-card-border rounded-2xl focus:outline-none focus:border-primary text-sm placeholder:text-gray-400">
                    <button type="submit" class="p-4 bg-primary text-white rounded-2xl hover:bg-primary-hover transition-all shadow-lg shadow-primary/20">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>



    <?php include '../includes/bottom_nav.php'; ?>
    <script>
        lucide.createIcons();

        let map, riderMarker, routingControl, riderGPS;
        let currentRiderLoc = null;
        const orderStatus = '<?php echo $order['status']; ?>';
        const displayOrderId = <?php echo $order['id']; ?>;

        const resLat = <?php echo $resLat; ?>;
        const resLng = <?php echo $resLng; ?>;
        const custLat = <?php echo $custLat; ?>;
        const custLng = <?php echo $custLng; ?>;

        function initMap() {
            map = L.map('map', { zoomControl: false }).setView([custLat, custLng], 14);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            const kitchenIcon = L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/1046/1046857.png', iconSize: [40, 40], iconAnchor: [20, 20] });
            const clientIcon = L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/3177/3177361.png', iconSize: [40, 40], iconAnchor: [20, 20] });

            L.marker([resLat, resLng], { icon: kitchenIcon }).addTo(map).bindPopup("<b>Source: Kitchen</b>");
            L.marker([custLat, custLng], { icon: clientIcon }).addTo(map).bindPopup("<b>Target: Client</b>");

            riderMarker = L.marker([0, 0], {
                icon: L.icon({ 
                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/7542/7542670.png', 
                    iconSize: [44, 44], 
                    iconAnchor: [22, 22] 
                })
            }).addTo(map).bindPopup("<b>Me (Rider)</b>");

            // Initialize Unified Master Switch
            riderGPS = new RiderGPS({
                toggleBtnId: 'gpsToggle',
                statusIndicatorId: 'gpsStatus',
                dotId: 'gpsDot',
                coordDisplayId: 'coordDisplay',
                onLocationUpdate: (lat, lng) => {
                    handleGPSUpdate(lat, lng);
                }
            });

            const group = new L.featureGroup([
                L.marker([resLat, resLng]),
                L.marker([custLat, custLng])
            ]);
            map.fitBounds(group.getBounds().pad(0.3));
        }

        function handleGPSUpdate(lat, lng) {
            currentRiderLoc = { lat, lng };
            if (riderMarker) riderMarker.setLatLng([lat, lng]);
            
            // If navigating, update the route
            if (routingControl) {
                const target = routingControl.getWaypoints()[1].latLng;
                routingControl.setWaypoints([L.latLng(lat, lng), target]);
            }
        }

        function startInAppNavigation() {
            if (!currentRiderLoc) {
                if (!riderGPS.isActive()) riderGPS.start();
                alert("Acquiring GPS Signal... Please wait.");
                return;
            }

            const btn = document.getElementById('btn-start-nav');
            btn.innerHTML = '<i data-lucide="navigation-off" class="w-4 h-4"></i> Stop Navigation';
            btn.classList.add('text-red-500');
            btn.setAttribute('onclick', 'stopNavigation()');
            lucide.createIcons();

            let targetLat, targetLng;
            if (['confirmed', 'preparing'].includes(orderStatus)) {
                targetLat = resLat; targetLng = resLng;
            } else {
                targetLat = custLat; targetLng = custLng;
            }

            if (routingControl) map.removeControl(routingControl);

            routingControl = L.Routing.control({
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                waypoints: [L.latLng(currentRiderLoc.lat, currentRiderLoc.lng), L.latLng(targetLat, targetLng)],
                routeWhileDragging: false,
                addWaypoints: false,
                fitSelectedRoutes: true,
                show: false,
                createMarker: () => null,
                lineOptions: { styles: [{ color: '#FF6B35', weight: 8, opacity: 0.8 }] }
            }).addTo(map);

            map.flyTo([currentRiderLoc.lat, currentRiderLoc.lng], 16);
        }

        function stopNavigation() {
            if (routingControl) {
                map.removeControl(routingControl);
                routingControl = null;
            }
            const btn = document.getElementById('btn-start-nav');
            btn.innerHTML = '<i data-lucide="navigation" class="w-4 h-4"></i> Start Navigation';
            btn.classList.remove('text-red-500');
            btn.setAttribute('onclick', 'startInAppNavigation()');
            lucide.createIcons();
        }

        document.addEventListener('DOMContentLoaded', initMap);
        // Chat Logic
        const orderId = <?php echo (int)$order_id; ?>;
        const customerId = <?php echo (int)$order['user_id']; ?>;
        const userId = <?php echo (int)$user_id; ?>;
        let lastMsgId = 0;
        let isChatOpen = false;

        function toggleChat() {
            const modal = document.getElementById('chatModal');
            const container = document.getElementById('chatContainer');
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => {
                    container.classList.remove('scale-95', 'opacity-0');
                    container.classList.add('scale-100', 'opacity-100');
                    scrollToBottom();
                }, 10);
                isChatOpen = true;
                document.getElementById('msgBadge').classList.add('hidden');
            } else {
                container.classList.remove('scale-100', 'opacity-100');
                container.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 300);
                isChatOpen = false;
            }
        }

        async function fetchMessages() {
            try {
                const res = await fetch(`../api/get_messages.php?order_id=${orderId}&last_id=${lastMsgId}`);
                const data = await res.json();
                if (data.success && data.messages.length > 0) {
                    const chatMessages = document.getElementById('chatMessages');
                    if (lastMsgId === 0) chatMessages.innerHTML = '';
                    
                    data.messages.forEach(msg => {
                        const isMe = msg.sender_id == userId;
                        const msgHtml = `
                            <div class="flex ${isMe ? 'justify-end' : 'justify-start'} w-full">
                                <div class="max-w-[80%] ${isMe ? 'bg-primary text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-900 rounded-2xl rounded-tl-none border border-card-border'} p-4 shadow-sm">
                                    <p class="text-sm font-medium leading-relaxed">${msg.message}</p>
                                    <p class="text-[9px] ${isMe ? 'text-white/70' : 'text-gray-400'} mt-1 font-bold uppercase tracking-widest">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                </div>
                            </div>
                        `;
                        chatMessages.insertAdjacentHTML('beforeend', msgHtml);
                        lastMsgId = msg.id;
                    });
                    
                    scrollToBottom();
                    if (!isChatOpen) {
                        document.getElementById('msgBadge').classList.remove('hidden');
                    }
                }
            } catch (e) {
                console.error("Chat sync failed", e);
            }
        }

        async function sendMessage(e) {
            e.preventDefault();
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            if (!msg) return;

            input.value = '';
            input.disabled = true;

            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('message', msg);
                formData.append('receiver_id', customerId);

                const res = await fetch('../api/send_message.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    fetchMessages();
                }
            } catch (e) {
                console.error("Message send failed", e);
            } finally {
                input.disabled = false;
                input.focus();
            }
        }

        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Initialize polling
        fetchMessages();
        setInterval(fetchMessages, 3000);
    </script>
</body>
</html>
