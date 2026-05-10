<?php
// includes/bottom_nav.php

$role = $_SESSION['user_role'] ?? 'user';
$is_root = (basename(dirname($_SERVER['PHP_SELF'])) === 'food-verse');
$base = $is_root ? '' : '../';
$customer_base = $is_root ? 'customer/' : '';
$rider_base    = $is_root ? 'delivery/' : '';

// Current page for active-state detection
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Unified Flat Bottom Navigation -->
<nav class="<?php echo $role === 'delivery' ? 'md:hidden' : ''; ?> fixed bottom-0 left-0 right-0 z-[90]
            bg-white border-t border-card-border
            flex items-stretch
            safe-area-inset-bottom">

    <?php if ($role === 'delivery'): ?>
        <!-- ── Rider Nav (5 items) ── -->
        <?php
        $rider_items = [
            ['href' => $rider_base.'dashboard.php', 'icon' => 'layout-grid',   'label' => 'Duty'],
            ['href' => $rider_base.'history.php',   'icon' => 'package',        'label' => 'History'],
            ['href' => $rider_base.'earnings.php',  'icon' => 'wallet',         'label' => 'Earnings'],
            ['href' => $rider_base.'reviews.php',   'icon' => 'star',           'label' => 'Reviews'],
            ['href' => $rider_base.'profile.php',   'icon' => 'user',           'label' => 'Profile',  'is_profile' => true],
        ];
        foreach ($rider_items as $item):
            $active = ($current_page === basename($item['href']));
        ?>
        <a href="<?php echo $item['href']; ?>"
           class="flex-1 flex flex-col items-center justify-center py-3 gap-1
                  <?php echo $active ? 'text-primary' : 'text-gray-400 hover:text-primary'; ?>
                  transition-colors">
            <?php if (!empty($item['is_profile']) && is_logged_in() && !empty($_SESSION['profile_pic']) && file_exists($base.$_SESSION['profile_pic'])): ?>
                <div class="w-6 h-6 rounded-full overflow-hidden border-2 <?php echo $active ? 'border-primary' : 'border-gray-300'; ?>">
                    <img src="<?php echo $base.$_SESSION['profile_pic']; ?>" class="w-full h-full object-cover">
                </div>
            <?php else: ?>
                <i data-lucide="<?php echo $item['icon']; ?>" class="w-6 h-6"></i>
            <?php endif; ?>
            <span class="text-[10px] font-bold"><?php echo $item['label']; ?></span>
        </a>
        <?php endforeach; ?>

    <?php else: ?>
        <!-- ── Customer Nav (5 items) ── -->
        <?php
        $customer_items = [
            ['href' => $base.'index.php',             'icon' => 'home',           'label' => 'Home'],
            ['href' => $customer_base.'orders.php',   'icon' => 'clipboard-list', 'label' => 'Orders'],
            ['href' => $customer_base.'wishlist.php', 'icon' => 'heart',          'label' => 'Wishlist'],
            ['href' => $customer_base.'profile.php',  'icon' => 'user',           'label' => 'Profile', 'is_profile' => true],
        ];
        foreach ($customer_items as $item):
            $href_page = basename($item['href']);
            $active = ($current_page === $href_page) || ($current_page === 'index.php' && $href_page === 'index.php');
        ?>
        <a href="<?php echo $item['href']; ?>"
           class="flex-1 flex flex-col items-center justify-center py-3 gap-1 relative
                  <?php echo $active ? 'text-primary' : 'text-gray-400 hover:text-primary'; ?>
                  transition-colors">
            <?php if (!empty($item['is_profile']) && is_logged_in() && !empty($_SESSION['profile_pic'])): ?>
                <div class="w-6 h-6 rounded-full overflow-hidden border-2 <?php echo $active ? 'border-primary' : 'border-gray-300'; ?>">
                    <img src="<?php echo $base.$_SESSION['profile_pic']; ?>" class="w-full h-full object-cover">
                </div>
            <?php else: ?>
                <i data-lucide="<?php echo $item['icon']; ?>" class="w-6 h-6"></i>
            <?php endif; ?>
            <span class="text-[10px] font-bold"><?php echo $item['label']; ?></span>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>
</nav>

<!-- Safe-area padding spacer so content doesn't hide behind nav -->
<div class="<?php echo $role === 'delivery' ? 'md:hidden' : ''; ?> h-[68px]"></div>

<script>
    // Re-run active highlight after lucide replaces icons
    document.addEventListener("DOMContentLoaded", function () {
        const path = window.location.pathname.split("/").pop();
        document.querySelectorAll('nav.fixed.bottom-0 a').forEach(link => {
            if (link.getAttribute('href') && link.getAttribute('href').includes(path) && path !== '') {
                link.classList.remove('text-gray-400', 'hover:text-primary');
                link.classList.add('text-primary');
            }
        });
        
        <?php if ($role === 'user' && is_logged_in()): ?>
        // Customer Notification Polling
        const notifBadge = document.getElementById('notif-badge');
        let seenNotifs = new Set();
        
        function showNotifToast(title, msg, link) {
            const toastId = 'toast-' + Date.now();
            const html = `
                <div id="${toastId}" class="fixed top-4 left-1/2 -translate-x-1/2 w-[90%] max-w-sm bg-gray-900 border border-gray-800 rounded-2xl p-4 shadow-2xl flex items-start gap-4 z-[100] transform -translate-y-full opacity-0 transition-all duration-500 ease-out cursor-pointer" onclick="window.location.href='<?php echo $base; ?>${link}'">
                    <div class="w-10 h-10 bg-primary/20 rounded-xl flex items-center justify-center text-primary shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h4 class="text-white font-bold text-sm tracking-wide uppercase truncate">${title}</h4>
                        <p class="text-gray-400 text-xs mt-1 leading-tight line-clamp-2">${msg}</p>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', html);
            
            const toast = document.getElementById(toastId);
            requestAnimationFrame(() => {
                toast.classList.remove('-translate-y-full', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
                if (navigator.vibrate) navigator.vibrate([100, 50, 100]); // Haptic feedback
            });

            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('-translate-y-full', 'opacity-0');
                setTimeout(() => toast.remove(), 500);
            }, 6000); // 6s duration
        }

        function pollCustomerNotifications() {
            fetch('<?php echo $base; ?>customer/api/get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (notifBadge && data.count > 0) {
                            notifBadge.textContent = data.count;
                            notifBadge.classList.replace('opacity-0', 'opacity-100');
                            notifBadge.classList.add('animate-pulse');
                            
                            // Iterate new and toast them
                            data.notifications.forEach(n => {
                                if (!seenNotifs.has(n.id)) {
                                    seenNotifs.add(n.id);
                                    showNotifToast(n.title, n.message, n.link);
                                }
                            });
                        } else if (notifBadge) {
                            notifBadge.classList.replace('opacity-100', 'opacity-0');
                            notifBadge.classList.remove('animate-pulse');
                        }
                    }
                })
                .catch(err => console.error('Notif poll error:', err));
        }

        setInterval(pollCustomerNotifications, 5000); // Poll every 5s
        setTimeout(pollCustomerNotifications, 1000); // initial fetch
        <?php endif; ?>
    });
</script>
