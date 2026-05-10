<?php
// track_order.php — Real-time tracking with Google Maps + OSRM routing
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_logged_in()) {
    redirect('../login.php');
}

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT o.*, u.full_name, u.address as user_address,
                           d.full_name as rider_name, d.phone as rider_phone, d.profile_pic as rider_image
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          LEFT JOIN users d ON o.delivery_user_id = d.id
                          WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['flash_message'] = "Order not found or access denied.";
        redirect('orders.php');
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$rider_name = $order['rider_name'] ?? 'Delivery Rider';
$rider_phone = $order['rider_phone'] ?? '';
$rider_id = $order['delivery_user_id'] ?? 0;
$map_api_key = "AIzaSyCkMFL4onPbayhlnowww9gdfTUMq6Pf0x8";

// Get rider's average rating
$riderAvgRating = 0;
if ($rider_id) {
    $rStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM rider_ratings WHERE rider_id = ?");
    $rStmt->execute([$rider_id]);
    $riderAvgRating = round($rStmt->fetch()['avg_rating'] ?? 0, 1);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include '../includes/header_meta.php'; ?>
    <title>Track Order #<?php echo $order['id']; ?> - FoodVerse</title>
    <!-- Leaflet JS & CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 20px;
        }
        .pulse-primary {
            box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.7);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.7); }
            70%  { box-shadow: 0 0 0 10px rgba(255, 107, 53, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0); }
        }
    </style>
</head>

<body class="bg-dark-bg min-h-screen pb-32 transition-colors">

    <!-- Header -->
    <header class="p-6 bg-white dark:bg-dark-card flex items-center justify-between sticky top-0 z-40 shadow-sm border-b border-card-border dark:border-dark-border transition-colors h-[88px]">
        <div class="flex items-center gap-4">
            <button onclick="history.back()" class="p-2 bg-gray-50 dark:bg-dark-bg rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-all border border-card-border dark:border-dark-border">
                <i data-lucide="chevron-left" class="w-6 h-6 text-gray-700 dark:text-gray-300"></i>
            </button>
            <h1 class="text-xl font-black italic tracking-tighter text-gray-900 dark:text-white uppercase transition-colors">Tracking Mission #FE-<?php echo $order['id']; ?></h1>
        </div>
        <div class="px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-[10px] font-black uppercase tracking-widest animate-pulse">
            <?php echo str_replace('_', ' ', $order['status']); ?>
        </div>
    </header>

    <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 lg:h-[calc(100vh-120px)] lg:min-h-[700px]">
        <?php
        $progress = match ($order['status']) {
            'pending' => 20,
            'confirmed' => 40,
            'preparing' => 60,
            'out_for_delivery' => 85,
            'delivered' => 100,
            default => 0
        };
        ?>
        <!-- Column 1: Order Status & ETA -->
        <div class="lg:col-span-3 space-y-6 h-full min-h-[400px]">
            <div class="bg-white dark:bg-dark-card p-8 rounded-[2.5rem] border border-card-border dark:border-dark-border shadow-sm transition-colors h-full flex flex-col justify-between">
                <div class="space-y-12">
                    <div class="flex items-center gap-6">
                        <div class="w-20 h-20 bg-section-bg dark:bg-dark-bg rounded-3xl flex items-center justify-center border border-card-border dark:border-dark-border relative transition-colors shadow-inner">
                            <?php if ($order['status'] === 'out_for_delivery'): ?>
                                <div class="absolute -top-1 -right-1 w-5 h-5 bg-primary rounded-full pulse-primary border-4 border-white dark:border-dark-card transition-colors"></div>
                            <?php endif; ?>
                            <i data-lucide="<?php
                            echo match ($order['status']) {
                                'pending' => 'clock',
                                'confirmed' => 'check-circle',
                                'preparing' => 'utensils',
                                'out_for_delivery' => 'truck',
                                'delivered' => 'package-check',
                                default => 'alert-circle'
                            };
                            ?>" class="w-10 h-10 text-primary"></i>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.25em] mb-2 transition-colors">ETA Fix</h3>
                            <p id="eta" class="text-2xl font-black italic text-gray-900 dark:text-white transition-colors uppercase">Detecting...</p>
                        </div>
                    </div>

                    <!-- Status List (Vertical for Sidebar feel) -->
                    <div class="space-y-10 px-2 relative">
                        <!-- Connecting Line Track -->
                        <div class="absolute left-[14.5px] top-1.5 bottom-1.5 w-0.5 bg-gray-200 dark:bg-gray-800"></div>
                        <!-- Active Connecting Line -->
                        <div class="absolute left-[14.5px] top-1.5 w-0.5 bg-primary shadow-[0_0_10px_rgba(255,107,53,0.5)] transition-all duration-1000"
                             style="height: <?php 
                                echo match($order['status']) {
                                    'pending', 'confirmed' => '0%',
                                    'preparing' => '33%',
                                    'out_for_delivery' => '66%',
                                    'delivered' => '100%',
                                    default => '0%'
                                };
                             ?>"></div>

                        <div class="flex items-start gap-5 group relative z-10">
                            <div class="w-3.5 h-3.5 rounded-full <?php echo $progress >= 40 ? 'bg-primary ring-4 ring-primary/20' : 'bg-gray-200 dark:bg-gray-800'; ?> mt-1 transition-all duration-500"></div>
                            <div class="flex-1">
                                <p class="text-[13px] font-black uppercase tracking-tight <?php echo $progress >= 40 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600'; ?> transition-colors">
                                    Confirmed</p>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1 transition-colors italic">Received</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-5 relative z-10">
                            <div class="w-3.5 h-3.5 rounded-full <?php echo $progress >= 60 ? 'bg-primary ring-4 ring-primary/20' : 'bg-gray-200 dark:bg-gray-800'; ?> mt-1 transition-all duration-500"></div>
                            <div class="flex-1">
                                <p class="text-[13px] font-black uppercase tracking-tight <?php echo $progress >= 60 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600'; ?> transition-colors">
                                    Preparing</p>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1 transition-colors italic">Kitchen</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-5 relative z-10">
                            <div class="w-3.5 h-3.5 rounded-full <?php echo $progress >= 85 ? 'bg-primary ring-4 ring-primary/20' : 'bg-gray-200 dark:bg-gray-800'; ?> mt-1 transition-all duration-500"></div>
                            <div class="flex-1">
                                <p class="text-[13px] font-black uppercase tracking-tight <?php echo $progress >= 85 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600'; ?> transition-colors">Transit</p>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1 transition-colors italic">On Way</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-5 relative z-10">
                            <div class="w-3.5 h-3.5 rounded-full <?php echo $progress >= 100 ? 'bg-primary ring-4 ring-primary/20' : 'bg-gray-200 dark:bg-gray-800'; ?> mt-1 transition-all duration-500"></div>
                            <div class="flex-1">
                                <p class="text-[13px] font-black uppercase tracking-tight <?php echo $progress >= 100 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-600'; ?> transition-colors">Delivered</p>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1 transition-colors italic">Arrival</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="relative h-3 bg-gray-100 dark:bg-dark-bg rounded-full overflow-hidden mt-12 transition-colors">
                    <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-primary to-accent transition-all duration-1000 shadow-[0_0_15px_rgba(255,107,53,0.4)]"
                        style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Column 2: Map Visualization -->
        <div class="lg:col-span-6 h-full min-h-[500px] flex flex-col">
            <div id="map" class="flex-1 w-full rounded-[3rem] border border-card-border dark:border-dark-border shadow-2xl overflow-hidden transition-colors"></div>
        </div>

        <!-- Column 3: Rider Identity & Actions -->
        <div class="lg:col-span-3 space-y-6">
            <?php if ($rider_id): ?>
                <div class="bg-white dark:bg-dark-card p-8 rounded-[2.5rem] border border-card-border dark:border-dark-border shadow-sm transition-colors h-full flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-[10px] text-gray-400 dark:text-gray-500 font-black uppercase tracking-widest">Rider Identity</h3>
                        <a href="rider_profile.php?id=<?php echo $rider_id; ?>&order_id=<?php echo $order['id']; ?>" class="px-4 py-1.5 bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-full text-[8px] font-black uppercase tracking-widest hover:bg-gray-100 dark:hover:bg-gray-800 transition-all text-gray-900 dark:text-white">View Profile</a>
                    </div>
                    
                    <div class="flex flex-col items-center text-center mb-10">
                        <div class="w-24 h-24 rounded-[2rem] bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border overflow-hidden mb-4 transition-colors shadow-lg">
                            <?php
                            $riderAvatar = (!empty($order['rider_image']) && file_exists('../' . $order['rider_image']))
                                ? '../' . $order['rider_image']
                                : 'https://ui-avatars.com/api/?name=' . urlencode($rider_name) . '&background=FF6B35&color=fff';
                            ?>
                            <img src="<?php echo $riderAvatar; ?>" alt="Rider" class="w-full h-full object-cover">
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-white uppercase italic transition-colors"><?php echo htmlspecialchars($rider_name); ?></h4>
                        <div class="flex items-center gap-2 mt-2">
                            <div class="flex items-center gap-1 text-yellow-500 text-[10px]">
                                <i data-lucide="star" class="w-3 h-3 fill-yellow-500"></i>
                                <span class="font-black"><?php echo $riderAvgRating ?: 'New'; ?></span>
                            </div>
                            <span class="text-[9px] text-primary font-black italic uppercase tracking-[0.15em]">Protocol Assigned</span>
                        </div>
                    </div>

                    <div class="space-y-3 mt-auto">
                        <a href="tel:<?php echo $rider_phone; ?>" class="w-full py-4 bg-accent text-white rounded-2xl font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-3 hover:bg-accent/90 transition-all shadow-lg shadow-accent/20 active:scale-95">
                            <i data-lucide="phone" class="w-4 h-4"></i> Establish Link
                        </a>
                        <button onclick="toggleChat()" class="w-full py-4 bg-gray-900 dark:bg-dark-bg text-white rounded-2xl font-black uppercase tracking-widest text-[9px] flex items-center justify-center gap-3 hover:bg-black dark:hover:bg-gray-800 transition-all active:scale-95 border border-transparent dark:border-dark-border relative">
                            <i data-lucide="message-square" class="w-4 h-4"></i> Data Msg
                            <div id="msgBadge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-primary rounded-full border-2 border-white dark:border-dark-card pulse-primary"></div>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white dark:bg-dark-card p-8 rounded-[2.5rem] border border-card-border dark:border-dark-border shadow-sm transition-colors text-center">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-dark-bg rounded-2xl flex items-center justify-center mx-auto mb-4 border border-card-border dark:border-dark-border">
                        <i data-lucide="user-minus" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase italic">Awaiting Rider</h4>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-2">Connecting to Logistics...</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Modal -->
    <div id="chatModal" class="fixed inset-0 z-[2000] hidden bg-black/60 backdrop-blur-sm p-6 items-center justify-center">
        <div class="bg-white dark:bg-dark-card w-full max-w-md h-[600px] rounded-[3rem] shadow-2xl border border-card-border dark:border-dark-border flex flex-col overflow-hidden transition-all scale-95 opacity-0" id="chatContainer">
            <!-- Chat Header -->
            <div class="p-6 border-b border-card-border dark:border-dark-border flex items-center justify-between bg-gray-50 dark:bg-dark-bg">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl overflow-hidden border border-card-border dark:border-dark-border">
                        <img src="<?php echo $riderAvatar; ?>" alt="Rider" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-gray-900 dark:text-white uppercase italic"><?php echo htmlspecialchars($rider_name); ?></h4>
                        <p class="text-[9px] text-primary font-bold uppercase tracking-widest italic">Rider Link Active</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="tel:<?php echo $rider_phone; ?>" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-all group">
                        <i data-lucide="phone" class="w-6 h-6 text-primary group-hover:animate-bounce"></i>
                    </a>
                    <button onclick="toggleChat()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-all">
                        <i data-lucide="x" class="w-6 h-6 text-gray-500"></i>
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-section-bg/30 dark:bg-dark-bg/30 scroll-smooth">
                <!-- Messages will load here -->
                <div class="flex flex-col items-center justify-center h-full text-center space-y-4 opacity-50">
                    <i data-lucide="message-circle" class="w-12 h-12 text-gray-300"></i>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">No transmissions yet</p>
                </div>
            </div>

            <!-- Chat Input -->
            <div class="p-4 bg-white dark:bg-dark-card border-t border-card-border dark:border-dark-border">
                <form id="chatForm" class="flex gap-2" onsubmit="return sendMessage(event)">
                    <input type="text" id="chatInput" placeholder="Enter transmission..." 
                        class="flex-1 p-4 bg-gray-50 dark:bg-dark-bg border border-card-border dark:border-dark-border rounded-2xl focus:outline-none focus:border-primary text-sm dark:text-white placeholder:text-gray-400">
                    <button type="submit" class="p-4 bg-primary text-white rounded-2xl hover:bg-primary-hover transition-all shadow-lg shadow-primary/20">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>



    <!-- Leaflet.js Base Container -->
    <!-- (CSS imported in head) -->

    <script>
        lucide.createIcons();

        // OSM Geocoding Cache (The "Magic")
        async function geocodeAddress(address) {
            if (!address || address === 'Not provided') return null;
            const cacheKey = `geo_${btoa(unescape(encodeURIComponent(address)))}`;
            const cached = localStorage.getItem(cacheKey);
            if (cached) return JSON.parse(cached);

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`, {
                    headers: { 'User-Agent': 'FoodVerse/1.0' }
                });
                const data = await response.json();
                if (data && data.length > 0) {
                    const result = { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
                    localStorage.setItem(cacheKey, JSON.stringify(result));
                    return result;
                }
            } catch (e) {
                console.error("Geocoding failed:", address, e);
            }
            return null;
        }

        const orderId = <?php echo $order['id']; ?>;
        let resLat, resLng, custLat, custLng;
        let lastStatus = "<?php echo $order['status']; ?>";
        let map, deliveryMarker, routePolyline;

        async function initMap() {
            // Resolve coordinates
            let kitchenPos = { lat: <?php echo (float)($order['restaurant_lat'] ?: 0); ?>, lng: <?php echo (float)($order['restaurant_lng'] ?: 0); ?> };
            let customerPos = { lat: <?php echo (float)($order['delivery_lat'] ?: 0); ?>, lng: <?php echo (float)($order['delivery_lng'] ?: 0); ?> };

            if (kitchenPos.lat === 0) {
                const resGeo = await geocodeAddress("FoodVerse Kitchen, Kathmandu, Nepal");
                kitchenPos = resGeo || { lat: 27.7215, lng: 85.3210 };
            }

            if (customerPos.lat === 0) {
                const custGeo = await geocodeAddress("<?php echo addslashes($order['user_address'] ?? 'Kathmandu, Nepal'); ?>");
                customerPos = custGeo || { lat: 27.7172, lng: 85.3240 };
            }

            resLat = kitchenPos.lat;
            resLng = kitchenPos.lng;
            custLat = customerPos.lat;
            custLng = customerPos.lng;

            // Init Leaflet Map
            map = L.map('map').setView([(resLat + custLat) / 2, (resLng + custLng) / 2], 14);
            
            // Force map to fill container
            setTimeout(() => { map.invalidateSize(); }, 200);
            window.addEventListener('resize', () => { map.invalidateSize(); });

            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO'
            }).addTo(map);

            // Restaurant Marker
            const resIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/1046/1046857.png',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });
            L.marker([resLat, resLng], {icon: resIcon}).addTo(map).bindPopup("Restaurant");

            // Customer Marker
            const custIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/1239/1239525.png',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });
            L.marker([custLat, custLng], {icon: custIcon}).addTo(map).bindPopup("Your Location");

            // Delivery Rider Marker (hidden initially)
            const deliveryIcon = L.icon({
                iconUrl: 'https://cdn-icons-png.flaticon.com/512/2972/2972185.png',
                iconSize: [44, 44],
                iconAnchor: [22, 22]
            });
            
            deliveryMarker = L.marker([resLat, resLng], {icon: deliveryIcon}).bindPopup("Delivery Rider");
            if (lastStatus === 'out_for_delivery') {
                deliveryMarker.addTo(map);
            }

            // Fit Bounds
            const group = new L.featureGroup([
                L.marker([resLat, resLng]),
                L.marker([custLat, custLng])
            ]);
            map.fitBounds(group.getBounds().pad(0.2));

            // Fetch Route from PHP API
            fetchRoute(resLat, resLng, custLat, custLng);

            // Start polling every 5 seconds
            setInterval(pollStatus, 5000);
        }

        async function fetchRoute(fromLat, fromLng, toLat, toLng) {
            try {
                const res = await fetch(`../api/route.php?order_id=${orderId}&start_lat=${fromLat}&start_lng=${fromLng}&end_lat=${toLat}&end_lng=${toLng}`);
                const data = await res.json();
                
                if (data.success && data.geometry) {
                    if (routePolyline) map.removeLayer(routePolyline);
                    
                    routePolyline = L.geoJSON(data.geometry, {
                        style: {
                            color: '#FF6B35',
                            weight: 5,
                            opacity: 0.7
                        }
                    }).addTo(map);

                    // Update ETA
                    if (data.estimated_time) {
                        document.getElementById('eta').innerText = data.estimated_time.toUpperCase();
                    }
                }
            } catch (err) {
                console.error('Route API error:', err);
                document.getElementById('eta').innerText = '15-20 MINS';
            }
        }

        async function pollStatus() {
            try {
                // Poll our pure PHP location endpoint instead of the old get_order_status
                const response = await fetch(`../api/get_driver_location.php?order_id=${orderId}`);
                const data = await response.json();

                if (data.success) {
                    if (data.status === 'out_for_delivery') {
                        if (!map.hasLayer(deliveryMarker)) deliveryMarker.addTo(map);
                        
                        const riderLat = parseFloat(data.lat) || resLat;
                        const riderLng = parseFloat(data.lng) || resLng;

                        // Move Marker smoothly and center
                        deliveryMarker.setLatLng([riderLat, riderLng]);
                        map.panTo([riderLat, riderLng]);
                        
                        // We also call fetchRoute to draw remaining path, caching means it could dynamically re-route, but for now we simply draw it
                        fetchRoute(riderLat, riderLng, custLat, custLng);
                    }
                    
                    if (data.status && data.status !== lastStatus) {
                        lastStatus = data.status;
                        location.reload();
                    }
                }
            } catch (err) {
                console.error('Poll error:', err);
            }
        }
        
        // Auto init
        document.addEventListener('DOMContentLoaded', initMap);
        const riderId = <?php echo (int)$rider_id; ?>;
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
                    if (lastMsgId === 0) chatMessages.innerHTML = ''; // Clear empty state
                    
                    data.messages.forEach(msg => {
                        const isMe = msg.sender_id == userId;
                        const msgHtml = `
                            <div class="flex ${isMe ? 'justify-end' : 'justify-start'} w-full">
                                <div class="max-w-[80%] ${isMe ? 'bg-primary text-white rounded-2xl rounded-tr-none' : 'bg-white dark:bg-dark-card text-gray-900 dark:text-white rounded-2xl rounded-tl-none border border-card-border dark:border-dark-border'} p-4 shadow-sm">
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
                formData.append('receiver_id', riderId);

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
        if (riderId > 0) {
            fetchMessages();
            setInterval(fetchMessages, 3000);
        }
    </script>
</body>

</html>